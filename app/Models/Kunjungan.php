<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kunjungan extends Model
{
    protected $table = 'kunjungan';

    public const STATUS_TERDAFTAR = 'Terdaftar';
    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DIPERIKSA = 'Diperiksa';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $fillable = [
        'pasien_id',
        'dokter_id',
        'tgl_kunjungan',
        'no_antrian',
        'status',
        'keluhan',
        'hasil_pemeriksaan',
        'catatan_tambahan',
        'biaya_pendaftaran',
    ];

    protected function casts(): array
    {
        return [
            'tgl_kunjungan' => 'date',
            'biaya_pendaftaran' => 'decimal:2',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function pemindahanDokter(): HasMany
    {
        return $this->hasMany(PemindahanDokter::class, 'kunjungan_id')->orderByDesc('waktu');
    }

    public function detailDiagnosis(): HasMany
    {
        return $this->hasMany(DetailDiagnosis::class, 'kunjungan_id');
    }

    public function riwayatDiagnosis(): HasMany
    {
        return $this->hasMany(RiwayatDiagnosis::class, 'kunjungan_id')->orderByDesc('waktu_ubah');
    }

    public function detailTindakan(): HasMany
    {
        return $this->hasMany(DetailTindakan::class, 'kunjungan_id');
    }

    public function resep(): HasOne
    {
        return $this->hasOne(Resep::class, 'kunjungan_id');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'kunjungan_id');
    }

    public function bisaDibatalkan(): bool
    {
        return in_array($this->status, [self::STATUS_TERDAFTAR, self::STATUS_MENUNGGU], true);
    }
}
