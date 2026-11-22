<?php

namespace App\Services;

use App\Models\DetailResep;
use App\Models\DetailTindakan;
use App\Models\ItemBiaya;
use App\Models\KlaimAsuransi;
use App\Models\KlaimItem;
use App\Models\Kunjungan;
use App\Models\Pembayaran;
use App\Models\PembayaranPasien;
use App\Models\PolisAsuransi;
use App\Models\Resep;
use DomainException;
use Illuminate\Support\Facades\DB;

class PembayaranService
{
    public function bukaAtauSinkronkan(Kunjungan $kunjungan): Pembayaran
    {
        if ($kunjungan->status !== Kunjungan::STATUS_SELESAI) {
            throw new DomainException("Transaksi pembayaran hanya dapat dibuka untuk kunjungan berstatus Selesai (saat ini: {$kunjungan->status}).");
        }

        return DB::transaction(function () use ($kunjungan) {
            $kunjungan->load(['dokter', 'detailTindakan.tindakan', 'resep.detailResep.obat']);

            $pembayaran = Pembayaran::firstOrCreate(
                ['kunjungan_id' => $kunjungan->id],
                [
                    'tanggal' => now()->toDateString(),
                    'total_biaya' => 0,
                    'status_transaksi' => Pembayaran::STATUS_TERBUKA,
                ]
            );

            if ($pembayaran->status_transaksi === Pembayaran::STATUS_DITUTUP) {
                return $pembayaran;
            }

            $this->sinkronkanItemBiaya($pembayaran, $kunjungan);

            return $pembayaran->fresh([
                'itemBiaya',
                'pembayaranPasien',
                'klaimAsuransi.polisAsuransi.asuransi',
                'klaimAsuransi.klaimItem.itemBiaya',
            ]);
        });
    }

    public function konfirmasiPenebusanResep(Pembayaran $pembayaran, array $keputusanPerDetailId): Pembayaran
    {
        $this->pastikanBelumDitutup($pembayaran);

        return DB::transaction(function () use ($pembayaran, $keputusanPerDetailId) {
            $kunjungan = $pembayaran->kunjungan()->with('resep.detailResep.obat')->firstOrFail();
            $resep = $kunjungan->resep;

            if (! $resep) {
                throw new DomainException('Kunjungan ini tidak memiliki resep.');
            }

            foreach ($resep->detailResep as $detailResep) {
                if ($detailResep->status_stok === DetailResep::STOK_HABIS) {
                    continue;
                }

                $keputusan = $keputusanPerDetailId[$detailResep->id] ?? null;
                if (! in_array($keputusan, [DetailResep::TEBUS_DITEBUS, DetailResep::TEBUS_TIDAK], true)) {
                    continue;
                }

                $itemBiaya = ItemBiaya::where('pembayaran_id', $pembayaran->id)
                    ->where('referensi_type', 'detail_resep')
                    ->where('referensi_id', $detailResep->id)
                    ->first();

                if ($itemBiaya && $itemBiaya->status_bayar === ItemBiaya::STATUS_LUNAS && $keputusan === DetailResep::TEBUS_TIDAK) {
                    throw new DomainException("Obat {$detailResep->obat->nama_obat} sudah lunas dan tidak dapat dibatalkan penebusannya.");
                }

                $detailResep->update(['status_tebus' => $keputusan]);
            }

            $this->sinkronkanItemBiaya($pembayaran, $kunjungan);
            $this->perbaruiStatusResep($resep);

            return $pembayaran->fresh(['itemBiaya', 'kunjungan.resep.detailResep.obat']);
        });
    }

