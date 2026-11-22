@extends('layouts.app')

@section('title', 'Kehadiran & Pemindahan Dokter')
@section('header', 'Kehadiran & Pemindahan Dokter')
@section('subheader', 'Ubah status kehadiran dokter dan pindahkan pasien terdaftar ke dokter pengganti yang berjadwal')

@section('content')
    <form method="GET" action="{{ route('kehadiran.index') }}" class="flex flex-wrap items-center gap-3">
        <label class="text-xs font-medium text-slate-600">Pilih Tanggal:</label>
        <input type="date" name="tanggal" value="{{ $tanggal }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm cursor-pointer">Tampilkan ({{ $hari }})</button>
    </form>

    <div class="space-y-4">
        @foreach($dokterList as $dokter)
            @php
                $hadir = $dokter->kehadiran->first()?->status ?? 'Hadir';
                $jadwal = $dokter->jadwalPraktik->first();
                $penggantiLain = $dokterTersedia->where('id', '!=', $dokter->id);
            @endphp
            <div class="bg-white rounded-xl border {{ $hadir === 'Tidak Hadir' ? 'border-rose-300' : 'border-slate-200' }} p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold text-slate-900 text-base">{{ $dokter->nama }}</h2>
                            <x-status-badge :status="$hadir" />
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $dokter->spesialisasi }}
                            @if($jadwal)
                                &bull; Jadwal {{ $hari }}: {{ substr($jadwal->jam_mulai, 0, 5) }}–{{ substr($jadwal->jam_selesai, 0, 5) }}
                            @else
                                &bull; Tidak berjadwal hari {{ $hari }}
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('kehadiran.update', $dokter) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <input type="text" name="catatan" placeholder="Catatan (opsional)"
                               value="{{ $dokter->kehadiran->first()?->catatan }}"
                               class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">
                        @if($hadir === 'Hadir')
                            <input type="hidden" name="status" value="Tidak Hadir">
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-medium cursor-pointer">
                                Tandai Tidak Hadir
                            </button>
                        @else
                            <input type="hidden" name="status" value="Hadir">
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium cursor-pointer">
                                Tandai Hadir Kembali
                            </button>
                        @endif
                    </form>
                </div>

                @if($dokter->kunjungan->isNotEmpty())
                    <div class="rounded-lg border {{ $hadir === 'Tidak Hadir' ? 'border-amber-200 bg-amber-50/70' : 'border-slate-200 bg-slate-50' }} p-4 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-xs font-semibold text-slate-800">
                                Pasien Terdaftar / Menunggu pada {{ $dokter->nama }} ({{ $dokter->kunjungan->count() }} pasien)
                            </div>

                            @if($hadir === 'Tidak Hadir')
                                <form method="POST" action="{{ route('kehadiran.pindahkan-massal', $dokter) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                                    <input type="hidden" name="alasan" value="Dokter asal tidak hadir">
                                    @if($penggantiLain->isNotEmpty())
                                        <input type="hidden" name="aksi" value="pindah">
                                        <select name="dokter_tujuan_id" required class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs">
                                            @foreach($penggantiLain as $p)
                                                <option value="{{ $p->id }}">Pindahkan Semua ke {{ $p->nama }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium cursor-pointer">
                                            Pindahkan Semua
                                        </button>
                                    @else
                                        <input type="hidden" name="aksi" value="menunggu">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-medium cursor-pointer">
                                            Tidak Ada Pengganti — Set Semua Menunggu
                                        </button>
                                    @endif
                                </form>
                            @endif
                        </div>

                        <div class="space-y-2">
                            @foreach($dokter->kunjungan as $k)
                                <div class="bg-white rounded-lg border border-slate-200 px-3 py-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <div>
                                        <span class="font-semibold">#{{ $k->no_antrian }}</span> &bull;
                                        <span class="font-medium text-slate-900">{{ $k->pasien->nama }}</span>
                                        <x-status-badge :status="$k->status" class="ml-1" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($penggantiLain->isNotEmpty())
                                            <form method="POST" action="{{ route('kehadiran.pindahkan', $k) }}" class="flex items-center gap-1.5">
                                                @csrf
                                                <input type="hidden" name="alasan" value="Dokter tidak hadir">
                                                <select name="dokter_tujuan_id" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                    @foreach($penggantiLain as $p)
                                                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-xs cursor-pointer">
                                                    Pindah
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('kunjungan.show', $k) }}" class="text-slate-600 hover:underline">Detail</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Audit Log Pemindahan --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 font-semibold text-slate-800">Catatan Pemindahan Dokter Terbaru</div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pasien</th>
                        <th class="px-4 py-3">Dokter Asal &rarr; Tujuan</th>
                        <th class="px-4 py-3">Alasan</th>
                        <th class="px-4 py-3">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($riwayatPemindahan as $rp)
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">{{ $rp->waktu->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5 font-medium text-slate-900">{{ $rp->kunjungan->pasien->nama }}</td>
                            <td class="px-4 py-2.5">{{ $rp->dokterAsal->nama }} &rarr; <strong>{{ $rp->dokterTujuan->nama }}</strong></td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $rp->alasan }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $rp->petugas->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada riwayat pemindahan dokter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
