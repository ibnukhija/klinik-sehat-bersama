@extends('layouts.app')

@section('title', 'Dashboard - Klinik Sehat Bersama')
@section('header', 'Dashboard Operasional Klinik')
@section('subheader', 'Ringkasan kunjungan, kehadiran dokter, dan status transaksi hari ini')

@section('content')
    {{-- Alerts --}}
    @if($dokterAbsen->isNotEmpty())
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-amber-900">
                <span class="font-semibold">Perhatian Kehadiran Dokter Hari Ini:</span>
                {{ $dokterAbsen->pluck('dokter.nama')->join(', ') }} berstatus <strong>Tidak Hadir</strong>.
            </div>
            @if($user->isRole('admin'))
                <a href="{{ route('kehadiran.index') }}" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-amber-600 text-white hover:bg-amber-700">
                    Kelola Pemindahan Pasien
                </a>
            @endif
        </div>
    @endif

    @if($pembayaranPerluPenyesuaian->isNotEmpty())
        <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-rose-900">
                <span class="font-semibold">Transaksi Perlu Penyesuaian ({{ $pembayaranPerluPenyesuaian->count() }}):</span>
                Dokter mengubah diagnosis/tindakan setelah tagihan dibuat.
            </div>
            @if($user->isRole('kasir'))
                <a href="{{ route('kasir.index', ['status' => 'Perlu Penyesuaian']) }}" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                    Tinjau di Kasir
                </a>
            @endif
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Terdaftar</div>
            <div class="text-2xl font-bold text-sky-600 mt-1">{{ $stats['terdaftar'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Menunggu Pengganti</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['menunggu'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Sedang Diperiksa</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['diperiksa'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Selesai Periksa</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['selesai'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Dibatalkan</div>
            <div class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['dibatalkan'] }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Antrian Hari Ini --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Kunjungan Hari Ini</h2>
                @if($user->isRole('admin'))
                    <a href="{{ route('kunjungan.create') }}" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                        + Daftarkan Pasien
                    </a>
                @elseif($user->isRole('dokter'))
                    <a href="{{ route('pemeriksaan.index') }}" class="text-xs font-medium text-emerald-700 hover:underline">Buka Workspace Pemeriksaan &rarr;</a>
                @elseif($user->isRole('kasir'))
                    <a href="{{ route('kasir.index') }}" class="text-xs font-medium text-emerald-700 hover:underline">Buka Antrian Kasir &rarr;</a>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Antrian</th>
                            <th class="px-4 py-3">Pasien</th>
                            <th class="px-4 py-3">Dokter</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($kunjunganHariIni as $k)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-700">#{{ $k->no_antrian }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $k->pasien->nama }}</div>
                                    <div class="text-xs text-slate-500">{{ $k->pasien->no_identitas }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $k->dokter->nama }}</td>
                                <td class="px-4 py-3">
                                    <x-status-badge :status="$k->status" />
                                    @if($k->pembayaran)
                                        <x-status-badge :status="$k->pembayaran->status_transaksi" class="ml-1" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if($user->isRole('dokter') && in_array($k->status, ['Terdaftar', 'Diperiksa', 'Selesai']))
                                            <a href="{{ route('pemeriksaan.show', $k) }}"
                                               class="px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-xs font-medium text-white transition">
                                                Periksa
                                            </a>
                                        @elseif($user->isRole('kasir') && $k->status === 'Selesai')
                                            <a href="{{ route('kasir.show', $k) }}"
                                               class="px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-xs font-medium text-white transition">
                                                Kasir
                                            </a>
                                        @endif
                                        <a href="{{ route('kunjungan.show', $k) }}"
                                           class="px-2.5 py-1 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition">
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada kunjungan terdaftar hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Jadwal & Kehadiran Dokter --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Jadwal Dokter Hari Ini</h2>
                @if($user->isRole('admin'))
                    <a href="{{ route('kehadiran.index') }}" class="text-xs text-emerald-700 hover:underline">Ubah Status</a>
                @endif
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($jadwalHariIni as $dokter)
                    @php
                        $hadirStatus = $dokter->kehadiran->first()?->status ?? 'Hadir';
                        $jadwal = $dokter->jadwalPraktik->first();
                    @endphp
                    <div class="px-5 py-3.5 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-medium text-slate-900">{{ $dokter->nama }}</div>
                            <div class="text-xs text-slate-500">
                                {{ $dokter->spesialisasi }}
                                @if($jadwal)
                                    &bull; {{ substr($jadwal->jam_mulai, 0, 5) }}–{{ substr($jadwal->jam_selesai, 0, 5) }}
                                @else
                                    &bull; Tidak berjadwal
                                @endif
                            </div>
                        </div>
                        <x-status-badge :status="$hadirStatus" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($user->isRole('kasir') && $antrianKasir->isNotEmpty())
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="font-semibold text-slate-800">Siap Diproses Kasir (Status Kunjungan Selesai)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Pasien</th>
                            <th class="px-4 py-3">Dokter</th>
                            <th class="px-4 py-3">Resep</th>
                            <th class="px-4 py-3">Status Transaksi</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($antrianKasir as $ks)
                            <tr>
                                <td class="px-4 py-3 text-slate-600">{{ $ks->tgl_kunjungan->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $ks->pasien->nama }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $ks->dokter->nama }}</td>
                                <td class="px-4 py-3">
                                    @if($ks->resep)
                                        <x-status-badge :status="$ks->resep->status" />
                                    @else
                                        <span class="text-xs text-slate-400">Tanpa Resep</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-badge :status="$ks->pembayaran?->status_transaksi ?? 'Belum Dibuka'" />
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('kasir.show', $ks) }}" class="inline-block whitespace-nowrap text-xs font-medium px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                        Buka Pembayaran
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
