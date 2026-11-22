<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatDiagnosis extends Model
{
    protected $table = 'riwayat_diagnosis';

    protected $fillable = [
        'detail_diagnosis_id',
        'kunjungan_id',
        'dokter_id',
        'aksi',
        'diagnosis_lama',
        'diagnosis_baru',
        'alasan_ubah',
        'waktu_ubah',
    ];

    protected function casts(): array
    {
        return [
            'waktu_ubah' => 'datetime',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }
}
