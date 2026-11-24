<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndDokterSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Siti Aminah',
            'username' => 'admin',
            'email' => 'admin@klinik.test',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Rina Wulandari',
            'username' => 'kasir',
            'email' => 'kasir@klinik.test',
            'role' => User::ROLE_KASIR,
            'password' => Hash::make('password'),
        ]);

        $dokterSpecs = [
            [
                'username' => 'dokter1',
                'email' => 'andi@klinik.test',
                'nama' => 'dr. Andi Wijaya, Sp.PD',
                'spesialisasi' => 'Penyakit Dalam',
                'tarif_pemeriksaan' => 75000,
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '13:00:00',
            ],
            [
                'username' => 'dokter2',
                'email' => 'budi@klinik.test',
                'nama' => 'dr. Budi Santoso',
                'spesialisasi' => 'Umum',
                'tarif_pemeriksaan' => 50000,
                'jam_mulai' => '09:00:00',
                'jam_selesai' => '15:00:00',
            ],
            [
                'username' => 'dokter3',
                'email' => 'citra@klinik.test',
                'nama' => 'dr. Citra Lestari',
                'spesialisasi' => 'Umum',
                'tarif_pemeriksaan' => 50000,
                'jam_mulai' => '13:00:00',
                'jam_selesai' => '19:00:00',
            ],
        ];

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        foreach ($dokterSpecs as $spec) {
            $user = User::create([
                'name' => $spec['nama'],
                'username' => $spec['username'],
                'email' => $spec['email'],
                'role' => User::ROLE_DOKTER,
                'password' => Hash::make('password'),
            ]);

            $dokter = Dokter::create([
                'user_id' => $user->id,
                'nama' => $spec['nama'],
                'spesialisasi' => $spec['spesialisasi'],
                'tarif_pemeriksaan' => $spec['tarif_pemeriksaan'],
            ]);

            foreach ($hariList as $hari) {
                JadwalPraktik::create([
                    'dokter_id' => $dokter->id,
                    'hari' => $hari,
                    'jam_mulai' => $spec['jam_mulai'],
                    'jam_selesai' => $spec['jam_selesai'],
                ]);
            }
        }
    }
}
