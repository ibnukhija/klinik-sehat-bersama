<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailDiagnosis extends Model
{
    protected $table = 'detail_diagnosis';

    protected $fillable = [
        'kunjungan_id',
        'nama_diagnosis',
    ];

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatDiagnosis::class, 'detail_diagnosis_id');
    }
}
