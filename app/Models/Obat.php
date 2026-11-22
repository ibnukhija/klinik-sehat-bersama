<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    protected $table = 'obat';

    protected $fillable = [
        'kode_obat',
        'nama_obat',
        'stok',
        'harga',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
        ];
    }

    public function detailResep(): HasMany
    {
        return $this->hasMany(DetailResep::class, 'obat_id');
    }

    public function cekStok(int $jumlah = 1): bool
    {
        return $this->stok >= $jumlah && $this->stok > 0;
    }

    public function kurangiStok(int $jumlah): void
    {
        $this->stok = max(0, $this->stok - $jumlah);
        $this->save();
    }
}
