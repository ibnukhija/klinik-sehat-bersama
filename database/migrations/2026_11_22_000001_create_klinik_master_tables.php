<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_identitas', 50)->unique();
            $table->string('nama');
            $table->date('tgl_lahir');
            $table->text('alamat');
            $table->string('no_telp', 30);
            $table->timestamps();
        });

        Schema::create('dokter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama');
            $table->string('spesialisasi');
            $table->decimal('tarif_pemeriksaan', 12, 2)->default(50000);
            $table->timestamps();
        });

        Schema::create('jadwal_praktik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained('dokter')->cascadeOnDelete();
            $table->string('hari', 20); // Senin..Minggu
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();
        });

        Schema::create('kehadiran_dokter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained('dokter')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 30)->default('Hadir'); // Hadir, Tidak Hadir
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['dokter_id', 'tanggal']);
        });

        Schema::create('tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tindakan', 30)->unique();
            $table->string('nama_tindakan');
            $table->decimal('tarif', 12, 2);
            $table->timestamps();
        });

        Schema::create('obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_obat', 30)->unique();
            $table->string('nama_obat');
            $table->integer('stok')->default(0);
            $table->decimal('harga', 12, 2);
            $table->timestamps();
        });

        Schema::create('asuransi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama_perusahaan');
            $table->timestamps();
        });

        Schema::create('polis_asuransi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasien')->cascadeOnDelete();
            $table->foreignId('asuransi_id')->constrained('asuransi')->restrictOnDelete();
            $table->string('no_polis', 80)->unique();
            $table->unsignedTinyInteger('prioritas')->default(1);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polis_asuransi');
        Schema::dropIfExists('asuransi');
        Schema::dropIfExists('obat');
        Schema::dropIfExists('tindakan');
        Schema::dropIfExists('kehadiran_dokter');
        Schema::dropIfExists('jadwal_praktik');
        Schema::dropIfExists('dokter');
        Schema::dropIfExists('pasien');
    }
};
