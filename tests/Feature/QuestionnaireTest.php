<?php

namespace Tests\Feature;

use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use App\Services\QuestionnaireScorer;
use Database\Seeders\QuestionnaireQuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(QuestionnaireQuestionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Admin Utama']);
    }

    /**
     * Susun input answers[<id pertanyaan>] dari daftar skor berurutan sesuai nomor pernyataan 1–10.
     *
     * @param  array<int, int|string>  $scores
     * @return array<int, int|string>
     */
    private function answers(array $scores): array
    {
        $ids = QuestionnaireQuestion::orderBy('order')->pluck('id')->all();

        return array_combine($ids, $scores);
    }

    public function test_seeder_creates_ten_ordered_statements_and_is_idempotent(): void
    {
        $this->seed(QuestionnaireQuestionSeeder::class);

        $questions = QuestionnaireQuestion::orderBy('order')->get();

        $this->assertCount(10, $questions);
        $this->assertSame(range(1, 10), $questions->pluck('order')->all());
        $this->assertSame('Kemudahan', $questions->first()->aspect);
        $this->assertSame('Secara keseluruhan, sistem bermanfaat bagi Toko Yery Collection.', $questions->last()->statement);
    }

    public function test_form_is_shown_to_user_who_has_not_filled_the_questionnaire(): void
    {
        $this->actingAs($this->admin)
            ->get(route('questionnaire.index'))
            ->assertOk()
            ->assertSee('Kirim Jawaban')
            ->assertSee('Sistem mudah dipelajari saat pertama kali digunakan.');
    }

    public function test_user_can_submit_the_questionnaire(): void
    {
        $this->actingAs($this->admin)
            ->post(route('questionnaire.store'), ['answers' => $this->answers([5, 4, 4, 3, 5, 4, 4, 5, 4, 5])])
            ->assertRedirect(route('questionnaire.index'))
            ->assertSessionHas('success');

        $response = $this->admin->fresh()->questionnaireResponse;
        $this->assertNotNull($response);
        $this->assertNotNull($response->submitted_at);
        $this->assertCount(10, $response->answers);
        $this->assertSame(43, $response->answers->sum('score'));

        $this->actingAs($this->admin)
            ->get(route('questionnaire.index'))
            ->assertSee('Anda sudah mengisi kuesioner')
            ->assertDontSee('Kirim Jawaban');
    }

    public function test_user_cannot_submit_the_questionnaire_twice(): void
    {
        $this->actingAs($this->admin)
            ->post(route('questionnaire.store'), ['answers' => $this->answers(array_fill(0, 10, 5))]);

        $this->actingAs($this->admin)
            ->post(route('questionnaire.store'), ['answers' => $this->answers(array_fill(0, 10, 1))])
            ->assertRedirect(route('questionnaire.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, QuestionnaireResponse::count());
        $this->assertSame(50, $this->admin->fresh()->questionnaireResponse->answers->sum('score'));
    }

    public function test_scores_outside_one_to_five_are_rejected(): void
    {
        foreach ([0, 6, 'a', 3.5] as $invalid) {
            $scores = array_fill(0, 10, 3);
            $scores[4] = $invalid;
            $answers = $this->answers($scores);
            $invalidKey = 'answers.'.array_keys($answers)[4];

            $this->actingAs($this->admin)
                ->post(route('questionnaire.store'), ['answers' => $answers])
                ->assertSessionHasErrors($invalidKey);
        }

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_every_statement_must_be_answered(): void
    {
        $answers = $this->answers(array_fill(0, 10, 4));
        $missingId = array_key_last($answers);
        unset($answers[$missingId]);

        $this->actingAs($this->admin)
            ->post(route('questionnaire.store'), ['answers' => $answers])
            ->assertSessionHasErrors("answers.{$missingId}");

        $this->actingAs($this->admin)
            ->post(route('questionnaire.store'), [])
            ->assertSessionHasErrors('answers');

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_unknown_question_id_is_rejected(): void
    {
        $answers = $this->answers(array_fill(0, 10, 4)) + [9999 => 4];

        $this->actingAs($this->admin)
            ->post(route('questionnaire.store'), ['answers' => $answers])
            ->assertSessionHasErrors('answers');

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_results_page_shows_empty_state_without_respondents(): void
    {
        $summary = app(QuestionnaireScorer::class)->summarize();

        $this->assertSame(0, $summary['respondent_count']);
        $this->assertNull($summary['overall']['index']);
        $this->assertNull($summary['questions'][0]['index']);

        $this->actingAs($this->admin)
            ->get(route('questionnaire.results'))
            ->assertOk()
            ->assertSee('Belum ada pengguna yang mengisi kuesioner.');
    }

    public function test_index_and_interpretation_are_calculated_correctly(): void
    {
        // Skor tiap responden untuk pernyataan 1–10.
        $respondents = [
            'Responden A' => [5, 5, 1, 2, 2, 3, 4, 4, 3, 1],
            'Responden B' => [4, 5, 1, 2, 2, 3, 4, 5, 4, 2],
            'Responden C' => [3, 5, 1, 2, 3, 3, 4, 4, 4, 3],
        ];

        foreach ($respondents as $name => $scores) {
            $this->actingAs(User::factory()->create(['name' => $name]))
                ->post(route('questionnaire.store'), ['answers' => $this->answers($scores)])
                ->assertSessionHasNoErrors();
        }

        $summary = app(QuestionnaireScorer::class)->summarize();

        $this->assertSame(3, $summary['respondent_count']);

        // [total skor, indeks %, interpretasi] per pernyataan; maksimum per pernyataan = 5 x 3 = 15.
        $expected = [
            [12, 80.00, 'Baik'],              // 12/15 — tepat di batas 80 masih "Baik"
            [15, 100.00, 'Sangat Baik'],
            [3, 20.00, 'Sangat Tidak Baik'],  // tepat di batas 20
            [6, 40.00, 'Tidak Baik'],         // tepat di batas 40
            [7, 46.67, 'Cukup'],              // 7/15 = 46,666...
            [9, 60.00, 'Cukup'],              // tepat di batas 60
            [12, 80.00, 'Baik'],
            [13, 86.67, 'Sangat Baik'],
            [11, 73.33, 'Baik'],
            [6, 40.00, 'Tidak Baik'],
        ];

        foreach ($expected as $i => [$total, $index, $interpretation]) {
            $row = $summary['questions'][$i];
            $this->assertSame($i + 1, $row['question']->order);
            $this->assertSame($total, $row['total'], 'Total skor pernyataan '.($i + 1));
            $this->assertEqualsWithDelta($index, $row['index'], 0.0001, 'Indeks pernyataan '.($i + 1));
            $this->assertSame($interpretation, $row['interpretation'], 'Interpretasi pernyataan '.($i + 1));
        }

        // Pernyataan 1 dijawab 5, 4, 3 -> masing-masing satu jawaban di skala 3, 4, dan 5.
        $this->assertSame([1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 1], $summary['questions'][0]['distribution']);

        // Keseluruhan: 94 / (5 x 3 responden x 10 pernyataan) x 100 = 62,666... -> 62,67 (Baik).
        $this->assertSame(94, $summary['overall']['total']);
        $this->assertEqualsWithDelta(62.67, $summary['overall']['index'], 0.0001);
        $this->assertSame('Baik', $summary['overall']['interpretation']);

        $this->actingAs($this->admin)
            ->get(route('questionnaire.results'))
            ->assertOk()
            ->assertSee('62,67%')
            ->assertSee('Responden A')
            ->assertSee('Responden C');
    }

    public function test_interpretation_boundaries(): void
    {
        $cases = [
            [0, 'Sangat Tidak Baik'],
            [20, 'Sangat Tidak Baik'],
            [20.01, 'Tidak Baik'],
            [40, 'Tidak Baik'],
            [40.01, 'Cukup'],
            [60, 'Cukup'],
            [60.01, 'Baik'],
            [80, 'Baik'],
            [80.01, 'Sangat Baik'],
            [100, 'Sangat Baik'],
        ];

        foreach ($cases as [$index, $expected]) {
            $this->assertSame($expected, QuestionnaireScorer::interpret((float) $index), "Indeks {$index}");
        }
    }
}
