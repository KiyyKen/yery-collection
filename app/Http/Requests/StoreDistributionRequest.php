<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistributionRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
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
            $product = Product::find($this->product_id);

            if ($product && $this->quantity > $product->stock) {
                $validator->errors()->add(
                    'quantity',
                    "Jumlah distribusi melebihi stok produk yang tersedia ({$product->stock})."
                );
            }
        });
    }
}
