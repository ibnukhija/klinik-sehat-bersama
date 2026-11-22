<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->unique()->constrained('kunjungan')->cascadeOnDelete();
            $table->foreignId('kasir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal');
            $table->decimal('total_biaya', 12, 2)->default(0);
            $table->string('status_transaksi', 30)->default('Terbuka'); // Terbuka, Menunggu Klaim, Perlu Penyesuaian, Ditutup
            $table->dateTime('ditutup_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('item_biaya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->cascadeOnDelete();
            $table->string('jenis', 30); // Pendaftaran, Pemeriksaan, Tindakan, Obat
            $table->string('referensi_type', 50)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('deskripsi');
            $table->decimal('nominal', 12, 2);
            $table->decimal('ditanggung_asuransi', 12, 2)->default(0);
            $table->decimal('ditanggung_pasien', 12, 2)->default(0);
            $table->string('status_bayar', 30)->default('Belum Lunas'); // Belum Lunas, Menunggu Klaim, Belum Ditebus, Perlu Penyesuaian, Lunas, Tidak Ditagihkan
            $table->timestamps();
        });

        Schema::create('pembayaran_pasien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->cascadeOnDelete();
            $table->string('metode', 30)->default('Tunai');
            $table->decimal('jumlah', 12, 2);
            $table->dateTime('tanggal');
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('klaim_asuransi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->cascadeOnDelete();
            $table->foreignId('polis_asuransi_id')->constrained('polis_asuransi')->restrictOnDelete();
            $table->date('tanggal_klaim');
            $table->string('status', 30)->default('Diajukan'); // Diajukan, Disetujui, Disetujui Sebagian, Ditolak
            $table->decimal('jumlah_diajukan', 12, 2)->default(0);
            $table->decimal('jumlah_disetujui', 12, 2)->default(0);
            $table->string('catatan_keputusan')->nullable();
            $table->timestamps();
        });

        Schema::create('klaim_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klaim_asuransi_id')->constrained('klaim_asuransi')->cascadeOnDelete();
            $table->foreignId('item_biaya_id')->constrained('item_biaya')->cascadeOnDelete();
            $table->decimal('jumlah_diajukan', 12, 2);
            $table->decimal('jumlah_ditanggung', 12, 2)->default(0);
            $table->string('status_item', 30)->default('Diajukan'); // Diajukan, Disetujui, Ditolak
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klaim_item');
        Schema::dropIfExists('klaim_asuransi');
        Schema::dropIfExists('pembayaran_pasien');
        Schema::dropIfExists('item_biaya');
        Schema::dropIfExists('pembayaran');
    }
};
