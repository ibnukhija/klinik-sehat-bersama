<?php

namespace Database\Seeders;

use App\Models\Asuransi;
use App\Models\Obat;
use App\Models\Tindakan;
use Illuminate\Database\Seeder;

class MasterKlinikSeeder extends Seeder
{
    public function run(): void
    {
        $tindakanList = [
            ['kode_tindakan' => 'TDK-01', 'nama_tindakan' => 'Cek Tekanan Darah & Fisik Lengkap', 'tarif' => 35000],
            ['kode_tindakan' => 'TDK-02', 'nama_tindakan' => 'Pemeriksaan Darah Lengkap', 'tarif' => 120000],
            ['kode_tindakan' => 'TDK-03', 'nama_tindakan' => 'Nebulizer / Terapi Uap', 'tarif' => 85000],
            ['kode_tindakan' => 'TDK-04', 'nama_tindakan' => 'Perawatan & Jahit Luka Ringan', 'tarif' => 150000],
            ['kode_tindakan' => 'TDK-05', 'nama_tindakan' => 'Elektrokardiogram (EKG)', 'tarif' => 180000],
        ];

        foreach ($tindakanList as $tindakan) {
            Tindakan::create($tindakan);
        }

        $obatList = [
            ['kode_obat' => 'OBT-01', 'nama_obat' => 'Paracetamol 500mg', 'stok' => 50, 'harga' => 12000],
            ['kode_obat' => 'OBT-02', 'nama_obat' => 'Amoxicillin 500mg', 'stok' => 30, 'harga' => 25000],
            ['kode_obat' => 'OBT-03', 'nama_obat' => 'Salbutamol Inhaler', 'stok' => 0, 'harga' => 95000],
            ['kode_obat' => 'OBT-04', 'nama_obat' => 'Omeprazole 20mg', 'stok' => 25, 'harga' => 30000],
            ['kode_obat' => 'OBT-05', 'nama_obat' => 'Vitamin C 500mg', 'stok' => 4, 'harga' => 18000],
        ];

        foreach ($obatList as $obat) {
            Obat::create($obat);
        }

        $asuransiList = [
            ['kode' => 'BPJS', 'nama_perusahaan' => 'BPJS Kesehatan'],
            ['kode' => 'PRU', 'nama_perusahaan' => 'Prudential Indonesia'],
            ['kode' => 'ALZ', 'nama_perusahaan' => 'Allianz Life Indonesia'],
        ];

        foreach ($asuransiList as $asuransi) {
            Asuransi::create($asuransi);
        }
    }
}
