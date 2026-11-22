<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemindahanDokter extends Model
{
    protected $table = 'pemindahan_dokter';

    protected $fillable = [
        'kunjungan_id',
        'dokter_asal_id',
        'dokter_tujuan_id',
        'petugas_id',
        'waktu',
        'alasan',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function dokterAsal(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_asal_id');
    }

    public function dokterTujuan(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_tujuan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
