@extends('layouts.app')

@section('title', 'Data Pasien & Polis Asuransi')
@section('header', 'Data Pasien & Polis Asuransi')
@section('subheader', 'Kelola identitas pasien dan kepemilikan polis asuransi (mendukung multi-polis dengan prioritas)')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('pasien.index') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Identitas atau Nama..."
                   class="w-full sm:w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm cursor-pointer">Cari</button>
            @if($search !== '')
                <a href="{{ route('pasien.index') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        @if(auth()->user()->isRole('admin'))
            <a href="{{ route('pasien.create') }}" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
                + Pasien Baru
            </a>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">No. Identitas</th>
                        <th class="px-4 py-3">Nama Pasien</th>
                        <th class="px-4 py-3">Tgl Lahir & Telp</th>
                        <th class="px-4 py-3">Polis Asuransi</th>
                        <th class="px-4 py-3 text-center">Kunjungan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pasienList as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $p->no_identitas }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $p->nama }}</div>
                                <div class="text-xs text-slate-500 truncate max-w-xs">{{ $p->alamat }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                <div>{{ $p->tgl_lahir->format('d/m/Y') }}</div>
                                <div>{{ $p->no_telp }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @forelse($p->polisAsuransi as $pol)
                                    <div class="flex flex-wrap items-center gap-1.5 text-xs mb-1 last:mb-0">
                                        <span><span class="font-semibold text-slate-700">P{{ $pol->prioritas }}:</span> {{ $pol->asuransi->nama_perusahaan }} ({{ $pol->no_polis }})</span>
                                        <x-status-badge :status="$pol->status_aktif ? 'Aktif' : 'Nonaktif'" />
                                    </div>
                                @empty
                                    <span class="text-xs text-slate-400">Umum / Tunai</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ $p->kunjungan_count }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <a href="{{ route('pasien.show', $p) }}"
                                       class="px-2.5 py-1 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition">
                                        Detail & Polis
                                    </a>
                                    @if(auth()->user()->isRole('admin'))
                                        <a href="{{ route('kunjungan.create', ['no_identitas' => $p->no_identitas]) }}"
                                           class="px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-xs font-medium text-white transition">
                                            Daftarkan
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Data pasien tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $pasienList->links() }}
        </div>
    </div>
@endsection
