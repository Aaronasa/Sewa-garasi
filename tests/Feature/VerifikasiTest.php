<?php

use App\Enums\StatusPendaftaran;
use App\Models\User;

use function Pest\Laravel\actingAs;

/*
 * Feature test verifikasi pendaftar baru oleh admin.
 */

test('verifikasi_dibukaPenyewa_ditolak403', function () {
    // Arrange
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($penyewa)->get(route('admin.verifikasi.index'));

    // Assert
    $response->assertForbidden();
});

test('verifikasi_index_hanyaMenampilkanPendaftarMenunggu', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    User::factory()->create(['nama' => 'Budi Menunggu']);
    User::factory()->diterima()->create(['nama' => 'Ani Diterima']);

    // Act
    $response = actingAs($admin)->get(route('admin.verifikasi.index'));

    // Assert
    $response->assertOk();
    $response->assertSee('Budi Menunggu');
    $response->assertDontSee('Ani Diterima');
});

test('verifikasi_terima_statusMenjadiDiterima', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $pendaftar = User::factory()->create();

    // Act
    $response = actingAs($admin)->patch(route('admin.verifikasi.terima', $pendaftar));

    // Assert
    $response->assertRedirect(route('admin.verifikasi.index'));
    expect($pendaftar->fresh()->status)->toBe(StatusPendaftaran::Diterima);
});

test('verifikasi_tolak_statusMenjadiDitolak', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $pendaftar = User::factory()->create();

    // Act
    $response = actingAs($admin)->patch(route('admin.verifikasi.tolak', $pendaftar));

    // Assert
    $response->assertRedirect(route('admin.verifikasi.index'));
    expect($pendaftar->fresh()->status)->toBe(StatusPendaftaran::Ditolak);
});

test('verifikasi_terimaPenyewaYangSudahDiterima_404', function () {
    // Arrange
    $admin = User::factory()->admin()->create();
    $penyewa = User::factory()->diterima()->create();

    // Act
    $response = actingAs($admin)->patch(route('admin.verifikasi.terima', $penyewa));

    // Assert
    $response->assertNotFound();
});
