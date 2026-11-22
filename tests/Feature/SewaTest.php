<?php

use App\Enums\TipeGarasi;
use App\Enums\TipeKendaraan;
use App\Models\Garasi;
use App\Models\Pembayaran;
use App\Models\Sewa;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\travelTo;

/*
 * Feature test sewa garasi baru oleh penyewa.
 * Waktu "hari ini" dikunci ke 21 November 2026 supaya hasil test selalu sama.
 */

beforeEach(function () {
    travelTo(Carbon::parse('2026-11-21'));
});

/**
 * Membuat satu garasi untuk data uji.
 */
function buatGarasi(string $kode, TipeGarasi $tipe, int $hargaHarian = 20000, ?int $hargaListrik = null): Garasi
{
    return Garasi::create([
        'kode' => $kode,
        'tipe' => $tipe,
        'harga_harian' => $hargaHarian,
        'harga_listrik_per_kwh' => $hargaListrik,
    ]);
}

/**
 * Membuat sewa milik penyewa lain supaya garasi terpakai pada rentang tanggal tertentu.
 */
function buatSewaLain(Garasi $garasi, string $mulai, string $selesai): Sewa
{
    $kendaraan = User::factory()->diterima()->create()->kendaraan()->create([
        'tipe' => TipeKendaraan::Mobil,
        'plat_nomor' => 'W 9999 ZZ',
        'merek_tipe' => 'Honda Jazz',
    ]);

    return Sewa::create([
        'kendaraan_id' => $kendaraan->id,
        'garasi_id' => $garasi->id,
        'tipe_sewa' => 'harian',
        'tanggal_mulai' => $mulai,
        'tanggal_selesai' => $selesai,
        'total_biaya' => 100000,
    ]);
}

/**
 * Data form sewa yang valid, bisa diubah sebagian lewat $ubah.
 *
 * @param  array<string, mixed>  $ubah
 * @return array<string, mixed>
 */
function dataSewa(array $ubah = []): array
{
    return [
        'tipe_kendaraan' => 'mobil',
        'plat_nomor' => 'L 1234 AB',
        'merek_tipe' => 'Toyota Avanza',
        'tipe_sewa' => 'harian',
        'jumlah_hari' => 2,
        'tanggal_mulai' => '2026-11-21',
        'tipe_garasi' => 'mobil',
        ...$ubah,
    ];
}

test('sewa_dibukaAdmin_ditolak403', function () {
    // Arrange
    $admin = User::factory()->admin()->create();

    // Act
    $response = actingAs($admin)->get(route('sewa.create'));

    // Assert
    $response->assertForbidden();
});

test('pilihGarasi_hanyaMenampilkanGarasiTersediaDanSesuaiTipe', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();
    buatGarasi('C001', TipeGarasi::Mobil);
    $garasiTerpakai = buatGarasi('C002', TipeGarasi::Mobil);
    buatGarasi('E001', TipeGarasi::MobilListrik, 20000, 2500);
    buatSewaLain($garasiTerpakai, '2026-11-20', '2026-11-22');

    // Act
    $response = actingAs($penyewa)->get(route('sewa.pilih-garasi', dataSewa()));

    // Assert
    $response->assertOk();
    $response->assertSee('C001');
    $response->assertDontSee('C002');
    $response->assertDontSee('E001');
});

test('pilihGarasi_garasiTidakCocokDenganKendaraan_gagal', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($penyewa)->get(route('sewa.pilih-garasi', dataSewa([
        'tipe_kendaraan' => 'sepeda_motor',
        'tipe_garasi' => 'mobil',
    ])));

    // Assert
    $response->assertSessionHasErrors('tipe_garasi');
});

test('pilihGarasi_tanggalMulaiKemarin_gagal', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($penyewa)->get(route('sewa.pilih-garasi', dataSewa(['tanggal_mulai' => '2026-11-20'])));

    // Assert
    $response->assertSessionHasErrors('tanggal_mulai');
});

test('pilihGarasi_harianDiFebruari_batasnya27Hari', function (int $jumlahHari, bool $harusGagal) {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($penyewa)->get(route('sewa.pilih-garasi', dataSewa([
        'tanggal_mulai' => '2027-02-01',
        'jumlah_hari' => $jumlahHari,
    ])));

    // Assert
    if ($harusGagal) {
        $response->assertSessionHasErrors('jumlah_hari');
    } else {
        $response->assertOk();
    }
})->with([
    'batas: 27 hari' => [27, false],
    'batas + 1: 28 hari' => [28, true],
]);

