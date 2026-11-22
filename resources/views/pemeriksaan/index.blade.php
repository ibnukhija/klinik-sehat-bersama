@extends('layouts.app')

@section('title', 'Antrian & Pemeriksaan Dokter')
@section('header', 'Antrian Pemeriksaan — ' . $dokter->nama)
@section('subheader', 'Daftar pasien terdaftar dan riwayat pemeriksaan untuk ' . $dokter->spesialisasi)

@section('content')
    <form method="GET" action="{{ route('pemeriksaan.index') }}" class="flex items-center gap-2">
        <input type="date" name="tanggal" value="{{ $tanggal }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm cursor-pointer">Filter Tanggal</button>
        @if($tanggal !== '')
            <a href="{{ route('pemeriksaan.index') }}" class="text-xs text-slate-500 hover:underline">Tampilkan Semua</a>
        @endif
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Tgl & Antrian</th>
                        <th class="px-4 py-3">Pasien</th>
                        <th class="px-4 py-3">Diagnosis</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Pembayaran</th>
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
                            <td class="px-4 py-3 text-xs text-slate-700">
                                {{ $k->detailDiagnosis->pluck('nama_diagnosis')->join(', ') ?: '-' }}
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
                                <a href="{{ route('pemeriksaan.show', $k) }}"
                                   class="inline-block whitespace-nowrap px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition">
                                    {{ $k->status === 'Selesai' ? 'Lihat / Ubah Diagnosis' : 'Periksa Pasien' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Tidak ada kunjungan untuk dokter ini.</td>
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