    /**
     * @param  array<int, int|string>  $itemBiayaIds
     */
    public function ajukanKlaimAsuransi(Pembayaran $pembayaran, int $polisAsuransiId, array $itemBiayaIds): KlaimAsuransi
    {
        $this->pastikanBelumDitutup($pembayaran);
        $this->pastikanBukanPerluPenyesuaian($pembayaran);

        $polis = PolisAsuransi::with('asuransi')->findOrFail($polisAsuransiId);
        if ($polis->pasien_id !== $pembayaran->kunjungan->pasien_id || ! $polis->status_aktif) {
            throw new DomainException('Polis asuransi tidak valid atau tidak aktif untuk pasien ini.');
        }

        if (empty($itemBiayaIds)) {
            throw new DomainException('Pilih minimal satu item biaya untuk diajukan klaim.');
        }

        return DB::transaction(function () use ($pembayaran, $polis, $itemBiayaIds) {
            $items = ItemBiaya::where('pembayaran_id', $pembayaran->id)
                ->whereIn('id', $itemBiayaIds)
                ->get();

            if ($items->isEmpty()) {
                throw new DomainException('Item biaya yang dipilih tidak ditemukan.');
            }

            $totalDiajukan = 0.0;
            foreach ($items as $item) {
                if ($item->status_bayar === ItemBiaya::STATUS_BELUM_DITEBUS) {
                    throw new DomainException("Item '{$item->deskripsi}' belum dikonfirmasi penebusannya. Konfirmasi resep terlebih dahulu.");
                }
                if ($item->status_bayar === ItemBiaya::STATUS_TIDAK_DITAGIHKAN) {
                    throw new DomainException("Item '{$item->deskripsi}' berstatus Tidak Ditagihkan dan tidak dapat diklaim.");
                }
                if ($item->sisaBelumDitanggungAsuransi() <= 0) {
                    throw new DomainException("Item '{$item->deskripsi}' sudah ditanggung penuh oleh asuransi.");
                }
                $totalDiajukan += $item->sisaBelumDitanggungAsuransi();
            }

            $klaim = KlaimAsuransi::create([
                'pembayaran_id' => $pembayaran->id,
                'polis_asuransi_id' => $polis->id,
                'tanggal_klaim' => now()->toDateString(),
                'status' => KlaimAsuransi::STATUS_DIAJUKAN,
                'jumlah_diajukan' => $totalDiajukan,
                'jumlah_disetujui' => 0,
            ]);

            foreach ($items as $item) {
                KlaimItem::create([
                    'klaim_asuransi_id' => $klaim->id,
                    'item_biaya_id' => $item->id,
                    'jumlah_diajukan' => $item->sisaBelumDitanggungAsuransi(),
                    'jumlah_ditanggung' => 0,
                    'status_item' => KlaimItem::STATUS_DIAJUKAN,
                ]);

                $item->update(['status_bayar' => ItemBiaya::STATUS_MENUNGGU_KLAIM]);
            }

            $pembayaran->update(['status_transaksi' => Pembayaran::STATUS_MENUNGGU_KLAIM]);

            return $klaim->fresh(['klaimItem.itemBiaya', 'polisAsuransi.asuransi']);
        });
    }

    /**
     * Simulate external insurance company decision per claim item.
     *
     * @param  array<int, float|int|string>  $nominalPerKlaimItem
     */
    public function prosesKeputusanKlaim(
        KlaimAsuransi $klaim,
        string $mode,
        array $nominalPerKlaimItem = [],
        ?string $catatan = null
    ): KlaimAsuransi {
        if ($klaim->status !== KlaimAsuransi::STATUS_DIAJUKAN) {
            throw new DomainException('Klaim ini sudah diproses sebelumnya.');
        }

        $pembayaran = $klaim->pembayaran;
        $this->pastikanBelumDitutup($pembayaran);

        return DB::transaction(function () use ($klaim, $pembayaran, $mode, $nominalPerKlaimItem, $catatan) {
            $klaim->load('klaimItem.itemBiaya');
            $totalDisetujui = 0.0;
            $adaDitolak = false;
            $adaDisetujui = false;

            foreach ($klaim->klaimItem as $ki) {
                $maks = (float) $ki->jumlah_diajukan;

                if ($mode === 'setujui_penuh') {
                    $disetujui = $maks;
                } elseif ($mode === 'tolak') {
                    $disetujui = 0.0;
                } else {
                    $raw = (float) ($nominalPerKlaimItem[$ki->id] ?? 0);
                    $disetujui = max(0.0, min($maks, $raw));
                }

                $statusItem = $disetujui > 0 ? KlaimItem::STATUS_DISETUJUI : KlaimItem::STATUS_DITOLAK;
                if ($disetujui > 0) {
                    $adaDisetujui = true;
                }
                if ($disetujui < $maks) {
                    $adaDitolak = true;
                }

                $ki->update([
                    'jumlah_ditanggung' => $disetujui,
                    'status_item' => $statusItem,
                ]);

                $totalDisetujui += $disetujui;
            }

            if (! $adaDisetujui) {
                $statusKlaim = KlaimAsuransi::STATUS_DITOLAK;
            } elseif ($adaDitolak) {
                $statusKlaim = KlaimAsuransi::STATUS_DISETUJUI_SEBAGIAN;
            } else {
                $statusKlaim = KlaimAsuransi::STATUS_DISETUJUI;
            }

            $klaim->update([
                'status' => $statusKlaim,
                'jumlah_disetujui' => $totalDisetujui,
                'catatan_keputusan' => $catatan,
            ]);

            $this->rekalkulasiPembayaran($pembayaran);

            return $klaim->fresh(['klaimItem.itemBiaya', 'polisAsuransi.asuransi']);
        });
    }

