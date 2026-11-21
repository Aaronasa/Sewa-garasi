<?php

namespace App\Enums;

/**
 * Tipe kendaraan beserta aturan garasi yang boleh dipakai.
 */
enum TipeKendaraan: string
{
    case Sepeda = 'sepeda';
    case SepedaMotor = 'sepeda_motor';
    case Mobil = 'mobil';
    case MobilListrik = 'mobil_listrik';

    /**
     * Nama tipe kendaraan untuk ditampilkan di halaman.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sepeda => 'Sepeda',
            self::SepedaMotor => 'Sepeda Motor',
            self::Mobil => 'Mobil',
            self::MobilListrik => 'Mobil Listrik',
        };
    }

    /**
     * Tipe garasi yang boleh dipakai kendaraan ini.
     *
     * @return array<int, TipeGarasi>
     */
    public function tipeGarasiYangCocok(): array
    {
        return match ($this) {
            self::Sepeda, self::SepedaMotor => [TipeGarasi::Sepeda],
            self::Mobil => [TipeGarasi::Mobil],
            self::MobilListrik => [TipeGarasi::Mobil, TipeGarasi::MobilListrik],
        };
    }

    /**
     * Semua kendaraan wajib punya plat nomor, kecuali sepeda.
     */
    public function wajibPlatNomor(): bool
    {
        return $this !== self::Sepeda;
    }

    /**
     * Cek apakah kendaraan ini mobil listrik.
     */
    public function isListrik(): bool
    {
        return $this === self::MobilListrik;
    }
}
