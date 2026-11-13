# Struktur File

Aplikasi ini sengaja dibuat datar. Tidak ada folder controller, model, atau view.
Satu halaman web = satu file PHP di folder utama. Semua file memakai `koneksi.php`
untuk menyambung ke database dan `layout.php` untuk header/footer.

```
Project_PemwebPraktikum/
├── index.php          beranda: statistik, program unggulan, tentang, form kontak
├── program.php        daftar program + detail satu program (kalau ada ?id=)
├── donasi.php         formulir donasi untuk satu program
├── bayar.php          halaman payment gateway simulasi + status donasi
├── cek.php            cek status donasi berdasarkan kode
├── login.php          login admin, sekaligus proses logout
├── admin.php          panel admin: dashboard, program, donasi, laporan, pesan
├── koneksi.php        koneksi PDO + fungsi bantu (e, rupiah, tanggal, csrf, dll)
├── layout.php         header, navigasi, footer, dan layout panel admin
├── assets/
│   ├── css/style.css  seluruh tampilan
│   └── js/script.js   salin teks, konfirmasi hapus, timer pembayaran
├── database/
│   ├── 01_schema.sql  tabel, view, trigger
│   └── 02_seed.sql    data contoh
├── docker/php/php.ini
├── deploy/dockge/     compose untuk Dockge + server pgAdmin
├── docs/              dokumentasi
├── Dockerfile
├── docker-compose.yml
└── .env.example
```

Jumlah file PHP: 9. Itu batas yang dipakai supaya kodenya masih bisa dijelaskan
satu per satu saat presentasi.

## Isi tiap file

**koneksi.php** — di-`require` paling atas di setiap halaman. Isinya: mulai session,
set timezone, ambil kredensial dari environment, buat objek PDO, lalu kumpulan fungsi
bantu yang dipakai semua halaman:

| Fungsi | Kegunaan |
|---|---|
| `e($teks)` | escape HTML sebelum mencetak data dari database |
| `rupiah($angka)` | format `Rp 1.500.000` |
| `tanggal($waktu, $jam)` | format `10 Nov 2026` |
| `persen($terkumpul, $target)` | persentase progress, maksimal 100 |
| `label_status()`, `kelas_status()`, `label_metode()` | teks dan warna badge |
| `csrf_token()`, `csrf_input()`, `csrf_valid()` | proteksi form |
| `flash()`, `tampil_flash()` | pesan sukses/gagal setelah redirect |
| `sudah_login()`, `wajib_login()`, `redirect()` | urusan login admin |

**layout.php** — empat fungsi tampilan: `tampil_atas()` dan `tampil_bawah()` untuk
halaman publik, `tampil_admin_atas()` dan `tampil_admin_bawah()` untuk panel admin
(sidebar, topbar, jumlah donasi yang menunggu verifikasi).

**index.php** — query statistik (total donasi terverifikasi, jumlah donatur, program
aktif), program unggulan, lalu menangani POST form kontak.

**program.php** — kalau dibuka tanpa parameter, menampilkan katalog program dengan
filter kategori dan pencarian. Kalau ada `?id=`, menampilkan detail program, daftar
donasi terbaru, dan tombol donasi.

**donasi.php** — menampilkan formulir donasi untuk program yang dipilih. Saat
disubmit: validasi, simpan/ambil data donatur, buat kode donasi, simpan donasi
berstatus `pending`, lalu redirect ke `bayar.php`.

**bayar.php** — menampilkan halaman mirip payment gateway: kode pembayaran, nominal,
nomor referensi atau QR (untuk QRIS), dan tombol "Bayar Sekarang (Simulasi)".
Tombol itu mengubah status donasi dari `pending` ke `paid`. Kalau donasi sudah
dibayar atau diverifikasi, halaman ini menampilkan status akhirnya.

**cek.php** — form pencarian kode donasi. Kalau kode ditemukan, menampilkan rincian
dan timeline: dicatat → dibayar → diverifikasi → disalurkan.

**login.php** — form login admin. Password dicek dengan `password_verify()`.
Parameter `?keluar=1` dipakai untuk logout.

**admin.php** — satu file untuk seluruh panel admin. Halaman dipilih lewat
`?halaman=`:

| Nilai | Isi |
|---|---|
| (kosong) | dashboard: ringkasan angka, grafik 6 bulan, donasi yang perlu diverifikasi |
| `program` | tabel program + pencarian, tombol edit dan hapus |
| `program_form` | form tambah/edit program |
| `donasi` | tabel donasi + filter status + aksi verifikasi/tolak |
| `laporan` | rekap per program dan per bulan |
| `export` | unduh CSV donasi terverifikasi |
| `pesan` | pesan dari form kontak |

Semua aksi POST (simpan program, hapus program, verifikasi, tolak, tandai pesan
dibaca) ditangani di bagian atas file yang sama, lalu redirect kembali ke halamannya.
Pola ini yang paling gampang dijelaskan: "prosesnya di atas, tampilannya di bawah".
