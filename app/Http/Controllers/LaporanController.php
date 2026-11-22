<?php

namespace App\Http\Controllers;

use App\Enums\TipeGarasi;
use App\Models\Sewa;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Laporan hasil persewaan garasi: jumlah sewa dan pendapatan.
 * Sewa dikelompokkan berdasarkan tanggal mulai sewa pada tahun yang dipilih.
 *
 * @author Aaron
 *
 * @version 1.0
 */
class LaporanController extends Controller
{
    /** Pilihan jenis laporan beserta judulnya. */
    public const JENIS_LAPORAN = [
        'bulan' => 'Per Bulan dalam Tahun',
        'hari' => 'Per Hari dalam Bulan',
        'tipe' => 'Per Tipe Garasi',
        'penyewa' => 'Per Penyewa',
    ];

    /** Judul kolom pertama tabel laporan untuk tiap jenis laporan. */
    public const JUDUL_KOLOM = [
        'bulan' => 'Bulan',
        'hari' => 'Tanggal',
        'tipe' => 'Tipe Garasi',
        'penyewa' => 'Penyewa',
    ];

    /** Nama bulan dalam bahasa Indonesia, indeks 1 = Januari. */
    public const NAMA_BULAN = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Menampilkan laporan sesuai jenis, tahun, dan bulan yang dipilih.
     */
    public function index(Request $request): View
    {
        $data = $request->validate([
            'jenis' => ['nullable', Rule::in(array_keys(self::JENIS_LAPORAN))],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $jenis = $data['jenis'] ?? 'bulan';
        $tahun = (int) ($data['tahun'] ?? Carbon::today()->year);
        $bulan = (int) ($data['bulan'] ?? Carbon::today()->month);

        $baris = match ($jenis) {
            'bulan' => $this->laporanPerBulan($tahun),
            'hari' => $this->laporanPerHari($tahun, $bulan),
            'tipe' => $this->laporanPerTipeGarasi($tahun),
            'penyewa' => $this->laporanPerPenyewa($tahun),
        };

        return view('admin.laporan.index', [
            'daftarJenis' => self::JENIS_LAPORAN,
            'daftarBulan' => self::NAMA_BULAN,
            'jenis' => $jenis,
            'judulKolom' => self::JUDUL_KOLOM[$jenis],
            'tahun' => $tahun,
            'bulan' => $bulan,
            'baris' => $baris,
            'totalSewa' => array_sum(array_column($baris, 'jumlahSewa')),
            'totalPendapatan' => array_sum(array_column($baris, 'pendapatan')),
        ]);
    }

    /**
     * Laporan 12 bulan dalam satu tahun.
     *
     * @return array<int, array{label: string, jumlahSewa: int, pendapatan: int}>
     */
    private function laporanPerBulan(int $tahun): array
    {
        $daftarSewa = Sewa::whereYear('tanggal_mulai', $tahun)->get();
        $baris = [];

        foreach (self::NAMA_BULAN as $nomorBulan => $namaBulan) {
            $sewaBulanIni = $daftarSewa->filter(fn (Sewa $sewa): bool => $sewa->tanggal_mulai->month === $nomorBulan);
            $baris[] = $this->barisLaporan($namaBulan, $sewaBulanIni);
        }

        return $baris;
    }

    /**
     * Laporan setiap hari dalam satu bulan.
     *
     * @return array<int, array{label: string, jumlahSewa: int, pendapatan: int}>
     */
    private function laporanPerHari(int $tahun, int $bulan): array
    {
        $daftarSewa = Sewa::whereYear('tanggal_mulai', $tahun)->whereMonth('tanggal_mulai', $bulan)->get();
        $jumlahHari = Carbon::create($tahun, $bulan)->daysInMonth;
        $baris = [];

        for ($hari = 1; $hari <= $jumlahHari; $hari++) {
            $sewaHariIni = $daftarSewa->filter(fn (Sewa $sewa): bool => $sewa->tanggal_mulai->day === $hari);
            $baris[] = $this->barisLaporan($hari.' '.self::NAMA_BULAN[$bulan].' '.$tahun, $sewaHariIni);
        }

        return $baris;
    }

    /**
     * Laporan per tipe garasi dalam satu tahun.
     *
     * @return array<int, array{label: string, jumlahSewa: int, pendapatan: int}>
     */
    private function laporanPerTipeGarasi(int $tahun): array
    {
        $daftarSewa = Sewa::with('garasi')->whereYear('tanggal_mulai', $tahun)->get();
        $baris = [];

        foreach (TipeGarasi::cases() as $tipe) {
            $sewaTipeIni = $daftarSewa->filter(fn (Sewa $sewa): bool => $sewa->garasi->tipe === $tipe);
            $baris[] = $this->barisLaporan($tipe->label(), $sewaTipeIni);
        }

        return $baris;
    }

    /**
     * Laporan per penyewa dalam satu tahun.
     *
     * @return array<int, array{label: string, jumlahSewa: int, pendapatan: int}>
     */
    private function laporanPerPenyewa(int $tahun): array
    {
        $daftarSewa = Sewa::with('kendaraan.pemilik')->whereYear('tanggal_mulai', $tahun)->get();
        $sewaPerPenyewa = $daftarSewa->groupBy(fn (Sewa $sewa): int => $sewa->kendaraan->pemilik->id);
        $baris = [];

        foreach ($sewaPerPenyewa as $sewaPenyewa) {
            $pemilik = $sewaPenyewa->first()->kendaraan->pemilik;
            $baris[] = $this->barisLaporan("{$pemilik->nama} ({$pemilik->email})", $sewaPenyewa);
        }

        return $baris;
    }

    /**
     * Satu baris laporan: label, jumlah sewa, dan total pendapatan.
     *
     * @param  Collection<int, Sewa>  $daftarSewa
     * @return array{label: string, jumlahSewa: int, pendapatan: int}
     */
    private function barisLaporan(string $label, Collection $daftarSewa): array
    {
        return [
            'label' => $label,
            'jumlahSewa' => $daftarSewa->count(),
            'pendapatan' => (int) $daftarSewa->sum('total_biaya'),
        ];
    }
}
