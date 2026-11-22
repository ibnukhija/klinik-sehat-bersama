@extends('layouts.app')

@section('title', 'Kasir & Klaim Asuransi')
@section('header', 'Kasir & Pembayaran Asuransi')
@section('subheader', 'Daftar kunjungan selesai periksa yang siap ditagih, klaim asuransi, hingga penutupan transaksi')

@section('content')
    <form method="GET" action="{{ route('kasir.index') }}" class="flex flex-wrap items-center gap-2">
        <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">Semua Status Transaksi</option>
            @foreach(['Belum Dibuka', 'Terbuka', 'Menunggu Klaim', 'Perlu Penyesuaian', 'Ditutup'] as $st)
                <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm cursor-pointer">Filter</button>
        @if($statusFilter !== '')
            <a href="{{ route('kasir.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Tgl</th>
                        <th class="px-4 py-3">Pasien & Polis</th>
                        <th class="px-4 py-3">Dokter</th>
                        <th class="px-4 py-3">Status Resep</th>
                        <th class="px-4 py-3 text-right">Total Tagihan</th>
                        <th class="px-4 py-3">Status Transaksi</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kunjunganSelesai as $k)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-600">{{ $k->tgl_kunjungan->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $k->pasien->nama }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $k->pasien->polisAsuransi->where('status_aktif', true)->count() }} polis aktif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $k->dokter->nama }}</td>
                            <td class="px-4 py-3">
                                @if($k->resep)
                                    <x-status-badge :status="$k->resep->status" />
                                @else
                                    <span class="text-xs text-slate-400">Tanpa Resep</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                {{ $k->pembayaran ? 'Rp ' . number_format((float) $k->pembayaran->total_biaya, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-status-badge :status="$k->pembayaran?->status_transaksi ?? 'Belum Dibuka'" />
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('kasir.show', $k) }}"
                                   class="inline-block whitespace-nowrap px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition">
                                    Proses Kasir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada kunjungan selesai untuk diproses kasir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $kunjunganSelesai->links() }}
        </div>
    </div>
@endsection
