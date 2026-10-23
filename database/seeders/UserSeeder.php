<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Akun awal sistem. Semua akun setara (tanpa role) dan memakai password "password".
     *
     * Tanggal dibuat sengaja ditulis tetap (bukan acak saat seeding) supaya setiap
     * `migrate:fresh --seed` menghasilkan data yang sama persis.
     */
    private const USERS = [
        ['name' => 'Admin Yery Collection', 'email' => 'yery123@gmail.com', 'created_at' => '2026-07-14 08:30:00'],
        ['name' => 'Dafa Hervansyah', 'email' => 'dafahervansyah10@gmail.com', 'created_at' => '2026-10-20 10:12:00'],
        ['name' => 'Rangga Abdi', 'email' => 'ranggaadz@gmail.com', 'created_at' => '2026-10-22 13:47:00'],
        ['name' => 'Fathir Muhammad', 'email' => 'fathir2020@gmail.com', 'created_at' => '2026-10-25 09:05:00'],
    ];

    /**
     * Aman dijalankan berulang: akun dicari berdasarkan email, jadi tidak terduplikasi.
     */
    public function run(): void
    {
        foreach (self::USERS as $data) {
            $createdAt = Carbon::parse($data['created_at']);

            User::firstOrNew(['email' => $data['email']])
                ->forceFill([
                    'name' => $data['name'],
                    'password' => 'password',
                    'email_verified_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])
                ->save();
        }
    }
}
