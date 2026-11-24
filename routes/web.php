<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokterKehadiranController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KunjunganController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PemeriksaanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/master-data', [MasterDataController::class, 'index'])->name('master.index');
    Route::patch('/master-data/obat/{obat}/stok', [MasterDataController::class, 'updateStokObat'])
        ->middleware('role:admin')
        ->name('master.obat.stok');

    // Pasien & Polis
    Route::get('/pasien', [PasienController::class, 'index'])->name('pasien.index');
    Route::get('/pasien/create', [PasienController::class, 'create'])
        ->middleware('role:admin')
        ->name('pasien.create');
    Route::post('/pasien', [PasienController::class, 'store'])
        ->middleware('role:admin')
        ->name('pasien.store');
    Route::get('/pasien/{pasien}', [PasienController::class, 'show'])->name('pasien.show');
    Route::post('/pasien/{pasien}/polis', [PasienController::class, 'storePolis'])
        ->middleware('role:admin')
        ->name('pasien.polis.store');
    Route::patch('/polis/{polis}/toggle', [PasienController::class, 'togglePolis'])
        ->middleware('role:admin')
        ->name('polis.toggle');

    // Pendaftaran & Kunjungan
    Route::get('/kunjungan', [KunjunganController::class, 'index'])->name('kunjungan.index');
    Route::get('/kunjungan/create', [KunjunganController::class, 'create'])
        ->middleware('role:admin')
        ->name('kunjungan.create');
    Route::post('/kunjungan', [KunjunganController::class, 'store'])
        ->middleware('role:admin')
        ->name('kunjungan.store');
    Route::get('/kunjungan/{kunjungan}', [KunjunganController::class, 'show'])->name('kunjungan.show');
    Route::post('/kunjungan/{kunjungan}/batalkan', [KunjunganController::class, 'batalkan'])
        ->middleware('role:admin')
        ->name('kunjungan.batalkan');
    Route::post('/kunjungan/{kunjungan}/jadwalkan-ulang', [KunjunganController::class, 'jadwalkanUlang'])
        ->middleware('role:admin')
        ->name('kunjungan.jadwalkan-ulang');

    // Kehadiran & Pemindahan Dokter
    Route::middleware('role:admin')->group(function () {
        Route::get('/kehadiran-dokter', [DokterKehadiranController::class, 'index'])->name('kehadiran.index');
        Route::post('/kehadiran-dokter/{dokter}', [DokterKehadiranController::class, 'updateStatus'])->name('kehadiran.update');
        Route::post('/kunjungan/{kunjungan}/pindah-dokter', [DokterKehadiranController::class, 'pindahkan'])->name('kehadiran.pindahkan');
        Route::post('/kehadiran-dokter/{dokter}/pindah-massal', [DokterKehadiranController::class, 'pindahkanMassal'])->name('kehadiran.pindahkan-massal');
    });

    // Modul Dokter (Pemeriksaan, Diagnosis, Tindakan, Resep)
    Route::middleware('role:dokter')->prefix('pemeriksaan')->name('pemeriksaan.')->group(function () {
        Route::get('/', [PemeriksaanController::class, 'index'])->name('index');
        Route::get('/{kunjungan}', [PemeriksaanController::class, 'show'])->name('show');
        Route::post('/{kunjungan}/panggil', [PemeriksaanController::class, 'panggil'])->name('panggil');
        Route::post('/{kunjungan}/catatan', [PemeriksaanController::class, 'simpanCatatan'])->name('catatan');
        Route::post('/{kunjungan}/diagnosis', [PemeriksaanController::class, 'tambahDiagnosis'])->name('diagnosis.tambah');
        Route::put('/diagnosis/{detail}', [PemeriksaanController::class, 'ubahDiagnosis'])->name('diagnosis.ubah');
        Route::delete('/diagnosis/{detail}', [PemeriksaanController::class, 'hapusDiagnosis'])->name('diagnosis.hapus');
        Route::post('/{kunjungan}/tindakan', [PemeriksaanController::class, 'tambahTindakan'])->name('tindakan.tambah');
        Route::delete('/tindakan/{detail}', [PemeriksaanController::class, 'hapusTindakan'])->name('tindakan.hapus');
        Route::post('/{kunjungan}/resep', [PemeriksaanController::class, 'simpanResep'])->name('resep.simpan');
        Route::post('/{kunjungan}/selesai', [PemeriksaanController::class, 'selesaikan'])->name('selesai');
    });

    // Modul Kasir & Simulasi Klaim Asuransi
    Route::middleware('role:kasir')->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/', [KasirController::class, 'index'])->name('index');
        Route::get('/kunjungan/{kunjungan}', [KasirController::class, 'show'])->name('show');
        Route::post('/pembayaran/{pembayaran}/konfirmasi-penyesuaian', [KasirController::class, 'konfirmasiPenyesuaian'])->name('penyesuaian');
        Route::post('/pembayaran/{pembayaran}/konfirmasi-resep', [KasirController::class, 'konfirmasiResep'])->name('resep');
        Route::post('/pembayaran/{pembayaran}/klaim', [KasirController::class, 'ajukanKlaim'])->name('klaim.ajukan');
        Route::post('/klaim/{klaim}/keputusan', [KasirController::class, 'prosesKeputusanKlaim'])->name('klaim.keputusan');
        Route::post('/pembayaran/{pembayaran}/bayar-tunai', [KasirController::class, 'bayarTunai'])->name('bayar-tunai');
        Route::post('/pembayaran/{pembayaran}/tutup', [KasirController::class, 'tutupTransaksi'])->name('tutup');
    });
});
