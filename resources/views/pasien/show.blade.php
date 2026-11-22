@extends('layouts.app')

@section('title', 'Detail Pasien - ' . $pasien->nama)
@section('header', $pasien->nama)
@section('subheader', 'No. Identitas: ' . $pasien->no_identitas)
@section('back_url', route('pasien.index'))

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Profil --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Profil Pasien</h2>
                @if(auth()->user()->isRole('admin'))
                    <a href="{{ route('kunjungan.create', ['no_identitas' => $pasien->no_identitas]) }}"
                       class="text-xs px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                        + Daftarkan Kunjungan
                    </a>
                @endif
            </div>
            <dl class="text-sm space-y-2 pt-2 border-t border-slate-100">
                <div>
                    <dt class="text-xs text-slate-500">No. Identitas</dt>
                    <dd class="font-mono text-slate-800">{{ $pasien->no_identitas }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Tanggal Lahir</dt>
                    <dd class="text-slate-800">{{ $pasien->tgl_lahir->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">No. Telepon</dt>
                    <dd class="text-slate-800">{{ $pasien->no_telp }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Alamat</dt>
                    <dd class="text-slate-800">{{ $pasien->alamat }}</dd>
                </div>
            </dl>
        </div>

        {{-- Polis Asuransi (Multi-polis) --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <div>
                <h2 class="font-semibold text-slate-800">Polis Asuransi Pasien (Urut Prioritas Klaim)</h2>
                <p class="text-xs text-slate-500">Pasien dapat memiliki lebih dari satu jenis asuransi. Prioritas 1 diklaim lebih dulu, prioritas 2 menanggung sisa biaya.</p>
            </div>

            <div class="divide-y divide-slate-100 border border-slate-200 rounded-lg">
                @forelse($pasien->polisAsuransi as $pol)
                    <div class="p-3.5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">
                                <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs mr-1.5">Prioritas {{ $pol->prioritas }}</span>
                                {{ $pol->asuransi->nama_perusahaan }}
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">No. Polis: <span class="font-mono">{{ $pol->no_polis }}</span></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$pol->status_aktif ? 'Aktif' : 'Nonaktif'" />
                            @if(auth()->user()->isRole('admin'))
                                <form method="POST" action="{{ route('polis.toggle', $pol) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs px-2.5 py-1 rounded border border-slate-300 hover:bg-slate-50 cursor-pointer">
                                        {{ $pol->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-slate-400 text-center">Pasien ini belum memiliki polis asuransi terdaftar.</div>
                @endforelse
            </div>

            @if(auth()->user()->isRole('admin'))
                <form method="POST" action="{{ route('pasien.polis.store', $pasien) }}" class="pt-3 border-t border-slate-100 grid md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tambah Perusahaan Asuransi</label>
                        <select name="asuransi_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach($asuransiList as $a)
                                <option value="{{ $a->id }}">{{ $a->nama_perusahaan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nomor Polis</label>
                        <input type="text" name="no_polis" required placeholder="PRU-12345" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="flex gap-2">
                        <div class="w-20">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Prioritas</label>
                            <input type="number" name="prioritas" value="{{ $pasien->polisAsuransi->count() + 1 }}" min="1" max="10" required
                                   class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium cursor-pointer">
                            Tambah
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Riwayat Kunjungan --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 font-semibold text-slate-800">Riwayat Kunjungan Pasien</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Antrian</th>
                        <th class="px-4 py-3">Dokter</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Pembayaran</th>
                        <th class="px-4 py-3 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pasien->kunjungan as $k)
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ $k->tgl_kunjungan->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-semibold">#{{ $k->no_antrian }}</td>
                            <td class="px-4 py-3">{{ $k->dokter->nama }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$k->status" /></td>
                            <td class="px-4 py-3">
                                @if($k->pembayaran)
                                    <x-status-badge :status="$k->pembayaran->status_transaksi" />
                                    <span class="text-xs text-slate-500 ml-1">Rp {{ number_format((float) $k->pembayaran->total_biaya, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('kunjungan.show', $k) }}" class="text-xs font-medium text-emerald-700 hover:underline">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada riwayat kunjungan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
