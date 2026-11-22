@props(['status'])

@php
    $classes = match ($status) {
        'Terdaftar', 'Diajukan', 'Menunggu Klaim' => 'bg-sky-100 text-sky-800 border-sky-200',
        'Diperiksa', 'Terbuka', 'Sebagian Ditebus' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'Selesai', 'Lunas', 'Hadir', 'Tersedia', 'Ditebus', 'Sudah Ditebus', 'Disetujui', 'Ditutup', 'Aktif' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'Menunggu', 'Belum Ditebus', 'Disetujui Sebagian', 'Perlu Penyesuaian' => 'bg-amber-100 text-amber-800 border-amber-200',
        'Dibatalkan', 'Tidak Hadir', 'Habis', 'Stok Habis', 'Ditolak', 'Belum Lunas' => 'bg-rose-100 text-rose-800 border-rose-200',
        'Tidak Ditagihkan', 'Tidak Ditebus', 'Nonaktif' => 'bg-slate-100 text-slate-600 border-slate-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {$classes}"]) }}>
    {{ $status }}
</span>
