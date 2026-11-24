<?php

namespace Database\Seeders;

use App\Models\Asuransi;
use App\Models\Pasien;
use App\Models\PolisAsuransi;
use Illuminate\Database\Seeder;

class PasienSeeder extends Seeder
{
    public function run(): void
    {
        $bpjs = Asuransi::where('kode', 'BPJS')->firstOrFail();
        $pru = Asuransi::where('kode', 'PRU')->firstOrFail();
        $allianz = Asuransi::where('kode', 'ALZ')->firstOrFail();

        $pasien1 = Pasien::create([
            'no_identitas' => '3507011205950001',
            'nama' => 'Ahmad Fauzi',
            'tgl_lahir' => '1995-05-12',
            'alamat' => 'Jl. Mayor Bismo No. 14, Kota Kediri',
            'no_telp' => '081234567890',
        ]);

        PolisAsuransi::create([
            'pasien_id' => $pasien1->id,
            'asuransi_id' => $bpjs->id,
            'no_polis' => 'BPJS-00112233',
            'prioritas' => 1,
            'status_aktif' => true,
        ]);

        PolisAsuransi::create([
            'pasien_id' => $pasien1->id,
            'asuransi_id' => $pru->id,
            'no_polis' => 'PRU-88990011',
            'prioritas' => 2,
            'status_aktif' => true,
        ]);

        $pasien2 = Pasien::create([
            'no_identitas' => '3507012008980002',
            'nama' => 'Dewi Kartika',
            'tgl_lahir' => '1998-08-20',
            'alamat' => 'Jl. Dhoho No. 45, Kota Kediri',
            'no_telp' => '081345678901',
        ]);

        PolisAsuransi::create([
            'pasien_id' => $pasien2->id,
            'asuransi_id' => $allianz->id,
            'no_polis' => 'ALZ-55443322',
            'prioritas' => 1,
            'status_aktif' => true,
        ]);

        Pasien::create([
            'no_identitas' => '3507010501900003',
            'nama' => 'Bambang Hermawan',
            'tgl_lahir' => '1990-01-05',
            'alamat' => 'Jl. Brawijaya No. 88, Kota Kediri',
            'no_telp' => '085612345678',
        ]);
    }
}
