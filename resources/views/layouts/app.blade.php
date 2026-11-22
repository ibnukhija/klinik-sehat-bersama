<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Klinik Sehat Bersama')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased">
    @auth
        @php
            $fallbackBack = trim($__env->yieldContent('back_url')) ?: route('dashboard');
            $prevUrl = url()->previous();
            $prevPath = parse_url($prevUrl, PHP_URL_PATH) ?: '';
            $currPath = '/' . ltrim(request()->path(), '/');
            $prevHost = parse_url($prevUrl, PHP_URL_HOST);
            $sameHost = ! $prevHost || $prevHost === request()->getHost();
            $isValidPrev = $sameHost
                && $prevPath !== ''
                && $prevPath !== $currPath
                && ! in_array($prevPath, ['/login', '/quick-login', '/logout'], true);
            $backUrl = $isValidPrev ? $prevUrl : $fallbackBack;
        @endphp

        <div class="min-h-screen flex flex-col lg:flex-row">
            {{-- Mobile Top Bar --}}
            <div class="lg:hidden bg-slate-900 text-slate-100 px-4 py-3 flex items-center justify-between border-b border-slate-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <span class="h-8 w-8 rounded-lg bg-emerald-500 text-white font-bold flex items-center justify-center">K</span>
                    <div>
                        <div class="font-semibold text-sm leading-tight">Klinik Sehat Bersama</div>
                        <div class="text-[11px] text-emerald-400">{{ auth()->user()->roleLabel() }}</div>
                    </div>
                </a>
                <button type="button" id="mobile-menu-toggle" aria-expanded="false" aria-controls="app-sidebar"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-100 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Menu
                </button>
            </div>

            {{-- Sidebar (Collapsible on mobile, fixed on lg+) --}}
            <aside id="app-sidebar" class="hidden lg:flex w-full lg:w-64 bg-slate-900 text-slate-100 flex-shrink-0 flex-col">
                <div class="hidden lg:block px-5 py-5 border-b border-slate-800">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <span class="h-9 w-9 rounded-lg bg-emerald-500 text-white font-bold flex items-center justify-center text-lg">K</span>
                        <div>
                            <div class="font-semibold text-sm leading-tight">Klinik Sehat Bersama</div>
                            <div class="text-xs text-slate-400">Sistem Informasi Klinik</div>
                        </div>
                    </a>
                </div>

                <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                    <a href="{{ route('dashboard') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        Dashboard
                    </a>

                    @if(auth()->user()->isRole('admin'))
                        <div class="pt-3 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administrasi</div>
                        <a href="{{ route('kunjungan.create') }}"
                           class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('kunjungan.create') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                            Pendaftaran Pasien
                        </a>
                        <a href="{{ route('kehadiran.index') }}"
                           class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('kehadiran.*') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                            Kehadiran & Pemindahan Dokter
                        </a>
                    @endif

                    @if(auth()->user()->isRole('dokter'))
                        <div class="pt-3 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Poliklinik Dokter</div>
                        <a href="{{ route('pemeriksaan.index') }}"
                           class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('pemeriksaan.*') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                            Antrian & Pemeriksaan
                        </a>
                    @endif

                    @if(auth()->user()->isRole('kasir'))
                        <div class="pt-3 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Kasir & Asuransi</div>
                        <a href="{{ route('kasir.index') }}"
                           class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('kasir.*') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                            Pembayaran & Klaim Asuransi
                        </a>
                    @endif

                    <div class="pt-3 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Data & Riwayat</div>
                    <a href="{{ route('kunjungan.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('kunjungan.index', 'kunjungan.show') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        Daftar Kunjungan
                    </a>
                    <a href="{{ route('pasien.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('pasien.*') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        Data Pasien & Polis
                    </a>
                    <a href="{{ route('master.index') }}"
                       class="block px-3 py-2 rounded-lg transition {{ request()->routeIs('master.*') ? 'bg-emerald-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        Master Dokter, Tindakan & Obat
                    </a>
                </nav>

                <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                    <div class="text-xs font-medium text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-emerald-400 mb-3">{{ auth()->user()->roleLabel() }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-xs px-3 py-1.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-200 transition cursor-pointer">
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main Content --}}
            <main class="flex-1 flex flex-col min-w-0">
                <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-start sm:items-center gap-3 min-w-0">
                        @hasSection('back_url')
                            <a href="{{ $backUrl }}"
                               aria-label="Kembali ke halaman sebelumnya"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 flex-shrink-0 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Kembali
                            </a>
                        @endif
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-xl font-semibold text-slate-900 truncate">@yield('header', 'Dashboard')</h1>
                            @hasSection('subheader')
                                <p class="text-xs text-slate-500 mt-0.5">@yield('subheader')</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span>{{ \App\Models\Dokter::hariIndonesia(now()) }}, {{ now()->format('d M Y') }}</span>
                    </div>
                </header>

                <div class="p-4 sm:p-6 flex-1 space-y-5">
                    @if(session('success'))
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('warning'))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ session('warning') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            <div class="font-semibold mb-1">Periksa kembali input Anda:</div>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>

        <script>
            (function () {
                const btn = document.getElementById('mobile-menu-toggle');
                const sidebar = document.getElementById('app-sidebar');
                if (!btn || !sidebar) return;
                btn.addEventListener('click', function () {
                    const isHidden = sidebar.classList.contains('hidden');
                    sidebar.classList.toggle('hidden', !isHidden);
                    sidebar.classList.toggle('flex', isHidden);
                    btn.setAttribute('aria-expanded', String(isHidden));
                });
            })();
        </script>
    @else
        @yield('content')
    @endauth
</body>
</html>
