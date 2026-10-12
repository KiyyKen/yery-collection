<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-denim leading-tight">
            {{ __('Tambah Distribusi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                <form method="POST" action="{{ route('distributions.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="product_id" value="Produk" />
                        <select id="product_id" name="product_id" required
                            class="mt-1 block w-full border-denim/20 focus:border-denim focus:ring-2 focus:ring-denim/20 rounded-lg font-sans text-ink px-3.5 py-2.5 transition-shadow">
                            <option value="">-- Pilih Produk --</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                    {{ $product->name }} (Stok: {{ $product->stock }} {{ $product->unit }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="distribution_date" value="Tanggal Distribusi" />
                        <x-text-input id="distribution_date" name="distribution_date" type="date" class="mt-1 block w-full font-mono"
                            :value="old('distribution_date', now()->toDateString())" :max="now()->toDateString()" required />
                        <x-input-error :messages="$errors->get('distribution_date')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="quantity" value="Jumlah" />
                        <x-text-input id="quantity" name="quantity" type="number" min="1" class="mt-1 block w-full font-mono"
                            :value="old('quantity')" required />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="notes" value="Catatan (opsional)" />
                        <textarea id="notes" name="notes" rows="3" placeholder="Opsional, misal keterangan pengiriman"
                            class="mt-1 block w-full border-denim/20 focus:border-denim focus:ring-2 focus:ring-denim/20 rounded-lg font-sans text-ink placeholder:text-ink/40 px-3.5 py-2.5 transition-shadow">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-6">
                        <a href="{{ route('distributions.index') }}" class="font-sans text-sm text-ink/60 hover:text-denim hover:underline focus:outline-none focus-visible:underline transition-colors">Batal</a>
                        <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
