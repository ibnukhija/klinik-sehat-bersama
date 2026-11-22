@extends('layouts.app')

@section('title', 'Tambah Pasien Baru')
@section('header', 'Tambah Data Pasien Baru')
@section('subheader', 'Catat identitas pasien baru beserta polis asuransi awal jika ada')
@section('back_url', route('pasien.index'))

@section('content')
    <form method="POST" action="{{ route('pasien.store') }}" class="max-w-3xl bg-white rounded-xl border border-slate-200 p-6 space-y-5">
        @csrf
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">No. Identitas (NIK/KTP) *</label>
                <input type="text" name="no_identitas" value="{{ old('no_identitas') }}" required
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
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Alamat *</label>
            <textarea name="alamat" rows="2" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('alamat') }}</textarea>
        </div>

        <div class="border-t border-slate-200 pt-4">
            <div class="text-xs font-semibold text-slate-700 mb-3">Polis Asuransi Awal (Opsional)</div>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Perusahaan Asuransi</label>
                    <select name="asuransi_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">-- Tidak Pakai Asuransi --</option>
                        @foreach($asuransiList as $a)
                            <option value="{{ $a->id }}" @selected(old('asuransi_id') == $a->id)>{{ $a->nama_perusahaan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Nomor Polis</label>
                    <input type="text" name="no_polis" value="{{ old('no_polis') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-600 mb-1">Prioritas Klaim</label>
                    <input type="number" name="prioritas" value="{{ old('prioritas', 1) }}" min="1" max="5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('pasien.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">Simpan Pasien</button>
        </div>
    </form>
@endsection
