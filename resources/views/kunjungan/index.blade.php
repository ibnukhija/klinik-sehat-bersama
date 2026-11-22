@extends('layouts.app')

@section('title', 'Daftar Kunjungan')
@section('header', 'Daftar Kunjungan Pasien')
@section('subheader', 'Seluruh riwayat dan antrian kunjungan dari pendaftaran hingga transaksi kasir')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('kunjungan.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                @foreach(['Terdaftar', 'Menunggu', 'Diperiksa', 'Selesai', 'Dibatalkan'] as $st)
                    <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
                @endforeach
            </select>
            <select name="dokter_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">Semua Dokter</option>
                @foreach($dokterList as $d)
                    <option value="{{ $d->id }}" @selected($dokterId == $d->id)>{{ $d->nama }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm cursor-pointer">Filter</button>
            @if($tanggal !== '' || $status !== '' || $dokterId !== '')
                <a href="{{ route('kunjungan.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        @if(auth()->user()->isRole('admin'))
            <a href="{{ route('kunjungan.create') }}" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
                + Daftarkan Kunjungan
            </a>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Tgl & Antrian</th>
                        <th class="px-4 py-3">Pasien</th>
                        <th class="px-4 py-3">Dokter</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Transaksi</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kunjunganList as $k)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">#{{ $k->no_antrian }}</div>
                                <div class="text-xs text-slate-500">{{ $k->tgl_kunjungan->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $k->pasien->nama }}</div>
                                <div class="text-xs text-slate-500">{{ $k->pasien->no_identitas }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $k->dokter->nama }}</div>
                                @if($k->pemindahanDokter->isNotEmpty())
                                    <div class="text-[11px] text-amber-700">Pernah dipindah ({{ $k->pemindahanDokter->count() }}x)</div>
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-status-badge :status="$k->status" /></td>
                            <td class="px-4 py-3">
                                @if($k->pembayaran)
                                    <x-status-badge :status="$k->pembayaran->status_transaksi" />
                                @else
                                    <span class="text-xs text-slate-400">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    @if(auth()->user()->isRole('dokter') && auth()->user()->dokter?->id === $k->dokter_id && $k->status !== 'Dibatalkan')
                                        <a href="{{ route('pemeriksaan.show', $k) }}"
                                           class="px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-xs font-medium text-white transition">
                                            Periksa
                                        </a>
                                    @endif
                                    @if(auth()->user()->isRole('kasir') && $k->status === 'Selesai')
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
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Tidak ada kunjungan yang sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $kunjunganList->links() }}
        </div>
    </div>
@endsection
