<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Admin Utama']);
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_user_list_marks_the_current_account(): void
    {
        User::factory()->create(['name' => 'Admin Kedua']);

        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Admin Utama')
            ->assertSee('Admin Kedua')
            ->assertSee('Anda');
    }

    public function test_user_can_be_created(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Pemilik Toko',
                'email' => 'pemilik@yerycollection.test',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $user = User::where('email', 'pemilik@yerycollection.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_password_must_be_confirmed_and_at_least_eight_characters(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Pemilik Toko',
                'email' => 'pemilik@yerycollection.test',
                'password' => 'pendek',
                'password_confirmation' => 'beda',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame(1, User::count());
    }

    public function test_email_must_be_unique_when_creating(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Duplikat',
                'email' => $this->admin->email,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_email_must_be_unique_when_updating_but_own_email_is_allowed(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('users.update', $other), [
                'name' => $other->name,
                'email' => $this->admin->email,
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($this->admin)
            ->put(route('users.update', $other), [
                'name' => 'Nama Baru',
                'email' => $other->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertSame('Nama Baru', $other->fresh()->name);
    }

    public function test_empty_password_on_update_keeps_the_old_password(): void
    {
        $other = User::factory()->create(['password' => 'passwordlama']);

        $this->actingAs($this->admin)
            ->put(route('users.update', $other), [
                'name' => 'Nama Baru',
                'email' => 'baru@yerycollection.test',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasNoErrors();

        $other->refresh();
        $this->assertSame('baru@yerycollection.test', $other->email);
        $this->assertTrue(Hash::check('passwordlama', $other->password));
    }

    public function test_filled_password_on_update_changes_the_password(): void
    {
        $other = User::factory()->create(['password' => 'passwordlama']);

        $this->actingAs($this->admin)
            ->put(route('users.update', $other), [
                'name' => $other->name,
                'email' => $other->email,
                'password' => 'passwordbaru',
                'password_confirmation' => 'passwordbaru',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru', $other->fresh()->password));
    }

    public function test_other_user_can_be_deleted(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $other))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertNull($other->fresh());
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun yang sedang Anda gunakan.');

        $this->assertNotNull($this->admin->fresh());
    }

    public function test_public_registration_page_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
