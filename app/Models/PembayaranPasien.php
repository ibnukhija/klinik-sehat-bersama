<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranPasien extends Model
{
    protected $table = 'pembayaran_pasien';

    protected $fillable = [
        'pembayaran_id',
        'metode',
        'jumlah',
        'tanggal',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'tanggal' => 'datetime',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }
}
