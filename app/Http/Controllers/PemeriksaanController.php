<?php

namespace App\Http\Controllers;

use App\Models\DetailDiagnosis;
use App\Models\DetailTindakan;
use App\Models\Kunjungan;
use App\Models\Obat;
use App\Models\Tindakan;
use App\Services\PemeriksaanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PemeriksaanController extends Controller
{
    public function __construct(private readonly PemeriksaanService $pemeriksaanService)
    {
    }

    public function index(Request $request): View
    {
        $dokter = $request->user()->dokter;
        abort_if(! $dokter, 403, 'Akun dokter ini belum terhubung ke profil dokter.');

        $tanggal = $request->query('tanggal', '');

        $kunjunganList = Kunjungan::with(['pasien', 'detailDiagnosis', 'resep', 'pembayaran'])
            ->where('dokter_id', $dokter->id)
            ->when($tanggal !== '', fn ($q) => $q->whereDate('tgl_kunjungan', $tanggal))
            ->orderByDesc('tgl_kunjungan')
            ->orderBy('no_antrian')
            ->paginate(15)
            ->withQueryString();

        return view('pemeriksaan.index', compact('dokter', 'kunjunganList', 'tanggal'));
    }

    public function show(Request $request, Kunjungan $kunjungan): View
    {
        $dokter = $request->user()->dokter;
        abort_if(! $dokter || $kunjungan->dokter_id !== $dokter->id, 403, 'Anda tidak berhak mengakses pemeriksaan kunjungan ini.');

        $kunjungan->load([
            'pasien.polisAsuransi.asuransi',
            'dokter',
            'pemindahanDokter.dokterAsal',
            'detailDiagnosis',
            'riwayatDiagnosis.dokter',
            'detailTindakan.tindakan',
            'resep.detailResep.obat',
            'pembayaran',
        ]);

        $tindakanList = Tindakan::orderBy('nama_tindakan')->get();
        $obatList = Obat::orderBy('nama_obat')->get();

        return view('pemeriksaan.show', compact('kunjungan', 'tindakanList', 'obatList'));
    }

    public function panggil(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        try {
            $this->pemeriksaanService->panggilPasien($kunjungan, $request->user()->dokter);

            return back()->with('success', 'Pasien dipanggil. Status kunjungan menjadi Diperiksa.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function simpanCatatan(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $data = $request->validate([
            'keluhan' => ['nullable', 'string'],
            'hasil_pemeriksaan' => ['nullable', 'string'],
            'catatan_tambahan' => ['nullable', 'string'],
        ]);

        try {
            $this->pemeriksaanService->simpanCatatanPemeriksaan($kunjungan, $request->user()->dokter, $data);

            return back()->with('success', 'Catatan pemeriksaan berhasil disimpan.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function tambahDiagnosis(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $data = $request->validate([
            'nama_diagnosis' => ['required', 'string', 'max:255'],
            'alasan_ubah' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->pemeriksaanService->tambahDiagnosis(
                $kunjungan,
                $request->user()->dokter,
                $data['nama_diagnosis'],
                $data['alasan_ubah'] ?? null
            );

            $msg = $kunjungan->pembayaran
                ? 'Diagnosis baru ditambahkan & dicatat ke riwayat. Transaksi kasir ditandai Perlu Penyesuaian.'
                : 'Diagnosis berhasil ditambahkan.';

            return back()->with('success', $msg);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ubahDiagnosis(Request $request, DetailDiagnosis $detail): RedirectResponse
    {
        $data = $request->validate([
            'nama_diagnosis' => ['required', 'string', 'max:255'],
            'alasan_ubah' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->pemeriksaanService->ubahDiagnosis(
                $detail,
                $request->user()->dokter,
                $data['nama_diagnosis'],
                $data['alasan_ubah']
            );

            $msg = $detail->kunjungan->pembayaran
                ? 'Diagnosis diubah & log riwayat tersimpan. Status transaksi kasir berubah menjadi Perlu Penyesuaian.'
                : 'Diagnosis berhasil diubah & dicatat ke riwayat.';

            return back()->with('success', $msg);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function hapusDiagnosis(Request $request, DetailDiagnosis $detail): RedirectResponse
    {
        $data = $request->validate([
            'alasan_ubah' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->pemeriksaanService->hapusDiagnosis($detail, $request->user()->dokter, $data['alasan_ubah'] ?? null);

            return back()->with('success', 'Diagnosis berhasil dihapus.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function tambahTindakan(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $data = $request->validate([
            'tindakan_id' => ['required', 'exists:tindakan,id'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->pemeriksaanService->tambahTindakan($kunjungan, $request->user()->dokter, (int) $data['tindakan_id'], (int) $data['jumlah']);

            return back()->with('success', 'Tindakan medis berhasil ditambahkan.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function hapusTindakan(Request $request, DetailTindakan $detail): RedirectResponse
    {
        try {
            $this->pemeriksaanService->hapusTindakan($detail, $request->user()->dokter);

            return back()->with('success', 'Tindakan medis berhasil dihapus.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function simpanResep(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.obat_id' => ['required', 'exists:obat,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.aturan_pakai' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $this->pemeriksaanService->simpanResep($kunjungan, $request->user()->dokter, $data['items']);

            if (! empty($result['obat_habis'])) {
                return back()->with(
                    'warning',
                    'Resep tetap tersimpan (status: Belum Ditebus), namun stok obat berikut habis/kurang di klinik: '
                    .implode('; ', $result['obat_habis'])
                );
            }

            return back()->with('success', 'Resep obat berhasil disimpan dengan status Belum Ditebus.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function selesaikan(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        try {
            $this->pemeriksaanService->selesaikanPemeriksaan($kunjungan, $request->user()->dokter);

            return back()->with('success', 'Pemeriksaan selesai. Kunjungan siap diproses di kasir.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
