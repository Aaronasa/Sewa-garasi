<?php

/*
 * Pesan validasi bahasa Indonesia untuk aturan yang dipakai di aplikasi ini.
 * Aturan yang tidak ada di sini otomatis memakai pesan bawaan Laravel (bahasa Inggris).
 */

return [
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi.',
    'required_unless' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'integer' => ':attribute harus berupa angka bulat.',
    'numeric' => ':attribute harus berupa angka.',
    'date' => ':attribute harus berupa tanggal yang valid.',
    'after' => ':attribute harus setelah :date.',
    'image' => ':attribute harus berupa file gambar.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'unique' => ':attribute sudah terdaftar.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'in' => ':attribute yang dipilih tidak valid.',
    'digits' => ':attribute harus :digits digit angka.',
    'digits_between' => ':attribute harus :min sampai :max digit angka.',
    'gt' => [
        'numeric' => ':attribute harus lebih dari :value.',
    ],
    'max' => [
        'string' => ':attribute maksimal :max karakter.',
        'numeric' => ':attribute maksimal :max.',
        'file' => ':attribute maksimal :max kilobyte.',
    ],
    'min' => [
        'string' => ':attribute minimal :min karakter.',
        'numeric' => ':attribute minimal :min.',
    ],

    'custom' => [
        'harga_listrik_per_kwh' => [
            'required_if' => 'Harga listrik per kWh wajib diisi untuk garasi mobil listrik.',
        ],
        'plat_nomor' => [
            'required_unless' => 'Plat nomor wajib diisi, kecuali untuk sepeda.',
        ],
        'kapasitas_baterai_kwh' => [
            'required_if' => 'Kapasitas baterai wajib diisi untuk mobil listrik.',
        ],
        'sisa_baterai_kwh' => [
            'required_if' => 'Sisa baterai wajib diisi untuk mobil listrik.',
            'lte' => 'Sisa baterai tidak boleh melebihi kapasitas baterai.',
        ],
        'jumlah_hari' => [
            'required_if' => 'Jumlah hari wajib diisi untuk sewa harian.',
        ],
        'tanggal_mulai' => [
            'after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
        ],
        'tanggal_selesai_baru' => [
            'after' => 'Tanggal selesai baru harus setelah tanggal selesai lama.',
        ],
    ],

    'attributes' => [
        'nik' => 'NIK',
        'nama' => 'Nama lengkap',
        'telepon' => 'Nomor telepon',
        'email' => 'Email',
        'password' => 'Password',
        'tipe' => 'Tipe garasi',
        'jumlah' => 'Jumlah garasi',
        'harga_harian' => 'Harga sewa harian',
        'harga_listrik_per_kwh' => 'Harga listrik per kWh',
        'tipe_kendaraan' => 'Tipe kendaraan',
        'plat_nomor' => 'Plat nomor',
        'merek_tipe' => 'Merek dan tipe',
        'kapasitas_baterai_kwh' => 'Kapasitas baterai',
        'sisa_baterai_kwh' => 'Sisa baterai',
        'tipe_sewa' => 'Tipe sewa',
        'jumlah_hari' => 'Jumlah hari',
        'tanggal_mulai' => 'Tanggal mulai',
        'tipe_garasi' => 'Tipe garasi',
        'garasi_id' => 'Garasi',
        'bukti_pembayaran' => 'Bukti pembayaran',
        'tanggal_selesai_baru' => 'Tanggal selesai baru',
        'jenis' => 'Jenis laporan',
        'tahun' => 'Tahun',
        'bulan' => 'Bulan',
    ],
];
