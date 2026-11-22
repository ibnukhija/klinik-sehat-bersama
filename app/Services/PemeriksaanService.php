<?php

namespace App\Services;

use App\Models\DetailDiagnosis;
use App\Models\DetailResep;
use App\Models\DetailTindakan;
use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\Obat;
use App\Models\Pembayaran;
use App\Models\Resep;
use App\Models\RiwayatDiagnosis;
use App\Models\Tindakan;
use DomainException;
use Illuminate\Support\Facades\DB;

class PemeriksaanService
{
    public function panggilPasien(Kunjungan $kunjungan, Dokter $dokter): Kunjungan
    {
        $this->pastikanDokterBerhak($kunjungan, $dokter);

        if (! in_array($kunjungan->status, [Kunjungan::STATUS_TERDAFTAR, Kunjungan::STATUS_MENUNGGU], true)) {
            throw new DomainException("Pasien berstatus '{$kunjungan->status}' tidak dapat dipanggil.");
        }

        $kunjungan->update(['status' => Kunjungan::STATUS_DIPERIKSA]);

        return $kunjungan->fresh();
    }

    public function simpanCatatanPemeriksaan(Kunjungan $kunjungan, Dokter $dokter, array $data): Kunjungan
    {
        $this->pastikanDokterBerhak($kunjungan, $dokter);
        $this->pastikanTransaksiBelumDitutup($kunjungan);

        $update = [
            'keluhan' => $data['keluhan'] ?? $kunjungan->keluhan,
            'hasil_pemeriksaan' => $data['hasil_pemeriksaan'] ?? $kunjungan->hasil_pemeriksaan,
            'catatan_tambahan' => $data['catatan_tambahan'] ?? $kunjungan->catatan_tambahan,
        ];

        if ($kunjungan->status === Kunjungan::STATUS_TERDAFTAR) {
            $update['status'] = Kunjungan::STATUS_DIPERIKSA;
        }

        $kunjungan->update($update);

        return $kunjungan->fresh();
    }

    public function tambahDiagnosis(
        Kunjungan $kunjungan,
        Dokter $dokter,
        string $namaDiagnosis,
        ?string $alasanUbah = null
    ): DetailDiagnosis {
        $this->pastikanDokterBerhak($kunjungan, $dokter);
        $this->pastikanTransaksiBelumDitutup($kunjungan);

        $pembayaran = $kunjungan->pembayaran;
        if ($pembayaran && blank($alasanUbah)) {
            throw new DomainException('Transaksi pembayaran sudah dibuat. Wajib mengisi alasan penambahan diagnosis.');
        }

        return DB::transaction(function () use ($kunjungan, $dokter, $namaDiagnosis, $alasanUbah, $pembayaran) {
            $detail = DetailDiagnosis::create([
                'kunjungan_id' => $kunjungan->id,
                'nama_diagnosis' => trim($namaDiagnosis),
            ]);

            if ($pembayaran) {
                RiwayatDiagnosis::create([
                    'detail_diagnosis_id' => $detail->id,
                    'kunjungan_id' => $kunjungan->id,
                    'dokter_id' => $dokter->id,
                    'aksi' => 'Tambah',
                    'diagnosis_lama' => null,
                    'diagnosis_baru' => $detail->nama_diagnosis,
                    'alasan_ubah' => trim((string) $alasanUbah),
                    'waktu_ubah' => now(),
                ]);

                $pembayaran->update(['status_transaksi' => Pembayaran::STATUS_PERLU_PENYESUAIAN]);
            }

            return $detail;
        });
    }

