<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionnaireResponseRequest;
use App\Models\QuestionnaireQuestion;
use App\Services\QuestionnaireScorer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionnaireController extends Controller
{
    private const ALREADY_SUBMITTED = 'Anda sudah mengisi kuesioner. Jawaban yang sudah dikirim tidak dapat diubah.';

    /**
     * Tampilkan form kuesioner, atau jawaban (read-only) kalau pengguna sudah mengisi.
     */
    public function index(Request $request)
    {
        $questions = QuestionnaireQuestion::orderBy('order')->get();

        $response = $request->user()->questionnaireResponse()->with('answers')->first();
        $answers = $response?->answers->pluck('score', 'questionnaire_question_id') ?? collect();

        $scale = QuestionnaireScorer::SCALE;

        return view('questionnaire.index', compact('questions', 'response', 'answers', 'scale'));
    }

    /**
     * Simpan jawaban kuesioner. Setiap pengguna hanya boleh mengisi satu kali —
     * dicek di sini (bukan cuma disembunyikan di view) dan dijaga juga oleh
     * unique index user_id di tabel questionnaire_responses.
     */
    public function store(StoreQuestionnaireResponseRequest $request)
    {
        $user = $request->user();

        if ($user->questionnaireResponse()->exists()) {
            return redirect()->route('questionnaire.index')->with('error', self::ALREADY_SUBMITTED);
        }

        try {
            DB::transaction(function () use ($request, $user) {
                $response = $user->questionnaireResponse()->create(['submitted_at' => now()]);

                $response->answers()->createMany(
                    collect($request->validated('answers'))
                        ->map(fn ($score, $questionId) => [
                            'questionnaire_question_id' => (int) $questionId,
                            'score' => (int) $score,
                        ])
                        ->values()
                        ->all()
                );
            });
        } catch (UniqueConstraintViolationException) {
            return redirect()->route('questionnaire.index')->with('error', self::ALREADY_SUBMITTED);
        }

        return redirect()->route('questionnaire.index')
            ->with('success', 'Terima kasih, jawaban kuesioner Anda berhasil disimpan.');
    }

    /**
     * Tampilkan rekap hasil kuesioner seluruh responden.
     */
    public function results(QuestionnaireScorer $scorer)
    {
        $summary = $scorer->summarize();
        $scale = QuestionnaireScorer::SCALE;
        $interpretations = QuestionnaireScorer::INTERPRETATIONS;

        return view('questionnaire.results', compact('summary', 'scale', 'interpretations'));
    }
}
