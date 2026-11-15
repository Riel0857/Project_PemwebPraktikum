# PeduliKasih

Aplikasi web sederhana untuk lembaga amal: pengunjung bisa melihat program,
menyumbang, dan mengecek status donasinya. Pengurus punya panel admin untuk
memverifikasi donasi dan mengelola program.

Dibuat untuk tugas praktikum Pemrograman Web. Sengaja memakai PHP native tanpa
framework dan tanpa Composer, satu halaman satu file, supaya mudah dibaca dan
dijelaskan.

## Fitur

Halaman publik:

- Beranda dengan statistik, program unggulan, dan form kontak
- Katalog program dengan filter kategori dan pencarian
- Detail program: target, progress, daftar donasi terbaru
- Formulir donasi dengan pilihan transfer bank, QRIS, atau e-wallet
- Halaman pembayaran simulasi (meniru payment gateway)
- Cek status donasi dengan kode donasi

Panel admin:

- Dashboard: total dana terverifikasi, donasi yang menunggu verifikasi, grafik 6 bulan
- CRUD program (tambah, edit, hapus)
- CRUD kategori program (tambah, edit, hapus; kategori yang masih dipakai program tidak bisa dihapus)
- Verifikasi atau tolak donasi
- Laporan per program dan per bulan, ekspor CSV
- Kotak masuk pesan dari form kontak

## Teknologi

| Bagian | Dipakai |
|---|---|
| Bahasa | PHP 8.2 (PDO, session) |
| Database | PostgreSQL 16 |
| Web server | Apache di container resmi `php:8.2-apache` |
| Tampilan | CSS dan JavaScript tanpa library |
| Container | Docker Compose |

## Menjalankan

Butuh Docker dan Docker Compose.

```bash
git clone https://github.com/Riel0857/Project_PemwebPraktikum.git
cd Project_PemwebPraktikum

cp .env.example .env
# buka .env, isi DB_PASSWORD dan PGADMIN_PASSWORD

docker compose up -d --build
```

Tunggu sampai container sehat (`docker compose ps`), lalu buka:

- Aplikasi: http://localhost:8080
- pgAdmin: http://localhost:5050
- Database dari host: `localhost:5433`

Skema dan data contoh di folder `database/` dijalankan otomatis oleh PostgreSQL
saat volume database masih kosong. Kalau ingin mengulang dari nol:

```bash
docker compose down -v
docker compose up -d
```

## Akun demo

| Halaman | Email | Password |
|---|---|---|
| Admin | `admin@pedulikasih.test` | `admin123` |

Akun ini hanya untuk demonstrasi. Kalau aplikasi mau dipakai sungguhan, ganti
password-nya lewat `password_hash()` di PHP lalu update kolom `password_hash`.

## Struktur

```
index.php  program.php  donasi.php  bayar.php  cek.php
login.php  admin.php    koneksi.php  layout.php
assets/css/style.css   assets/js/script.js
database/01_schema.sql database/02_seed.sql
```

Sembilan file PHP. Tiap halaman memanggil `koneksi.php` (koneksi database dan fungsi
bantu) dan `layout.php` (header, navigasi, footer). Detailnya ada di
[docs/STRUKTUR.md](docs/STRUKTUR.md).

## Keamanan

- Query memakai prepared statement PDO, tidak ada string SQL yang disambung dari input.
- Semua output data database dilewatkan `htmlspecialchars()` lewat fungsi `e()`.
- Form POST memakai token CSRF di session.
- Password admin disimpan sebagai hash (`password_hash` / `password_verify`).
- Kredensial database diambil dari environment. File `.env` tidak ikut masuk repo,
  yang di-commit hanya `.env.example` tanpa nilai asli.

## Dokumentasi

- [docs/STRUKTUR.md](docs/STRUKTUR.md) — daftar file dan isinya
- [docs/ALUR-APLIKASI.md](docs/ALUR-APLIKASI.md) — alur donasi, verifikasi, login
- [docs/DATABASE.md](docs/DATABASE.md) — tabel, relasi, view, aturan status
- [docs/PANDUAN-INSTALASI.md](docs/PANDUAN-INSTALASI.md) — instalasi dan troubleshooting
