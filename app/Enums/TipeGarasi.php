<?php

namespace App\Enums;

use InvalidArgumentException;

/**
 * Tipe garasi beserta aturan kode garasinya.
 */
enum TipeGarasi: string
{
    case Sepeda = 'sepeda';
    case Mobil = 'mobil';
    case MobilListrik = 'mobil_listrik';

    /** Nomor urut terbesar, karena kode garasi hanya punya 3 digit. */
    public const NOMOR_MAKSIMAL = 999;

    /**
     * Huruf depan kode garasi sesuai tipenya.
     *
     * @return string S untuk sepeda, C untuk mobil, E untuk mobil listrik
     */
    public function prefixKode(): string
    {
        return match ($this) {
            self::Sepeda => 'S',
            self::Mobil => 'C',
            self::MobilListrik => 'E',
        };
    }

    /**
     * Membuat kode garasi dari nomor urut, contoh: nomor 1 tipe sepeda menjadi S001.
     *
     * @param  int  $nomor  nomor urut garasi, 1 sampai 999
     * @return string kode garasi, contoh C010
     *
     * @throws InvalidArgumentException jika nomor di luar 1 sampai 999
     */
    public function buatKode(int $nomor): string
    {
        if ($nomor < 1 || $nomor > self::NOMOR_MAKSIMAL) {
            throw new InvalidArgumentException('Nomor garasi harus 1 sampai '.self::NOMOR_MAKSIMAL.'.');
        }

        return $this->prefixKode().str_pad((string) $nomor, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Nama tipe garasi untuk ditampilkan di halaman.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sepeda => 'Sepeda',
            self::Mobil => 'Mobil',
            self::MobilListrik => 'Mobil Listrik',
        };
    }
}
