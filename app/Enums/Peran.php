<?php

namespace App\Enums;

/**
 * Peran pengguna di aplikasi sewa garasi.
 */
enum Peran: string
{
    case Admin = 'admin';
    case Penyewa = 'penyewa';
}
