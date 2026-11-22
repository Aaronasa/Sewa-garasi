<?php

namespace App\Http\Controllers;

use App\Enums\JenisPembayaran;
use App\Enums\TipeSewa;
use App\Models\Sewa;
use App\Services\KalkulatorBiayaSewa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin memperpanjang sewa garasi: mengubah tanggal selesai, menghitung sisa biaya, dan upload bukti bayar.
 *
 * @author Aaron
 *
 * @version 1.0
 */
class PerpanjanganController extends Controller
{
    /**
     * @param  KalkulatorBiayaSewa  $kalkulator  penghitung tanggal dan biaya sewa
     */
    public function __construct(private KalkulatorBiayaSewa $kalkulator) {}

    /**
     * Menampilkan garasi yang sedang disewa hari ini.
     */
    public function index(): View
    {
        $hariIni = Carbon::today();
        $daftarSewa = Sewa::bentrokDengan($hariIni, $hariIni)
            ->with(['garasi', 'kendaraan.pemilik'])
            ->orderBy('tanggal_selesai')
            ->get();

        return view('admin.perpanjangan.index', ['daftarSewa' => $daftarSewa]);
    }

    /**
     * Form perpanjangan. Jika tanggal selesai baru sudah diisi, tampilkan juga sisa biayanya.
     */
    public function edit(Request $request, Sewa $sewa): View
    {
        $hasil = null;

        if ($request->filled('tanggal_selesai_baru')) {
            $tanggalBaru = $this->validasiTanggalBaru($request, $sewa);
            $hasil = $this->hitungPerpanjangan($sewa, $tanggalBaru);
        }

        $sewa->load(['garasi', 'kendaraan.pemilik', 'pembayaran']);

        return view('admin.perpanjangan.edit', ['sewa' => $sewa, 'hasil' => $hasil]);
    }

    /**
     * Menyimpan tanggal selesai baru, total biaya baru, dan pembayaran sisa biaya.
     */
    public function update(Request $request, Sewa $sewa): RedirectResponse
    {
        $tanggalBaru = $this->validasiTanggalBaru($request, $sewa);
        $hasil = $this->hitungPerpanjangan($sewa, $tanggalBaru);

        $request->validate([
            'bukti_pembayaran' => [$hasil['sisaBiaya'] > 0 ? 'required' : 'nullable', 'image', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $sewa, $hasil) {
            $sewa->update([
                'tanggal_selesai' => $hasil['tanggalSelesaiBaru'],
                'tipe_sewa' => $hasil['tipeSewaBaru'],
                'total_biaya' => $hasil['totalBaru'],
            ]);

            if ($hasil['sisaBiaya'] > 0) {
                $sewa->pembayaran()->create([
                    'jenis' => JenisPembayaran::Perpanjangan,
                    'jumlah' => $hasil['sisaBiaya'],
                    'bukti_pembayaran' => $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public'),
                ]);
            }
        });

        return redirect()->route('admin.perpanjangan.index')->with(
            'sukses',
            "Sewa garasi {$sewa->garasi->kode} diperpanjang sampai {$hasil['tanggalSelesaiBaru']->format('d-m-Y')}."
        );
    }

    /**
     * Tanggal selesai baru harus setelah tanggal selesai lama dan tidak bentrok dengan sewa lain.
     *
     * @return Carbon tanggal selesai baru
     *
     * @throws ValidationException jika tanggal tidak valid atau bentrok
     */
    private function validasiTanggalBaru(Request $request, Sewa $sewa): Carbon
    {
        $request->validate([
            'tanggal_selesai_baru' => ['required', 'date', 'after:'.$sewa->tanggal_selesai->toDateString()],
        ]);

        $tanggalBaru = Carbon::parse($request->input('tanggal_selesai_baru'))->startOfDay();
        $mulaiTambahan = $sewa->tanggal_selesai->copy()->addDay();

        $garasiBentrok = $sewa->garasi->sewa()->whereKeyNot($sewa->id)
            ->bentrokDengan($mulaiTambahan, $tanggalBaru)->exists();
        $kendaraanBentrok = $sewa->kendaraan->sewa()->whereKeyNot($sewa->id)
            ->bentrokDengan($mulaiTambahan, $tanggalBaru)->exists();

        if ($garasiBentrok || $kendaraanBentrok) {
            throw ValidationException::withMessages([
                'tanggal_selesai_baru' => 'Tanggal baru bentrok dengan sewa lain pada garasi atau kendaraan ini.',
            ]);
        }

        return $tanggalBaru;
    }

    /**
     * Menghitung total biaya baru dan sisa yang harus dibayar.
     * Jika lama sewa mencapai 1 bulan atau lebih, tipe sewa menjadi bulanan.
     *
     * @return array{tanggalSelesaiBaru: Carbon, jumlahHari: int, tipeSewaBaru: TipeSewa, totalBaru: int, sudahDibayar: int, sisaBiaya: int}
     */
    private function hitungPerpanjangan(Sewa $sewa, Carbon $tanggalBaru): array
    {
        $biayaSewaBaru = $this->kalkulator->hitungBiayaSewa($sewa->garasi->harga_harian, $sewa->tanggal_mulai, $tanggalBaru);
        $totalBaru = $biayaSewaBaru + $sewa->biaya_isi_listrik;
        $sudahDibayar = $sewa->totalDibayar();
        $jumlahHari = (int) $sewa->tanggal_mulai->diffInDays($tanggalBaru) + 1;
        $hariSebulan = $this->kalkulator->hariDalamSebulan($sewa->tanggal_mulai);

        return [
            'tanggalSelesaiBaru' => $tanggalBaru,
            'jumlahHari' => $jumlahHari,
            'tipeSewaBaru' => $jumlahHari >= $hariSebulan ? TipeSewa::Bulanan : TipeSewa::Harian,
            'totalBaru' => $totalBaru,
            'sudahDibayar' => $sudahDibayar,
            'sisaBiaya' => max(0, $totalBaru - $sudahDibayar),
        ];
    }
}
