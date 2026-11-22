<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resep extends Model
{
    protected $table = 'resep';

    public const STATUS_BELUM_DITEBUS = 'Belum Ditebus';
    public const STATUS_SUDAH_DITEBUS = 'Sudah Ditebus';
    public const STATUS_SEBAGIAN_DITEBUS = 'Sebagian Ditebus';
    public const STATUS_TIDAK_DITEBUS = 'Tidak Ditebus';

    protected $fillable = [
        'kunjungan_id',
        'tanggal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function detailResep(): HasMany
    {
        return $this->hasMany(DetailResep::class, 'resep_id');
    }
}
