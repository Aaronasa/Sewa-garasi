<?php

namespace App\Models;

use App\Enums\JenisPembayaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bukti pembayaran untuk sewa baru atau perpanjangan sewa.
 *
 * @author Aaron
 *
 * @version 1.0
 */
#[Table('pembayaran')]
#[Fillable(['sewa_id', 'jenis', 'jumlah', 'bukti_pembayaran'])]
class Pembayaran extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisPembayaran::class,
            'jumlah' => 'integer',
        ];
    }

    /**
     * Sewa yang dibayar.
     *
     * @return BelongsTo<Sewa, $this>
     */
    public function sewa(): BelongsTo
    {
        return $this->belongsTo(Sewa::class);
    }
}
