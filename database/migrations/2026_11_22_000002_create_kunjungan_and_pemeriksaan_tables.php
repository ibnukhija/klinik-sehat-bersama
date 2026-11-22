<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasien')->cascadeOnDelete();
            $table->foreignId('dokter_id')->constrained('dokter')->restrictOnDelete();
            $table->date('tgl_kunjungan');
            $table->unsignedInteger('no_antrian');
            $table->string('status', 30)->default('Terdaftar'); // Terdaftar, Menunggu, Diperiksa, Selesai, Dibatalkan
            $table->text('keluhan')->nullable();
            $table->text('hasil_pemeriksaan')->nullable();
            $table->text('catatan_tambahan')->nullable();
            $table->decimal('biaya_pendaftaran', 12, 2)->default(20000);
            $table->timestamps();

            $table->index(['dokter_id', 'tgl_kunjungan']);
        });

        Schema::create('pemindahan_dokter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('kunjungan')->cascadeOnDelete();
            $table->foreignId('dokter_asal_id')->constrained('dokter')->restrictOnDelete();
            $table->foreignId('dokter_tujuan_id')->constrained('dokter')->restrictOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('waktu');
            $table->string('alasan');
            $table->timestamps();
        });

        Schema::create('detail_diagnosis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('kunjungan')->cascadeOnDelete();
            $table->string('nama_diagnosis');
            $table->timestamps();
        });

        Schema::create('riwayat_diagnosis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_diagnosis_id')->nullable()->constrained('detail_diagnosis')->nullOnDelete();
            $table->foreignId('kunjungan_id')->constrained('kunjungan')->cascadeOnDelete();
            $table->foreignId('dokter_id')->constrained('dokter')->restrictOnDelete();
            $table->string('aksi', 20)->default('Ubah'); // Ubah, Tambah, Hapus
            $table->string('diagnosis_lama')->nullable();
            $table->string('diagnosis_baru')->nullable();
            $table->string('alasan_ubah');
            $table->dateTime('waktu_ubah');
            $table->timestamps();
        });

        Schema::create('detail_tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('kunjungan')->cascadeOnDelete();
            $table->foreignId('tindakan_id')->constrained('tindakan')->restrictOnDelete();
            $table->unsignedInteger('jumlah')->default(1);
            $table->decimal('biaya', 12, 2);
            $table->timestamps();
        });

        Schema::create('resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->unique()->constrained('kunjungan')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 30)->default('Belum Ditebus'); // Belum Ditebus, Sudah Ditebus, Sebagian Ditebus, Tidak Ditebus
            $table->timestamps();
        });

        Schema::create('detail_resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resep_id')->constrained('resep')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->string('aturan_pakai');
            $table->string('status_stok', 20)->default('Tersedia'); // Tersedia, Habis
            $table->string('status_tebus', 30)->default('Belum Ditebus'); // Belum Ditebus, Ditebus, Tidak Ditebus, Stok Habis
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_resep');
        Schema::dropIfExists('resep');
        Schema::dropIfExists('detail_tindakan');
        Schema::dropIfExists('riwayat_diagnosis');
        Schema::dropIfExists('detail_diagnosis');
        Schema::dropIfExists('pemindahan_dokter');
        Schema::dropIfExists('kunjungan');
    }
};
