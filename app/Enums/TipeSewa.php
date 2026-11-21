<?php

namespace App\Enums;

/**
 * Jenis sewa garasi.
 */
enum TipeSewa: string
{
    case Harian = 'harian';
    case Bulanan = 'bulanan';

    /**
     * Nama tipe sewa untuk ditampilkan di halaman.
     */
    public function label(): string
    {
        return match ($this) {
            self::Harian => 'Harian',
            self::Bulanan => 'Bulanan',
        };
    }
}
