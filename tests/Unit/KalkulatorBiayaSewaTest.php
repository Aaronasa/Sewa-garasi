<?php

use App\Enums\TipeSewa;
use App\Services\KalkulatorBiayaSewa;
use Illuminate\Support\Carbon;

/*
 * Unit test KalkulatorBiayaSewa.
 * Nama test: namaMethod_kondisi_hasilDiharapkan, isi test memakai pola Arrange - Act - Assert.
 * Harga harian yang dipakai di semua contoh: Rp10.000, jadi harga bulanan Rp250.000.
 */

test('hariDalamSebulan_mengikutiBulanTanggalMulai', function (string $tanggalMulai, int $hariDiharapkan) {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $hasil = $kalkulator->hariDalamSebulan(Carbon::parse($tanggalMulai));

    // Assert
    expect($hasil)->toBe($hariDiharapkan);
})->with([
    'Januari' => ['2027-01-29', 31],
    'Februari biasa' => ['2027-02-10', 28],
    'Februari kabisat' => ['2028-02-10', 29],
    'April' => ['2027-04-05', 30],
]);

test('maksimalHariHarian_hariDalamSebulanDikurangiSatu', function (string $tanggalMulai, int $maksimalDiharapkan) {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $hasil = $kalkulator->maksimalHariHarian(Carbon::parse($tanggalMulai));

    // Assert
    expect($hasil)->toBe($maksimalDiharapkan);
})->with([
    'Januari' => ['2027-01-01', 30],
    'Februari biasa' => ['2027-02-01', 27],
]);

test('hitungTanggalSelesai_harian5Hari_selesaiHariKelima', function () {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $selesai = $kalkulator->hitungTanggalSelesai(Carbon::parse('2026-11-21'), TipeSewa::Harian, 5);

    // Assert
    expect($selesai->toDateString())->toBe('2026-11-25');
});

test('hitungTanggalSelesai_bulanan_selesaiSesuaiBulanMulai', function (string $tanggalMulai, string $selesaiDiharapkan) {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $selesai = $kalkulator->hitungTanggalSelesai(Carbon::parse($tanggalMulai), TipeSewa::Bulanan);

    // Assert
    expect($selesai->toDateString())->toBe($selesaiDiharapkan);
})->with([
    'mulai 1 Februari' => ['2027-02-01', '2027-02-28'],
    'mulai 29 Januari' => ['2027-01-29', '2027-02-28'],
]);

test('hitungTanggalSelesai_harianTepatBatas_berhasil', function () {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $selesai = $kalkulator->hitungTanggalSelesai(Carbon::parse('2027-01-01'), TipeSewa::Harian, 30);

    // Assert
    expect($selesai->toDateString())->toBe('2027-01-30');
});

test('hitungTanggalSelesai_jumlahHariHarianTidakValid_lemparException', function (?int $jumlahHari) {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $aksi = fn () => $kalkulator->hitungTanggalSelesai(Carbon::parse('2027-01-01'), TipeSewa::Harian, $jumlahHari);

    // Assert
    expect($aksi)->toThrow(InvalidArgumentException::class);
})->with([
    'batas + 1 (31 hari di Januari)' => [31],
    'nol hari' => [0],
    'kosong' => [null],
]);

test('hitungBiayaSewa_sesuaiJumlahHari', function (string $mulai, string $selesai, int $biayaDiharapkan) {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $biaya = $kalkulator->hitungBiayaSewa(10000, Carbon::parse($mulai), Carbon::parse($selesai));

    // Assert
    expect($biaya)->toBe($biayaDiharapkan);
})->with([
    '5 hari (harian)' => ['2026-11-21', '2026-11-25', 50000],
    'batas - 1: 30 hari Januari (harian)' => ['2027-01-01', '2027-01-30', 300000],
    'batas: 31 hari Januari (1 bulan)' => ['2027-01-01', '2027-01-31', 250000],
    'batas + 1: 32 hari (2 bulan)' => ['2027-01-01', '2027-02-01', 500000],
    '45 hari (2 bulan)' => ['2027-01-01', '2027-02-14', 500000],
    'Februari 28 hari (1 bulan)' => ['2027-02-01', '2027-02-28', 250000],
]);

test('hitungBiayaSewa_tanggalSelesaiSebelumMulai_lemparException', function () {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $aksi = fn () => $kalkulator->hitungBiayaSewa(10000, Carbon::parse('2027-01-10'), Carbon::parse('2027-01-09'));

    // Assert
    expect($aksi)->toThrow(InvalidArgumentException::class);
});

test('hitungBiayaIsiListrik_sisaBaterai_dihitungSampaiPenuh', function (float $kapasitas, float $sisa, int $biayaDiharapkan) {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $biaya = $kalkulator->hitungBiayaIsiListrik($kapasitas, $sisa, 2500);

    // Assert
    expect($biaya)->toBe($biayaDiharapkan);
})->with([
    'sisa 20 dari 60 kWh' => [60, 20, 100000],
    'angka desimal' => [72.6, 20.5, 130250],
    'batas: baterai sudah penuh' => [60, 60, 0],
    'batas: baterai kosong' => [60, 0, 150000],
]);

test('hitungBiayaIsiListrik_sisaBateraiTidakValid_lemparException', function (float $sisa) {
    // Arrange
    $kalkulator = new KalkulatorBiayaSewa;

    // Act
    $aksi = fn () => $kalkulator->hitungBiayaIsiListrik(60, $sisa, 2500);

    // Assert
    expect($aksi)->toThrow(InvalidArgumentException::class);
})->with([
    'melebihi kapasitas' => [60.5],
    'negatif' => [-1],
]);
