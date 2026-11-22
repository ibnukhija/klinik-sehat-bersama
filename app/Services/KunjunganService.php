<?php

namespace App\Services;

use App\Models\Dokter;
use App\Models\KehadiranDokter;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemindahanDokter;
use DomainException;
use Illuminate\Support\Facades\DB;

class KunjunganService
{
    public function daftarkanKunjungan(
        Pasien $pasien,
        int $dokterId,
        string $tglKunjungan,
        float $biayaPendaftaran = 20000
    ): Kunjungan {
        $dokter = Dokter::findOrFail($dokterId);

        if (! $dokter->punyaJadwalPada($tglKunjungan)) {
            $hari = Dokter::hariIndonesia($tglKunjungan);
            throw new DomainException("{$dokter->nama} tidak memiliki jadwal praktik pada hari {$hari}.");
        }

        if ($dokter->statusKehadiranPada($tglKunjungan) === KehadiranDokter::STATUS_TIDAK_HADIR) {
            throw new DomainException("{$dokter->nama} berstatus Tidak Hadir pada tanggal {$tglKunjungan}. Silakan pilih dokter lain yang tersedia.");
        }

        return DB::transaction(function () use ($pasien, $dokter, $tglKunjungan, $biayaPendaftaran) {
            $nextAntrian = $this->hitungAntrianBerikutnya($dokter->id, $tglKunjungan);

            return Kunjungan::create([
                'pasien_id' => $pasien->id,
                'dokter_id' => $dokter->id,
                'tgl_kunjungan' => $tglKunjungan,
                'no_antrian' => $nextAntrian,
                'status' => Kunjungan::STATUS_TERDAFTAR,
                'biaya_pendaftaran' => $biayaPendaftaran,
            ]);
        });
    }

    public function batalkanKunjungan(Kunjungan $kunjungan): Kunjungan
    {
        if (! $kunjungan->bisaDibatalkan()) {
            throw new DomainException("Kunjungan berstatus '{$kunjungan->status}' tidak dapat dibatalkan.");
        }

        $kunjungan->update(['status' => Kunjungan::STATUS_DIBATALKAN]);

        return $kunjungan->fresh();
    }

    public function ubahKehadiranDokter(
        Dokter $dokter,
        string $tanggal,
        string $status,
        ?string $catatan = null
    ): KehadiranDokter {
        return KehadiranDokter::updateOrCreate(
            [
                'dokter_id' => $dokter->id,
                'tanggal' => $tanggal,
            ],
            [
                'status' => $status,
                'catatan' => $catatan,
            ]
        );
    }

    public function pindahkanKunjungan(
        Kunjungan $kunjungan,
        int $dokterTujuanId,
        int $petugasId,
        string $alasan
    ): PemindahanDokter {
        if (! $kunjungan->bisaDibatalkan()) {
            throw new DomainException("Kunjungan berstatus '{$kunjungan->status}' tidak dapat dipindahkan.");
        }

        if ($kunjungan->dokter_id === $dokterTujuanId) {
            throw new DomainException('Dokter pengganti harus berbeda dari dokter asal.');
        }

        $dokterTujuan = Dokter::findOrFail($dokterTujuanId);
        $tgl = $kunjungan->tgl_kunjungan->toDateString();

        if (! $dokterTujuan->punyaJadwalPada($tgl)) {
            $hari = Dokter::hariIndonesia($tgl);
            throw new DomainException("{$dokterTujuan->nama} tidak memiliki jadwal praktik pada hari {$hari}.");
        }

        if ($dokterTujuan->statusKehadiranPada($tgl) === KehadiranDokter::STATUS_TIDAK_HADIR) {
            throw new DomainException("{$dokterTujuan->nama} juga berstatus Tidak Hadir pada tanggal {$tgl}.");
        }

        return DB::transaction(function () use ($kunjungan, $dokterTujuan, $tgl, $petugasId, $alasan) {
            $dokterAsalId = $kunjungan->dokter_id;
            $nextAntrian = $this->hitungAntrianBerikutnya($dokterTujuan->id, $tgl);

            $kunjungan->update([
                'dokter_id' => $dokterTujuan->id,
                'no_antrian' => $nextAntrian,
                'status' => Kunjungan::STATUS_TERDAFTAR,
            ]);

            return PemindahanDokter::create([
                'kunjungan_id' => $kunjungan->id,
                'dokter_asal_id' => $dokterAsalId,
                'dokter_tujuan_id' => $dokterTujuan->id,
                'petugas_id' => $petugasId,
                'waktu' => now(),
                'alasan' => $alasan,
            ]);
        });
    }

    public function tandaiMenunggu(Kunjungan $kunjungan): Kunjungan
    {
        if (! $kunjungan->bisaDibatalkan()) {
            throw new DomainException("Kunjungan berstatus '{$kunjungan->status}' tidak dapat diubah menjadi Menunggu.");
        }

        $kunjungan->update(['status' => Kunjungan::STATUS_MENUNGGU]);

        return $kunjungan->fresh();
    }

    public function jadwalkanUlang(Kunjungan $kunjungan, int $dokterId, string $tglBaru): Kunjungan
    {
        if (! $kunjungan->bisaDibatalkan()) {
            throw new DomainException("Kunjungan berstatus '{$kunjungan->status}' tidak dapat dijadwalkan ulang.");
        }

        $dokter = Dokter::findOrFail($dokterId);

        if (! $dokter->punyaJadwalPada($tglBaru)) {
            $hari = Dokter::hariIndonesia($tglBaru);
            throw new DomainException("{$dokter->nama} tidak memiliki jadwal praktik pada hari {$hari}.");
        }

        if ($dokter->statusKehadiranPada($tglBaru) === KehadiranDokter::STATUS_TIDAK_HADIR) {
            throw new DomainException("{$dokter->nama} berstatus Tidak Hadir pada tanggal {$tglBaru}.");
        }

        return DB::transaction(function () use ($kunjungan, $dokter, $tglBaru) {
            $nextAntrian = $this->hitungAntrianBerikutnya($dokter->id, $tglBaru);

            $kunjungan->update([
                'dokter_id' => $dokter->id,
                'tgl_kunjungan' => $tglBaru,
                'no_antrian' => $nextAntrian,
                'status' => Kunjungan::STATUS_TERDAFTAR,
            ]);

            return $kunjungan->fresh();
        });
    }

    private function hitungAntrianBerikutnya(int $dokterId, string $tanggal): int
    {
        $max = Kunjungan::query()
            ->where('dokter_id', $dokterId)
            ->whereDate('tgl_kunjungan', $tanggal)
            ->max('no_antrian');

        return ((int) $max) + 1;
    }
}
