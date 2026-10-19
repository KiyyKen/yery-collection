<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@yerycollection.test']);
    }

    private function productData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'YRY-001',
            'name' => 'Celana Uji',
            'category' => 'Celana Anak',
            'size' => 'All Size',
            'price' => 100000,
            'stock' => 10,
            'unit' => 'lusin',
        ], $overrides);
    }

    public function test_default_locale_is_indonesian(): void
    {
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id', app()->getLocale());
    }

    public function test_failed_login_message_is_in_indonesian(): void
    {
        $this->post('/login', ['email' => 'admin@yerycollection.test', 'password' => 'salah-total'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);

        $this->assertGuest();
    }

    public function test_login_throttle_message_is_in_indonesian(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@yerycollection.test', 'password' => 'salah-total']);
        }

        $this->post('/login', ['email' => 'admin@yerycollection.test', 'password' => 'salah-total'])
            ->assertSessionHasErrors('email');

        $this->assertMatchesRegularExpression(
            '/^Terlalu banyak percobaan login\. Silakan coba lagi dalam \d+ detik\.$/',
            session('errors')->first('email')
        );
    }

    public function test_required_messages_use_indonesian_attribute_names(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), [])
            ->assertSessionHasErrors([
                'code' => 'Kode produk wajib diisi.',
                'name' => 'Nama wajib diisi.',
                'category' => 'Kategori wajib diisi.',
                'size' => 'Ukuran wajib diisi.',
                'price' => 'Harga wajib diisi.',
                'stock' => 'Stok wajib diisi.',
                'unit' => 'Satuan wajib diisi.',
            ]);
    }

    public function test_type_and_length_messages_are_in_indonesian(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), $this->productData([
                'code' => str_repeat('A', 51),
                'price' => 'mahal',
                'stock' => -1,
            ]))
            ->assertSessionHasErrors([
                'code' => 'Kode produk tidak boleh lebih dari 50 karakter.',
                'price' => 'Harga harus berupa angka.',
                'stock' => 'Stok minimal bernilai 0.',
            ]);

        $product = Product::create($this->productData());

        $this->actingAs($this->admin)
            ->post(route('distributions.store'), [
                'product_id' => 9999,
                'distribution_date' => 'bukan-tanggal',
                'quantity' => 'dua',
            ])
            ->assertSessionHasErrors([
                'product_id' => 'Produk yang dipilih tidak valid.',
                'distribution_date' => 'Tanggal distribusi harus berupa tanggal yang valid.',
                'quantity' => 'Jumlah harus berupa bilangan bulat.',
            ]);

        $this->actingAs($this->admin)
            ->post(route('distributions.store'), [
                'product_id' => $product->id,
                'distribution_date' => now()->toDateString(),
                'quantity' => 0,
            ])
            ->assertSessionHasErrors(['quantity' => 'Jumlah minimal bernilai 1.']);
    }

    public function test_email_and_unique_messages_are_in_indonesian(): void
    {
        $other = User::factory()->create(['email' => 'lain@yerycollection.test']);

        $this->actingAs($this->admin)
            ->patch(route('profile.update'), ['name' => 'Admin', 'email' => 'bukan-email'])
            ->assertSessionHasErrors(['email' => 'Email harus berupa alamat email yang valid.']);

        $this->actingAs($this->admin)
            ->patch(route('profile.update'), ['name' => 'Admin', 'email' => $other->email])
            ->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
    }

    public function test_confirmed_message_is_in_indonesian_when_no_custom_message_exists(): void
    {
        $this->actingAs($this->admin)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'passwordbaru',
                'password_confirmation' => 'berbeda123',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password' => 'Konfirmasi password tidak cocok.']);
    }

    public function test_in_and_before_or_equal_rules_have_indonesian_messages(): void
    {
        $validator = Validator::make(
            ['unit' => 'kodi', 'distribution_date' => '2999-01-01'],
            ['unit' => 'in:lusin,pcs', 'distribution_date' => 'before_or_equal:today'],
        );

        $this->assertSame('Satuan yang dipilih tidak valid.', $validator->errors()->first('unit'));
        $this->assertSame(
            'Tanggal distribusi harus berupa tanggal sebelum atau sama dengan hari ini.',
            $validator->errors()->first('distribution_date')
        );
    }

    public function test_existing_custom_messages_are_not_overridden(): void
    {
        Product::create($this->productData())->delete();

        $this->actingAs($this->admin)
            ->post(route('products.store'), $this->productData())
            ->assertSessionHasErrors([
                'code' => 'Kode produk sudah pernah dipakai, termasuk oleh produk yang sudah dihapus. Gunakan kode lain.',
            ]);

        $product = Product::create($this->productData(['code' => 'YRY-002']));

        $this->actingAs($this->admin)
            ->post(route('distributions.store'), [
                'product_id' => $product->id,
                'distribution_date' => now()->addDay()->toDateString(),
                'quantity' => 1,
            ])
            ->assertSessionHasErrors([
                'distribution_date' => 'Tanggal distribusi tidak boleh melebihi tanggal hari ini.',
            ]);

        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Pengguna Baru',
                'email' => $this->admin->email,
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->assertSessionHasErrors([
                'email' => 'Email ini sudah dipakai oleh pengguna lain.',
                'password' => 'Password minimal 8 karakter.',
            ]);
    }
}
