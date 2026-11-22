# Sistem Informasi Klinik Sehat Bersama

Aplikasi web manajemen layanan klinik berbasis **Laravel 12**, **MySQL**, dan **Tailwind CSS v4** yang mencakup pendaftaran pasien, manajemen kehadiran & pemindahan dokter, pemeriksaan medis & peresepan, hingga pembayaran kasir serta simulasi klaim asuransi multi-polis.

## Fitur Utama

### 1. Petugas Administrasi
- Pencarian pasien berdasarkan No. Identitas (pasien lama) maupun pendaftaran pasien baru.
- Pendaftaran kunjungan dengan validasi jadwal praktik (`jadwal_praktik`) dan status kehadiran dokter.
- Pembatalan pendaftaran sebelum pemeriksaan dimulai (`Terdaftar` / `Menunggu`).
- Pengelolaan kehadiran dokter (`Hadir` / `Tidak Hadir`) serta pemindahan pasien satuan atau massal ke dokter pengganti yang berjadwal pada hari yang sama, lengkap dengan pencatatan audit (`pemindahan_dokter`).
- Pengelolaan polis asuransi pasien dengan dukungan **lebih dari satu polis berdasarkan prioritas klaim**.

### 2. Dokter
- Antrian pemeriksaan pasien dan pembaruan status kunjungan (`Terdaftar` → `Diperiksa` → `Selesai`).
- Pencatatan keluhan, hasil pemeriksaan, catatan tambahan, multi-diagnosis, dan multi-tindakan medis.
- Pembuatan resep obat dengan pengecekan stok otomatis: jika stok habis/kurang, sistem menampilkan peringatan namun resep tetap dapat disimpan dengan status `Belum Ditebus`.
- Perubahan diagnosis setelah transaksi kasir dibuat tetap tercatat pada `riwayat_diagnosis` dan menandai transaksi menjadi `Perlu Penyesuaian` (otomatis dikunci apabila transaksi sudah `Ditutup`).

### 3. Petugas Kasir & Simulasi Perusahaan Asuransi
- Perhitungan rincian item biaya (`Pendaftaran`, `Pemeriksaan`, `Tindakan`, dan `Obat`).
- Konfirmasi penebusan resep (`Ditebus` / `Tidak Ditebus`); obat dengan stok habis otomatis berstatus `Tidak Ditagihkan`.
- Pembayaran tunai maupun pengajuan klaim asuransi **per item biaya** dan **berurutan per polis** sesuai prioritas.
- Simulasi respon perusahaan asuransi (`Setujui Penuh`, `Disetujui Sebagian`, atau `Ditolak` → item terkait menjadi `Belum Lunas`).
- Pengurangan stok obat otomatis saat seluruh item obat yang ditebus berstatus `Lunas`.
- Validasi penutupan transaksi: hanya dapat ditutup apabila tidak berstatus `Perlu Penyesuaian`, tidak ada klaim tertunda, dan seluruh item berstatus final (`Lunas` / `Tidak Ditagihkan`).

---

## Teknologi
- PHP `^8.2` & Laravel Framework `^12.0`
- MySQL (atau SQLite `:memory:` untuk automated test)
- Blade Templates + Vite + Tailwind CSS v4

---

## Cara Instalasi Lokal

1. **Clone repository dan masuk ke folder project:**
   ```bash
   git clone https://github.com/username-kamu/klinik-sehat-bersama.git
   cd klinik-sehat-bersama
   ```

2. **Install dependency PHP & JavaScript:**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Sesuaikan konfigurasi database di `.env`, lalu buat database MySQL bernama `db_klinik_sehat_bersama`.

4. **Jalankan migrasi, seeder, dan build aset:**
   ```bash
   php artisan migrate --seed
   npm run build
   ```

5. **Jalankan server lokal:**
   ```bash
   php artisan serve
   ```
   Akses aplikasi di `http://127.0.0.1:8000` (atau via virtual host Laragon).

---

## Akun Demo (Hasil Seeder)

Seluruh akun demo menggunakan password: `password`

| Peran | Nama | Username | Email |
|---|---|---|---|
| Petugas Administrasi | Siti Aminah | `admin` | `admin@klinik.test` |
| Dokter (Penyakit Dalam) | dr. Andi Wijaya, Sp.PD | `dokter1` | `andi@klinik.test` |
| Dokter (Umum) | dr. Budi Santoso | `dokter2` | `budi@klinik.test` |
| Dokter (Umum) | dr. Citra Lestari | `dokter3` | `citra@klinik.test` |
| Petugas Kasir | Rina Wulandari | `kasir` | `kasir@klinik.test` |

---

## Pengujian Otomatis

```bash
php artisan test
```
