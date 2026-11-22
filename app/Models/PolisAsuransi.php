<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolisAsuransi extends Model
{
    protected $table = 'polis_asuransi';

    protected $fillable = [
        'pasien_id',
        'asuransi_id',
        'no_polis',
        'prioritas',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function asuransi(): BelongsTo
    {
        return $this->belongsTo(Asuransi::class, 'asuransi_id');
    }

    public function klaimAsuransi(): HasMany
    {
        return $this->hasMany(KlaimAsuransi::class, 'polis_asuransi_id');
    }
}
