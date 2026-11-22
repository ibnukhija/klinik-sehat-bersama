@extends('layouts.app')

@section('title', 'Master Data Klinik')
@section('header', 'Master Data Klinik')
@section('subheader', 'Daftar dokter & jadwal praktik, tarif tindakan medis, stok obat, serta perusahaan asuransi')

@section('content')
    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Dokter & Jadwal --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 font-semibold text-slate-800">Dokter & Jadwal Praktik</div>
            <div class="divide-y divide-slate-100">
                @foreach($dokterList as $dokter)
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $dokter->nama }}</div>
                                <div class="text-xs text-slate-500">{{ $dokter->spesialisasi }}</div>
                            </div>
                            <div class="text-xs font-medium text-emerald-700">
                                Tarif: Rp {{ number_format((float) $dokter->tarif_pemeriksaan, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($dokter->jadwalPraktik as $j)
                                <span class="text-[11px] px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                    {{ $j->hari }} ({{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }})
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tindakan & Asuransi --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 font-semibold text-slate-800">Master Tindakan Medis</div>
                <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-2.5">Kode</th>
                            <th class="px-4 py-2.5">Nama Tindakan</th>
                            <th class="px-4 py-2.5 text-right">Tarif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tindakanList as $t)
                            <tr>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $t->kode_tindakan }}</td>
                                <td class="px-4 py-2.5 text-slate-800">{{ $t->nama_tindakan }}</td>
                                <td class="px-4 py-2.5 text-right font-medium">Rp {{ number_format((float) $t->tarif, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 font-semibold text-slate-800">Perusahaan Asuransi Rekanan</div>
                <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-2.5">Kode</th>
                            <th class="px-4 py-2.5">Nama Perusahaan</th>
                            <th class="px-4 py-2.5 text-right">Jumlah Polis</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($asuransiList as $a)
                            <tr>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $a->kode }}</td>
                                <td class="px-4 py-2.5 text-slate-800">{{ $a->nama_perusahaan }}</td>
                                <td class="px-4 py-2.5 text-right">{{ $a->polis_asuransi_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Obat & Stok --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div class="font-semibold text-slate-800">Master Obat & Kontrol Stok</div>
            <div class="text-xs text-slate-500">Obat dengan stok 0 tetap bisa diresepkan dokter dengan peringatan</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama Obat</th>
                        <th class="px-4 py-3 text-right">Harga Satuan</th>
                        <th class="px-4 py-3">Stok</th>
                        @if(auth()->user()->isRole('admin'))
                            <th class="px-4 py-3 text-right">Sesuaikan Stok</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($obatList as $o)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $o->kode_obat }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $o->nama_obat }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format((float) $o->harga, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold {{ $o->stok <= 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ $o->stok }}</span>
                                <x-status-badge :status="$o->stok > 0 ? 'Tersedia' : 'Habis'" class="ml-1" />
                            </td>
                            @if(auth()->user()->isRole('admin'))
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('master.obat.stok', $o) }}" class="inline-flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="stok" value="{{ $o->stok }}" min="0"
                                               class="w-20 rounded-md border border-slate-300 px-2 py-1 text-xs text-right">
                                        <button type="submit" class="px-2.5 py-1 rounded-md bg-slate-800 hover:bg-slate-900 text-white text-xs cursor-pointer">
                                            Simpan
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
