@extends('layouts.app')

@section('title', 'Detail Kunjungan #' . $kunjungan->no_antrian)
@section('header', 'Detail Kunjungan — Antrian #' . $kunjungan->no_antrian)
@section('subheader', $kunjungan->pasien->nama . ' • ' . $kunjungan->tgl_kunjungan->format('d M Y'))
@section('back_url', route('kunjungan.index'))

@section('content')
    {{-- Header Actions --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-status-badge :status="$kunjungan->status" class="text-sm px-3 py-1" />
            <div class="text-sm text-slate-700">
                Dokter saat ini: <strong>{{ $kunjungan->dokter->nama }}</strong> ({{ $kunjungan->dokter->spesialisasi }})
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(auth()->user()->isRole('dokter') && auth()->user()->dokter?->id === $kunjungan->dokter_id && $kunjungan->status !== 'Dibatalkan')
                <a href="{{ route('pemeriksaan.show', $kunjungan) }}" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium">
                    Buka Pemeriksaan Dokter
                </a>
            @endif

            @if(auth()->user()->isRole('kasir') && $kunjungan->status === 'Selesai')
                <a href="{{ route('kasir.show', $kunjungan) }}" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium">
                    Proses Pembayaran Kasir
                </a>
            @endif

            @if(auth()->user()->isRole('admin') && $kunjungan->bisaDibatalkan())
                <form method="POST" action="{{ route('kunjungan.batalkan', $kunjungan) }}"
                      onsubmit="return confirm('Batalkan pendaftaran kunjungan ini?')">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-medium cursor-pointer">
                        Batalkan Pendaftaran
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Opsi Admin: Pindah Dokter / Jadwal Ulang --}}
    @if(auth()->user()->isRole('admin') && $kunjungan->bisaDibatalkan())
        <div class="grid lg:grid-cols-2 gap-6">
            <form method="POST" action="{{ route('kehadiran.pindahkan', $kunjungan) }}" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                @csrf
                <h3 class="text-sm font-semibold text-slate-800">Pindahkan Pasien ke Dokter Pengganti</h3>
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Dokter Pengganti (Berjadwal Hari Ini)</label>
                        <select name="dokter_tujuan_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @forelse($dokterPenggantiList as $dp)
                                <option value="{{ $dp->id }}">{{ $dp->nama }} ({{ $dp->spesialisasi }})</option>
                            @empty
                                <option value="" disabled>Tidak ada dokter pengganti tersedia</option>
                            @endforelse
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Alasan Pemindahan</label>
                        <input type="text" name="alasan" value="Dokter asal tidak hadir" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" @disabled($dokterPenggantiList->isEmpty())
                        class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white text-xs font-medium cursor-pointer">
                    Pindahkan & Catat Riwayat
                </button>
            </form>

            <form method="POST" action="{{ route('kunjungan.jadwalkan-ulang', $kunjungan) }}" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                @csrf
                <h3 class="text-sm font-semibold text-slate-800">Jadwalkan Ulang Kunjungan (Untuk Status Menunggu/Terdaftar)</h3>
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Dokter</label>
                        <select name="dokter_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach($semuaDokter as $sd)
                                <option value="{{ $sd->id }}" @selected($sd->id === $kunjungan->dokter_id)>{{ $sd->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Tanggal Baru</label>
                        <input type="date" name="tgl_kunjungan" value="{{ $kunjungan->tgl_kunjungan->toDateString() }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium cursor-pointer">
                    Simpan Jadwal Ulang
                </button>
            </form>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Riwayat Pemindahan Dokter --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
            <h2 class="font-semibold text-slate-800">Riwayat Pemindahan Dokter</h2>
            @forelse($kunjungan->pemindahanDokter as $pm)
                <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs space-y-1">
                    <div class="font-semibold text-slate-800">{{ $pm->dokterAsal->nama }} &rarr; {{ $pm->dokterTujuan->nama }}</div>
                    <div class="text-slate-600">Alasan: {{ $pm->alasan }}</div>
                    <div class="text-slate-500">Waktu: {{ $pm->waktu->format('d/m/Y H:i') }} &bull; Petugas: {{ $pm->petugas->name }}</div>
                </div>
            @empty
                <div class="text-xs text-slate-400">Tidak ada pemindahan dokter pada kunjungan ini.</div>
            @endforelse
        </div>

        {{-- Ringkasan Pemeriksaan --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
            <h2 class="font-semibold text-slate-800">Ringkasan Medis</h2>
            <div class="text-xs space-y-1.5">
                <div><span class="text-slate-500">Keluhan:</span> {{ $kunjungan->keluhan ?: '-' }}</div>
                <div><span class="text-slate-500">Hasil Pemeriksaan:</span> {{ $kunjungan->hasil_pemeriksaan ?: '-' }}</div>
                <div><span class="text-slate-500">Catatan Tambahan:</span> {{ $kunjungan->catatan_tambahan ?: '-' }}</div>
            </div>

            <div class="pt-2 border-t border-slate-100">
                <div class="text-xs font-semibold text-slate-700 mb-1">Diagnosis ({{ $kunjungan->detailDiagnosis->count() }})</div>
                <ul class="list-disc list-inside text-xs text-slate-700 space-y-0.5">
                    @forelse($kunjungan->detailDiagnosis as $diag)
                        <li>{{ $diag->nama_diagnosis }}</li>
                    @empty
                        <li class="list-none text-slate-400">Belum ada diagnosis.</li>
                    @endforelse
                </ul>
            </div>

            @if($kunjungan->riwayatDiagnosis->isNotEmpty())
                <div class="pt-2 border-t border-slate-100">
                    <div class="text-xs font-semibold text-amber-800 mb-1">Log Perubahan Diagnosis</div>
                    @foreach($kunjungan->riwayatDiagnosis as $rw)
                        <div class="text-[11px] bg-amber-50 border border-amber-200 rounded p-2 mb-1">
                            <strong>[{{ $rw->aksi }}]</strong>
                            {{ $rw->diagnosis_lama ?? '-' }} &rarr; {{ $rw->diagnosis_baru ?? '-' }}
                            <div class="text-slate-600">Alasan: {{ $rw->alasan_ubah }} ({{ $rw->waktu_ubah->format('d/m H:i') }})</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Tindakan & Resep --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">Tindakan & Resep Obat</h2>
            <div>
                <div class="text-xs font-semibold text-slate-700 mb-1">Tindakan Medis</div>
                @forelse($kunjungan->detailTindakan as $dt)
                    <div class="flex justify-between text-xs py-1 border-b border-slate-100">
                        <span>{{ $dt->tindakan->nama_tindakan }} (x{{ $dt->jumlah }})</span>
                        <span class="font-medium">Rp {{ number_format((float) $dt->biaya, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="text-xs text-slate-400">Tidak ada tindakan medis.</div>
                @endforelse
            </div>

            <div class="pt-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold text-slate-700">Resep Obat</span>
                    @if($kunjungan->resep)
                        <x-status-badge :status="$kunjungan->resep->status" />
                    @endif
                </div>
                @if($kunjungan->resep)
                    @foreach($kunjungan->resep->detailResep as $dr)
                        <div class="flex items-center justify-between text-xs py-1.5 border-b border-slate-100">
                            <div>
                                <div class="font-medium">{{ $dr->obat->nama_obat }} (x{{ $dr->jumlah }})</div>
                                <div class="text-slate-500">{{ $dr->aturan_pakai }}</div>
                            </div>
                            <div class="space-x-1">
                                <x-status-badge :status="$dr->status_stok" />
                                <x-status-badge :status="$dr->status_tebus" />
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-xs text-slate-400">Tidak ada resep obat.</div>
                @endif
            </div>
        </div>

        {{-- Ringkasan Pembayaran --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Ringkasan Pembayaran</h2>
                @if($kunjungan->pembayaran)
                    <x-status-badge :status="$kunjungan->pembayaran->status_transaksi" />
                @endif
            </div>
            @if($kunjungan->pembayaran)
                <div class="text-xs space-y-1">
                    @foreach($kunjungan->pembayaran->itemBiaya as $item)
                        <div class="flex items-center justify-between py-1 border-b border-slate-100">
                            <div>
                                <span>{{ $item->deskripsi }}</span>
                                <x-status-badge :status="$item->status_bayar" class="ml-1" />
                            </div>
                            <span class="font-medium">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="pt-2 text-xs space-y-1">
                    <div class="flex justify-between font-semibold text-slate-800">
                        <span>Total Biaya</span>
                        <span>Rp {{ number_format((float) $kunjungan->pembayaran->total_biaya, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-700">
                        <span>Ditanggung Asuransi</span>
                        <span>Rp {{ number_format($kunjungan->pembayaran->totalDitanggungAsuransi(), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-700">
                        <span>Ditanggung Pasien</span>
                        <span>Rp {{ number_format($kunjungan->pembayaran->totalDitanggungPasien(), 0, ',', '.') }}</span>
                    </div>
                </div>
            @else
                <div class="text-xs text-slate-400">Transaksi pembayaran belum dibuat (menunggu pemeriksaan selesai dan dibuka kasir).</div>
            @endif
        </div>
    </div>
@endsection
