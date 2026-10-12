<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDistributionRequest extends FormRequest
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
     * Produk tidak bisa diganti saat edit agar perhitungan stok tidak ambigu.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'distribution_date' => ['required', 'date', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Pesan validasi khusus.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'distribution_date.before_or_equal' => 'Tanggal distribusi tidak boleh melebihi tanggal hari ini.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $distribution = $this->route('distribution');

            if (! $distribution) {
                return;
            }

            $availableStock = $distribution->product->stock + $distribution->quantity;

            if ($this->quantity > $availableStock) {
                $validator->errors()->add(
                    'quantity',
                    "Jumlah distribusi melebihi stok produk yang tersedia ({$availableStock})."
                );
            }
        });
    }
}
