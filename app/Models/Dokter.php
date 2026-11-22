<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dokter extends Model
{
    protected $table = 'dokter';

    protected $fillable = [
        'user_id',
        'nama',
        'spesialisasi',
        'tarif_pemeriksaan',
    ];

    protected function casts(): array
    {
        return [
            'tarif_pemeriksaan' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jadwalPraktik(): HasMany
    {
        return $this->hasMany(JadwalPraktik::class, 'dokter_id');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(KehadiranDokter::class, 'dokter_id');
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'dokter_id');
    }

    /**
     * Map an ISO weekday to Indonesian day name matching jadwal_praktik.hari.
     */
    public static function hariIndonesia(Carbon|string $tanggal): string
    {
        $date = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);

        return match ($date->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };
    }

    public function punyaJadwalPada(Carbon|string $tanggal): bool
    {
        $hari = self::hariIndonesia($tanggal);

        return $this->jadwalPraktik()->where('hari', $hari)->exists();
    }

    public function statusKehadiranPada(Carbon|string $tanggal): string
    {
        $tgl = $tanggal instanceof Carbon ? $tanggal->toDateString() : Carbon::parse($tanggal)->toDateString();

        return $this->kehadiran()->whereDate('tanggal', $tgl)->value('status') ?? KehadiranDokter::STATUS_HADIR;
    }

    /**
     * Find doctors scheduled on the given date who are not marked absent.
     *
     * @return Collection<int, Dokter>
     */
    public static function cariDokterBerjadwal(Carbon|string $tanggal, ?int $excludeDokterId = null): Collection
    {
        $hari = self::hariIndonesia($tanggal);
        $tgl = $tanggal instanceof Carbon ? $tanggal->toDateString() : Carbon::parse($tanggal)->toDateString();

        return self::query()
            ->whereHas('jadwalPraktik', fn ($q) => $q->where('hari', $hari))
            ->whereDoesntHave('kehadiran', fn ($q) => $q->whereDate('tanggal', $tgl)->where('status', KehadiranDokter::STATUS_TIDAK_HADIR))
            ->when($excludeDokterId, fn ($q) => $q->where('id', '!=', $excludeDokterId))
            ->with(['jadwalPraktik' => fn ($q) => $q->where('hari', $hari)])
            ->orderBy('nama')
            ->get();
    }
}
