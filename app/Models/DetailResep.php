<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailResep extends Model
{
    protected $table = 'detail_resep';

    public const STOK_TERSEDIA = 'Tersedia';
    public const STOK_HABIS = 'Habis';

    public const TEBUS_BELUM = 'Belum Ditebus';
    public const TEBUS_DITEBUS = 'Ditebus';
    public const TEBUS_TIDAK = 'Tidak Ditebus';
    public const TEBUS_STOK_HABIS = 'Stok Habis';

    protected $fillable = [
        'resep_id',
        'obat_id',
        'jumlah',
        'aturan_pakai',
        'status_stok',
        'status_tebus',
        'stok_terpotong',
    ];

    protected function casts(): array
    {
        return [
            'stok_terpotong' => 'boolean',
        ];
    }

    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'resep_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }
}
