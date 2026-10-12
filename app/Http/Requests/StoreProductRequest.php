<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            // Unique index di database juga mencakup produk yang sudah di-soft-delete,
            // jadi validasinya ikut memeriksa semua baris (tanpa filter deleted_at).
            'code' => ['required', 'string', 'max:50', Rule::unique('products', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'size' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
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
            'code.unique' => 'Kode produk sudah pernah dipakai, termasuk oleh produk yang sudah dihapus. Gunakan kode lain.',
        ];
    }
}
