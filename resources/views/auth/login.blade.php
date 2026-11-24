@extends('layouts.app')

@section('title', 'Masuk - Klinik Sehat Bersama')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-900 via-slate-900 to-slate-950 p-6">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-10 w-10 rounded-xl bg-emerald-600 text-white font-bold flex items-center justify-center text-xl">K</span>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Klinik Sehat Bersama</h1>
                <p class="text-xs text-slate-500">Sistem Informasi Pelayanan & Klaim Asuransi</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label for="identity-input" class="block text-xs font-medium text-slate-700 mb-1">Username atau Email</label>
                <input id="identity-input" type="text" name="identity" value="{{ old('identity') }}" required autofocus
                       placeholder="Masukkan username atau email"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
            </div>
            <div>
                <label for="password-input" class="block text-xs font-medium text-slate-700 mb-1">Password</label>
                <div class="relative">
                    <input id="password-input" type="password" name="password" required placeholder="••••••••"
                           class="w-full rounded-lg border border-slate-300 pl-3 pr-10 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <button type="button" id="toggle-password" aria-label="Tampilkan atau sembunyikan password"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg id="eye-off-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit"
                    class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 text-sm transition cursor-pointer">
                Masuk ke Sistem
            </button>
        </form>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById('password-input');
        const toggle = document.getElementById('toggle-password');
        const eye = document.getElementById('eye-icon');
        const eyeOff = document.getElementById('eye-off-icon');
        if (!input || !toggle || !eye || !eyeOff) return;

        toggle.addEventListener('click', function () {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            eye.classList.toggle('hidden', show);
            eyeOff.classList.toggle('hidden', !show);
        });
    })();
</script>
@endsection
