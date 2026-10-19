<?php

namespace Tests\Feature;

use App\Http\Controllers\PredictionController;
use App\Models\Distribution;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FinalReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
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

    private function productWithDistribution(): Product
    {
        $product = Product::create($this->productData());
        $product->distributions()->create(['distribution_date' => now()->toDateString(), 'quantity' => 2]);

        return $product;
    }

    public function test_code_of_deleted_product_is_rejected_with_validation_error_instead_of_server_error(): void
    {
        Product::create($this->productData())->delete();

        $this->actingAs($this->admin)
            ->from(route('products.create'))
            ->post(route('products.store'), $this->productData())
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Product::withTrashed()->count());
    }

    public function test_future_distribution_date_is_rejected(): void
    {
        $product = Product::create($this->productData());

        $this->actingAs($this->admin)
            ->post(route('distributions.store'), [
                'product_id' => $product->id,
                'distribution_date' => now()->addDay()->toDateString(),
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('distribution_date');

        $this->assertSame(0, Distribution::count());
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_today_distribution_date_is_accepted(): void
    {
        $product = Product::create($this->productData());

        $this->actingAs($this->admin)
            ->post(route('distributions.store'), [
                'product_id' => $product->id,
                'distribution_date' => now()->toDateString(),
                'quantity' => 2,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_invalid_report_dates_fall_back_to_default_range(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['from' => 'abc', 'to' => 'xyz']))
            ->assertOk();
    }

    public function test_analysis_result_survives_logout_and_login(): void
    {
        $this->productWithDistribution();

        $this->actingAs($this->admin)->post(route('predictions.process'));
        $this->assertNotNull(Cache::get(PredictionController::ANALYSIS_CACHE_KEY));

        $this->post(route('logout'));
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Belum ada analisis yang dijalankan.');
    }

    public function test_changing_data_clears_previous_analysis(): void
    {
        $product = $this->productWithDistribution();

        $this->actingAs($this->admin)->post(route('predictions.process'));
        $this->assertNotNull(Cache::get(PredictionController::ANALYSIS_CACHE_KEY));

        $this->put(route('products.update', $product), $this->productData(['stock' => 5]));

        $this->assertNull(Cache::get(PredictionController::ANALYSIS_CACHE_KEY));
    }
}
