<?php

namespace App\Http\Controllers;

use App\Enums\JenisPembayaran;
use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Enums\TipeSewa;
use App\Models\Garasi;
use App\Models\Kendaraan;
use App\Models\Sewa;
use App\Services\KalkulatorBiayaSewa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Penyewa menyewa garasi baru dalam dua langkah:
 * 1) mengisi data kendaraan dan sewa, 2) memilih garasi yang tersedia lalu upload bukti bayar.
 *
 * @author Aaron
 *
 * @version 1.0
 */
class SewaController extends Controller
{
    /**
     * @param  KalkulatorBiayaSewa  $kalkulator  penghitung tanggal dan biaya sewa
     */
    public function __construct(private KalkulatorBiayaSewa $kalkulator) {}

    /**
     * Menampilkan semua sewa milik penyewa yang sedang login.
     */
    public function index(Request $request): View
    {
        $daftarSewa = $request->user()->sewa()
            ->with(['garasi', 'kendaraan'])
            ->orderByDesc('tanggal_mulai')
            ->get();

        return view('sewa.index', ['daftarSewa' => $daftarSewa]);
    }

    /**
     * Langkah 1: form data kendaraan dan data sewa.
     */
    public function create(): View
    {
        return view('sewa.create', [
            'daftarTipeKendaraan' => TipeKendaraan::cases(),
            'daftarTipeSewa' => TipeSewa::cases(),
            'daftarTipeGarasi' => TipeGarasi::cases(),
            'hariIni' => Carbon::today()->toDateString(),
        ]);
    }

    /**
     * Langkah 2: menampilkan garasi yang tersedia beserta rincian biayanya.
     */
    public function pilihGarasi(Request $request): View
    {
        [$data, $mulai, $selesai] = $this->validasiSewa($request);

        $daftarGarasi = Garasi::where('tipe', $data['tipe_garasi'])
            ->tersedia($mulai, $selesai)
            ->orderBy('kode')
            ->get();

        $pilihan = $daftarGarasi->map(fn (Garasi $garasi): array => [
            'garasi' => $garasi,
            'biaya' => $this->hitungBiaya($garasi, $data, $mulai, $selesai),
        ]);

        return view('sewa.pilih-garasi', [
            'data' => $data,
            'tipeKendaraan' => TipeKendaraan::from($data['tipe_kendaraan']),
            'tipeSewa' => TipeSewa::from($data['tipe_sewa']),
            'mulai' => $mulai,
            'selesai' => $selesai,
            'pilihan' => $pilihan,
        ]);
    }

    /**
     * Menyimpan sewa baru, kendaraan, dan bukti pembayarannya.
     */
    public function store(Request $request): RedirectResponse
    {
        [$data, $mulai, $selesai] = $this->validasiSewa($request, [
            'garasi_id' => ['required', 'integer'],
            'bukti_pembayaran' => ['required', 'image', 'max:2048'],
        ]);

        $garasi = Garasi::where('tipe', $data['tipe_garasi'])
            ->tersedia($mulai, $selesai)
            ->find($data['garasi_id']);

        if ($garasi === null) {
            throw ValidationException::withMessages([
                'garasi_id' => 'Garasi yang dipilih sudah tidak tersedia. Silakan pilih garasi lain.',
            ]);
        }

        $biaya = $this->hitungBiaya($garasi, $data, $mulai, $selesai);
        $pathBukti = $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public');

        DB::transaction(function () use ($request, $data, $garasi, $mulai, $selesai, $biaya, $pathBukti) {
            $kendaraan = $this->simpanKendaraan($request, $data);

            $sewa = Sewa::create([
                'kendaraan_id' => $kendaraan->id,
                'garasi_id' => $garasi->id,
                'tipe_sewa' => $data['tipe_sewa'],
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'sisa_baterai_kwh' => $kendaraan->tipe->isListrik() ? $data['sisa_baterai_kwh'] : null,
                'biaya_isi_listrik' => $biaya['listrik'],
                'total_biaya' => $biaya['total'],
            ]);

            $sewa->pembayaran()->create([
                'jenis' => JenisPembayaran::SewaBaru,
                'jumlah' => $biaya['total'],
                'bukti_pembayaran' => $pathBukti,
            ]);
        });

        $total = number_format($biaya['total'], 0, ',', '.');

        return redirect()->route('sewa.index')
            ->with('sukses', "Sewa garasi {$garasi->kode} berhasil disimpan. Total biaya Rp{$total}.");
    }

    /**
     * Memvalidasi data sewa, mengecek aturan bisnis, lalu menghitung rentang tanggal sewa.
     *
     * @param  array<string, array<int, mixed>>  $aturanTambahan  aturan validasi khusus langkah simpan
     * @return array{0: array<string, mixed>, 1: Carbon, 2: Carbon} data valid, tanggal mulai, tanggal selesai
     *
     * @throws ValidationException jika data tidak valid
     */
    private function validasiSewa(Request $request, array $aturanTambahan = []): array
    {
        $data = $request->validate([...$this->aturanDataSewa(), ...$aturanTambahan]);
        $this->cekAturanSewa($data);

        $mulai = Carbon::parse($data['tanggal_mulai'])->startOfDay();
        $jumlahHari = isset($data['jumlah_hari']) ? (int) $data['jumlah_hari'] : null;
        $selesai = $this->kalkulator->hitungTanggalSelesai($mulai, TipeSewa::from($data['tipe_sewa']), $jumlahHari);

        $this->cekKendaraanBelumDisewa($request, $data, $mulai, $selesai);

        return [$data, $mulai, $selesai];
    }

