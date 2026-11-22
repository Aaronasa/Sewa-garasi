<?php

namespace App\Models;

use App\Enums\TipeGarasi;
use App\Services\KalkulatorBiayaSewa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Garasi yang disewakan, dengan kode seperti S001, C001, atau E001.
 *
 * @author Aaron
 *
 * @version 1.0
 */
#[Table('garasi')]
#[Fillable(['kode', 'tipe', 'harga_harian', 'harga_listrik_per_kwh'])]
class Garasi extends Model
{
    public const STATUS_DIPAKAI = 'used';

    public const STATUS_TERSEDIA = 'tersedia';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe' => TipeGarasi::class,
            'harga_harian' => 'integer',
            'harga_listrik_per_kwh' => 'integer',
        ];
    }

    /**
     * Semua sewa yang pernah memakai garasi ini.
     *
     * @return HasMany<Sewa, $this>
     */
    public function sewa(): HasMany
    {
        return $this->hasMany(Sewa::class);
    }

    /**
     * Harga sewa bulanan, yaitu 25 kali harga harian.
     */
    public function hargaBulanan(): int
    {
        return $this->harga_harian * KalkulatorBiayaSewa::KALI_HARGA_BULANAN;
    }

    /**
     * Status garasi hari ini: 'used' kalau sedang disewa, selain itu 'tersedia'.
     */
    public function statusHariIni(): string
    {
        $hariIni = Carbon::today();
        $sedangDisewa = $this->sewa()->bentrokDengan($hariIni, $hariIni)->exists();

        return $sedangDisewa ? self::STATUS_DIPAKAI : self::STATUS_TERSEDIA;
    }

    /**
     * Hanya ambil garasi yang tidak punya sewa bentrok pada rentang tanggal tersebut.
     *
     * @param  Builder<Garasi>  $query
     * @param  Carbon  $mulai  tanggal mulai sewa
     * @param  Carbon  $selesai  tanggal selesai sewa
     * @return Builder<Garasi>
     */
    public function scopeTersedia(Builder $query, Carbon $mulai, Carbon $selesai): Builder
    {
        return $query->whereDoesntHave('sewa', function (Builder $sewa) use ($mulai, $selesai) {
            $sewa->bentrokDengan($mulai, $selesai);
        });
    }
}
