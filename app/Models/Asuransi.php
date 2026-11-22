<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asuransi extends Model
{
    protected $table = 'asuransi';

    protected $fillable = [
        'kode',
        'nama_perusahaan',
    ];

    public function polisAsuransi(): HasMany
    {
        return $this->hasMany(PolisAsuransi::class, 'asuransi_id');
    }
}
