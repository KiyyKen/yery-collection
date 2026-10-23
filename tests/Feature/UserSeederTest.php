<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_initial_accounts_with_fixed_dates(): void
    {
        $this->seed(UserSeeder::class);

        $users = User::orderBy('created_at')->get();

        $this->assertSame(
            ['Admin Yery Collection', 'Dafa Hervansyah', 'Rangga Abdi', 'Fathir Muhammad'],
            $users->pluck('name')->all()
        );
        $this->assertSame('2026-07-14 08:30:00', $users[0]->created_at->toDateTimeString());
        $this->assertSame('2026-10-20 10:12:00', $users[1]->created_at->toDateTimeString());

        foreach ($users as $user) {
            $this->assertTrue(Hash::check('password', $user->password), $user->name);
        }
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(4, User::count());
        $this->assertSame('2026-10-25 09:05:00', User::where('email', 'fathir2020@gmail.com')->first()->created_at->toDateTimeString());
    }

    public function test_seeded_admin_can_log_in(): void
    {
        $this->seed(UserSeeder::class);

        $this->post('/login', ['email' => 'yery123@gmail.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
