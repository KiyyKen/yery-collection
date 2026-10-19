<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        Product::create([
            'code' => 'YRY-001',
            'name' => 'Celana Laporan',
            'category' => 'Celana Anak',
            'size' => 'All Size',
            'price' => 100000,
            'stock' => 10,
            'unit' => 'lusin',
        ])->distributions()->create(['distribution_date' => '2026-03-10', 'quantity' => 4]);
    }

    public function test_report_shows_distributions_within_the_range(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk()
            ->assertSee('Celana Laporan')
            ->assertDontSee('Tanggal awal tidak boleh melebihi tanggal akhir.');
    }

    public function test_reversed_range_shows_error_and_empty_table_without_querying(): void
    {
        DB::enableQueryLog();

        $this->actingAs($this->admin)
            ->get(route('reports.index', ['from' => '2026-03-31', 'to' => '2026-03-01']))
            ->assertOk()
            ->assertSee('Tanggal awal tidak boleh melebihi tanggal akhir.')
            ->assertSee('Perbaiki rentang tanggal untuk menampilkan laporan.')
            ->assertDontSee('Celana Laporan')
            ->assertViewHas('distributions', fn ($distributions) => $distributions->isEmpty());

        $distributionQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], '"distributions"'));

        $this->assertCount(0, $distributionQueries);
    }

    public function test_same_day_range_is_allowed(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['from' => '2026-03-10', 'to' => '2026-03-10']))
            ->assertOk()
            ->assertSee('Celana Laporan')
            ->assertDontSee('Tanggal awal tidak boleh melebihi tanggal akhir.');
    }

    public function test_invalid_date_format_still_falls_back_to_default_range(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['from' => 'abc', 'to' => 'xyz']))
            ->assertOk()
            ->assertDontSee('Tanggal awal tidak boleh melebihi tanggal akhir.')
            ->assertViewHas('from', fn ($from) => $from->isSameDay(now()->startOfMonth()))
            ->assertViewHas('to', fn ($to) => $to->isSameDay(now()));
    }
}
