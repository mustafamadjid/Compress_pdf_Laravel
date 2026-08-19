# PDF Compressor

Web sederhana untuk mengecilkan ukuran file PDF. Aplikasi memakai Laravel, Livewire, dan Ghostscript.

Panduan ini ditulis untuk pengguna yang belum terbiasa dengan Laravel.

## 1. Siapkan Program

Install program berikut di komputer/server:

- PHP 8.2 atau lebih baru
- Composer 2
- Node.js versi LTS dan npm
- Git
- Ghostscript

Setelah instalasi, buka **PowerShell** di Windows atau **Terminal** di Linux/macOS. Pastikan program terbaca:

```bash
php --version
composer --version
node --version
npm --version
git --version
```

Cek Ghostscript:

```bash
gs --version
```

Jika Windows tidak mengenali `gs`, cari lokasi Ghostscript. Nama executable biasanya `gswin64c.exe`.

## 2. Clone dari GitHub

Ganti URL contoh berikut dengan URL repository GitHub proyek:

```bash
git clone https://github.com/USERNAME/REPOSITORY.git
cd REPOSITORY
```

Contoh jika repository bernama `pdf-compressor`:

```bash
git clone https://github.com/USERNAME/pdf-compressor.git
cd pdf-compressor
```

Semua perintah berikut harus dijalankan dari folder proyek, yaitu folder yang berisi file `artisan`.

## 3. Install Dependency

Jalankan:

```bash
composer install
npm install
```

Composer meng-install dependency PHP. npm meng-install dependency frontend.

## 4. Buat File Environment

### Windows PowerShell

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### Linux/macOS

```bash
cp .env.example .env
php artisan key:generate
```

Perintah `key:generate` membuat kunci enkripsi aplikasi. Jangan membagikan isi file `.env`.

## 5. Atur Ghostscript

Buka file `.env`, lalu pastikan nilai berikut sesuai sistem:

```dotenv
GHOSTSCRIPT_BINARY=gs
```

Untuk Windows, jika `gs` tidak masuk PATH, gunakan lokasi executable. Contoh:

```dotenv
GHOSTSCRIPT_BINARY="C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe"
```

Sesuaikan versi dan lokasi dengan instalasi Ghostscript di komputer.

Pengaturan PDF lain yang tersedia:

```dotenv
PDF_COMPRESSOR_MAX_UPLOAD_MB=25
PDF_COMPRESSOR_RETENTION_MINUTES=60
PDF_COMPRESSOR_PROCESS_TIMEOUT=120
```

Artinya:

- Ukuran upload maksimum: 25 MB.
- File sementara dihapus setelah 60 menit.
- Proses Ghostscript dihentikan setelah 120 detik.

## 6. Build Frontend

```bash
npm run build
```

Build harus selesai tanpa error. Perintah ini membuat asset production di `public/build`.

## 7. Jalankan Aplikasi

```bash
php artisan serve
```

Buka browser dan kunjungi:

```text
http://127.0.0.1:8000
```

Upload satu file PDF, pilih level kompresi, lalu klik **Compress PDF**.

Untuk menghentikan server, tekan `Ctrl+C` di terminal.

## 8. Jalankan Test

```bash
php artisan test
```

Perintah tambahan untuk memeriksa format kode dan build frontend:

```bash
./vendor/bin/pint --test
npm run build
```

Pada Windows PowerShell, jalankan Pint dengan:

```powershell
.\vendor\bin\pint --test
```

Test otomatis tidak membutuhkan Ghostscript karena proses eksternal di-fake pada test service. Ghostscript tetap wajib untuk memakai aplikasi secara nyata.

## 9. Cleanup File Sementara

File upload dan hasil kompresi disimpan sementara di:

```text
storage/app/temporary/uploads
storage/app/temporary/compressed
```

Hapus file yang sudah kedaluwarsa secara manual:

```bash
php artisan pdf:cleanup
```

Aplikasi menjadwalkan cleanup setiap 30 menit. Agar scheduler berjalan di server Linux, tambahkan cron berikut:

```cron
* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
```

Ganti `/path/ke/proyek` dengan lokasi asli folder aplikasi.

## 10. Jika Muncul Error

### `php is not recognized`

PHP belum ter-install atau folder PHP belum masuk PATH. Install PHP, atau gunakan terminal yang disediakan Laragon/XAMPP sesuai setup komputer.

### `composer is not recognized`

Install Composer, lalu tutup dan buka kembali terminal.

### `npm is not recognized`

Install Node.js versi LTS, lalu buka kembali terminal.

### `gs is not recognized`

Install Ghostscript. Pada Windows, isi `GHOSTSCRIPT_BINARY` dengan path lengkap ke `gswin64c.exe`.

### `Vite manifest not found`

Jalankan:

```bash
npm install
npm run build
```

### `The PDF could not be compressed`

Periksa hal berikut:

- Ghostscript sudah ter-install.
- `GHOSTSCRIPT_BINARY` benar.
- File PDF tidak rusak atau dilindungi password.
- Folder `storage/` bisa ditulis aplikasi.

## 11. Deploy ke Server

Sebelum aplikasi dipakai publik:

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set `APP_URL` ke URL asli aplikasi.
- Install Ghostscript di server.
- Pastikan `storage/` dan `bootstrap/cache/` writable.
- Jalankan `npm run build`.
- Aktifkan scheduler Laravel.
- Set `upload_max_filesize` dan `post_max_size` PHP lebih besar dari batas upload aplikasi.
- Set batas body request Nginx `client_max_body_size` atau batas serupa di Apache.
- Jangan letakkan `storage/app/temporary` di dalam folder public web.
- Gunakan HTTPS.

Database, login, Redis, queue, dan authentication tidak diperlukan untuk MVP ini.

## Keamanan

- Upload memeriksa extension, MIME type, dan ukuran file.
- Nama internal file dibuat memakai UUID.
- Preset Ghostscript ditentukan aplikasi, bukan input user.
- Download memakai signed URL yang memiliki masa berlaku.
- Client tidak pernah mengirim path filesystem.
- Error teknis masuk log server; UI hanya menampilkan pesan umum.
- File sementara dibersihkan otomatis.
