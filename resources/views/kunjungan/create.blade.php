@extends('layouts.app')

@section('title', 'Pendaftaran Kunjungan')
@section('header', 'Pendaftaran Pasien & Kunjungan')
@section('subheader', 'Cari pasien berdasarkan No. Identitas terlebih dahulu; jika pasien baru, buat data pasien sekaligus')

@section('content')
    {{-- Langkah 1: Cari No Identitas --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">1. Cek Status Pasien (Lama / Baru)</h2>
        <form method="GET" action="{{ route('kunjungan.create') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[240px]">
                <label class="block text-xs text-slate-600 mb-1">Nomor Identitas Pasien (NIK / KTP)</label>
                <input type="text" name="no_identitas" value="{{ $noIdentitas }}" required placeholder="Contoh: 3507011205950001"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Tanggal Kunjungan</label>
                <input type="date" name="tgl_kunjungan" value="{{ $tglKunjungan }}" required
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium cursor-pointer">
                Cari Pasien & Cek Jadwal
            </button>
        </form>

        <div class="mt-3 text-xs text-slate-500">
            Contoh cepat pasien terdaftar:
            <a href="{{ route('kunjungan.create', ['no_identitas' => '3507011205950001', 'tgl_kunjungan' => $tglKunjungan]) }}" class="text-emerald-700 hover:underline mr-2">3507011205950001 (Ahmad Fauzi - 2 Polis)</a>
            <a href="{{ route('kunjungan.create', ['no_identitas' => '3507010501900003', 'tgl_kunjungan' => $tglKunjungan]) }}" class="text-emerald-700 hover:underline">3507010501900003 (Bambang - Tunai)</a>
        </div>
    </div>

    @if($noIdentitas !== '')
        <form method="POST" action="{{ route('kunjungan.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-6">
            @csrf
            <input type="hidden" name="tgl_kunjungan" value="{{ $tglKunjungan }}">

            @if($pasienDitemukan)
                <input type="hidden" name="mode_pasien" value="lama">
                <input type="hidden" name="pasien_id" value="{{ $pasienDitemukan->id }}">

                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="text-xs font-semibold uppercase text-emerald-700 mb-1">Pasien Lama Ditemukan</div>
                    <div class="text-base font-bold text-slate-900">{{ $pasienDitemukan->nama }} ({{ $pasienDitemukan->no_identitas }})</div>
                    <div class="text-xs text-slate-600 mt-1">
                        Lahir: {{ $pasienDitemukan->tgl_lahir->format('d/m/Y') }} &bull; Telp: {{ $pasienDitemukan->no_telp }} &bull; {{ $pasienDitemukan->alamat }}
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @forelse($pasienDitemukan->polisAsuransi as $pol)
                            <span class="text-xs px-2 py-0.5 rounded bg-white border border-emerald-200 text-slate-700">
                                Prioritas {{ $pol->prioritas }}: {{ $pol->asuransi->nama_perusahaan }} ({{ $pol->no_polis }})
                            </span>
                        @empty
                            <span class="text-xs text-slate-500">Pembayaran Umum / Tunai</span>
                        @endforelse
                    </div>
                </div>
            @else
                <input type="hidden" name="mode_pasien" value="baru">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                    No. Identitas <strong>{{ $noIdentitas }}</strong> belum terdaftar. Silakan lengkapi data <strong>Pasien Baru</strong> di bawah ini:
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">No. Identitas *</label>
                        <input type="text" name="no_identitas" value="{{ old('no_identitas', $noIdentitas) }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama" value="{{ old('nama') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Lahir *</label>
                        <input type="date" name="tgl_lahir" value="{{ old('tgl_lahir') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">No. Telepon *</label>
                        <input type="text" name="no_telp" value="{{ old('no_telp') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Alamat *</label>
                        <textarea name="alamat" rows="2" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('alamat') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Asuransi Awal (Opsional)</label>
                        <select name="asuransi_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">-- Tanpa Asuransi --</option>
                            @foreach($asuransiList as $a)
                                <option value="{{ $a->id }}">{{ $a->nama_perusahaan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">No. Polis Awal (Opsional)</label>
                        <input type="text" name="no_polis" value="{{ old('no_polis') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
            @endif

            {{-- Pilih Dokter --}}
            <div class="border-t border-slate-200 pt-5 space-y-3">
                <h2 class="text-sm font-semibold text-slate-800">
                    2. Pilih Dokter Tujuan ({{ $hariDipilih }}, {{ \Carbon\Carbon::parse($tglKunjungan)->format('d/m/Y') }})
                </h2>

                <div class="grid md:grid-cols-3 gap-3">
                    @foreach($dokterList as $dokter)
                        @php
                            $jadwalHari = $dokter->jadwalPraktik->firstWhere('hari', $hariDipilih);
                            $statusHadir = $dokter->kehadiran->first()?->status ?? 'Hadir';
                            $bisaDipilih = $jadwalHari && $statusHadir === 'Hadir';
                        @endphp
                        <label class="border rounded-xl p-3.5 block {{ $bisaDipilih ? 'border-slate-200 hover:border-emerald-500 cursor-pointer bg-white' : 'border-rose-200 bg-rose-50/50 opacity-75 cursor-not-allowed' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="dokter_id" value="{{ $dokter->id }}" {{ $bisaDipilih ? '' : 'disabled' }} required>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $dokter->nama }}</div>
                                        <div class="text-xs text-slate-500">{{ $dokter->spesialisasi }}</div>
                                    </div>
                                </div>
                                <x-status-badge :status="$statusHadir" />
                            </div>
                            <div class="mt-2 text-xs text-slate-600">
                                @if($jadwalHari)
                                    Jadwal: {{ substr($jadwalHari->jam_mulai, 0, 5) }}–{{ substr($jadwalHari->jam_selesai, 0, 5) }}
                                @else
                                    <span class="text-rose-600">Tidak ada jadwal di hari {{ $hariDipilih }}</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="max-w-xs pt-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Biaya Pendaftaran (Rp)</label>
                    <input type="number" name="biaya_pendaftaran" value="{{ old('biaya_pendaftaran', 20000) }}" min="0"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">
                    Simpan Pendaftaran & Cetak Nomor Antrian
                </button>
            </div>
        </form>
    @endif
@endsection
