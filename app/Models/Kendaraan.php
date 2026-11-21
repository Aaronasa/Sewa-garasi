<?php

namespace App\Models;

use App\Enums\TipeKendaraan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kendaraan milik penyewa yang disimpan di garasi.
 *
 * @author Aaron
 *
 * @version 1.0
 */
#[Table('kendaraan')]
#[Fillable(['user_id', 'tipe', 'plat_nomor', 'merek_tipe', 'kapasitas_baterai_kwh'])]
class Kendaraan extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe' => TipeKendaraan::class,
            'kapasitas_baterai_kwh' => 'float',
        ];
    }

    /**
     * Penyewa pemilik kendaraan ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Riwayat sewa garasi untuk kendaraan ini.
     *
     * @return HasMany<Sewa, $this>
     */
    public function sewa(): HasMany
    {
        return $this->hasMany(Sewa::class);
    }
}
