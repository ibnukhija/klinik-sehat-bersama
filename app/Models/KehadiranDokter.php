<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KehadiranDokter extends Model
{
    protected $table = 'kehadiran_dokter';

    public const STATUS_HADIR = 'Hadir';
    public const STATUS_TIDAK_HADIR = 'Tidak Hadir';

    protected $fillable = [
        'dokter_id',
        'tanggal',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }
}