test('store_mobilListrikDiGarasiListrik_tersimpanDenganBiayaIsiListrik', function () {
    // Arrange
    Storage::fake('public');
    $penyewa = User::factory()->diterima()->create();
    $garasi = buatGarasi('E001', TipeGarasi::MobilListrik, 20000, 2500);

    // Act
    $response = actingAs($penyewa)->post(route('sewa.store'), dataSewa([
        'tipe_kendaraan' => 'mobil_listrik',
        'kapasitas_baterai_kwh' => 60,
        'sisa_baterai_kwh' => 20,
        'tipe_garasi' => 'mobil_listrik',
        'garasi_id' => $garasi->id,
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]));

    // Assert: 2 hari x Rp20.000 + (60 - 20) kWh x Rp2.500 = Rp40.000 + Rp100.000
    $response->assertRedirect(route('sewa.index'));
    $sewa = Sewa::first();
    expect($sewa->garasi_id)->toBe($garasi->id);
    expect($sewa->tanggal_selesai->toDateString())->toBe('2026-11-22');
    expect($sewa->biaya_isi_listrik)->toBe(100000);
    expect($sewa->total_biaya)->toBe(140000);
    assertDatabaseHas('pembayaran', ['jenis' => 'sewa_baru', 'jumlah' => 140000]);
    expect(Storage::disk('public')->exists(Pembayaran::first()->bukti_pembayaran))->toBeTrue();
});

test('store_mobilListrikDiGarasiMobilBiasa_tanpaBiayaIsiListrik', function () {
    // Arrange
    Storage::fake('public');
    $penyewa = User::factory()->diterima()->create();
    $garasi = buatGarasi('C001', TipeGarasi::Mobil, 15000);

    // Act
    actingAs($penyewa)->post(route('sewa.store'), dataSewa([
        'tipe_kendaraan' => 'mobil_listrik',
        'kapasitas_baterai_kwh' => 60,
        'sisa_baterai_kwh' => 20,
        'garasi_id' => $garasi->id,
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]));

    // Assert
    assertDatabaseHas('sewa', ['biaya_isi_listrik' => 0, 'total_biaya' => 30000]);
});

test('store_garasiSudahTerpakai_gagal', function () {
    // Arrange
    Storage::fake('public');
    $penyewa = User::factory()->diterima()->create();
    $garasi = buatGarasi('C001', TipeGarasi::Mobil);
    buatSewaLain($garasi, '2026-11-22', '2026-11-25');

    // Act
    $response = actingAs($penyewa)->post(route('sewa.store'), dataSewa([
        'garasi_id' => $garasi->id,
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]));

    // Assert
    $response->assertSessionHasErrors('garasi_id');
    assertDatabaseCount('sewa', 1);
});

test('store_tanpaBuktiPembayaran_gagal', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();
    $garasi = buatGarasi('C001', TipeGarasi::Mobil);

    // Act
    $response = actingAs($penyewa)->post(route('sewa.store'), dataSewa(['garasi_id' => $garasi->id]));

    // Assert
    $response->assertSessionHasErrors('bukti_pembayaran');
    assertDatabaseCount('sewa', 0);
});

test('store_kendaraanSamaSudahPunyaSewaBentrok_gagal', function () {
    // Arrange
    Storage::fake('public');
    $penyewa = User::factory()->diterima()->create();
    $garasiPertama = buatGarasi('C001', TipeGarasi::Mobil);
    $garasiKedua = buatGarasi('C002', TipeGarasi::Mobil);
    actingAs($penyewa)->post(route('sewa.store'), dataSewa([
        'garasi_id' => $garasiPertama->id,
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]));

    // Act: mobil yang sama mencoba menyewa garasi lain di tanggal yang sama
    $response = actingAs($penyewa)->post(route('sewa.store'), dataSewa([
        'garasi_id' => $garasiKedua->id,
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]));

    // Assert
    $response->assertSessionHasErrors('plat_nomor');
    assertDatabaseCount('sewa', 1);
});

test('index_hanyaMenampilkanSewaMilikSendiri', function () {
    // Arrange
    Storage::fake('public');
    $penyewa = User::factory()->diterima()->create();
    $garasiSendiri = buatGarasi('C001', TipeGarasi::Mobil);
    $garasiOrangLain = buatGarasi('C002', TipeGarasi::Mobil);
    buatSewaLain($garasiOrangLain, '2026-11-21', '2026-11-22');
    actingAs($penyewa)->post(route('sewa.store'), dataSewa([
        'garasi_id' => $garasiSendiri->id,
        'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
    ]));

    // Act
    $response = actingAs($penyewa)->get(route('sewa.index'));

    // Assert
    $response->assertSee('C001');
    $response->assertDontSee('C002');
});
