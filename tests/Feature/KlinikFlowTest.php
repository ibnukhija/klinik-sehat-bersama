<?php

namespace Tests\Feature;

use App\Models\DetailResep;
use App\Models\Dokter;
use App\Models\ItemBiaya;
use App\Models\KehadiranDokter;
use App\Models\KlaimAsuransi;
use App\Models\Kunjungan;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Pembayaran;
use App\Models\Resep;
use App\Models\Tindakan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KlinikFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_role_access_control_is_enforced(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $dokter = User::where('username', 'dokter1')->firstOrFail();
        $kasir = User::where('username', 'kasir')->firstOrFail();

        $this->actingAs($admin)->get('/kehadiran-dokter')->assertOk();
        $this->actingAs($admin)->get('/pemeriksaan')->assertForbidden();
        $this->actingAs($admin)->get('/kasir')->assertForbidden();

        $this->actingAs($dokter)->get('/pemeriksaan')->assertOk();
        $this->actingAs($dokter)->get('/kehadiran-dokter')->assertForbidden();
        $this->actingAs($dokter)->get('/kasir')->assertForbidden();

        $this->actingAs($kasir)->get('/kasir')->assertOk();
        $this->actingAs($kasir)->get('/kehadiran-dokter')->assertForbidden();
        $this->actingAs($kasir)->get('/pemeriksaan')->assertForbidden();
    }

    public function test_pendaftaran_pasien_baru_dan_pembatalan_kunjungan(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $dokter = Dokter::firstOrFail();
        $tgl = now()->addDay()->toDateString();

        $this->actingAs($admin)->post('/kunjungan', [
            'mode_pasien' => 'baru',
            'no_identitas' => '3507019999990009',
            'nama' => 'Rudi Hartono',
            'tgl_lahir' => '1992-03-15',
            'alamat' => 'Jl. Sudirman No. 10, Kediri',
            'no_telp' => '081900001111',
            'dokter_id' => $dokter->id,
            'tgl_kunjungan' => $tgl,
            'biaya_pendaftaran' => 20000,
        ])->assertRedirect();

        $pasien = Pasien::where('no_identitas', '3507019999990009')->firstOrFail();
        $kunjungan = Kunjungan::where('pasien_id', $pasien->id)->firstOrFail();
        $this->assertSame(Kunjungan::STATUS_TERDAFTAR, $kunjungan->status);

        // Kondisi #5: Batalkan setelah terdaftar
        $this->actingAs($admin)->post("/kunjungan/{$kunjungan->id}/batalkan")
            ->assertSessionHas('success');

        $this->assertSame(Kunjungan::STATUS_DIBATALKAN, $kunjungan->fresh()->status);
    }

    public function test_skenario_lengkap_7_kondisi_khusus_sampai_tutup_transaksi(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $uDokter2 = User::where('username', 'dokter2')->firstOrFail();
        $kasir = User::where('username', 'kasir')->firstOrFail();

        $dokter1 = Dokter::where('nama', 'like', '%Andi%')->firstOrFail();
        $dokter2 = Dokter::where('nama', 'like', '%Budi%')->firstOrFail();
        $pasien1 = Pasien::where('no_identitas', '3507011205950001')->firstOrFail(); // Punya 2 polis aktif
        $kunjungan = Kunjungan::where('pasien_id', $pasien1->id)->firstOrFail();

        // Kondisi #1: Dokter tidak hadir -> pindahkan pasien ke dokter pengganti & catat pemindahan
        $this->actingAs($admin)->post("/kehadiran-dokter/{$dokter1->id}", [
            'tanggal' => $kunjungan->tgl_kunjungan->toDateString(),
            'status' => KehadiranDokter::STATUS_TIDAK_HADIR,
            'catatan' => 'Sakit mendadak',
        ])->assertSessionHas('success');

        $this->actingAs($admin)->post("/kunjungan/{$kunjungan->id}/pindah-dokter", [
            'dokter_tujuan_id' => $dokter2->id,
            'alasan' => 'Dokter asal tidak hadir',
        ])->assertSessionHas('success');

        $kunjungan->refresh();
        $this->assertSame($dokter2->id, $kunjungan->dokter_id);
        $this->assertDatabaseHas('pemindahan_dokter', [
            'kunjungan_id' => $kunjungan->id,
            'dokter_asal_id' => $dokter1->id,
            'dokter_tujuan_id' => $dokter2->id,
        ]);

        // Dokter pengganti memeriksa pasien: panggil, catatan, >1 diagnosis, >1 tindakan
        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/panggil")->assertSessionHas('success');
        $this->assertSame(Kunjungan::STATUS_DIPERIKSA, $kunjungan->fresh()->status);

        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/catatan", [
            'keluhan' => 'Demam dan sesak',
            'hasil_pemeriksaan' => 'Suhu 38.5C',
            'catatan_tambahan' => 'Observasi',
        ])->assertSessionHas('success');

        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/diagnosis", [
            'nama_diagnosis' => 'Faringitis Akut',
        ])->assertSessionHas('success');
        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/diagnosis", [
            'nama_diagnosis' => 'Asma Ringan',
        ])->assertSessionHas('success');

        $tdk1 = Tindakan::where('kode_tindakan', 'TDK-01')->firstOrFail(); // 35.000
        $tdk2 = Tindakan::where('kode_tindakan', 'TDK-02')->firstOrFail(); // 120.000
        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/tindakan", ['tindakan_id' => $tdk1->id, 'jumlah' => 1]);
        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/tindakan", ['tindakan_id' => $tdk2->id, 'jumlah' => 1]);

        // Kondisi #2: Resep berisi obat tersedia + obat stok habis -> warning tapi tetap tersimpan
        $paracetamol = Obat::where('kode_obat', 'OBT-01')->firstOrFail(); // stok 50, harga 12.000
        $salbutamol = Obat::where('kode_obat', 'OBT-03')->firstOrFail(); // stok 0, harga 95.000
        $stokAwalParacetamol = $paracetamol->stok;

        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/resep", [
            'items' => [
                ['obat_id' => $paracetamol->id, 'jumlah' => 2, 'aturan_pakai' => '3x1 sesudah makan'],
                ['obat_id' => $salbutamol->id, 'jumlah' => 1, 'aturan_pakai' => 'Saat sesak'],
            ],
        ])->assertSessionHas('warning');

        $resep = Resep::where('kunjungan_id', $kunjungan->id)->firstOrFail();
        $this->assertSame(Resep::STATUS_BELUM_DITEBUS, $resep->status);

        $this->actingAs($uDokter2)->post("/pemeriksaan/{$kunjungan->id}/selesai")->assertSessionHas('success');
        $this->assertSame(Kunjungan::STATUS_SELESAI, $kunjungan->fresh()->status);

        // Kasir buka transaksi (membuat Pembayaran & ItemBiaya)
        $this->actingAs($kasir)->get("/kasir/kunjungan/{$kunjungan->id}")->assertOk();
        $pembayaran = Pembayaran::where('kunjungan_id', $kunjungan->id)->firstOrFail();
        $this->assertSame(Pembayaran::STATUS_TERBUKA, $pembayaran->status_transaksi);

        // Kondisi #6: Dokter mengubah diagnosis setelah transaksi pembayaran dibuat -> log + Perlu Penyesuaian
        $diagPertama = $kunjungan->detailDiagnosis()->firstOrFail();
        $this->actingAs($uDokter2)->put("/pemeriksaan/diagnosis/{$diagPertama->id}", [
            'nama_diagnosis' => 'Tonsilofaringitis Akut',
            'alasan_ubah' => 'Hasil pemeriksaan tenggorokan lebih spesifik',
        ])->assertSessionHas('success');

        $this->assertSame(Pembayaran::STATUS_PERLU_PENYESUAIAN, $pembayaran->fresh()->status_transaksi);
        $this->assertDatabaseHas('riwayat_diagnosis', [
            'kunjungan_id' => $kunjungan->id,
            'diagnosis_lama' => 'Faringitis Akut',
            'diagnosis_baru' => 'Tonsilofaringitis Akut',
        ]);

        // Kasir tidak bisa tutup saat Perlu Penyesuaian, lalu konfirmasi peninjauan
        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/tutup")->assertSessionHas('error');
        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/konfirmasi-penyesuaian")->assertSessionHas('success');
        $this->assertSame(Pembayaran::STATUS_TERBUKA, $pembayaran->fresh()->status_transaksi);

        // Kondisi #7: Belum bisa tutup selama obat tersedia berstatus Belum Ditebus
        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/tutup")->assertSessionHas('error');

        $detailParacetamol = DetailResep::where('resep_id', $resep->id)
            ->where('obat_id', $paracetamol->id)
            ->firstOrFail();

        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/konfirmasi-resep", [
            'keputusan' => [
                $detailParacetamol->id => DetailResep::TEBUS_DITEBUS,
            ],
        ])->assertSessionHas('success');

        // Kondisi #3 & #4: Klaim Polis Prioritas 1 (BPJS) menanggung sebagian tindakan saja
        $polis1 = $pasien1->polisAsuransi()->where('prioritas', 1)->firstOrFail();
        $polis2 = $pasien1->polisAsuransi()->where('prioritas', 2)->firstOrFail();

        $tagihanItems = $pembayaran->itemBiaya()
            ->whereNotIn('status_bayar', [ItemBiaya::STATUS_TIDAK_DITAGIHKAN])
            ->get();

        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/klaim", [
            'polis_asuransi_id' => $polis1->id,
            'item_biaya_ids' => $tagihanItems->pluck('id')->all(),
        ])->assertSessionHas('success');

        $klaim1 = KlaimAsuransi::where('pembayaran_id', $pembayaran->id)->where('polis_asuransi_id', $polis1->id)->firstOrFail();
        $nominalParsial1 = [];
        $itemTindakan2 = null;
        $itemObat = null;
        foreach ($klaim1->klaimItem as $ki) {
            if (str_contains($ki->itemBiaya->deskripsi, 'Darah Lengkap')) {
                $nominalParsial1[$ki->id] = 0; // ditolak BPJS
                $itemTindakan2 = $ki->itemBiaya;
            } elseif ($ki->itemBiaya->jenis === ItemBiaya::JENIS_OBAT) {
                $nominalParsial1[$ki->id] = 0; // ditolak BPJS
                $itemObat = $ki->itemBiaya;
            } else {
                $nominalParsial1[$ki->id] = (float) $ki->jumlah_diajukan; // disetujui BPJS
            }
        }

        $this->actingAs($kasir)->post("/kasir/klaim/{$klaim1->id}/keputusan", [
            'mode' => 'parsial',
            'nominal_item' => $nominalParsial1,
        ])->assertSessionHas('success');

        $this->assertSame(KlaimAsuransi::STATUS_DISETUJUI_SEBAGIAN, $klaim1->fresh()->status);

        // Ajukan sisa item yang belum ditanggung ke Polis Prioritas 2 (Prudential), lalu disimulasikan DITOLAK
        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/klaim", [
            'polis_asuransi_id' => $polis2->id,
            'item_biaya_ids' => [$itemTindakan2->id, $itemObat->id],
        ])->assertSessionHas('success');

        $klaim2 = KlaimAsuransi::where('pembayaran_id', $pembayaran->id)->where('polis_asuransi_id', $polis2->id)->firstOrFail();
        $this->actingAs($kasir)->post("/kasir/klaim/{$klaim2->id}/keputusan", [
            'mode' => 'tolak',
        ])->assertSessionHas('success');

        $this->assertSame(KlaimAsuransi::STATUS_DITOLAK, $klaim2->fresh()->status);
        $this->assertSame(ItemBiaya::STATUS_BELUM_LUNAS, $itemTindakan2->fresh()->status_bayar);
        $this->assertSame(ItemBiaya::STATUS_BELUM_LUNAS, $itemObat->fresh()->status_bayar);

        // Belum boleh tutup karena masih ada item Belum Lunas
        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/tutup")->assertSessionHas('error');

        // Bayar tunai sisa tagihan pasien (120.000 + 24.000 = 144.000)
        $sisa = $pembayaran->fresh()->sisaTagihanPasien();
        $this->assertEquals(144000.0, $sisa);

        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/bayar-tunai", [
            'jumlah' => $sisa,
            'catatan' => 'Pelunasan sisa setelah klaim ditolak',
        ])->assertSessionHas('success');

        // Stok Paracetamol berkurang & status resep menjadi Sebagian Ditebus (karena Salbutamol stok habis)
        $this->assertSame($stokAwalParacetamol - 2, $paracetamol->fresh()->stok);
        $this->assertSame(Resep::STATUS_SEBAGIAN_DITEBUS, $resep->fresh()->status);

        // Sekarang semua biaya jelas -> transaksi berhasil ditutup!
        $this->actingAs($kasir)->post("/kasir/pembayaran/{$pembayaran->id}/tutup")->assertSessionHas('success');
        $this->assertSame(Pembayaran::STATUS_DITUTUP, $pembayaran->fresh()->status_transaksi);

        // Dokter tidak boleh mengubah diagnosis lagi setelah transaksi Ditutup
        $this->actingAs($uDokter2)->put("/pemeriksaan/diagnosis/{$diagPertama->id}", [
            'nama_diagnosis' => 'Ubah Ilegal',
            'alasan_ubah' => 'Test kunci',
        ])->assertSessionHas('error');
        $this->assertSame('Tonsilofaringitis Akut', $diagPertama->fresh()->nama_diagnosis);
    }

    public function test_semua_halaman_utama_dapat_dirender(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $dokter1 = User::where('username', 'dokter1')->firstOrFail();
        $dokter2 = User::where('username', 'dokter2')->firstOrFail();
        $kasir = User::where('username', 'kasir')->firstOrFail();

        $pasien = Pasien::firstOrFail();
        $kunjunganTerdaftar = Kunjungan::where('status', Kunjungan::STATUS_TERDAFTAR)->firstOrFail();
        $kunjunganSelesai = Kunjungan::where('status', Kunjungan::STATUS_SELESAI)->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get('/pasien')->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get('/pasien/create')->assertOk()->assertSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get("/pasien/{$pasien->id}")->assertOk()->assertSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get('/kunjungan')->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get('/kunjungan/create?no_identitas=' . $pasien->no_identitas)->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get("/kunjungan/{$kunjunganTerdaftar->id}")->assertOk()->assertSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get('/kehadiran-dokter')->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');
        $this->actingAs($admin)->get('/master-data')->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');

        $this->actingAs($dokter1)->get('/pemeriksaan')->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');
        $this->actingAs($dokter1)->get("/pemeriksaan/{$kunjunganTerdaftar->id}")->assertOk()->assertSee('Kembali ke halaman sebelumnya');
        $this->actingAs($dokter2)->get("/pemeriksaan/{$kunjunganSelesai->id}")->assertOk()->assertSee('Kembali ke halaman sebelumnya');

        $this->actingAs($kasir)->get('/kasir')->assertOk()->assertDontSee('Kembali ke halaman sebelumnya');
        $this->actingAs($kasir)->get("/kasir/kunjungan/{$kunjunganSelesai->id}")->assertOk()->assertSee('Kembali ke halaman sebelumnya');
    }
}
