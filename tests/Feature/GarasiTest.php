<?php

use App\Enums\TipeGarasi;
use App\Models\Garasi;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

/*
 * Feature test pembuatan garasi oleh admin.
 */

/**
 * Menyewakan garasi ke penyewa baru pada rentang tanggal tertentu.
 */
test('garasi_dibukaPenyewa_ditolak403', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($penyewa)->get(route('admin.garasi.index'));

    // Assert
    $response->assertForbidden();
});

test('garasi_store_kodeDibuatBerurutan', function () {
    // Arrange
    $admin = User::factory()->admin()->create();

    // Act
    $response = actingAs($admin)->post(route('admin.garasi.store'), [
        'tipe' => 'sepeda',
        'jumlah' => 3,
        'harga_harian' => 5000,
    ]);

    // Assert
    $response->assertRedirect(route('admin.garasi.index'));
    assertDatabaseCount('garasi', 3);
    assertDatabaseHas('garasi', ['kode' => 'S001']);
    assertDatabaseHas('garasi', ['kode' => 'S003']);
});

test('garasi_store_melanjutkanNomorTerakhir', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    Garasi::create(['kode' => 'C010', 'tipe' => TipeGarasi::Mobil, 'harga_harian' => 15000]);

    // Act
    actingAs($admin)->post(route('admin.garasi.store'), [
        'tipe' => 'mobil',
        'jumlah' => 2,
        'harga_harian' => 15000,
    ]);

    // Assert
    assertDatabaseHas('garasi', ['kode' => 'C011']);
    assertDatabaseHas('garasi', ['kode' => 'C012']);
});

test('garasi_store_mobilListrikTanpaHargaListrik_gagalValidasi', function () {
    // Arrange
    $admin = User::factory()->admin()->create();

    // Act
    $response = actingAs($admin)->post(route('admin.garasi.store'), [
        'tipe' => 'mobil_listrik',
        'jumlah' => 1,
        'harga_harian' => 20000,
    ]);

    // Assert
    $response->assertSessionHasErrors('harga_listrik_per_kwh');
    assertDatabaseCount('garasi', 0);
});

test('garasi_store_bukanMobilListrik_hargaListrikDiabaikan', function () {
    // Arrange
    $admin = User::factory()->admin()->create();

    // Act
    actingAs($admin)->post(route('admin.garasi.store'), [
        'tipe' => 'mobil',
        'jumlah' => 1,
        'harga_harian' => 15000,
        'harga_listrik_per_kwh' => 2500,
    ]);

    // Assert
    assertDatabaseHas('garasi', ['kode' => 'C001', 'harga_listrik_per_kwh' => null]);
});

test('garasi_store_jumlahDiLuarBatas_gagalValidasi', function (int $jumlah) {
    // Arrange
    $admin = User::factory()->admin()->create();

    // Act
    $response = actingAs($admin)->post(route('admin.garasi.store'), [
        'tipe' => 'sepeda',
        'jumlah' => $jumlah,
        'harga_harian' => 5000,
    ]);

    // Assert
    $response->assertSessionHasErrors('jumlah');
    assertDatabaseCount('garasi', 0);
})->with([
    'batas bawah - 1: 0' => [0],
    'batas atas + 1: 1000' => [1000],
]);

test('garasi_store_melebihiSisaNomor_gagal', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    Garasi::create(['kode' => 'S998', 'tipe' => TipeGarasi::Sepeda, 'harga_harian' => 5000]);

    // Act
    $response = actingAs($admin)->post(route('admin.garasi.store'), [
        'tipe' => 'sepeda',
        'jumlah' => 2,
        'harga_harian' => 5000,
    ]);

    // Assert
    $response->assertSessionHasErrors('jumlah');
    assertDatabaseCount('garasi', 1);
});

test('garasi_store_tepatSisaNomorTerakhir_berhasilS999', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    Garasi::create(['kode' => 'S998', 'tipe' => TipeGarasi::Sepeda, 'harga_harian' => 5000]);

    // Act
    actingAs($admin)->post(route('admin.garasi.store'), [
        'tipe' => 'sepeda',
        'jumlah' => 1,
        'harga_harian' => 5000,
    ]);

    // Assert
    assertDatabaseHas('garasi', ['kode' => 'S999']);
});

test('garasi_index_menampilkanDaftarGarasi', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    Garasi::create(['kode' => 'E001', 'tipe' => TipeGarasi::MobilListrik, 'harga_harian' => 20000, 'harga_listrik_per_kwh' => 2500]);

    // Act
    $response = actingAs($admin)->get(route('admin.garasi.index'));

    // Assert
    $response->assertOk();
    $response->assertSee('E001');
    $response->assertSee('Mobil Listrik');
});
