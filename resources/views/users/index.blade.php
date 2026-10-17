<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display font-semibold text-xl text-denim leading-tight">
                {{ __('Data Pengguna') }}
            </h2>
            <a href="{{ route('users.create') }}">
                <x-primary-button>{{ __('Tambah Pengguna') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            <div class="bg-surface border border-denim/10 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-[640px] w-full text-sm">
                        <thead class="bg-denim text-white font-sans">
                            <tr class="text-left">
                                <th class="py-3.5 px-5 font-medium">Nama</th>
                                <th class="py-3.5 px-5 font-medium">Email</th>
                                <th class="py-3.5 px-5 font-medium">Tanggal Dibuat</th>
                                <th class="py-3.5 px-5 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="font-sans text-sm text-ink">
                            @forelse ($users as $user)
                                <tr class="odd:bg-cream even:bg-surface border-b border-denim/10 hover:bg-denim/5 transition-colors">
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex flex-wrap items-center gap-2">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))
                                                <span class="px-2 py-0.5 bg-denim/10 text-denim text-xs font-semibold rounded-md">Anda</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 font-mono whitespace-nowrap">{{ $user->email }}</td>
                                    <td class="py-3.5 px-5 font-mono text-ink/60 whitespace-nowrap">{{ $user->created_at?->translatedFormat('d M Y') }}</td>
                                    <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('users.edit', $user) }}" class="text-denim hover:text-denim-light hover:underline focus:outline-none focus-visible:underline transition-colors">Edit</a>
                                            @unless ($user->is(auth()->user()))
                                                <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm(@js('Hapus pengguna '.$user->name.'?'));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-brick hover:text-brick/80 hover:underline focus:outline-none focus-visible:underline transition-colors">Hapus</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-14 text-center text-ink/70">Belum ada data pengguna.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="px-5 py-3 border-t border-denim/10">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
