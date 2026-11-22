<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\KehadiranDokter;
use App\Models\Kunjungan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->toDateString();

        $kunjunganHariIni = Kunjungan::with(['pasien', 'dokter', 'pembayaran', 'resep'])
            ->whereDate('tgl_kunjungan', $today)
            ->when($user->isRole('dokter') && $user->dokter, fn ($q) => $q->where('dokter_id', $user->dokter->id))
            ->orderBy('no_antrian')
            ->get();

        $stats = [
            'terdaftar' => $kunjunganHariIni->where('status', Kunjungan::STATUS_TERDAFTAR)->count(),
            'menunggu' => $kunjunganHariIni->where('status', Kunjungan::STATUS_MENUNGGU)->count(),
            'diperiksa' => $kunjunganHariIni->where('status', Kunjungan::STATUS_DIPERIKSA)->count(),
            'selesai' => $kunjunganHariIni->where('status', Kunjungan::STATUS_SELESAI)->count(),
            'dibatalkan' => $kunjunganHariIni->where('status', Kunjungan::STATUS_DIBATALKAN)->count(),
        ];

        $dokterAbsen = KehadiranDokter::with('dokter')
            ->whereDate('tanggal', $today)
            ->where('status', KehadiranDokter::STATUS_TIDAK_HADIR)
            ->get();

        $pembayaranPerluPenyesuaian = Pembayaran::with(['kunjungan.pasien', 'kunjungan.dokter'])
            ->where('status_transaksi', Pembayaran::STATUS_PERLU_PENYESUAIAN)
            ->get();

        $antrianKasir = Kunjungan::with(['pasien', 'dokter', 'pembayaran', 'resep'])
            ->where('status', Kunjungan::STATUS_SELESAI)
            ->orderByDesc('tgl_kunjungan')
            ->limit(10)
            ->get();

        $jadwalHariIni = Dokter::with([
            'jadwalPraktik' => fn ($q) => $q->where('hari', Dokter::hariIndonesia($today)),
            'kehadiran' => fn ($q) => $q->whereDate('tanggal', $today),
        ])->orderBy('nama')->get();

        return view('dashboard', compact(
            'user',
            'today',
            'kunjunganHariIni',
            'stats',
            'dokterAbsen',
            'pembayaranPerluPenyesuaian',
            'antrianKasir',
            'jadwalHariIni'
        ));
    }
}
