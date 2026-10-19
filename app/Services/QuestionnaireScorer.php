<?php

namespace App\Services;

use App\Models\QuestionnaireAnswer;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;

/**
 * Rekap hasil kuesioner kepuasan pengguna berskala Likert 1–5.
 *
 * Indeks per pernyataan  = total skor / (skor maksimum × jumlah responden) × 100
 * Indeks keseluruhan     = total skor semua / (skor maksimum × jumlah responden × jumlah pertanyaan) × 100
 */
class QuestionnaireScorer
{
    public const MAX_SCORE = 5;

    /**
     * Label skala jawaban, dari skor 1 sampai 5.
     */
    public const SCALE = [
        1 => 'Sangat Tidak Setuju',
        2 => 'Tidak Setuju',
        3 => 'Netral',
        4 => 'Setuju',
        5 => 'Sangat Setuju',
    ];

    /**
     * Rentang interpretasi indeks (%): batas bawah inklusif => kategori.
     * 0 ≤ x < 20 Sangat Tidak Baik, 20 ≤ x < 40 Kurang Baik, 40 ≤ x < 60 Netral,
     * 60 ≤ x < 80 Baik, 80 ≤ x ≤ 100 Sangat Baik.
     */
    public const INTERPRETATIONS = [
        0 => 'Sangat Tidak Baik',
        20 => 'Kurang Baik',
        40 => 'Netral',
        60 => 'Baik',
        80 => 'Sangat Baik',
    ];

    /**
     * Hitung rekap seluruh kuesioner.
     *
     * Kalau belum ada responden, indeks bernilai null (tidak ada pembagian dengan nol).
     *
     * @return array{
     *     respondent_count: int,
     *     respondents: \Illuminate\Support\Collection<int, QuestionnaireResponse>,
     *     questions: array<int, array{question: QuestionnaireQuestion, distribution: array<int, int>, total: int, index: ?float, interpretation: ?string}>,
     *     overall: array{total: int, index: ?float, interpretation: ?string},
     * }
     */
    public function summarize(): array
    {
        $questions = QuestionnaireQuestion::orderBy('order')->get();
        $respondents = QuestionnaireResponse::with('user')->orderBy('submitted_at')->get();
        $respondentCount = $respondents->count();

        $answersByQuestion = QuestionnaireAnswer::get(['questionnaire_question_id', 'score'])
            ->groupBy('questionnaire_question_id');

        $rows = [];
        $grandTotal = 0;

        foreach ($questions as $question) {
            $scores = $answersByQuestion->get($question->id, collect())->pluck('score');

            $distribution = [];
            foreach (array_keys(self::SCALE) as $score) {
                $distribution[$score] = $scores->filter(fn (int $value) => $value === $score)->count();
            }

            $total = (int) $scores->sum();
            $grandTotal += $total;

            $index = $this->index($total, self::MAX_SCORE * $respondentCount);

            $rows[] = [
                'question' => $question,
                'distribution' => $distribution,
                'total' => $total,
                'index' => $index,
                'interpretation' => $index === null ? null : self::interpret($index),
            ];
        }

        $overallIndex = $this->index($grandTotal, self::MAX_SCORE * $respondentCount * $questions->count());

        return [
            'respondent_count' => $respondentCount,
            'respondents' => $respondents,
            'questions' => $rows,
            'overall' => [
                'total' => $grandTotal,
                'index' => $overallIndex,
                'interpretation' => $overallIndex === null ? null : self::interpret($overallIndex),
            ],
        ];
    }

    /**
     * Tentukan kategori interpretasi dari indeks persentase (0–100).
     */
    public static function interpret(float $index): string
    {
        $result = self::INTERPRETATIONS[array_key_first(self::INTERPRETATIONS)];

        foreach (self::INTERPRETATIONS as $lowerBound => $label) {
            if ($index >= $lowerBound) {
                $result = $label;
            }
        }

        return $result;
    }

    /**
     * Persentase skor terhadap skor maksimum, dibulatkan 2 desimal.
     * Interpretasi memakai nilai yang sudah dibulatkan, supaya kategori selalu
     * konsisten dengan angka yang tampil di layar.
     */
    private function index(int $total, int $maxTotal): ?float
    {
        if ($maxTotal === 0) {
            return null;
        }

        return round($total / $maxTotal * 100, 2);
    }
}
