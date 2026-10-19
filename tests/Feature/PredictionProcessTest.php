<?php

namespace Tests\Feature;

use App\Http\Controllers\PredictionController;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PredictionProcessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    private function createProduct(string $code): Product
    {
        return Product::create([
            'code' => $code,
            'name' => "Celana {$code}",
            'category' => 'Celana Anak',
            'size' => 'All Size',
            'price' => 100000,
            'stock' => 10,
            'unit' => 'lusin',
        ]);
    }

    public function test_analysis_is_rejected_when_there_are_no_products(): void
    {
        $this->actingAs($this->admin)
            ->post(route('predictions.process'))
            ->assertRedirect(route('predictions.index'))
            ->assertSessionHas('error', 'Belum ada data produk untuk dianalisis.');

        $this->assertNull(Cache::get(PredictionController::ANALYSIS_CACHE_KEY));
    }

    public function test_analysis_is_rejected_when_there_are_no_distributions(): void
    {
        $product = $this->createProduct('YRY-001');

        $this->actingAs($this->admin)
            ->post(route('predictions.process'))
            ->assertRedirect(route('predictions.index'))
            ->assertSessionHas('error', 'Belum ada data distribusi untuk dianalisis.');

        $this->assertNull(Cache::get(PredictionController::ANALYSIS_CACHE_KEY));
        $this->assertNull($product->fresh()->predicted_label);
    }

    public function test_deleted_distributions_do_not_count_as_data(): void
    {
        $product = $this->createProduct('YRY-001');
        $product->distributions()->create(['distribution_date' => now()->toDateString(), 'quantity' => 3])->delete();

        $this->actingAs($this->admin)
            ->post(route('predictions.process'))
            ->assertSessionHas('error', 'Belum ada data distribusi untuk dianalisis.');
    }

    public function test_analysis_runs_when_distribution_data_exists(): void
    {
        $sold = $this->createProduct('YRY-001');
        $this->createProduct('YRY-002');
        $sold->distributions()->create(['distribution_date' => now()->toDateString(), 'quantity' => 5]);

        $this->actingAs($this->admin)
            ->post(route('predictions.process'))
            ->assertRedirect(route('predictions.index'))
            ->assertSessionHas('success');

        $this->assertNotNull(Cache::get(PredictionController::ANALYSIS_CACHE_KEY));
        $this->assertSame('Laris', $sold->fresh()->predicted_label);
    }
}
