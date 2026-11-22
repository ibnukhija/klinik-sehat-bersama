<?php

namespace App\Http\Controllers;

use App\Models\KlaimAsuransi;
use App\Models\Kunjungan;
use App\Models\Pembayaran;
use App\Services\PembayaranService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KasirController extends Controller
{
    public function __construct(private readonly PembayaranService $pembayaranService)
    {
    }

    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', '');

        $kunjunganSelesai = Kunjungan::with(['pasien.polisAsuransi.asuransi', 'dokter', 'pembayaran', 'resep'])
            ->where('status', Kunjungan::STATUS_SELESAI)
            ->when($statusFilter !== '', function ($q) use ($statusFilter) {
                if ($statusFilter === 'Belum Dibuka') {
                    $q->whereDoesntHave('pembayaran');
                } else {
                    $q->whereHas('pembayaran', fn ($sub) => $sub->where('status_transaksi', $statusFilter));
                }
            })
            ->orderByDesc('tgl_kunjungan')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('kasir.index', compact('kunjunganSelesai', 'statusFilter'));
    }

    public function show(Kunjungan $kunjungan): View|RedirectResponse
    {
        try {
            $pembayaran = $this->pembayaranService->bukaAtauSinkronkan($kunjungan);
        } catch (DomainException $e) {
            return redirect()->route('kasir.index')->with('error', $e->getMessage());
        }

        $kunjungan->load([
            'pasien.polisAsuransi.asuransi',
            'dokter',
            'detailDiagnosis',
            'riwayatDiagnosis.dokter',
            'resep.detailResep.obat',
        ]);

        $polisAktif = $kunjungan->pasien->polisAktifUrutPrioritas();

        return view('kasir.show', compact('kunjungan', 'pembayaran', 'polisAktif'));
    }

    public function konfirmasiPenyesuaian(Pembayaran $pembayaran): RedirectResponse
    {
        try {
            $this->pembayaranService->konfirmasiPenyesuaian($pembayaran);

            return back()->with('success', 'Peninjauan perubahan diagnosis & sinkronisasi tagihan berhasil dikonfirmasi.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function konfirmasiResep(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $data = $request->validate([
            'keputusan' => ['required', 'array'],
            'keputusan.*' => ['required', 'in:Ditebus,Tidak Ditebus'],
        ]);

        try {
            $this->pembayaranService->konfirmasiPenebusanResep($pembayaran, $data['keputusan']);

            return back()->with('success', 'Konfirmasi penebusan resep berhasil disimpan dan tagihan diperbarui.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ajukanKlaim(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $data = $request->validate([
            'polis_asuransi_id' => ['required', 'exists:polis_asuransi,id'],
            'item_biaya_ids' => ['required', 'array', 'min:1'],
            'item_biaya_ids.*' => ['integer', 'exists:item_biaya,id'],
        ]);

        try {
            $klaim = $this->pembayaranService->ajukanKlaimAsuransi(
                $pembayaran,
                (int) $data['polis_asuransi_id'],
                $data['item_biaya_ids']
            );

            return back()->with('success', "Klaim #{$klaim->id} berhasil diajukan ke {$klaim->polisAsuransi->asuransi->nama_perusahaan}. Silakan proses simulasi keputusan asuransi di bawah.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function prosesKeputusanKlaim(Request $request, KlaimAsuransi $klaim): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:setujui_penuh,parsial,tolak'],
            'nominal_item' => ['nullable', 'array'],
            'nominal_item.*' => ['nullable', 'numeric', 'min:0'],
            'catatan_keputusan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $updated = $this->pembayaranService->prosesKeputusanKlaim(
                $klaim,
                $data['mode'],
                $data['nominal_item'] ?? [],
                $data['catatan_keputusan'] ?? null
            );

            $msg = "Keputusan klaim #{$updated->id}: {$updated->status} (Disetujui: Rp "
                .number_format((float) $updated->jumlah_disetujui, 0, ',', '.').').';

            return back()->with('success', $msg);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function bayarTunai(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $data = $request->validate([
            'jumlah' => ['required', 'numeric', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->pembayaranService->terimaPembayaranTunai(
                $pembayaran,
                (float) $data['jumlah'],
                $data['catatan'] ?? null
            );

            return back()->with('success', 'Pembayaran tunai pasien berhasil dicatat.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function tutupTransaksi(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        try {
            $this->pembayaranService->tutupTransaksi($pembayaran, $request->user()->id);

            return back()->with('success', 'Seluruh biaya telah jelas. Transaksi pembayaran resmi Ditutup.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