    public function ubahDiagnosis(
        DetailDiagnosis $detail,
        Dokter $dokter,
        string $namaDiagnosisBaru,
        string $alasanUbah
    ): DetailDiagnosis {
        $kunjungan = $detail->kunjungan;
        $this->pastikanDokterBerhak($kunjungan, $dokter);
        $this->pastikanTransaksiBelumDitutup($kunjungan);

        return DB::transaction(function () use ($detail, $kunjungan, $dokter, $namaDiagnosisBaru, $alasanUbah) {
            $lama = $detail->nama_diagnosis;
            $baru = trim($namaDiagnosisBaru);

            $detail->update(['nama_diagnosis' => $baru]);

            RiwayatDiagnosis::create([
                'detail_diagnosis_id' => $detail->id,
                'kunjungan_id' => $kunjungan->id,
                'dokter_id' => $dokter->id,
                'aksi' => 'Ubah',
                'diagnosis_lama' => $lama,
                'diagnosis_baru' => $baru,
                'alasan_ubah' => trim($alasanUbah),
                'waktu_ubah' => now(),
            ]);

            if ($kunjungan->pembayaran) {
                $kunjungan->pembayaran->update(['status_transaksi' => Pembayaran::STATUS_PERLU_PENYESUAIAN]);
            }

            return $detail->fresh();
        });
    }

    public function hapusDiagnosis(DetailDiagnosis $detail, Dokter $dokter, ?string $alasanUbah = null): void
    {
        $kunjungan = $detail->kunjungan;
        $this->pastikanDokterBerhak($kunjungan, $dokter);
        $this->pastikanTransaksiBelumDitutup($kunjungan);

        if ($kunjungan->status === Kunjungan::STATUS_SELESAI && $kunjungan->detailDiagnosis()->count() <= 1) {
            throw new DomainException('Kunjungan yang sudah selesai harus memiliki minimal 1 diagnosis.');
        }

        $pembayaran = $kunjungan->pembayaran;
        if ($pembayaran && blank($alasanUbah)) {
            throw new DomainException('Transaksi pembayaran sudah dibuat. Wajib mengisi alasan penghapusan diagnosis.');
        }

        DB::transaction(function () use ($detail, $kunjungan, $dokter, $alasanUbah, $pembayaran) {
            if ($pembayaran) {
                RiwayatDiagnosis::create([
                    'detail_diagnosis_id' => null,
                    'kunjungan_id' => $kunjungan->id,
                    'dokter_id' => $dokter->id,
                    'aksi' => 'Hapus',
                    'diagnosis_lama' => $detail->nama_diagnosis,
                    'diagnosis_baru' => null,
                    'alasan_ubah' => trim((string) $alasanUbah),
                    'waktu_ubah' => now(),
                ]);

                $pembayaran->update(['status_transaksi' => Pembayaran::STATUS_PERLU_PENYESUAIAN]);
            }

            $detail->delete();
        });
    }

    public function tambahTindakan(Kunjungan $kunjungan, Dokter $dokter, int $tindakanId, int $jumlah = 1): DetailTindakan
    {
        $this->pastikanDokterBerhak($kunjungan, $dokter);
        $this->pastikanTransaksiBelumDitutup($kunjungan);

        $tindakan = Tindakan::findOrFail($tindakanId);

        return DB::transaction(function () use ($kunjungan, $tindakan, $jumlah) {
            $detail = DetailTindakan::create([
                'kunjungan_id' => $kunjungan->id,
                'tindakan_id' => $tindakan->id,
                'jumlah' => $jumlah,
                'biaya' => (float) $tindakan->tarif * $jumlah,
            ]);

            if ($kunjungan->pembayaran) {
                $kunjungan->pembayaran->update(['status_transaksi' => Pembayaran::STATUS_PERLU_PENYESUAIAN]);
            }

            return $detail;
        });
    }

    public function hapusTindakan(DetailTindakan $detail, Dokter $dokter): void
    {
        $kunjungan = $detail->kunjungan;
        $this->pastikanDokterBerhak($kunjungan, $dokter);
        $this->pastikanTransaksiBelumDitutup($kunjungan);

        DB::transaction(function () use ($detail, $kunjungan) {
            $detail->delete();

            if ($kunjungan->pembayaran) {
                $kunjungan->pembayaran->update(['status_transaksi' => Pembayaran::STATUS_PERLU_PENYESUAIAN]);
            }
        });
    }

