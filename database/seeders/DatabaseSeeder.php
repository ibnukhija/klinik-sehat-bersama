<?php

namespace Database\Seeders;

use App\Models\Asuransi;
use App\Models\DetailDiagnosis;
use App\Models\DetailResep;
use App\Models\DetailTindakan;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\Kunjungan;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\PolisAsuransi;
use App\Models\Resep;
use App\Models\Tindakan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Siti Aminah',
            'username' => 'admin',
            'email' => 'admin@klinik.test',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
        ]);

        $kasir = User::create([
            'name' => 'Rina Wulandari',
            'username' => 'kasir',
            'email' => 'kasir@klinik.test',
            'role' => User::ROLE_KASIR,
            'password' => Hash::make('password'),
        ]);

        $uDokter1 = User::create([
            'name' => 'dr. Andi Wijaya, Sp.PD',
            'username' => 'dokter1',
            'email' => 'andi@klinik.test',
            'role' => User::ROLE_DOKTER,
            'password' => Hash::make('password'),
        ]);

        $uDokter2 = User::create([
            'name' => 'dr. Budi Santoso',
            'username' => 'dokter2',
            'email' => 'budi@klinik.test',
            'role' => User::ROLE_DOKTER,
            'password' => Hash::make('password'),
        ]);

        $uDokter3 = User::create([
            'name' => 'dr. Citra Lestari',
            'username' => 'dokter3',
            'email' => 'citra@klinik.test',
            'role' => User::ROLE_DOKTER,
            'password' => Hash::make('password'),
        ]);

        $dokter1 = Dokter::create([
            'user_id' => $uDokter1->id,
            'nama' => 'dr. Andi Wijaya, Sp.PD',
            'spesialisasi' => 'Penyakit Dalam',
            'tarif_pemeriksaan' => 75000,
        ]);

        $dokter2 = Dokter::create([
            'user_id' => $uDokter2->id,
            'nama' => 'dr. Budi Santoso',
            'spesialisasi' => 'Umum',
            'tarif_pemeriksaan' => 50000,
        ]);

        $dokter3 = Dokter::create([
            'user_id' => $uDokter3->id,
            'nama' => 'dr. Citra Lestari',
            'spesialisasi' => 'Umum',
            'tarif_pemeriksaan' => 50000,
        ]);

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        foreach ($hariList as $hari) {
            JadwalPraktik::create([
                'dokter_id' => $dokter1->id,
                'hari' => $hari,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '13:00:00',
            ]);
            JadwalPraktik::create([
                'dokter_id' => $dokter2->id,
                'hari' => $hari,
                'jam_mulai' => '09:00:00',
                'jam_selesai' => '15:00:00',
            ]);
            JadwalPraktik::create([
                'dokter_id' => $dokter3->id,
                'hari' => $hari,
                'jam_mulai' => '13:00:00',
                'jam_selesai' => '19:00:00',
            ]);
        }

        $tdk1 = Tindakan::create([
            'kode_tindakan' => 'TDK-01',
            'nama_tindakan' => 'Cek Tekanan Darah & Fisik Lengkap',
            'tarif' => 35000,
        ]);
        $tdk2 = Tindakan::create([
            'kode_tindakan' => 'TDK-02',
            'nama_tindakan' => 'Pemeriksaan Darah Lengkap',
            'tarif' => 120000,
        ]);
        $tdk3 = Tindakan::create([
            'kode_tindakan' => 'TDK-03',
            'nama_tindakan' => 'Nebulizer / Terapi Uap',
            'tarif' => 85000,
        ]);
        Tindakan::create([
            'kode_tindakan' => 'TDK-04',
            'nama_tindakan' => 'Perawatan & Jahit Luka Ringan',
            'tarif' => 150000,
        ]);
        Tindakan::create([
            'kode_tindakan' => 'TDK-05',
            'nama_tindakan' => 'Elektrokardiogram (EKG)',
            'tarif' => 180000,
        ]);

        $obt1 = Obat::create([
            'kode_obat' => 'OBT-01',
            'nama_obat' => 'Paracetamol 500mg',
            'stok' => 50,
            'harga' => 12000,
        ]);
        $obt2 = Obat::create([
            'kode_obat' => 'OBT-02',
            'nama_obat' => 'Amoxicillin 500mg',
            'stok' => 30,
            'harga' => 25000,
        ]);
        $obt3 = Obat::create([
            'kode_obat' => 'OBT-03',
            'nama_obat' => 'Salbutamol Inhaler',
            'stok' => 0,
            'harga' => 95000,
        ]);
        Obat::create([
            'kode_obat' => 'OBT-04',
            'nama_obat' => 'Omeprazole 20mg',
            'stok' => 25,
            'harga' => 30000,
        ]);
        Obat::create([
            'kode_obat' => 'OBT-05',
            'nama_obat' => 'Vitamin C 500mg',
            'stok' => 4,
            'harga' => 18000,
        ]);

        $bpjs = Asuransi::create([
            'kode' => 'BPJS',
            'nama_perusahaan' => 'BPJS Kesehatan',
        ]);
        $pru = Asuransi::create([
            'kode' => 'PRU',
            'nama_perusahaan' => 'Prudential Indonesia',
        ]);
        $allianz = Asuransi::create([
            'kode' => 'ALZ',
            'nama_perusahaan' => 'Allianz Life Indonesia',
        ]);

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

        // Kunjungan contoh 1: Terdaftar ke dr. Andi (siap untuk demo dokter tidak hadir -> pindah -> periksa -> resep -> kasir)
        Kunjungan::create([
            'pasien_id' => $pasien1->id,
            'dokter_id' => $dokter1->id,
            'tgl_kunjungan' => now()->toDateString(),
            'no_antrian' => 1,
            'status' => Kunjungan::STATUS_TERDAFTAR,
            'biaya_pendaftaran' => 20000,
        ]);

        // Kunjungan contoh 2: Selesai diperiksa dr. Budi, punya 2 diagnosis, 2 tindakan, dan resep campuran tersedia + habis
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
