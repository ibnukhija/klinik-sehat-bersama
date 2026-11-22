<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTindakan extends Model
{
    protected $table = 'detail_tindakan';

    protected $fillable = [
        'kunjungan_id',
        'tindakan_id',
        'jumlah',
        'biaya',
    ];

    protected function casts(): array
    {
        return [
            'biaya' => 'decimal:2',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function tindakan(): BelongsTo
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id');
    }
}
