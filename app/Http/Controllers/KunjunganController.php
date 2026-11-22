<?php

namespace App\Http\Controllers;

use App\Models\Asuransi;
use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PolisAsuransi;
use App\Services\KunjunganService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KunjunganController extends Controller
{
    public function __construct(private readonly KunjunganService $kunjunganService)
    {
    }

    public function index(Request $request): View
    {
        $tanggal = $request->query('tanggal', '');
        $status = $request->query('status', '');
        $dokterId = $request->query('dokter_id', '');

        $kunjunganList = Kunjungan::with(['pasien', 'dokter', 'pembayaran', 'pemindahanDokter'])
            ->when($tanggal !== '', fn ($q) => $q->whereDate('tgl_kunjungan', $tanggal))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($dokterId !== '', fn ($q) => $q->where('dokter_id', $dokterId))
            ->orderByDesc('tgl_kunjungan')
            ->orderBy('no_antrian')
            ->paginate(15)
            ->withQueryString();

        $dokterList = Dokter::orderBy('nama')->get();

        return view('kunjungan.index', compact('kunjunganList', 'dokterList', 'tanggal', 'status', 'dokterId'));
    }

    public function create(Request $request): View
    {
        $noIdentitas = trim((string) $request->query('no_identitas', ''));
        $tglKunjungan = $request->query('tgl_kunjungan', now()->toDateString());

        $pasienDitemukan = $noIdentitas !== ''
            ? Pasien::with('polisAsuransi.asuransi')->where('no_identitas', $noIdentitas)->first()
            : null;

        $dokterList = Dokter::with([
            'jadwalPraktik',
            'kehadiran' => fn ($q) => $q->whereDate('tanggal', $tglKunjungan),
        ])->orderBy('nama')->get();

        $asuransiList = Asuransi::orderBy('nama_perusahaan')->get();
        $hariDipilih = Dokter::hariIndonesia($tglKunjungan);

        return view('kunjungan.create', compact(
            'noIdentitas',
            'tglKunjungan',
            'hariDipilih',
            'pasienDitemukan',
            'dokterList',
            'asuransiList'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $mode = $request->input('mode_pasien', 'lama');

        $rules = [
            'dokter_id' => ['required', 'exists:dokter,id'],
            'tgl_kunjungan' => ['required', 'date'],
            'biaya_pendaftaran' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($mode === 'lama') {
            $rules['pasien_id'] = ['required', 'exists:pasien,id'];
        } else {
            $rules['no_identitas'] = ['required', 'string', 'max:50', 'unique:pasien,no_identitas'];
            $rules['nama'] = ['required', 'string', 'max:255'];
            $rules['tgl_lahir'] = ['required', 'date'];
            $rules['alamat'] = ['required', 'string'];
            $rules['no_telp'] = ['required', 'string', 'max:30'];
            $rules['asuransi_id'] = ['nullable', 'exists:asuransi,id'];
            $rules['no_polis'] = ['nullable', 'required_with:asuransi_id', 'string', 'max:80', 'unique:polis_asuransi,no_polis'];
        }

        $data = $request->validate($rules);

        try {
            $pasien = $mode === 'lama'
                ? Pasien::findOrFail($data['pasien_id'])
                : Pasien::create([
                    'no_identitas' => $data['no_identitas'],
                    'nama' => $data['nama'],
                    'tgl_lahir' => $data['tgl_lahir'],
                    'alamat' => $data['alamat'],
                    'no_telp' => $data['no_telp'],
                ]);

            if ($mode !== 'lama' && ! empty($data['asuransi_id']) && ! empty($data['no_polis'])) {
                PolisAsuransi::create([
                    'pasien_id' => $pasien->id,
                    'asuransi_id' => $data['asuransi_id'],
                    'no_polis' => $data['no_polis'],
                    'prioritas' => 1,
                    'status_aktif' => true,
                ]);
            }

            $kunjungan = $this->kunjunganService->daftarkanKunjungan(
                $pasien,
                (int) $data['dokter_id'],
                $data['tgl_kunjungan'],
                (float) ($data['biaya_pendaftaran'] ?? 20000)
            );

            return redirect()
                ->route('kunjungan.show', $kunjungan)
                ->with('success', "Pendaftaran berhasil! Nomor antrian: #{$kunjungan->no_antrian}.");
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Kunjungan $kunjungan): View
    {
        $kunjungan->load([
            'pasien.polisAsuransi.asuransi',
            'dokter',
            'pemindahanDokter.dokterAsal',
            'pemindahanDokter.dokterTujuan',
            'pemindahanDokter.petugas',
            'detailDiagnosis',
            'riwayatDiagnosis.dokter',
            'detailTindakan.tindakan',
            'resep.detailResep.obat',
            'pembayaran.itemBiaya',
            'pembayaran.klaimAsuransi.polisAsuransi.asuransi',
            'pembayaran.pembayaranPasien',
        ]);

        $dokterPenggantiList = Dokter::cariDokterBerjadwal($kunjungan->tgl_kunjungan, $kunjungan->dokter_id);
        $semuaDokter = Dokter::with('jadwalPraktik')->orderBy('nama')->get();

        return view('kunjungan.show', compact('kunjungan', 'dokterPenggantiList', 'semuaDokter'));
    }

    public function batalkan(Kunjungan $kunjungan): RedirectResponse
    {
        try {
            $this->kunjunganService->batalkanKunjungan($kunjungan);

            return back()->with('success', 'Pendaftaran kunjungan berhasil dibatalkan.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function jadwalkanUlang(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $data = $request->validate([
            'dokter_id' => ['required', 'exists:dokter,id'],
            'tgl_kunjungan' => ['required', 'date'],
        ]);

        try {
            $updated = $this->kunjunganService->jadwalkanUlang($kunjungan, (int) $data['dokter_id'], $data['tgl_kunjungan']);

            return back()->with('success', "Kunjungan dijadwalkan ulang ke {$updated->dokter->nama} pada {$updated->tgl_kunjungan->format('d/m/Y')} (Antrian #{$updated->no_antrian}).");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