    public function terimaPembayaranTunai(Pembayaran $pembayaran, float $jumlah, ?string $catatan = null): PembayaranPasien
    {
        $this->pastikanBelumDitutup($pembayaran);
        $this->pastikanBukanPerluPenyesuaian($pembayaran);

        if ($pembayaran->itemBiaya()->where('status_bayar', ItemBiaya::STATUS_BELUM_DITEBUS)->exists()) {
            throw new DomainException('Konfirmasi penebusan resep terlebih dahulu sebelum memproses pembayaran tunai.');
        }

        if ($pembayaran->klaimAsuransi()->where('status', KlaimAsuransi::STATUS_DIAJUKAN)->exists()) {
            throw new DomainException('Masih ada klaim asuransi yang menunggu keputusan. Proses keputusan klaim terlebih dahulu.');
        }

        $sisa = $pembayaran->sisaTagihanPasien();
        if ($sisa <= 0) {
            throw new DomainException('Tidak ada sisa tagihan yang perlu dibayar pasien.');
        }

        if ($jumlah <= 0 || $jumlah > $sisa) {
            throw new DomainException('Nominal pembayaran harus lebih dari 0 dan tidak melebihi sisa tagihan pasien (Rp '.number_format($sisa, 0, ',', '.').').');
        }

        return DB::transaction(function () use ($pembayaran, $jumlah, $catatan) {
            $bayar = PembayaranPasien::create([
                'pembayaran_id' => $pembayaran->id,
                'metode' => 'Tunai',
                'jumlah' => $jumlah,
                'tanggal' => now(),
                'catatan' => $catatan,
            ]);

            $this->rekalkulasiPembayaran($pembayaran);

            return $bayar;
        });
    }

    public function konfirmasiPenyesuaian(Pembayaran $pembayaran): Pembayaran
    {
        $this->pastikanBelumDitutup($pembayaran);

        return DB::transaction(function () use ($pembayaran) {
            $kunjungan = $pembayaran->kunjungan()->with(['dokter', 'detailTindakan.tindakan', 'resep.detailResep.obat'])->firstOrFail();
            $this->sinkronkanItemBiaya($pembayaran, $kunjungan);

            $adaPendingKlaim = $pembayaran->klaimAsuransi()->where('status', KlaimAsuransi::STATUS_DIAJUKAN)->exists();
            $pembayaran->update([
                'status_transaksi' => $adaPendingKlaim ? Pembayaran::STATUS_MENUNGGU_KLAIM : Pembayaran::STATUS_TERBUKA,
            ]);

            $this->rekalkulasiPembayaran($pembayaran);

            return $pembayaran->fresh('itemBiaya');
        });
    }

