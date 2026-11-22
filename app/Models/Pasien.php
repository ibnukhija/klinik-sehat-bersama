<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pasien extends Model
{
    protected $table = 'pasien';

    protected $fillable = [
        'no_identitas',
        'nama',
        'tgl_lahir',
        'alamat',
        'no_telp',
    ];

    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date',
        ];
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'pasien_id');
    }

    public function polisAsuransi(): HasMany
    {
        return $this->hasMany(PolisAsuransi::class, 'pasien_id')->orderBy('prioritas');
    }

    public function polisAktifUrutPrioritas()
    {
        return $this->polisAsuransi()
            ->with('asuransi')
            ->where('status_aktif', true)
            ->orderBy('prioritas')
            ->get();
    }
}
