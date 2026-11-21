<?php

namespace App\Enums;

/**
 * Status pendaftaran penyewa setelah dicek admin.
 */
enum StatusPendaftaran: string
{
    case Menunggu = 'menunggu';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
}
