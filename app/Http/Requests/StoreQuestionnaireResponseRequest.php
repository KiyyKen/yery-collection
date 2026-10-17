<?php

namespace App\Http\Requests;

use App\Models\QuestionnaireQuestion;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionnaireResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Bentuk input: answers[<id pertanyaan>] = skor 1–5.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'between:1,5'],
        ];
    }

    /**
     * Pastikan setiap pertanyaan dijawab tepat satu kali dan tidak ada id pertanyaan
     * yang tidak dikenal.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $answers = $this->input('answers');

            if (! is_array($answers)) {
                return;
            }

            $questionIds = QuestionnaireQuestion::pluck('id')->map(fn ($id) => (string) $id);
            $answeredIds = collect(array_keys($answers))->map(fn ($id) => (string) $id);

            foreach ($questionIds->diff($answeredIds) as $missingId) {
                $validator->errors()->add("answers.{$missingId}", 'Pernyataan ini wajib dijawab.');
            }

            if ($answeredIds->diff($questionIds)->isNotEmpty()) {
                $validator->errors()->add('answers', 'Terdapat jawaban untuk pernyataan yang tidak valid.');
            }
        });
    }

    /**
     * Pesan validasi khusus.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Semua pernyataan wajib dijawab.',
            'answers.*.required' => 'Pernyataan ini wajib dijawab.',
            'answers.*.integer' => 'Nilai jawaban harus berupa angka 1 sampai 5.',
            'answers.*.between' => 'Nilai jawaban harus berupa angka 1 sampai 5.',
        ];
    }
}
