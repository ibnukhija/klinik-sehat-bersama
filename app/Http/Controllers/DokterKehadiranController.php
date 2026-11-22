<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\KehadiranDokter;
use App\Models\Kunjungan;
use App\Models\PemindahanDokter;
use App\Services\KunjunganService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DokterKehadiranController extends Controller
{
    public function __construct(private readonly KunjunganService $kunjunganService)
    {
    }

    public function index(Request $request): View
    {
        $tanggal = $request->query('tanggal', now()->toDateString());
        $hari = Dokter::hariIndonesia($tanggal);

        $dokterList = Dokter::with([
            'jadwalPraktik' => fn ($q) => $q->where('hari', $hari),
            'kehadiran' => fn ($q) => $q->whereDate('tanggal', $tanggal),
            'kunjungan' => fn ($q) => $q->with('pasien')
                ->whereDate('tgl_kunjungan', $tanggal)
                ->whereIn('status', [Kunjungan::STATUS_TERDAFTAR, Kunjungan::STATUS_MENUNGGU])
                ->orderBy('no_antrian'),
        ])->orderBy('nama')->get();

        $dokterTersedia = Dokter::cariDokterBerjadwal($tanggal);

        $riwayatPemindahan = PemindahanDokter::with(['kunjungan.pasien', 'dokterAsal', 'dokterTujuan', 'petugas'])
            ->latest('waktu')
            ->limit(15)
            ->get();

        return view('kehadiran.index', compact('tanggal', 'hari', 'dokterList', 'dokterTersedia', 'riwayatPemindahan'));
    }

    public function updateStatus(Request $request, Dokter $dokter): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:Hadir,Tidak Hadir'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->kunjunganService->ubahKehadiranDokter($dokter, $data['tanggal'], $data['status'], $data['catatan'] ?? null);

        return redirect()
            ->route('kehadiran.index', ['tanggal' => $data['tanggal']])
            ->with('success', "Status kehadiran {$dokter->nama} pada {$data['tanggal']} diubah menjadi {$data['status']}.");
    }

    public function pindahkan(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $data = $request->validate([
            'dokter_tujuan_id' => ['required', 'exists:dokter,id'],
            'alasan' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->kunjunganService->pindahkanKunjungan(
                $kunjungan,
                (int) $data['dokter_tujuan_id'],
                $request->user()->id,
                $data['alasan']
            );

            return back()->with('success', 'Pasien berhasil dipindahkan ke dokter pengganti dan pemindahan telah dicatat.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pindahkanMassal(Request $request, Dokter $dokter): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'aksi' => ['required', 'in:pindah,menunggu'],
            'dokter_tujuan_id' => ['nullable', 'required_if:aksi,pindah', 'exists:dokter,id'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        $kunjunganList = Kunjungan::where('dokter_id', $dokter->id)
            ->whereDate('tgl_kunjungan', $data['tanggal'])
            ->whereIn('status', [Kunjungan::STATUS_TERDAFTAR, Kunjungan::STATUS_MENUNGGU])
            ->get();

        if ($kunjunganList->isEmpty()) {
            return back()->with('error', 'Tidak ada pasien terdaftar yang perlu dipindahkan untuk dokter dan tanggal tersebut.');
        }

        try {
            $count = 0;
            foreach ($kunjunganList as $kunjungan) {
                if ($data['aksi'] === 'pindah') {
                    $this->kunjunganService->pindahkanKunjungan(
                        $kunjungan,
                        (int) $data['dokter_tujuan_id'],
                        $request->user()->id,
                        $data['alasan'] ?: 'Dokter asal tidak hadir'
                    );
                } else {
                    if ($kunjungan->status === Kunjungan::STATUS_TERDAFTAR) {
                        $this->kunjunganService->tandaiMenunggu($kunjungan);
                    }
                }
                $count++;
            }

            $msg = $data['aksi'] === 'pindah'
                ? "{$count} pasien berhasil dipindahkan ke dokter pengganti."
                : "{$count} pasien ditandai berstatus Menunggu (belum ada dokter pengganti).";

            return back()->with('success', $msg);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
