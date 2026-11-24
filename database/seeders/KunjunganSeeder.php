<?php

namespace Database\Seeders;

use App\Models\DetailDiagnosis;
use App\Models\DetailResep;
use App\Models\DetailTindakan;
use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Resep;
use App\Models\Tindakan;
use Illuminate\Database\Seeder;

class KunjunganSeeder extends Seeder
{
    public function run(): void
    {
        $pasien1 = Pasien::where('no_identitas', '3507011205950001')->firstOrFail();
        $pasien2 = Pasien::where('no_identitas', '3507012008980002')->firstOrFail();

        $dokter1 = Dokter::whereHas('user', fn ($q) => $q->where('username', 'dokter1'))->firstOrFail();
        $dokter2 = Dokter::whereHas('user', fn ($q) => $q->where('username', 'dokter2'))->firstOrFail();

        $tdk1 = Tindakan::where('kode_tindakan', 'TDK-01')->firstOrFail();
        $tdk3 = Tindakan::where('kode_tindakan', 'TDK-03')->firstOrFail();

        $obt1 = Obat::where('kode_obat', 'OBT-01')->firstOrFail();
        $obt3 = Obat::where('kode_obat', 'OBT-03')->firstOrFail();

        // Kunjungan contoh 1: Terdaftar ke dr. Andi (siap untuk demo alur lengkap)
        Kunjungan::create([
            'pasien_id' => $pasien1->id,
            'dokter_id' => $dokter1->id,
            'tgl_kunjungan' => now()->toDateString(),
            'no_antrian' => 1,
            'status' => Kunjungan::STATUS_TERDAFTAR,
            'biaya_pendaftaran' => 20000,
        ]);

        // Kunjungan contoh 2: Selesai diperiksa dr. Budi dengan multi-diagnosis, multi-tindakan, dan resep campuran
        $kunjungan2 = Kunjungan::create([
            'pasien_id' => $pasien2->id,
            'dokter_id' => $dokter2->id,
            'tgl_kunjungan' => now()->toDateString(),
            'no_antrian' => 1,
            'status' => Kunjungan::STATUS_SELESAI,
            'keluhan' => 'Demam tinggi 3 hari, batuk, dan sesak napas ringan.',
            'hasil_pemeriksaan' => 'Suhu 38.6 C, TD 110/70 mmHg, wheezing ringan pada paru.',
            'catatan_tambahan' => 'Istirahat cukup, kontrol ulang jika demam berlanjut.',
            'biaya_pendaftaran' => 20000,
        ]);

        DetailDiagnosis::create([
            'kunjungan_id' => $kunjungan2->id,
            'nama_diagnosis' => 'ISPA Akut',
        ]);
        DetailDiagnosis::create([
            'kunjungan_id' => $kunjungan2->id,
            'nama_diagnosis' => 'Bronkospasme Ringan',
        ]);

        DetailTindakan::create([
            'kunjungan_id' => $kunjungan2->id,
            'tindakan_id' => $tdk1->id,
            'jumlah' => 1,
            'biaya' => $tdk1->tarif,
        ]);
        DetailTindakan::create([
            'kunjungan_id' => $kunjungan2->id,
            'tindakan_id' => $tdk3->id,
            'jumlah' => 1,
            'biaya' => $tdk3->tarif,
        ]);

        $resep2 = Resep::create([
            'kunjungan_id' => $kunjungan2->id,
            'tanggal' => now()->toDateString(),
            'status' => Resep::STATUS_BELUM_DITEBUS,
        ]);

        DetailResep::create([
            'resep_id' => $resep2->id,
            'obat_id' => $obt1->id,
            'jumlah' => 2,
            'aturan_pakai' => '3x1 sesudah makan',
            'status_stok' => DetailResep::STOK_TERSEDIA,
            'status_tebus' => DetailResep::TEBUS_BELUM,
        ]);
        DetailResep::create([
            'resep_id' => $resep2->id,
            'obat_id' => $obt3->id,
            'jumlah' => 1,
            'aturan_pakai' => '2x semprot saat sesak (tebus apotek luar)',
            'status_stok' => DetailResep::STOK_HABIS,
            'status_tebus' => DetailResep::TEBUS_STOK_HABIS,
        ]);
    }
}
