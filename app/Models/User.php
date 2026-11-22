<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_DOKTER = 'dokter';
    public const ROLE_KASIR = 'kasir';

    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function dokter(): HasOne
    {
        return $this->hasOne(Dokter::class, 'user_id');
    }

    public function isRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Petugas Administrasi',
            self::ROLE_DOKTER => 'Dokter',
            self::ROLE_KASIR => 'Petugas Kasir',
            default => ucfirst($this->role),
        };
    }
}
