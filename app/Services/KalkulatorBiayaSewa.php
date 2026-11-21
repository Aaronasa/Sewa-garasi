<?php

namespace App\Services;

use App\Enums\TipeSewa;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Menghitung tanggal selesai dan biaya sewa garasi sesuai aturan bisnis.
 *
 * Aturan "1 bulan" mengikuti jumlah hari di bulan tanggal mulai sewa,
 * jadi sewa yang mulai di bulan Februari tetap dihitung 1 bulan walau hanya 28 atau 29 hari.
 *
 * @author Aaron
 *
 * @version 1.0
 */
class KalkulatorBiayaSewa
{
    /** Harga sewa bulanan adalah 25 kali harga sewa harian. */
    public const KALI_HARGA_BULANAN = 25;

    /**
     * Jumlah hari dalam satu bulan sewa, mengikuti bulan dari tanggal mulai.
     *
     * @param  Carbon  $tanggalMulai  tanggal mulai sewa
     * @return int 28 sampai 31
     */
    public function hariDalamSebulan(Carbon $tanggalMulai): int
    {
        return $tanggalMulai->daysInMonth;
    }

    /**
     * Batas maksimal sewa harian, yaitu jumlah hari di bulan tanggal mulai dikurangi 1.
     *
     * @param  Carbon  $tanggalMulai  tanggal mulai sewa
     * @return int 27 sampai 30
     */
    public function maksimalHariHarian(Carbon $tanggalMulai): int
    {
        return $this->hariDalamSebulan($tanggalMulai) - 1;
    }

    /**
     * Menghitung tanggal selesai sewa. Tanggal selesai ikut dihitung sebagai hari sewa.
     *
     * @param  Carbon  $tanggalMulai  tanggal mulai sewa
     * @param  TipeSewa  $tipeSewa  harian atau bulanan
     * @param  int|null  $jumlahHari  wajib untuk sewa harian, diabaikan untuk sewa bulanan
     * @return Carbon tanggal selesai sewa
     *
     * @throws InvalidArgumentException jika jumlah hari sewa harian di luar batas
     */
    public function hitungTanggalSelesai(Carbon $tanggalMulai, TipeSewa $tipeSewa, ?int $jumlahHari = null): Carbon
    {
        $lamaHari = $tipeSewa === TipeSewa::Bulanan
            ? $this->hariDalamSebulan($tanggalMulai)
            : $this->validasiJumlahHariHarian($tanggalMulai, $jumlahHari);

        return $tanggalMulai->copy()->addDays($lamaHari - 1);
    }

    /**
     * Menghitung biaya sewa dari tanggal mulai sampai tanggal selesai.
     *
     * Jika kurang dari 1 bulan, biaya = harga harian x jumlah hari.
     * Jika 1 bulan atau lebih, jumlah bulan dibulatkan ke atas lalu dikali harga bulanan
     * (contoh: 45 hari = 2 x harga bulanan).
     *
     * @param  int  $hargaHarian  harga sewa per hari dalam rupiah
     * @param  Carbon  $tanggalMulai  tanggal mulai sewa
     * @param  Carbon  $tanggalSelesai  tanggal selesai sewa, tidak boleh sebelum tanggal mulai
     * @return int biaya sewa dalam rupiah
     *
     * @throws InvalidArgumentException jika tanggal selesai sebelum tanggal mulai
     */
    public function hitungBiayaSewa(int $hargaHarian, Carbon $tanggalMulai, Carbon $tanggalSelesai): int
    {
        if ($tanggalSelesai->lt($tanggalMulai)) {
            throw new InvalidArgumentException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $jumlahHari = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;
        $hariSebulan = $this->hariDalamSebulan($tanggalMulai);

        if ($jumlahHari < $hariSebulan) {
            return $hargaHarian * $jumlahHari;
        }

        $jumlahBulan = (int) ceil($jumlahHari / $hariSebulan);

        return $jumlahBulan * $hargaHarian * self::KALI_HARGA_BULANAN;
    }

    /**
     * Menghitung biaya isi listrik mobil listrik sampai baterai penuh.
     *
     * @param  float  $kapasitasKwh  kapasitas baterai penuh dalam kWh
     * @param  float  $sisaKwh  sisa baterai saat masuk garasi dalam kWh
     * @param  int  $hargaPerKwh  harga listrik per kWh dalam rupiah
     * @return int biaya isi listrik dalam rupiah, dibulatkan ke rupiah terdekat
     *
     * @throws InvalidArgumentException jika sisa baterai negatif atau melebihi kapasitas
     */
    public function hitungBiayaIsiListrik(float $kapasitasKwh, float $sisaKwh, int $hargaPerKwh): int
    {
        if ($sisaKwh < 0 || $sisaKwh > $kapasitasKwh) {
            throw new InvalidArgumentException('Sisa baterai harus antara 0 dan kapasitas baterai.');
        }

        return (int) round(($kapasitasKwh - $sisaKwh) * $hargaPerKwh);
    }

    /**
     * Memastikan jumlah hari sewa harian berada di antara 1 dan batas maksimalnya.
     *
     * @throws InvalidArgumentException jika jumlah hari kosong atau di luar batas
     */
    private function validasiJumlahHariHarian(Carbon $tanggalMulai, ?int $jumlahHari): int
    {
        $maksimal = $this->maksimalHariHarian($tanggalMulai);

        if ($jumlahHari === null || $jumlahHari < 1 || $jumlahHari > $maksimal) {
            throw new InvalidArgumentException("Jumlah hari sewa harian harus 1 sampai {$maksimal} hari.");
        }

        return $jumlahHari;
    }
}
