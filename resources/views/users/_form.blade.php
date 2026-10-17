<div>
    <x-input-label for="name" value="Nama" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
        :value="old('name', $user->name ?? '')" required autofocus autocomplete="name" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="email" value="Email" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full font-mono"
        :value="old('email', $user->email ?? '')" required autocomplete="off" />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="password" value="Password" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
            :required="! isset($user)" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Konfirmasi Password" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full"
            :required="! isset($user)" autocomplete="new-password" />
    </div>
</div>

<p class="mt-2 font-sans text-xs text-ink/50">
    @isset($user)
        Kosongkan password jika tidak ingin mengubahnya. Jika diisi, minimal 8 karakter.
    @else
        Minimal 8 karakter.
    @endisset
</p>
