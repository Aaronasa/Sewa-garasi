<?php

namespace App\Models;

use App\Enums\TipeSewa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Data sewa satu garasi oleh satu kendaraan.
 *
 * @author Aaron
 *
 * @version 1.0
 */
#[Table('sewa')]
#[Fillable([
    'kendaraan_id', 'garasi_id', 'tipe_sewa', 'tanggal_mulai', 'tanggal_selesai',
    'sisa_baterai_kwh', 'biaya_isi_listrik', 'total_biaya',
])]
class Sewa extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe_sewa' => TipeSewa::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'sisa_baterai_kwh' => 'float',
            'biaya_isi_listrik' => 'integer',
            'total_biaya' => 'integer',
        ];
    }

    /**
     * Kendaraan yang disimpan pada sewa ini.
     *
     * @return BelongsTo<Kendaraan, $this>
     */
    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class);
    }

    /**
     * Garasi yang disewa.
     *
     * @return BelongsTo<Garasi, $this>
     */
    public function garasi(): BelongsTo
    {
        return $this->belongsTo(Garasi::class);
    }

    /**
     * Semua pembayaran untuk sewa ini (sewa baru dan perpanjangan).
     *
     * @return HasMany<Pembayaran, $this>
     */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /**
     * Lama sewa dalam hari. Tanggal mulai dan tanggal selesai ikut dihitung.
     */
    public function jumlahHari(): int
    {
        return (int) $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }

    /**
     * Total uang yang sudah dibayar untuk sewa ini.
     */
    public function totalDibayar(): int
    {
        return (int) $this->pembayaran()->sum('jumlah');
    }

    /**
     * Hanya ambil sewa yang tanggalnya beririsan dengan rentang $mulai sampai $selesai.
     *
     * Dua rentang dianggap bentrok jika mulai yang satu tidak lewat dari selesai yang lain, dan sebaliknya.
     *
     * @param  Builder<Sewa>  $query
     * @param  Carbon  $mulai  tanggal mulai rentang yang dicek
     * @param  Carbon  $selesai  tanggal selesai rentang yang dicek
     * @return Builder<Sewa>
     */
    public function scopeBentrokDengan(Builder $query, Carbon $mulai, Carbon $selesai): Builder
    {
        return $query->whereDate('tanggal_mulai', '<=', $selesai)
            ->whereDate('tanggal_selesai', '>=', $mulai);
    }
}