    public function tutupTransaksi(Pembayaran $pembayaran, int $kasirId): Pembayaran
    {
        $this->pastikanBelumDitutup($pembayaran);

        if ($pembayaran->status_transaksi === Pembayaran::STATUS_PERLU_PENYESUAIAN) {
            throw new DomainException('Transaksi berstatus Perlu Penyesuaian setelah perubahan diagnosis/tindakan. Tinjau ulang tagihan terlebih dahulu.');
        }

        if ($pembayaran->klaimAsuransi()->where('status', KlaimAsuransi::STATUS_DIAJUKAN)->exists()) {
            throw new DomainException('Tidak dapat menutup transaksi: masih ada klaim asuransi yang belum mendapat keputusan.');
        }

        $itemBelumJelas = $pembayaran->itemBiaya()
            ->whereNotIn('status_bayar', [ItemBiaya::STATUS_LUNAS, ItemBiaya::STATUS_TIDAK_DITAGIHKAN])
            ->pluck('deskripsi')
            ->all();

        if (! empty($itemBelumJelas)) {
            throw new DomainException('Tidak dapat menutup transaksi. Item belum memiliki status jelas/lunas: '.implode(', ', $itemBelumJelas).'.');
        }

        $pembayaran->update([
            'status_transaksi' => Pembayaran::STATUS_DITUTUP,
            'kasir_id' => $kasirId,
            'ditutup_pada' => now(),
        ]);

        return $pembayaran->fresh();
    }

    private function sinkronkanItemBiaya(Pembayaran $pembayaran, Kunjungan $kunjungan): void
    {
        $activeKeys = [];

        $pendaftaran = $this->upsertItem(
            $pembayaran,
            ItemBiaya::JENIS_PENDAFTARAN,
            'kunjungan',
            $kunjungan->id,
            'Biaya Pendaftaran Kunjungan',
            (float) $kunjungan->biaya_pendaftaran
        );
        $activeKeys[] = $pendaftaran->id;

        $pemeriksaan = $this->upsertItem(
            $pembayaran,
            ItemBiaya::JENIS_PEMERIKSAAN,
            'dokter',
            $kunjungan->dokter_id,
            "Biaya Pemeriksaan ({$kunjungan->dokter->nama})",
            (float) $kunjungan->dokter->tarif_pemeriksaan
        );
        $activeKeys[] = $pemeriksaan->id;

        foreach ($kunjungan->detailTindakan as $dt) {
            /** @var DetailTindakan $dt */
            $item = $this->upsertItem(
                $pembayaran,
                ItemBiaya::JENIS_TINDAKAN,
                'detail_tindakan',
                $dt->id,
                "{$dt->tindakan->nama_tindakan} (x{$dt->jumlah})",
                (float) $dt->biaya
            );
            $activeKeys[] = $item->id;
        }

        if ($kunjungan->resep) {
            foreach ($kunjungan->resep->detailResep as $dr) {
                /** @var DetailResep $dr */
                $habisAtauBatal = $dr->status_stok === DetailResep::STOK_HABIS
                    || $dr->status_tebus === DetailResep::TEBUS_TIDAK;

                $nominal = $habisAtauBatal ? 0.0 : ((float) $dr->obat->harga * $dr->jumlah);
                $labelSuffix = $dr->status_stok === DetailResep::STOK_HABIS
                    ? ' [Stok Habis]'
                    : ($dr->status_tebus === DetailResep::TEBUS_TIDAK ? ' [Tidak Ditebus]' : '');

                $statusOverride = null;
                if ($habisAtauBatal) {
                    $statusOverride = ItemBiaya::STATUS_TIDAK_DITAGIHKAN;
                } elseif ($dr->status_tebus === DetailResep::TEBUS_BELUM) {
                    $statusOverride = ItemBiaya::STATUS_BELUM_DITEBUS;
                }

                $item = $this->upsertItem(
                    $pembayaran,
                    ItemBiaya::JENIS_OBAT,
                    'detail_resep',
                    $dr->id,
                    "{$dr->obat->nama_obat} (x{$dr->jumlah}){$labelSuffix}",
                    $nominal,
                    $statusOverride
                );
                $activeKeys[] = $item->id;
            }
        }

        // Remove orphan item rows if doctor removed an un-claimed action before closing
        $pembayaran->itemBiaya()
            ->whereNotIn('id', $activeKeys)
            ->whereDoesntHave('klaimItem')
            ->delete();

        $this->rekalkulasiPembayaran($pembayaran);
    }

