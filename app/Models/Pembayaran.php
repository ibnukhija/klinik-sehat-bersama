<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    public const STATUS_TERBUKA = 'Terbuka';
    public const STATUS_MENUNGGU_KLAIM = 'Menunggu Klaim';
    public const STATUS_PERLU_PENYESUAIAN = 'Perlu Penyesuaian';
    public const STATUS_DITUTUP = 'Ditutup';

    protected $fillable = [
        'kunjungan_id',
        'kasir_id',
        'tanggal',
        'total_biaya',
        'status_transaksi',
        'ditutup_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_biaya' => 'decimal:2',
            'ditutup_pada' => 'datetime',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function itemBiaya(): HasMany
    {
        return $this->hasMany(ItemBiaya::class, 'pembayaran_id');
    }

    public function pembayaranPasien(): HasMany
    {
        return $this->hasMany(PembayaranPasien::class, 'pembayaran_id')->orderByDesc('tanggal');
    }

    public function klaimAsuransi(): HasMany
    {
        return $this->hasMany(KlaimAsuransi::class, 'pembayaran_id')->orderBy('id');
    }

    public function cekSemuaBiayaJelas(): bool
    {
        if ($this->status_transaksi === self::STATUS_PERLU_PENYESUAIAN) {
            return false;
        }

        if ($this->klaimAsuransi()->where('status', KlaimAsuransi::STATUS_DIAJUKAN)->exists()) {
            return false;
        }

        return ! $this->itemBiaya()
            ->whereNotIn('status_bayar', [ItemBiaya::STATUS_LUNAS, ItemBiaya::STATUS_TIDAK_DITAGIHKAN])
            ->exists();
    }

    public function totalDitanggungAsuransi(): float
    {
        return (float) $this->itemBiaya()->sum('ditanggung_asuransi');
    }

    public function totalDitanggungPasien(): float
    {
        return (float) $this->itemBiaya()->sum('ditanggung_pasien');
    }

    public function totalSudahDibayarPasien(): float
    {
        return (float) $this->pembayaranPasien()->sum('jumlah');
    }

    public function sisaTagihanPasien(): float
    {
        return max(0.0, $this->totalDitanggungPasien() - $this->totalSudahDibayarPasien());
    }
}
