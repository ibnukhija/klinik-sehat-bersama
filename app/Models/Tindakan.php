<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tindakan extends Model
{
    protected $table = 'tindakan';

    protected $fillable = [
        'kode_tindakan',
        'nama_tindakan',
        'tarif',
    ];

    protected function casts(): array
    {
        return [
            'tarif' => 'decimal:2',
        ];
    }

    public function detailTindakan(): HasMany
    {
        return $this->hasMany(DetailTindakan::class, 'tindakan_id');
    }
}
