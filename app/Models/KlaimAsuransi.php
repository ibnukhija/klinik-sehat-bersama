<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KlaimAsuransi extends Model
{
    protected $table = 'klaim_asuransi';

    public const STATUS_DIAJUKAN = 'Diajukan';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DISETUJUI_SEBAGIAN = 'Disetujui Sebagian';
    public const STATUS_DITOLAK = 'Ditolak';

    protected $fillable = [
        'pembayaran_id',
        'polis_asuransi_id',
        'tanggal_klaim',
        'status',
        'jumlah_diajukan',
        'jumlah_disetujui',
        'catatan_keputusan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_klaim' => 'date',
            'jumlah_diajukan' => 'decimal:2',
            'jumlah_disetujui' => 'decimal:2',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    public function polisAsuransi(): BelongsTo
    {
        return $this->belongsTo(PolisAsuransi::class, 'polis_asuransi_id');
    }

    public function klaimItem(): HasMany
    {
        return $this->hasMany(KlaimItem::class, 'klaim_asuransi_id');
    }
}
