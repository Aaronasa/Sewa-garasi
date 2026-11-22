<?php

use App\Enums\JenisPembayaran;
use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Enums\TipeSewa;
use App\Models\Garasi;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\travelTo;

/*
 * Feature test perpanjangan sewa oleh admin.
 * Hari ini dikunci ke 21 November 2026 (November = 30 hari, jadi 1 bulan sewa = 30 hari).
 * Harga harian garasi Rp20.000, jadi harga bulanan Rp500.000.
 */

beforeEach(function () {
    travelTo(Carbon::parse('2026-11-21'));
    Storage::fake('public');
});

/**
 * Membuat sewa harian yang sudah lunas dan sedang berjalan.
 */
function buatSewaBerjalan(string $kodeGarasi, string $mulai, string $selesai, string $plat = 'L 1234 AB'): Sewa
{
    $garasi = Garasi::create(['kode' => $kodeGarasi, 'tipe' => TipeGarasi::Mobil, 'harga_harian' => 20000]);
    $kendaraan = User::factory()->diterima()->create()->kendaraan()->create([
        'tipe' => TipeKendaraan::Mobil,
        'plat_nomor' => $plat,
        'merek_tipe' => 'Toyota Avanza',
    ]);
    $jumlahHari = (int) Carbon::parse($mulai)->diffInDays(Carbon::parse($selesai)) + 1;

    $sewa = Sewa::create([
        'kendaraan_id' => $kendaraan->id,
        'garasi_id' => $garasi->id,
        'tipe_sewa' => TipeSewa::Harian,
        'tanggal_mulai' => $mulai,
        'tanggal_selesai' => $selesai,
        'total_biaya' => $jumlahHari * 20000,
    ]);
    $sewa->pembayaran()->create([
        'jenis' => JenisPembayaran::SewaBaru,
        'jumlah' => $jumlahHari * 20000,
        'bukti_pembayaran' => 'bukti-pembayaran/awal.jpg',
    ]);

    return $sewa;
}

test('perpanjangan_dibukaPenyewa_ditolak403', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($penyewa)->get(route('admin.perpanjangan.index'));

    // Assert
    $response->assertForbidden();
});

test('index_hanyaMenampilkanSewaYangBerjalanHariIni', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    buatSewaBerjalan('C001', '2026-11-20', '2026-11-22', 'L 1111 AA');
    buatSewaBerjalan('C002', '2026-11-25', '2026-11-26', 'L 2222 BB');

    // Act
    $response = actingAs($admin)->get(route('admin.perpanjangan.index'));

    // Assert
    $response->assertSee('C001');
    $response->assertDontSee('C002');
});

test('edit_isiTanggalBaru_menampilkanSisaBiaya', function () {
    // Arrange: sewa 2 hari (Rp40.000) sudah lunas
    $admin = User::factory()->admin()->create();
    $sewa = buatSewaBerjalan('C001', '2026-11-21', '2026-11-22');

    // Act: diperpanjang sampai 24 Nov = 4 hari = Rp80.000
    $response = actingAs($admin)->get(route('admin.perpanjangan.edit', [$sewa, 'tanggal_selesai_baru' => '2026-11-24']));

    // Assert
    $response->assertOk();
    $response->assertViewHas('hasil', fn (array $hasil): bool => $hasil['sisaBiaya'] === 40000);
});

test('update_perpanjangHarian_totalDanPembayaranBertambah', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $sewa = buatSewaBerjalan('C001', '2026-11-21', '2026-11-22');

    // Act
    $response = actingAs($admin)->patch(route('admin.perpanjangan.update', $sewa), [
        'tanggal_selesai_baru' => '2026-11-24',
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    // Assert
    $response->assertRedirect(route('admin.perpanjangan.index'));
    $sewa->refresh();
    expect($sewa->tanggal_selesai->toDateString())->toBe('2026-11-24');
    expect($sewa->total_biaya)->toBe(80000);
    expect($sewa->totalDibayar())->toBe(80000);
    expect($sewa->pembayaran()->latest('id')->first()->jenis)->toBe(JenisPembayaran::Perpanjangan);
});

test('update_lamaSewaMenjadiBulanan_dihitungHargaBulanan', function (string $tanggalBaru, int $totalDiharapkan) {
    // Arrange
    $admin = User::factory()->admin()->create();
    $sewa = buatSewaBerjalan('C001', '2026-11-21', '2026-11-22');

    // Act
    actingAs($admin)->patch(route('admin.perpanjangan.update', $sewa), [
        'tanggal_selesai_baru' => $tanggalBaru,
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    // Assert
    $sewa->refresh();
    expect($sewa->total_biaya)->toBe($totalDiharapkan);
    expect($sewa->tipe_sewa)->toBe(TipeSewa::Bulanan);
})->with([
    'tepat 30 hari = 1 bulan' => ['2026-12-20', 500000],
    '45 hari = 2 bulan' => ['2027-01-04', 1000000],
]);

test('update_tanggalBaruTidakSetelahTanggalLama_gagal', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $sewa = buatSewaBerjalan('C001', '2026-11-21', '2026-11-22');

    // Act
    $response = actingAs($admin)->patch(route('admin.perpanjangan.update', $sewa), [
        'tanggal_selesai_baru' => '2026-11-22',
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    // Assert
    $response->assertSessionHasErrors('tanggal_selesai_baru');
});

test('update_bentrokDenganSewaLainDiGarasiSama_gagal', function () {
    // Arrange: garasi C001 sudah dipesan penyewa lain mulai 25 Nov
    $admin = User::factory()->admin()->create();
    $sewa = buatSewaBerjalan('C001', '2026-11-21', '2026-11-22');
    $kendaraanLain = User::factory()->diterima()->create()->kendaraan()->create([
        'tipe' => TipeKendaraan::Mobil,
        'plat_nomor' => 'W 9999 ZZ',
        'merek_tipe' => 'Honda Jazz',
    ]);
    Sewa::create([
        'kendaraan_id' => $kendaraanLain->id,
        'garasi_id' => $sewa->garasi_id,
        'tipe_sewa' => TipeSewa::Harian,
        'tanggal_mulai' => '2026-11-25',
        'tanggal_selesai' => '2026-11-26',
        'total_biaya' => 40000,
    ]);

    // Act
    $response = actingAs($admin)->patch(route('admin.perpanjangan.update', $sewa), [
        'tanggal_selesai_baru' => '2026-11-25',
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    // Assert
    $response->assertSessionHasErrors('tanggal_selesai_baru');
    expect($sewa->fresh()->tanggal_selesai->toDateString())->toBe('2026-11-22');
});

test('update_adaSisaBiayaTanpaBukti_gagal', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $sewa = buatSewaBerjalan('C001', '2026-11-21', '2026-11-22');

    // Act
    $response = actingAs($admin)->patch(route('admin.perpanjangan.update', $sewa), [
        'tanggal_selesai_baru' => '2026-11-24',
    ]);

    // Assert
    $response->assertSessionHasErrors('bukti_pembayaran');
    assertDatabaseCount('pembayaran', 1);
});
