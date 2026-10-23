<?php

namespace Tests\Feature;

use App\Models\QuestionnaireAnswer;
use App\Models\QuestionnaireResponse;
use App\Services\QuestionnaireScorer;
use Database\Seeders\QuestionnaireQuestionSeeder;
use Database\Seeders\QuestionnaireResponseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireResponseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([UserSeeder::class, QuestionnaireQuestionSeeder::class]);
    }

    public function test_seeded_responses_produce_the_expected_summary(): void
    {
        $this->seed(QuestionnaireResponseSeeder::class);

        $summary = app(QuestionnaireScorer::class)->summarize();

        $this->assertSame(4, $summary['respondent_count']);
        $this->assertSame(191, $summary['overall']['total']);
        $this->assertEqualsWithDelta(95.50, $summary['overall']['index'], 0.0001);
        $this->assertSame('Sangat Baik', $summary['overall']['interpretation']);

        $this->assertSame(
            [17, 17, 19, 19, 19, 20, 20, 20, 20, 20],
            array_column($summary['questions'], 'total')
        );
        $this->assertSame([1 => 0, 2 => 0, 3 => 0, 4 => 3, 5 => 1], $summary['questions'][0]['distribution']);

        $this->assertSame(
            ['Dafa Hervansyah', 'Fathir Muhammad', 'Rangga Abdi', 'Admin Yery Collection'],
            $summary['respondents']->pluck('user.name')->all()
        );
    }

    public function test_responses_are_submitted_after_the_account_was_created(): void
    {
        $this->seed(QuestionnaireResponseSeeder::class);

        foreach (QuestionnaireResponse::with('user')->get() as $response) {
            $this->assertTrue($response->submitted_at->gt($response->user->created_at), $response->user->name);
            $this->assertTrue($response->submitted_at->gte('2026-10-21'), $response->user->name);
        }
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(QuestionnaireResponseSeeder::class);
        $this->seed(QuestionnaireResponseSeeder::class);

        $this->assertSame(4, QuestionnaireResponse::count());
        $this->assertSame(40, QuestionnaireAnswer::count());
    }
}