    /**
     * Aturan validasi data kendaraan dan data sewa.
     *
     * @return array<string, array<int, mixed>>
     */
    private function aturanDataSewa(): array
    {
        $sepeda = TipeKendaraan::Sepeda->value;
        $mobilListrik = TipeKendaraan::MobilListrik->value;

        return [
            'tipe_kendaraan' => ['required', Rule::enum(TipeKendaraan::class)],
            'plat_nomor' => ['nullable', "required_unless:tipe_kendaraan,{$sepeda}", 'string', 'max:15'],
            'merek_tipe' => ['required', 'string', 'max:100'],
            'kapasitas_baterai_kwh' => ['nullable', "required_if:tipe_kendaraan,{$mobilListrik}", 'numeric', 'gt:0', 'max:9999'],
            'sisa_baterai_kwh' => ['nullable', "required_if:tipe_kendaraan,{$mobilListrik}", 'numeric', 'min:0', 'lte:kapasitas_baterai_kwh'],
            'tipe_sewa' => ['required', Rule::enum(TipeSewa::class)],
            'jumlah_hari' => ['nullable', 'required_if:tipe_sewa,'.TipeSewa::Harian->value, 'integer', 'min:1'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tipe_garasi' => ['required', Rule::enum(TipeGarasi::class)],
        ];
    }

    /**
     * Mengecek kecocokan tipe garasi dengan kendaraan dan batas sewa harian.
     *
     * @param  array<string, mixed>  $data  data sewa yang sudah lolos validasi
     *
     * @throws ValidationException jika aturan dilanggar
     */
    private function cekAturanSewa(array $data): void
    {
        $tipeKendaraan = TipeKendaraan::from($data['tipe_kendaraan']);
        $tipeGarasi = TipeGarasi::from($data['tipe_garasi']);

        if (! in_array($tipeGarasi, $tipeKendaraan->tipeGarasiYangCocok(), true)) {
            throw ValidationException::withMessages([
                'tipe_garasi' => "{$tipeKendaraan->label()} tidak bisa disimpan di garasi {$tipeGarasi->label()}.",
            ]);
        }

        $maksimalHari = $this->kalkulator->maksimalHariHarian(Carbon::parse($data['tanggal_mulai']));

        if ($data['tipe_sewa'] === TipeSewa::Harian->value && (int) $data['jumlah_hari'] > $maksimalHari) {
            throw ValidationException::withMessages([
                'jumlah_hari' => "Sewa harian maksimal {$maksimalHari} hari untuk bulan ini. Gunakan sewa bulanan.",
            ]);
        }
    }

    /**
     * Satu kendaraan hanya boleh menempati satu garasi pada waktu yang sama.
     *
     * @param  array<string, mixed>  $data  data sewa yang sudah lolos validasi
     *
     * @throws ValidationException jika plat milik penyewa lain atau kendaraan sudah punya sewa bentrok
     */
    private function cekKendaraanBelumDisewa(Request $request, array $data, Carbon $mulai, Carbon $selesai): void
    {
        if (! TipeKendaraan::from($data['tipe_kendaraan'])->wajibPlatNomor()) {
            return;
        }

        $kendaraan = Kendaraan::where('plat_nomor', $data['plat_nomor'])->first();

        if ($kendaraan === null) {
            return;
        }

        if (! $kendaraan->pemilik->is($request->user())) {
            throw ValidationException::withMessages([
                'plat_nomor' => 'Plat nomor ini sudah terdaftar atas penyewa lain.',
            ]);
        }

        if ($kendaraan->sewa()->bentrokDengan($mulai, $selesai)->exists()) {
            throw ValidationException::withMessages([
                'plat_nomor' => 'Kendaraan ini sudah punya sewa garasi pada tanggal tersebut.',
            ]);
        }
    }

    /**
     * Rincian biaya sewa satu garasi. Biaya isi listrik hanya untuk mobil listrik di garasi mobil listrik.
     *
     * @param  array<string, mixed>  $data  data sewa yang sudah lolos validasi
     * @return array{sewa: int, listrik: int, total: int}
     */
    private function hitungBiaya(Garasi $garasi, array $data, Carbon $mulai, Carbon $selesai): array
    {
        $biayaSewa = $this->kalkulator->hitungBiayaSewa($garasi->harga_harian, $mulai, $selesai);

        $perluIsiListrik = $data['tipe_kendaraan'] === TipeKendaraan::MobilListrik->value
            && $garasi->tipe === TipeGarasi::MobilListrik;

        $biayaListrik = $perluIsiListrik
            ? $this->kalkulator->hitungBiayaIsiListrik(
                (float) $data['kapasitas_baterai_kwh'],
                (float) $data['sisa_baterai_kwh'],
                $garasi->harga_listrik_per_kwh
            )
            : 0;

        return ['sewa' => $biayaSewa, 'listrik' => $biayaListrik, 'total' => $biayaSewa + $biayaListrik];
    }

    /**
     * Menyimpan kendaraan penyewa. Kendaraan dengan plat yang sama dipakai ulang dan datanya diperbarui.
     *
     * @param  array<string, mixed>  $data  data sewa yang sudah lolos validasi
     */
    private function simpanKendaraan(Request $request, array $data): Kendaraan
    {
        $tipe = TipeKendaraan::from($data['tipe_kendaraan']);
        $atribut = [
            'tipe' => $tipe,
            'merek_tipe' => $data['merek_tipe'],
            'kapasitas_baterai_kwh' => $tipe->isListrik() ? $data['kapasitas_baterai_kwh'] : null,
        ];

        if (! $tipe->wajibPlatNomor()) {
            return $request->user()->kendaraan()->create($atribut);
        }

        return $request->user()->kendaraan()->updateOrCreate(['plat_nomor' => $data['plat_nomor']], $atribut);
    }
}
