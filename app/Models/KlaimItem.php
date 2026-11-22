<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KlaimItem extends Model
{
    protected $table = 'klaim_item';

    public const STATUS_DIAJUKAN = 'Diajukan';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DITOLAK = 'Ditolak';

    protected $fillable = [
        'klaim_asuransi_id',
        'item_biaya_id',
        'jumlah_diajukan',
        'jumlah_ditanggung',
        'status_item',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diajukan' => 'decimal:2',
            'jumlah_ditanggung' => 'decimal:2',
        ];
    }

    public function klaimAsuransi(): BelongsTo
    {
        return $this->belongsTo(KlaimAsuransi::class, 'klaim_asuransi_id');
    }

    public function itemBiaya(): BelongsTo
    {
        return $this->belongsTo(ItemBiaya::class, 'item_biaya_id');
    }
}
