@extends('layouts.app')

@section('title', 'Kasir - ' . $kunjungan->pasien->nama)
@section('header', 'Transaksi Pembayaran — ' . $kunjungan->pasien->nama)
@section('subheader', 'Kunjungan ' . $kunjungan->tgl_kunjungan->format('d/m/Y') . ' • Dokter: ' . $kunjungan->dokter->nama)
@section('back_url', route('kasir.index'))

@section('content')
    @php
        $ditutup = $pembayaran->status_transaksi === 'Ditutup';
        $perluPenyesuaian = $pembayaran->status_transaksi === 'Perlu Penyesuaian';
        $totalAsuransi = $pembayaran->totalDitanggungAsuransi();
        $totalPasien = $pembayaran->totalDitanggungPasien();
        $sudahBayar = $pembayaran->totalSudahDibayarPasien();
        $sisaPasien = $pembayaran->sisaTagihanPasien();
        $bisaDitutup = $pembayaran->cekSemuaBiayaJelas();
    @endphp

    {{-- Banner Kondisi #6: Perlu Penyesuaian --}}
    @if($perluPenyesuaian)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-bold text-amber-900">Perhatian: Dokter Mengubah Diagnosis / Tindakan Setelah Transaksi Dibuat</div>
                    <div class="text-xs text-amber-800 mt-0.5">Tinjau log perubahan di bawah lalu konfirmasi sinkronisasi tagihan sebelum melanjutkan klaim atau menutup transaksi.</div>
                </div>
                <form method="POST" action="{{ route('kasir.penyesuaian', $pembayaran) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold cursor-pointer">
                        Konfirmasi Peninjauan & Sinkronkan Tagihan
                    </button>
                </form>
            </div>
            @if($kunjungan->riwayatDiagnosis->isNotEmpty())
                <div class="space-y-1">
                    @foreach($kunjungan->riwayatDiagnosis as $rw)
                        <div class="text-xs bg-white/80 border border-amber-200 rounded px-3 py-1.5">
                            <strong>[{{ $rw->aksi }}]</strong> {{ $rw->diagnosis_lama ?? '-' }} &rarr; {{ $rw->diagnosis_baru ?? '-' }}
                            (Alasan: {{ $rw->alasan_ubah }} &bull; {{ $rw->waktu_ubah->format('d/m H:i') }})
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Ringkasan Angka Keuangan --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="col-span-2 lg:col-span-1 bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Status Transaksi</div>
            <div class="mt-1.5"><x-status-badge :status="$pembayaran->status_transaksi" class="text-sm" /></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Total Biaya</div>
            <div class="text-lg font-bold text-slate-900 mt-1">Rp {{ number_format((float) $pembayaran->total_biaya, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Ditanggung Asuransi</div>
            <div class="text-lg font-bold text-emerald-700 mt-1">Rp {{ number_format($totalAsuransi, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Ditanggung Pasien</div>
            <div class="text-lg font-bold text-slate-900 mt-1">Rp {{ number_format($totalPasien, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Sisa Belum Dibayar</div>
            <div class="text-lg font-bold {{ $sisaPasien > 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                Rp {{ number_format($sisaPasien, 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Kondisi #7: Konfirmasi Penebusan Resep --}}
    @if($kunjungan->resep)
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-slate-800">1. Konfirmasi Penebusan Resep Obat</h2>
                    <p class="text-xs text-slate-500">Obat dengan stok habis otomatis tidak ditagihkan di klinik. Obat tersedia dikonfirmasi apakah ditebus pasien di klinik atau tidak.</p>
                </div>
                <x-status-badge :status="$kunjungan->resep->status" />
            </div>

            <form method="POST" action="{{ route('kasir.resep', $pembayaran) }}" class="space-y-3">
                @csrf
                <div class="divide-y divide-slate-100 border border-slate-200 rounded-lg">
                    @foreach($kunjungan->resep->detailResep as $dr)
                        <div class="p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $dr->obat->nama_obat }} (x{{ $dr->jumlah }}) — Rp {{ number_format((float) $dr->obat->harga * $dr->jumlah, 0, ',', '.') }}</div>
                                <div class="text-slate-500">Aturan: {{ $dr->aturan_pakai }} &bull; Stok di klinik: {{ $dr->obat->stok }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-status-badge :status="$dr->status_stok" />
                                @if($dr->status_stok === 'Habis')
                                    <span class="text-rose-700 font-medium">Tidak Ditagihkan (Beli di Luar)</span>
                                @elseif($ditutup || $dr->stok_terpotong)
                                    <x-status-badge :status="$dr->status_tebus" />
                                @else
                                    <select name="keputusan[{{ $dr->id }}]" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs">
                                        <option value="Ditebus" @selected($dr->status_tebus === 'Ditebus' || $dr->status_tebus === 'Belum Ditebus')>Tebus di Klinik</option>
                                        <option value="Tidak Ditebus" @selected($dr->status_tebus === 'Tidak Ditebus')>Tidak Ditebus di Klinik</option>
                                    </select>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(! $ditutup && $kunjungan->resep->detailResep->where('status_stok', 'Tersedia')->where('stok_terpotong', false)->isNotEmpty())
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium cursor-pointer">
                        Simpan Konfirmasi Penebusan Obat
                    </button>
                @endif
            </form>
        </div>
    @endif

    {{-- Rincian Item Biaya --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 font-semibold text-slate-800">2. Rincian Item Biaya</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-right">Ditanggung Asuransi</th>
                        <th class="px-4 py-3 text-right">Ditanggung Pasien</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($pembayaran->itemBiaya as $item)
                        <tr>
                            <td class="px-4 py-3 text-xs font-medium text-slate-600">{{ $item->jenis }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $item->deskripsi }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700 font-medium">Rp {{ number_format((float) $item->ditanggung_asuransi, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format((float) $item->ditanggung_pasien, 0, ',', '.') }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$item->status_bayar" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Klaim Asuransi (Multi-polis & Parsial per Item) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="font-semibold text-slate-800">3. Pembayaran & Klaim Asuransi (Multi-Polis / Sebagian Tindakan)</h2>

            @if($polisAktif->isEmpty())
                <div class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3">
                    Pasien ini tidak memiliki polis asuransi aktif. Silakan proses pembayaran secara tunai.
                </div>
            @else
                @php
                    $itemBisaDiklaim = $pembayaran->itemBiaya->filter(fn ($i) =>
                        ! in_array($i->status_bayar, ['Belum Ditebus', 'Tidak Ditagihkan', 'Menunggu Klaim'], true)
                        && $i->sisaBelumDitanggungAsuransi() > 0
                    );
                @endphp

                @if(! $ditutup && ! $perluPenyesuaian && $itemBisaDiklaim->isNotEmpty())
                    <form method="POST" action="{{ route('kasir.klaim.ajukan', $pembayaran) }}" class="space-y-3 border border-slate-200 rounded-lg p-4 bg-slate-50/70">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Pilih Polis Asuransi Pasien (Urut Prioritas)</label>
                            <select name="polis_asuransi_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                @foreach($polisAktif as $pol)
                                    <option value="{{ $pol->id }}">
                                        Prioritas {{ $pol->prioritas }} — {{ $pol->asuransi->nama_perusahaan }} ({{ $pol->no_polis }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Pilih Item Biaya yang Diklaim (Sisa Belum Ditanggung):</label>
                            <div class="space-y-1.5 max-h-44 overflow-y-auto bg-white border border-slate-200 rounded-lg p-2.5">
                                @foreach($itemBisaDiklaim as $ib)
                                    <label class="flex items-center justify-between text-xs py-1 cursor-pointer">
                                        <span class="flex items-center gap-2">
                                            <input type="checkbox" name="item_biaya_ids[]" value="{{ $ib->id }}" checked>
                                            <span>{{ $ib->deskripsi }}</span>
                                        </span>
                                        <span class="font-semibold text-slate-700">
                                            Rp {{ number_format($ib->sisaBelumDitanggungAsuransi(), 0, ',', '.') }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-medium cursor-pointer">
                            Ajukan Klaim ke Perusahaan Asuransi
                        </button>
                    </form>
                @endif

                {{-- Daftar & Simulasi Keputusan Klaim --}}
                <div class="space-y-3">
                    @foreach($pembayaran->klaimAsuransi as $klaim)
                        <div class="border {{ $klaim->status === 'Diajukan' ? 'border-sky-300 bg-sky-50/40' : 'border-slate-200' }} rounded-lg p-4 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">
                                        Klaim #{{ $klaim->id }} — {{ $klaim->polisAsuransi->asuransi->nama_perusahaan }} (P{{ $klaim->polisAsuransi->prioritas }})
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Diajukan: Rp {{ number_format((float) $klaim->jumlah_diajukan, 0, ',', '.') }}
                                        &bull; Disetujui: <strong>Rp {{ number_format((float) $klaim->jumlah_disetujui, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                                <x-status-badge :status="$klaim->status" />
                            </div>

                            @if($klaim->status === 'Diajukan' && ! $ditutup)
                                <form method="POST" action="{{ route('kasir.klaim.keputusan', $klaim) }}" class="space-y-2.5 pt-2 border-t border-sky-200">
                                    @csrf
                                    <div class="text-xs font-semibold text-sky-900">Simulasi Respon Perusahaan Asuransi (Per Item):</div>

                                    <div class="space-y-1.5 bg-white border border-slate-200 rounded-lg p-2.5">
                                        @foreach($klaim->klaimItem as $ki)
                                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                                <div>
                                                    <div class="font-medium text-slate-800">{{ $ki->itemBiaya->deskripsi }}</div>
                                                    <div class="text-[11px] text-slate-500">Diajukan: Rp {{ number_format((float) $ki->jumlah_diajukan, 0, ',', '.') }}</div>
                                                </div>
                                                <input type="number" name="nominal_item[{{ $ki->id }}]"
                                                       value="{{ (int) $ki->jumlah_diajukan }}" min="0" max="{{ (int) $ki->jumlah_diajukan }}"
                                                       class="w-full sm:w-28 rounded border border-slate-300 px-2 py-1 text-xs text-right"
                                                       title="Nominal disetujui untuk mode Parsial">
                                            </div>
                                        @endforeach
                                    </div>

                                    <input type="text" name="catatan_keputusan" placeholder="Catatan keputusan (opsional)"
                                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs">

                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" name="mode" value="setujui_penuh"
                                                class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium cursor-pointer">
                                            Setujui Penuh
                                        </button>
                                        <button type="submit" name="mode" value="parsial"
                                                class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium cursor-pointer">
                                            Simpan Sebagian (Sesuai Nominal)
                                        </button>
                                        <button type="submit" name="mode" value="tolak"
                                                class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-medium cursor-pointer">
                                            Tolak Klaim (&rarr; Belum Lunas)
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="text-xs space-y-1 pt-2 border-t border-slate-100">
                                    @foreach($klaim->klaimItem as $ki)
                                        <div class="flex justify-between">
                                            <span>{{ $ki->itemBiaya->deskripsi }}</span>
                                            <span>
                                                Rp {{ number_format((float) $ki->jumlah_ditanggung, 0, ',', '.') }}
                                                <x-status-badge :status="$ki->status_item" class="ml-1" />
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pembayaran Tunai & Tutup Transaksi --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <h2 class="font-semibold text-slate-800">4. Pembayaran Tunai Pasien</h2>

                @if(! $ditutup && ! $perluPenyesuaian && $sisaPasien > 0)
                    <form method="POST" action="{{ route('kasir.bayar-tunai', $pembayaran) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nominal Tunai Diterima (Sisa: Rp {{ number_format($sisaPasien, 0, ',', '.') }})</label>
                            <input type="number" name="jumlah" value="{{ (int) $sisaPasien }}" min="1" max="{{ (int) $sisaPasien }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">Catatan (Opsional)</label>
                            <input type="text" name="catatan" placeholder="Pelunasan tunai / sisa klaim asuransi"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium cursor-pointer">
                            Terima Pembayaran Tunai
                        </button>
                    </form>
                @elseif($sisaPasien <= 0)
                    <div class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                        Tidak ada sisa tagihan yang harus dibayar tunai oleh pasien.
                    </div>
                @endif

                @if($pembayaran->pembayaranPasien->isNotEmpty())
                    <div class="pt-3 border-t border-slate-100">
                        <div class="text-xs font-semibold text-slate-700 mb-1.5">Riwayat Pembayaran Pasien</div>
                        @foreach($pembayaran->pembayaranPasien as $bp)
                            <div class="flex justify-between text-xs py-1 border-b border-slate-100">
                                <span>{{ $bp->metode }} ({{ $bp->tanggal->format('d/m/Y H:i') }}) {{ $bp->catatan ? '— ' . $bp->catatan : '' }}</span>
                                <span class="font-semibold text-emerald-700">Rp {{ number_format((float) $bp->jumlah, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Tutup Transaksi --}}
            <div class="bg-white rounded-xl border {{ $bisaDitutup ? 'border-emerald-300' : 'border-slate-200' }} p-5 space-y-3">
                <h2 class="font-semibold text-slate-800">5. Tutup Transaksi</h2>
                <p class="text-xs text-slate-600">
                    Petugas kasir hanya dapat menutup transaksi apabila seluruh biaya sudah berstatus jelas (<strong>Lunas</strong> atau <strong>Tidak Ditagihkan</strong>) dan tidak ada penyesuaian tertunda.
                </p>

                @if($ditutup)
                    <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-900">
                        Transaksi <strong>Ditutup</strong> pada {{ $pembayaran->ditutup_pada?->format('d/m/Y H:i') }} oleh {{ $pembayaran->kasir?->name ?? '-' }}.
                    </div>
                @else
                    <form method="POST" action="{{ route('kasir.tutup', $pembayaran) }}">
                        @csrf
                        <button type="submit"
                                class="w-full py-2.5 rounded-lg {{ $bisaDitutup ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }} text-sm font-semibold cursor-pointer">
                            Tutup Transaksi Pembayaran
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
