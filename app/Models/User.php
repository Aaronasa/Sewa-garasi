<?php

namespace App\Models;

use App\Enums\Peran;
use App\Enums\StatusPendaftaran;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Pengguna aplikasi, bisa admin atau penyewa garasi.
 *
 * @author Aaron
 *
 * @version 1.0
 */
#[Fillable(['nik', 'nama', 'telepon', 'email', 'password', 'peran', 'status'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'peran' => Peran::class,
            'status' => StatusPendaftaran::class,
        ];
    }

    /**
     * Kendaraan yang dimiliki penyewa ini.
     *
     * @return HasMany<Kendaraan, $this>
     */
    public function kendaraan(): HasMany
    {
        return $this->hasMany(Kendaraan::class);
    }

    /**
     * Semua sewa milik penyewa ini, diambil lewat kendaraannya.
     *
     * @return HasManyThrough<Sewa, Kendaraan, $this>
     */
    public function sewa(): HasManyThrough
    {
        return $this->hasManyThrough(Sewa::class, Kendaraan::class);
    }

    /**
     * Cek apakah pengguna ini admin.
     */
    public function isAdmin(): bool
    {
        return $this->peran === Peran::Admin;
    }

    /**
     * Cek apakah pendaftaran pengguna ini sudah diterima admin.
     */
    public function isDiterima(): bool
    {
        return $this->status === StatusPendaftaran::Diterima;
    }
}
