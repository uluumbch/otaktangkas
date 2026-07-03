<div class="flex min-h-full items-center justify-center px-4 py-12">
    <div class="w-full max-w-md space-y-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-primary-600">OtakTangkas</h1>
            <p class="mt-2 text-sm text-gray-600">Buat akun untuk mulai bermain.</p>
        </div>

        <form wire:submit="register" class="space-y-6 rounded-2xl bg-white p-8 shadow-xs">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
                <input wire:model="name" id="name" type="text" autocomplete="name"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-primary-500 focus:ring-primary-500">
                @error('name') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input wire:model="email" id="email" type="email" autocomplete="email"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-primary-500 focus:ring-primary-500">
                @error('email') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Kata Sandi</label>
                <input wire:model="password" id="password" type="password" autocomplete="new-password"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-primary-500 focus:ring-primary-500">
                @error('password') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-primary-500 focus:ring-primary-500">
            </div>

            <button type="submit"
                    class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 font-semibold text-white transition hover:bg-primary-700 focus:outline-hidden focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                Daftar
            </button>

            <p class="text-center text-sm text-gray-600">
                Sudah punya akun?
                <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary-600 hover:text-primary-500">Masuk</a>
            </p>
        </form>
    </div>
</div>
