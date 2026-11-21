<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GarasiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PerpanjanganController;
use App\Http\Controllers\SewaController;
use App\Http\Controllers\VerifikasiController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'tampilkanLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
    Route::get('/daftar', [AuthController::class, 'tampilkanDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->name('daftar.proses');
});

Route::middleware('auth')->group(function () {
    Route::view('/', 'beranda')->name('beranda');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
    Route::patch('/verifikasi/{user}/terima', [VerifikasiController::class, 'terima'])->name('verifikasi.terima');
    Route::patch('/verifikasi/{user}/tolak', [VerifikasiController::class, 'tolak'])->name('verifikasi.tolak');

    Route::get('/garasi', [GarasiController::class, 'index'])->name('garasi.index');
    Route::get('/garasi/create', [GarasiController::class, 'create'])->name('garasi.create');
    Route::post('/garasi', [GarasiController::class, 'store'])->name('garasi.store');

    Route::get('/perpanjangan', [PerpanjanganController::class, 'index'])->name('perpanjangan.index');
    Route::get('/perpanjangan/{sewa}', [PerpanjanganController::class, 'edit'])->name('perpanjangan.edit');
    Route::patch('/perpanjangan/{sewa}', [PerpanjanganController::class, 'update'])->name('perpanjangan.update');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
});

Route::middleware(['auth', 'can:penyewa'])->group(function () {
    Route::get('/sewa', [SewaController::class, 'index'])->name('sewa.index');
    Route::get('/sewa/baru', [SewaController::class, 'create'])->name('sewa.create');
    Route::get('/sewa/pilih-garasi', [SewaController::class, 'pilihGarasi'])->name('sewa.pilih-garasi');
    Route::post('/sewa', [SewaController::class, 'store'])->name('sewa.store');
});
