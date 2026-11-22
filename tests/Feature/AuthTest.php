<?php

use App\Enums\Peran;
use App\Enums\StatusPendaftaran;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\from;
use function Pest\Laravel\post;

/*
 * Feature test login, logout, dan pendaftaran penyewa.
 * Database diisi ulang di setiap test (RefreshDatabase di tests/Pest.php).
 */

test('login_penyewaDiterima_masukKeBeranda', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = post(route('login.proses'), [
        'email' => $penyewa->email,
        'password' => 'password',
    ]);

    // Assert
    $response->assertRedirect(route('beranda'));
    assertAuthenticatedAs($penyewa);
});

test('login_passwordSalah_gagalDenganPesanError', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = from(route('login'))->post(route('login.proses'), [
        'email' => $penyewa->email,
        'password' => 'salah-password',
    ]);

    // Assert
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    assertGuest();
});

test('login_penyewaBelumDiterima_tidakBolehMasuk', function (StatusPendaftaran $status) {
    // Arrange
    $penyewa = User::factory()->create(['status' => $status]);

    // Act
    $response = from(route('login'))->post(route('login.proses'), [
        'email' => $penyewa->email,
        'password' => 'password',
    ]);

    // Assert
    $response->assertSessionHasErrors('email');
    assertGuest();
})->with([
    'masih menunggu' => [StatusPendaftaran::Menunggu],
    'ditolak' => [StatusPendaftaran::Ditolak],
]);

test('daftar_dataValid_tersimpanSebagaiPenyewaMenunggu', function () {
    // Arrange
    $data = [
        'nik' => '3578010101010001',
        'nama' => 'Budi Santoso',
        'telepon' => '081234567890',
        'email' => 'budi@example.com',
        'password' => 'rahasia123',
    ];

    // Act
    $response = post(route('daftar.proses'), $data);

    // Assert
    $response->assertRedirect(route('login'));
    assertDatabaseHas('users', [
        'email' => 'budi@example.com',
        'peran' => Peran::Penyewa->value,
        'status' => StatusPendaftaran::Menunggu->value,
    ]);
    assertGuest();
});

test('daftar_nikTidak16Digit_gagalValidasi', function (string $nik) {
    // Arrange
    $data = [
        'nik' => $nik,
        'nama' => 'Budi Santoso',
        'telepon' => '081234567890',
        'email' => 'budi@example.com',
        'password' => 'rahasia123',
    ];

    // Act
    $response = post(route('daftar.proses'), $data);

    // Assert
    $response->assertSessionHasErrors('nik');
    assertDatabaseMissing('users', ['email' => 'budi@example.com']);
})->with([
    'batas - 1: 15 digit' => ['357801010101000'],
    'batas + 1: 17 digit' => ['35780101010100011'],
    'ada huruf' => ['35780101010100AB'],
]);

test('daftar_emailSudahTerdaftar_gagalValidasi', function () {
    // Arrange
    User::factory()->create(['email' => 'budi@example.com']);

    // Act
    $response = post(route('daftar.proses'), [
        'nik' => '3578010101010001',
        'nama' => 'Budi Santoso',
        'telepon' => '081234567890',
        'email' => 'budi@example.com',
        'password' => 'rahasia123',
    ]);

    // Assert
    $response->assertSessionHasErrors('email');
});

test('logout_penggunaLogin_kembaliKeHalamanLogin', function () {
    // Arrange
    $admin = User::factory()->admin()->create();

    // Act
    $response = actingAs($admin)->post(route('logout'));

    // Assert
    $response->assertRedirect(route('login'));
    assertGuest();
});
