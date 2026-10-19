<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Pesan bawaan untuk rule validasi yang dipakai di project ini. Rule lain
    | yang tidak tercantum di sini memakai pesan bahasa Inggris (fallback).
    | Pesan kustom di masing-masing Form Request tetap diutamakan.
    |
    */

    'array' => ':Attribute harus berupa daftar.',
    'before_or_equal' => ':Attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus berisi antara :min sampai :max item.',
        'file' => ':Attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => ':Attribute harus bernilai antara :min sampai :max.',
        'string' => ':Attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Password saat ini salah.',
    'date' => ':Attribute harus berupa tanggal yang valid.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'lowercase' => ':Attribute harus ditulis dengan huruf kecil.',
    'max' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :max item.',
        'file' => ':Attribute tidak boleh berukuran lebih dari :max kilobyte.',
        'numeric' => ':Attribute tidak boleh lebih dari :max.',
        'string' => ':Attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => ':Attribute minimal berisi :min item.',
        'file' => ':Attribute minimal berukuran :min kilobyte.',
        'numeric' => ':Attribute minimal bernilai :min.',
        'string' => ':Attribute minimal terdiri dari :min karakter.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah digunakan.',

    /*
    |--------------------------------------------------------------------------
    | Nama Atribut
    |--------------------------------------------------------------------------
    |
    | Mengganti nama field (mis. "distribution_date") dengan label yang mudah
    | dibaca (mis. "tanggal distribusi") di dalam pesan validasi.
    |
    */

    'attributes' => [
        // Pengguna, profil, dan autentikasi
        'name' => 'nama',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'konfirmasi password',
        'current_password' => 'password saat ini',

        // Produk
        'code' => 'kode produk',
        'category' => 'kategori',
        'size' => 'ukuran',
        'price' => 'harga',
        'stock' => 'stok',
        'unit' => 'satuan',

        // Distribusi
        'product_id' => 'produk',
        'distribution_date' => 'tanggal distribusi',
        'quantity' => 'jumlah',
        'notes' => 'catatan',

        // Kuesioner
        'answers' => 'jawaban',
        'answers.*' => 'jawaban',
    ],

    /*
    | Nilai parameter rule yang ikut tampil di pesan (mis. before_or_equal:today).
    */

    'values' => [
        'distribution_date' => [
            'today' => 'hari ini',
        ],
    ],

];
