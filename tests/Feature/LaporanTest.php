<?php

use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Models\Garasi;
use App\Models\Sewa;
use App\Models\User;

use function Pest\Laravel\actingAs;

/*
 * Feature test laporan sewa garasi oleh admin.
 * Data sewa dikelompokkan berdasarkan tanggal mulai sewa.
 */

/**
 * Membuat satu sewa untuk data laporan.
 */
function buatSewaLaporan(string $tanggalMulai, int $totalBiaya, TipeGarasi $tipe = TipeGarasi::Mobil, ?User $penyewa = null): Sewa
{
    $nomorGarasi = Garasi::where('tipe', $tipe)->count() + 1;
    $garasi = Garasi::create(['kode' => $tipe->buatKode($nomorGarasi), 'tipe' => $tipe, 'harga_harian' => 10000]);
    $penyewa ??= User::factory()->diterima()->create();
    $kendaraan = $penyewa->kendaraan()->create([
        'tipe' => TipeKendaraan::SepedaMotor,
        'plat_nomor' => fake()->unique()->bothify('L #### ??'),
        'merek_tipe' => 'Honda Beat',
    ]);

    return Sewa::create([
        'kendaraan_id' => $kendaraan->id,
        'garasi_id' => $garasi->id,
        'tipe_sewa' => 'harian',
        'tanggal_mulai' => $tanggalMulai,
        'tanggal_selesai' => $tanggalMulai,
        'total_biaya' => $totalBiaya,
    ]);
}

test('laporan_dibukaPenyewa_ditolak403', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($penyewa)->get(route('admin.laporan.index'));

    // Assert
    $response->assertForbidden();
});

test('laporanPerBulan_menghitungJumlahDanPendapatanTiapBulan', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    buatSewaLaporan('2027-01-05', 50000);
    buatSewaLaporan('2027-01-20', 30000);
    buatSewaLaporan('2027-03-10', 40000);
    buatSewaLaporan('2026-01-15', 99000);

    // Act
    $response = actingAs($admin)->get(route('admin.laporan.index', ['jenis' => 'bulan', 'tahun' => 2027]));

    // Assert
    $response->assertOk();
    $response->assertViewHas('baris', function (array $baris): bool {
        return count($baris) === 12
            && $baris[0] === ['label' => 'Januari', 'jumlahSewa' => 2, 'pendapatan' => 80000]
            && $baris[1]['jumlahSewa'] === 0
            && $baris[2]['pendapatan'] === 40000;
    });
    $response->assertViewHas('totalPendapatan', 120000);
});

test('laporanPerHari_barisSesuaiJumlahHariDiBulanItu', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    buatSewaLaporan('2027-02-05', 25000);

    // Act
    $response = actingAs($admin)->get(route('admin.laporan.index', ['jenis' => 'hari', 'tahun' => 2027, 'bulan' => 2]));

    // Assert
    $response->assertViewHas('baris', function (array $baris): bool {
        return count($baris) === 28
            && $baris[4] === ['label' => '5 Februari 2027', 'jumlahSewa' => 1, 'pendapatan' => 25000];
    });
});

test('laporanPerTipeGarasi_semuaTipeMunculWalauKosong', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    buatSewaLaporan('2027-01-05', 50000, TipeGarasi::Mobil);
    buatSewaLaporan('2027-01-06', 20000, TipeGarasi::Mobil);
    buatSewaLaporan('2027-01-07', 10000, TipeGarasi::Sepeda);

    // Act
    $response = actingAs($admin)->get(route('admin.laporan.index', ['jenis' => 'tipe', 'tahun' => 2027]));

    // Assert
    $response->assertViewHas('baris', function (array $baris): bool {
        return $baris === [
            ['label' => 'Sepeda', 'jumlahSewa' => 1, 'pendapatan' => 10000],
            ['label' => 'Mobil', 'jumlahSewa' => 2, 'pendapatan' => 70000],
            ['label' => 'Mobil Listrik', 'jumlahSewa' => 0, 'pendapatan' => 0],
        ];
    });
});

test('laporanPerPenyewa_dikelompokkanPerPenyewa', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $budi = User::factory()->diterima()->create(['nama' => 'Budi', 'email' => 'budi@example.com']);
    $ani = User::factory()->diterima()->create(['nama' => 'Ani', 'email' => 'ani@example.com']);
    buatSewaLaporan('2027-01-05', 50000, TipeGarasi::Mobil, $budi);
    buatSewaLaporan('2027-02-05', 30000, TipeGarasi::Mobil, $budi);
    buatSewaLaporan('2027-01-07', 10000, TipeGarasi::Sepeda, $ani);

    // Act
    $response = actingAs($admin)->get(route('admin.laporan.index', ['jenis' => 'penyewa', 'tahun' => 2027]));

    // Assert
    $response->assertSee('Budi (budi@example.com)');
    $response->assertViewHas('baris', fn (array $baris): bool => count($baris) === 2);
    $response->assertViewHas('totalSewa', 3);
});

test('laporan_jenisTidakValid_gagalValidasi', function () {
    // Arrange
    $admin = User::factory()->admin()->create();

    // Act
    $response = actingAs($admin)->get(route('admin.laporan.index', ['jenis' => 'mingguan']));

    // Assert
    $response->assertSessionHasErrors('jenis');
});
