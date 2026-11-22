<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemBiaya extends Model
{
    protected $table = 'item_biaya';

    public const JENIS_PENDAFTARAN = 'Pendaftaran';
    public const JENIS_PEMERIKSAAN = 'Pemeriksaan';
    public const JENIS_TINDAKAN = 'Tindakan';
    public const JENIS_OBAT = 'Obat';

    public const STATUS_BELUM_LUNAS = 'Belum Lunas';
    public const STATUS_MENUNGGU_KLAIM = 'Menunggu Klaim';
    public const STATUS_BELUM_DITEBUS = 'Belum Ditebus';
    public const STATUS_PERLU_PENYESUAIAN = 'Perlu Penyesuaian';
    public const STATUS_LUNAS = 'Lunas';
    public const STATUS_TIDAK_DITAGIHKAN = 'Tidak Ditagihkan';

    protected $fillable = [
        'pembayaran_id',
        'jenis',
        'referensi_type',
        'referensi_id',
        'deskripsi',
        'nominal',
        'ditanggung_asuransi',
        'ditanggung_pasien',
        'status_bayar',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'ditanggung_asuransi' => 'decimal:2',
            'ditanggung_pasien' => 'decimal:2',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    public function klaimItem(): HasMany
    {
        return $this->hasMany(KlaimItem::class, 'item_biaya_id');
    }

    public function sudahJelas(): bool
    {
        return in_array($this->status_bayar, [self::STATUS_LUNAS, self::STATUS_TIDAK_DITAGIHKAN], true);
    }

    public function sisaBelumDitanggungAsuransi(): float
    {
        return max(0.0, (float) $this->nominal - (float) $this->ditanggung_asuransi);
    }
}
