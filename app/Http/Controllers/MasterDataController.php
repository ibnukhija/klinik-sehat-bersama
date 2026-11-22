<?php

namespace App\Http\Controllers;

use App\Models\Asuransi;
use App\Models\Dokter;
use App\Models\Obat;
use App\Models\Tindakan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        $dokterList = Dokter::with('jadwalPraktik')->orderBy('nama')->get();
        $tindakanList = Tindakan::orderBy('kode_tindakan')->get();
        $obatList = Obat::orderBy('kode_obat')->get();
        $asuransiList = Asuransi::withCount('polisAsuransi')->orderBy('nama_perusahaan')->get();

        return view('master.index', compact('dokterList', 'tindakanList', 'obatList', 'asuransiList'));
    }

    public function updateStokObat(Request $request, Obat $obat): RedirectResponse
    {
        $data = $request->validate([
            'stok' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $obat->update(['stok' => (int) $data['stok']]);

        return back()->with('success', "Stok obat {$obat->nama_obat} diperbarui menjadi {$obat->stok}.");
    }
}
