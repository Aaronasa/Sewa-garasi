<?php

namespace App\Enums;

/**
 * Jenis pembayaran: saat sewa baru atau saat perpanjangan.
 */
enum JenisPembayaran: string
{
    case SewaBaru = 'sewa_baru';
    case Perpanjangan = 'perpanjangan';

    /**
     * Nama jenis pembayaran untuk ditampilkan di halaman.
     */
    public function label(): string
    {
        return match ($this) {
            self::SewaBaru => 'Sewa Baru',
            self::Perpanjangan => 'Perpanjangan',
        };
    }
}
