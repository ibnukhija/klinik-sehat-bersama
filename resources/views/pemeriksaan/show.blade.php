@extends('layouts.app')

@section('title', 'Pemeriksaan Pasien - ' . $kunjungan->pasien->nama)
@section('header', 'Pemeriksaan — ' . $kunjungan->pasien->nama . ' (Antrian #' . $kunjungan->no_antrian . ')')
@section('subheader', 'Tgl Kunjungan: ' . $kunjungan->tgl_kunjungan->format('d/m/Y') . ' • Dokter: ' . $kunjungan->dokter->nama)
@section('back_url', route('pemeriksaan.index'))

@section('content')
    @php
        $sudahAdaPembayaran = $kunjungan->pembayaran !== null;
        $transaksiDitutup = $kunjungan->pembayaran?->status_transaksi === 'Ditutup';
    @endphp

    {{-- Banner Status & Panggil --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-status-badge :status="$kunjungan->status" class="text-sm px-3 py-1" />
            @if($kunjungan->pembayaran)
                <span class="text-xs text-slate-500">Status Transaksi Kasir:</span>
                <x-status-badge :status="$kunjungan->pembayaran->status_transaksi" />
            @endif
            @if($kunjungan->pemindahanDokter->isNotEmpty())
                <span class="text-xs px-2.5 py-1 rounded bg-amber-50 text-amber-800 border border-amber-200">
                    Pasien Pindahan dari {{ $kunjungan->pemindahanDokter->first()->dokterAsal->nama }}
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if(in_array($kunjungan->status, ['Terdaftar', 'Menunggu']))
                <form method="POST" action="{{ route('pemeriksaan.panggil', $kunjungan) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium cursor-pointer">
                        Panggil Pasien (Mulai Diperiksa)
                    </button>
                </form>
            @endif

            @if($kunjungan->status === 'Diperiksa')
                <form method="POST" action="{{ route('pemeriksaan.selesai', $kunjungan) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium cursor-pointer">
                        Selesaikan Pemeriksaan &rarr; Kasir
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($transaksiDitutup)
        <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900">
            <strong>Transaksi Pembayaran Sudah Ditutup:</strong> Seluruh data medis dan diagnosis untuk kunjungan ini sudah dikunci karena transaksi keuangan telah selesai.
        </div>
    @elseif($sudahAdaPembayaran)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Perhatian (Kondisi Khusus #6):</strong> Transaksi pembayaran untuk kunjungan ini sudah dibuat (belum Ditutup). Jika Anda mengubah/menambah diagnosis atau tindakan, sistem akan mencatat ke <strong>RiwayatDiagnosis</strong> dan mengubah status transaksi menjadi <strong>Perlu Penyesuaian</strong> di Kasir.
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Catatan Pemeriksaan --}}
        <form method="POST" action="{{ route('pemeriksaan.catatan', $kunjungan) }}" class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            @csrf
            <h2 class="font-semibold text-slate-800">1. Keluhan, Hasil Pemeriksaan & Catatan</h2>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Keluhan Pasien</label>
                <textarea name="keluhan" rows="2" @disabled($transaksiDitutup)
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">{{ old('keluhan', $kunjungan->keluhan) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Hasil Pemeriksaan Fisik / Penunjang</label>
                <textarea name="hasil_pemeriksaan" rows="3" @disabled($transaksiDitutup)
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">{{ old('hasil_pemeriksaan', $kunjungan->hasil_pemeriksaan) }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Catatan Tambahan</label>
                <textarea name="catatan_tambahan" rows="2" @disabled($transaksiDitutup)
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">{{ old('catatan_tambahan', $kunjungan->catatan_tambahan) }}</textarea>
            </div>
            @if(! $transaksiDitutup)
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium cursor-pointer">
                    Simpan Catatan Pemeriksaan
                </button>
            @endif
        </form>

        {{-- Diagnosis (Banyak + Ubah Diagnosis pasca pembayaran) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">2. Diagnosis (Bisa Lebih Dari Satu)</h2>

            <div class="space-y-2.5">
                @forelse($kunjungan->detailDiagnosis as $diag)
                    <div class="border border-slate-200 rounded-lg p-3 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-slate-900">{{ $diag->nama_diagnosis }}</span>
                            @if(! $transaksiDitutup)
                                <form method="POST" action="{{ route('pemeriksaan.diagnosis.hapus', $diag) }}" class="inline-flex items-center gap-1.5">
                                    @csrf
                                    @method('DELETE')
                                    @if($sudahAdaPembayaran)
                                        <input type="text" name="alasan_ubah" required placeholder="Alasan hapus"
                                               class="w-32 rounded border border-slate-300 px-2 py-1 text-xs">
                                    @endif
                                    <button type="submit" class="text-xs text-rose-600 hover:underline cursor-pointer">Hapus</button>
                                </form>
                            @endif
                        </div>

                        @if(! $transaksiDitutup)
                            <form method="POST" action="{{ route('pemeriksaan.diagnosis.ubah', $diag) }}" class="flex flex-wrap gap-2 pt-1 border-t border-slate-100">
                                @csrf
                                @method('PUT')
                                <input type="text" name="nama_diagnosis" value="{{ $diag->nama_diagnosis }}" required
                                       class="flex-1 min-w-[140px] rounded border border-slate-300 px-2.5 py-1 text-xs">
                                <input type="text" name="alasan_ubah" required placeholder="Alasan ubah diagnosis *"
                                       class="flex-1 min-w-[140px] rounded border border-slate-300 px-2.5 py-1 text-xs">
                                <button type="submit" class="px-2.5 py-1 rounded bg-amber-600 hover:bg-amber-700 text-white text-xs cursor-pointer">
                                    Ubah
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-xs text-slate-400">Belum ada diagnosis tercatat.</div>
                @endforelse
            </div>

            @if(! $transaksiDitutup)
                <form method="POST" action="{{ route('pemeriksaan.diagnosis.tambah', $kunjungan) }}" class="pt-3 border-t border-slate-100 space-y-2">
                    @csrf
                    <div class="text-xs font-medium text-slate-700">Tambah Diagnosis Baru</div>
                    <div class="flex flex-wrap gap-2">
                        <input type="text" name="nama_diagnosis" required placeholder="Contoh: Hipertensi Grade 1"
                               class="flex-1 min-w-[180px] rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @if($sudahAdaPembayaran)
                            <input type="text" name="alasan_ubah" required placeholder="Alasan tambah setelah transaksi *"
                                   class="flex-1 min-w-[160px] rounded-lg border border-amber-300 bg-amber-50/40 px-3 py-2 text-sm">
                        @endif
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium cursor-pointer">
                            + Diagnosis
                        </button>
                    </div>
                </form>
            @endif

            @if($kunjungan->riwayatDiagnosis->isNotEmpty())
                <div class="pt-3 border-t border-slate-100">
                    <div class="text-xs font-semibold text-amber-800 mb-1.5">Riwayat Perubahan Diagnosis</div>
                    <div class="space-y-1 max-h-36 overflow-y-auto">
                        @foreach($kunjungan->riwayatDiagnosis as $rw)
                            <div class="text-[11px] bg-amber-50 border border-amber-200 rounded px-2.5 py-1.5">
                                <strong>[{{ $rw->aksi }}]</strong>
                                {{ $rw->diagnosis_lama ?? '-' }} &rarr; {{ $rw->diagnosis_baru ?? '-' }}
                                &bull; <em>{{ $rw->alasan_ubah }}</em> ({{ $rw->waktu_ubah->format('d/m H:i') }})
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Tindakan Medis --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">3. Tindakan Medis</h2>
            <div class="divide-y divide-slate-100">
                @forelse($kunjungan->detailTindakan as $dt)
                    <div class="py-2 flex items-center justify-between text-sm">
                        <div>
                            <div class="font-medium text-slate-800">{{ $dt->tindakan->nama_tindakan }} (x{{ $dt->jumlah }})</div>
                            <div class="text-xs text-slate-500">Biaya: Rp {{ number_format((float) $dt->biaya, 0, ',', '.') }}</div>
                        </div>
                        @if(! $transaksiDitutup)
                            <form method="POST" action="{{ route('pemeriksaan.tindakan.hapus', $dt) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-600 hover:underline cursor-pointer">Hapus</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-xs text-slate-400">Belum ada tindakan medis.</div>
                @endforelse
            </div>

            @if(! $transaksiDitutup)
                <form method="POST" action="{{ route('pemeriksaan.tindakan.tambah', $kunjungan) }}" class="pt-3 border-t border-slate-100 flex flex-wrap gap-2">
                    @csrf
                    <select name="tindakan_id" required class="flex-1 min-w-[200px] rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach($tindakanList as $t)
                            <option value="{{ $t->id }}">{{ $t->nama_tindakan }} (Rp {{ number_format((float) $t->tarif, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                    <input type="number" name="jumlah" value="1" min="1" max="20" class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium cursor-pointer">
                        + Tindakan
                    </button>
                </form>
            @endif
        </div>

        {{-- Resep Obat (Cek Stok Real-time + Tetap Bisa Simpan Bila Stok Habis) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">4. Resep Obat</h2>
                @if($kunjungan->resep)
                    <x-status-badge :status="$kunjungan->resep->status" />
                @endif
            </div>

            @if($kunjungan->resep)
                <div class="divide-y divide-slate-100 border border-slate-200 rounded-lg px-3">
                    @foreach($kunjungan->resep->detailResep as $dr)
                        <div class="py-2.5 flex items-center justify-between gap-2 text-xs">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $dr->obat->nama_obat }} (x{{ $dr->jumlah }})</div>
                                <div class="text-slate-500">Aturan: {{ $dr->aturan_pakai }}</div>
                            </div>
                            <div class="space-x-1">
                                <x-status-badge :status="$dr->status_stok" />
                                <x-status-badge :status="$dr->status_tebus" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(! $transaksiDitutup)
                <form method="POST" action="{{ route('pemeriksaan.resep.simpan', $kunjungan) }}" class="space-y-3 pt-2 border-t border-slate-100">
                    @csrf
                    <div class="text-xs text-slate-600">
                        Jika obat yang dipilih stoknya habis, sistem menampilkan peringatan <strong>tetapi resep tetap dapat disimpan</strong> (Kondisi #2).
                    </div>

                    <div id="resep-rows" class="space-y-2.5">
                        <div class="resep-row grid grid-cols-1 sm:grid-cols-12 gap-2 items-start">
                            <div class="sm:col-span-5">
                                <select name="items[0][obat_id]" required class="obat-select w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs">
                                    @foreach($obatList as $o)
                                        <option value="{{ $o->id }}" data-stok="{{ $o->stok }}">
                                            {{ $o->nama_obat }} (Stok: {{ $o->stok }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="stok-warning text-[11px] text-rose-600 mt-0.5 hidden">Stok habis/kurang — tetap bisa disimpan</div>
                            </div>
                            <div class="sm:col-span-2">
                                <input type="number" name="items[0][jumlah]" value="1" min="1" required
                                       class="jumlah-input w-full rounded-lg border border-slate-300 px-2 py-2 text-xs">
                            </div>
                            <div class="sm:col-span-5">
                                <input type="text" name="items[0][aturan_pakai]" value="3x1 sesudah makan" required placeholder="Aturan pakai"
                                       class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <button type="button" id="add-obat-btn" class="text-xs font-medium text-emerald-700 hover:underline cursor-pointer">
                            + Tambah Baris Obat
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium cursor-pointer">
                            Simpan Resep
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('resep-rows');
            const addBtn = document.getElementById('add-obat-btn');
            if (!container || !addBtn) return;

            let idx = 1;
            const checkRow = (row) => {
                const sel = row.querySelector('.obat-select');
                const qty = row.querySelector('.jumlah-input');
                const warn = row.querySelector('.stok-warning');
                if (!sel || !qty || !warn) return;
                const stok = parseInt(sel.selectedOptions[0]?.dataset?.stok ?? '0', 10);
                const req = parseInt(qty.value || '1', 10);
                warn.classList.toggle('hidden', stok >= req && stok > 0);
            };

            container.addEventListener('change', (e) => {
                const row = e.target.closest('.resep-row');
                if (row) checkRow(row);
            });
            container.addEventListener('input', (e) => {
                const row = e.target.closest('.resep-row');
                if (row) checkRow(row);
            });
            container.querySelectorAll('.resep-row').forEach(checkRow);

            addBtn.addEventListener('click', () => {
                const template = container.querySelector('.resep-row').cloneNode(true);
                template.querySelector('.obat-select').name = `items[${idx}][obat_id]`;
                template.querySelector('.jumlah-input').name = `items[${idx}][jumlah]`;
                template.querySelector('.jumlah-input').value = 1;
                const aturan = template.querySelector('input[type="text"]');
                aturan.name = `items[${idx}][aturan_pakai]`;
                aturan.value = '3x1 sesudah makan';
                idx++;
                container.appendChild(template);
                checkRow(template);
            });
        })();
    </script>
@endsection