    private function upsertItem(
        Pembayaran $pembayaran,
        string $jenis,
        string $refType,
        int $refId,
        string $deskripsi,
        float $nominal,
        ?string $statusOverride = null
    ): ItemBiaya {
        $item = ItemBiaya::where('pembayaran_id', $pembayaran->id)
            ->where('jenis', $jenis)
            ->where('referensi_type', $refType)
            ->where('referensi_id', $refId)
            ->first();

        if (! $item) {
            return ItemBiaya::create([
                'pembayaran_id' => $pembayaran->id,
                'jenis' => $jenis,
                'referensi_type' => $refType,
                'referensi_id' => $refId,
                'deskripsi' => $deskripsi,
                'nominal' => $nominal,
                'ditanggung_asuransi' => 0,
                'ditanggung_pasien' => $nominal,
                'status_bayar' => $statusOverride ?? ItemBiaya::STATUS_BELUM_LUNAS,
            ]);
        }

        $statusBayar = $statusOverride ?? $item->status_bayar;
        if ($statusOverride === null && in_array($item->status_bayar, [ItemBiaya::STATUS_BELUM_DITEBUS, ItemBiaya::STATUS_TIDAK_DITAGIHKAN], true)) {
            $statusBayar = ItemBiaya::STATUS_BELUM_LUNAS;
        }

        $item->update([
            'deskripsi' => $deskripsi,
            'nominal' => $nominal,
            'status_bayar' => $statusBayar,
        ]);

        return $item;
    }

    private function rekalkulasiPembayaran(Pembayaran $pembayaran): void
    {
        $pembayaran->load('itemBiaya.klaimItem.klaimAsuransi', 'pembayaranPasien', 'klaimAsuransi');

        $totalBiaya = 0.0;
        $totalTagihanPasien = 0.0;

        foreach ($pembayaran->itemBiaya as $item) {
            $nominal = (float) $item->nominal;
            $totalBiaya += $nominal;

            if ($item->status_bayar === ItemBiaya::STATUS_TIDAK_DITAGIHKAN) {
                $item->update([
                    'ditanggung_asuransi' => 0,
                    'ditanggung_pasien' => 0,
                ]);
                continue;
            }

            $ditanggungAsuransi = min(
                $nominal,
                (float) $item->klaimItem()
                    ->where('status_item', KlaimItem::STATUS_DISETUJUI)
                    ->sum('jumlah_ditanggung')
            );

            $ditanggungPasien = max(0.0, $nominal - $ditanggungAsuransi);
            $totalTagihanPasien += $ditanggungPasien;

            $item->update([
                'ditanggung_asuransi' => $ditanggungAsuransi,
                'ditanggung_pasien' => $ditanggungPasien,
            ]);
        }

        $totalBayarCash = (float) $pembayaran->pembayaranPasien()->sum('jumlah');
        $remainingCashPool = $totalBayarCash;

        foreach ($pembayaran->itemBiaya()->orderBy('id')->get() as $item) {
            if ($item->status_bayar === ItemBiaya::STATUS_TIDAK_DITAGIHKAN) {
                continue;
            }

            if ($item->status_bayar === ItemBiaya::STATUS_BELUM_DITEBUS) {
                continue;
            }

            $adaKlaimPending = $item->klaimItem()
                ->whereHas('klaimAsuransi', fn ($q) => $q->where('status', KlaimAsuransi::STATUS_DIAJUKAN))
                ->exists();

            if ($adaKlaimPending) {
                $item->update(['status_bayar' => ItemBiaya::STATUS_MENUNGGU_KLAIM]);
                continue;
            }

            $sisaPasienItem = (float) $item->ditanggung_pasien;
            if ($sisaPasienItem <= 0) {
                $item->update(['status_bayar' => ItemBiaya::STATUS_LUNAS]);
            } elseif ($remainingCashPool >= $sisaPasienItem) {
                $remainingCashPool -= $sisaPasienItem;
                $item->update(['status_bayar' => ItemBiaya::STATUS_LUNAS]);
            } else {
                $item->update(['status_bayar' => ItemBiaya::STATUS_BELUM_LUNAS]);
            }
        }

        $adaKlaimPending = $pembayaran->klaimAsuransi()->where('status', KlaimAsuransi::STATUS_DIAJUKAN)->exists();
        $statusTransaksi = $pembayaran->status_transaksi;
        if ($statusTransaksi !== Pembayaran::STATUS_PERLU_PENYESUAIAN && $statusTransaksi !== Pembayaran::STATUS_DITUTUP) {
            $statusTransaksi = $adaKlaimPending ? Pembayaran::STATUS_MENUNGGU_KLAIM : Pembayaran::STATUS_TERBUKA;
        }

        $pembayaran->update([
            'total_biaya' => $totalBiaya,
            'status_transaksi' => $statusTransaksi,
        ]);

        $this->finalisasiStokDanStatusResep($pembayaran);
    }

