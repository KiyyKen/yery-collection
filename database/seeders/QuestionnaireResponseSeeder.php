<?php

namespace Database\Seeders;

use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class QuestionnaireResponseSeeder extends Seeder
{
    /**
     * Jawaban kuesioner tiap akun dari UserSeeder, berurutan untuk pernyataan 1–10.
     *
     * Hasil rekapnya: 4 responden, total skor 191, indeks keseluruhan 95,50% (Sangat Baik).
     * Tanggal mengisi ditulis tetap supaya setiap `migrate:fresh --seed` menghasilkan data yang sama.
     */
    private const RESPONSES = [
        ['email' => 'dafahervansyah10@gmail.com', 'submitted_at' => '2026-10-21 09:34:00', 'scores' => [4, 5, 5, 5, 5, 5, 5, 5, 5, 5]],
        ['email' => 'fathir2020@gmail.com', 'submitted_at' => '2026-10-25 14:20:00', 'scores' => [4, 4, 5, 4, 4, 5, 5, 5, 5, 5]],
        ['email' => 'ranggaadz@gmail.com', 'submitted_at' => '2026-10-26 10:48:00', 'scores' => [4, 4, 5, 5, 5, 5, 5, 5, 5, 5]],
        ['email' => 'yery123@gmail.com', 'submitted_at' => '2026-10-27 16:05:00', 'scores' => [5, 4, 4, 5, 5, 5, 5, 5, 5, 5]],
    ];

    /**
     * Membutuhkan UserSeeder dan QuestionnaireQuestionSeeder sudah dijalankan.
     * Aman dijalankan berulang: jawaban yang sudah ada diperbarui, tidak diduplikasi.
     */
    public function run(): void
    {
        $questionIds = QuestionnaireQuestion::orderBy('order')->pluck('id')->all();

        foreach (self::RESPONSES as $data) {
            $user = User::where('email', $data['email'])->first();

            if ($user === null) {
                continue;
            }

            $response = QuestionnaireResponse::updateOrCreate(
                ['user_id' => $user->id],
                ['submitted_at' => Carbon::parse($data['submitted_at'])],
            );

            foreach ($questionIds as $i => $questionId) {
                $response->answers()->updateOrCreate(
                    ['questionnaire_question_id' => $questionId],
                    ['score' => $data['scores'][$i]],
                );
            }
        }
    }
}
