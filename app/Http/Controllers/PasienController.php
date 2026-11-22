<?php

namespace App\Http\Controllers;

use App\Models\Asuransi;
use App\Models\Pasien;
use App\Models\PolisAsuransi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasienController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $pasienList = Pasien::with(['polisAsuransi.asuransi'])
            ->withCount('kunjungan')
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('no_identitas', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            }))
            ->orderBy('nama')
            ->paginate(12)
            ->withQueryString();

        return view('pasien.index', compact('pasienList', 'search'));
    }

    public function create(): View
    {
        $asuransiList = Asuransi::orderBy('nama_perusahaan')->get();

        return view('pasien.create', compact('asuransiList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'no_identitas' => ['required', 'string', 'max:50', 'unique:pasien,no_identitas'],
            'nama' => ['required', 'string', 'max:255'],
            'tgl_lahir' => ['required', 'date'],
            'alamat' => ['required', 'string'],
            'no_telp' => ['required', 'string', 'max:30'],
            'asuransi_id' => ['nullable', 'exists:asuransi,id'],
            'no_polis' => ['nullable', 'required_with:asuransi_id', 'string', 'max:80', 'unique:polis_asuransi,no_polis'],
            'prioritas' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $pasien = Pasien::create([
            'no_identitas' => $data['no_identitas'],
            'nama' => $data['nama'],
            'tgl_lahir' => $data['tgl_lahir'],
            'alamat' => $data['alamat'],
            'no_telp' => $data['no_telp'],
        ]);

        if (! empty($data['asuransi_id']) && ! empty($data['no_polis'])) {
            PolisAsuransi::create([
                'pasien_id' => $pasien->id,
                'asuransi_id' => $data['asuransi_id'],
                'no_polis' => $data['no_polis'],
                'prioritas' => $data['prioritas'] ?? 1,
                'status_aktif' => true,
            ]);
        }

        return redirect()->route('pasien.show', $pasien)->with('success', 'Data pasien baru berhasil disimpan.');
    }

    public function show(Pasien $pasien): View
    {
        $pasien->load([
            'polisAsuransi.asuransi',
            'kunjungan' => fn ($q) => $q->with(['dokter', 'pembayaran'])->orderByDesc('tgl_kunjungan')->orderByDesc('id'),
        ]);
        $asuransiList = Asuransi::orderBy('nama_perusahaan')->get();

        return view('pasien.show', compact('pasien', 'asuransiList'));
    }

    public function storePolis(Request $request, Pasien $pasien): RedirectResponse
    {
        $data = $request->validate([
            'asuransi_id' => ['required', 'exists:asuransi,id'],
            'no_polis' => ['required', 'string', 'max:80', 'unique:polis_asuransi,no_polis'],
            'prioritas' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        PolisAsuransi::create([
            'pasien_id' => $pasien->id,
            'asuransi_id' => $data['asuransi_id'],
            'no_polis' => $data['no_polis'],
            'prioritas' => $data['prioritas'],
            'status_aktif' => true,
        ]);

        return redirect()->route('pasien.show', $pasien)->with('success', 'Polis asuransi berhasil ditambahkan.');
    }

    public function togglePolis(PolisAsuransi $polis): RedirectResponse
    {
        $polis->update(['status_aktif' => ! $polis->status_aktif]);

        return back()->with('success', 'Status keaktifan polis berhasil diperbarui.');
    }
}