    private function finalisasiStokDanStatusResep(Pembayaran $pembayaran): void
    {
        $kunjungan = $pembayaran->kunjungan()->with('resep.detailResep.obat')->first();
        $resep = $kunjungan?->resep;
        if (! $resep) {
            return;
        }

        $detailDitebus = $resep->detailResep->where('status_tebus', DetailResep::TEBUS_DITEBUS);
        $adaPerubahanStok = false;

        foreach ($detailDitebus as $dr) {
            if ($dr->stok_terpotong) {
                continue;
            }

            $item = ItemBiaya::where('pembayaran_id', $pembayaran->id)
                ->where('referensi_type', 'detail_resep')
                ->where('referensi_id', $dr->id)
                ->first();

            if ($item && $item->status_bayar === ItemBiaya::STATUS_LUNAS) {
                $dr->obat->kurangiStok($dr->jumlah);
                $dr->update(['stok_terpotong' => true]);
                $adaPerubahanStok = true;
            }
        }

        $semuaDitebusLunas = $detailDitebus->isNotEmpty() && $detailDitebus->every(
            fn (DetailResep $dr) => $dr->fresh()->stok_terpotong
        );

        $this->perbaruiStatusResep($resep, $semuaDitebusLunas || $adaPerubahanStok);
    }

    private function perbaruiStatusResep(Resep $resep, bool $sudahDilunasi = false): void
    {
        $resep->refresh();
        $details = $resep->detailResep;

        if ($details->contains('status_tebus', DetailResep::TEBUS_BELUM)) {
            $resep->update(['status' => Resep::STATUS_BELUM_DITEBUS]);
            return;
        }

        $jumlahDitebus = $details->where('status_tebus', DetailResep::TEBUS_DITEBUS)->count();
        if ($jumlahDitebus === 0) {
            $resep->update(['status' => Resep::STATUS_TIDAK_DITEBUS]);
            return;
        }

        if (! $sudahDilunasi && ! in_array($resep->status, [Resep::STATUS_SUDAH_DITEBUS, Resep::STATUS_SEBAGIAN_DITEBUS], true)) {
            $resep->update(['status' => Resep::STATUS_BELUM_DITEBUS]);
            return;
        }

        $statusBaru = $jumlahDitebus === $details->count()
            ? Resep::STATUS_SUDAH_DITEBUS
            : Resep::STATUS_SEBAGIAN_DITEBUS;

        $resep->update(['status' => $statusBaru]);
    }

    private function pastikanBelumDitutup(Pembayaran $pembayaran): void
    {
        if ($pembayaran->status_transaksi === Pembayaran::STATUS_DITUTUP) {
            throw new DomainException('Transaksi ini sudah Ditutup dan tidak dapat diubah lagi.');
        }
    }

    private function pastikanBukanPerluPenyesuaian(Pembayaran $pembayaran): void
    {
        if ($pembayaran->status_transaksi === Pembayaran::STATUS_PERLU_PENYESUAIAN) {
            throw new DomainException('Transaksi berstatus Perlu Penyesuaian. Silakan konfirmasi peninjauan tagihan terlebih dahulu.');
        }
    }
}