    /**
     * Save or append prescription items. Out-of-stock medications trigger a warning
     * returned alongside the saved Resep, while still persisting the prescription.
     *
     * @param  array<int, array{obat_id:int, jumlah:int, aturan_pakai:string}>  $items
     * @return array{resep: Resep, obat_habis: array<int, string>}
     */
    public function simpanResep(Kunjungan $kunjungan, Dokter $dokter, array $items): array
    {
        $this->pastikanDokterBerhak($kunjungan, $dokter);
        $this->pastikanTransaksiBelumDitutup($kunjungan);

        if (empty($items)) {
            throw new DomainException('Resep harus berisi minimal satu obat.');
        }

        return DB::transaction(function () use ($kunjungan, $items) {
            $resep = Resep::firstOrCreate(
                ['kunjungan_id' => $kunjungan->id],
                [
                    'tanggal' => now()->toDateString(),
                    'status' => Resep::STATUS_BELUM_DITEBUS,
                ]
            );

            $obatHabis = [];

            foreach ($items as $item) {
                $obat = Obat::findOrFail((int) $item['obat_id']);
                $jumlah = max(1, (int) $item['jumlah']);
                $tersedia = $obat->cekStok($jumlah);

                $statusStok = $tersedia ? DetailResep::STOK_TERSEDIA : DetailResep::STOK_HABIS;
                $statusTebus = $tersedia ? DetailResep::TEBUS_BELUM : DetailResep::TEBUS_STOK_HABIS;

                if (! $tersedia) {
                    $obatHabis[] = "{$obat->nama_obat} (stok tersedia: {$obat->stok}, diminta: {$jumlah})";
                }

                DetailResep::create([
                    'resep_id' => $resep->id,
                    'obat_id' => $obat->id,
                    'jumlah' => $jumlah,
                    'aturan_pakai' => trim((string) $item['aturan_pakai']),
                    'status_stok' => $statusStok,
                    'status_tebus' => $statusTebus,
                ]);
            }

            if ($kunjungan->pembayaran) {
                $kunjungan->pembayaran->update(['status_transaksi' => Pembayaran::STATUS_PERLU_PENYESUAIAN]);
            }

            return [
                'resep' => $resep->fresh('detailResep.obat'),
                'obat_habis' => $obatHabis,
            ];
        });
    }

    public function selesaikanPemeriksaan(Kunjungan $kunjungan, Dokter $dokter): Kunjungan
    {
        $this->pastikanDokterBerhak($kunjungan, $dokter);

        if ($kunjungan->detailDiagnosis()->count() === 0) {
            throw new DomainException('Catat minimal 1 diagnosis sebelum menyelesaikan pemeriksaan.');
        }

        if ($kunjungan->status === Kunjungan::STATUS_DIBATALKAN) {
            throw new DomainException('Kunjungan yang sudah dibatalkan tidak dapat diselesaikan.');
        }

        $kunjungan->update(['status' => Kunjungan::STATUS_SELESAI]);

        return $kunjungan->fresh();
    }

    private function pastikanDokterBerhak(Kunjungan $kunjungan, Dokter $dokter): void
    {
        if ($kunjungan->dokter_id !== $dokter->id) {
            throw new DomainException('Kunjungan ini terdaftar pada dokter lain.');
        }
    }

    private function pastikanTransaksiBelumDitutup(Kunjungan $kunjungan): void
    {
        if ($kunjungan->pembayaran?->status_transaksi === Pembayaran::STATUS_DITUTUP) {
            throw new DomainException('Transaksi pembayaran sudah Ditutup; data pemeriksaan dan diagnosis tidak dapat diubah langsung.');
        }
    }
}
