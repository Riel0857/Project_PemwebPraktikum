# Panduan Instalasi

Panduan lengkap menjalankan PeduliKasih di Docker, baik dengan database baru maupun
dengan PostgreSQL yang sudah berjalan.

## 1. Prasyarat

| Kebutuhan | Versi minimal | Cek dengan |
|---|---|---|
| Docker Engine | 24 | `docker --version` |
| Docker Compose | v2 | `docker compose version` |
| Git | 2.x | `git --version` |
| Port 8080 | bebas | `ss -ltn \| grep 8080` |
| Port 5433 | bebas | `ss -ltn \| grep 5433` |
| Port 5050 | bebas, untuk pgAdmin | `ss -ltn \| grep 5050` |
| Port 5001 | opsional, untuk Dockge | `ss -ltn \| grep 5001` |

Tidak perlu PHP, PostgreSQL, atau Composer di host — semuanya di dalam container.

## 2. Opsi A — Database baru (disarankan)

```bash
git clone https://github.com/Riel0857/Project_PemwebPraktikum.git
cd Project_PemwebPraktikum

cp .env.example .env
docker compose up -d --build
```

Yang terjadi:

1. Image `pedulikasih-app` dibangun dari `Dockerfile` (PHP 8.2 + Apache + `pdo_pgsql`).
2. Container `pedulikasih_db` (PostgreSQL 16) dibuat, lalu menjalankan
   `database/01_schema.sql` dan `database/02_seed.sql` karena volume masih kosong.
3. Container `pedulikasih_app` menunggu database sehat, kemudian Apache melayani
   aplikasi di port `8080`.
4. Container `pedulikasih_pgadmin` menyala di port `5050` sebagai GUI database.

Verifikasi:

```bash
docker compose ps
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/
# 200
```

Buka <http://localhost:8080>, login admin di <http://localhost:8080/login>.

### 2.1 Masuk ke pgAdmin

<http://localhost:5050> langsung terbuka tanpa halaman login karena container
berjalan di mode desktop (`PGADMIN_CONFIG_SERVER_MODE=False`).

Server `PeduliKasih` sudah diisi otomatis dari
`deploy/dockge/pgadmin/servers.json` yang di-mount ke `/pgadmin4/servers.json`.
Klik server itu, lalu masukkan password database (nilai `DB_PASSWORD` di `.env`)
dan centang **Save password**.

Kalau mau menambah server sendiri, isiannya:

| Field | Nilai |
|---|---|
| Name | `PeduliKasih` |
| Host name/address | `db` |
| Port | `5432` |
| Maintenance database | `pedulikasih` |
| Username | `peduli` |
| Password | sama dengan `DB_PASSWORD` di `.env` |

`db` adalah nama service PostgreSQL di compose. Kalau diisi `localhost`, pgAdmin
akan mencari database di dalam containernya sendiri dan gagal connect. Daftar
server dan password tersimpan di volume `pedulikasih_pgadmin`.

File `servers.json` hanya dibaca saat container pgAdmin pertama kali dibuat. Kalau
container sudah terlanjur ada sebelum file itu masuk, jalankan
`docker compose up -d --force-recreate pgadmin`.

## 3. Opsi B — Memakai PostgreSQL yang sudah ada

Asumsi: container `postgres_db` pada network `postgresql_default`, dengan superuser
`admin` dan database `mydb` (sesuaikan dengan milik Anda).

### 3.1 Buat database & user

```bash
docker exec -it postgres_db psql -U admin -d postgres <<'SQL'
CREATE DATABASE pedulikasih;
CREATE USER peduli WITH PASSWORD 'peduli_secret';
GRANT ALL PRIVILEGES ON DATABASE pedulikasih TO peduli;
SQL
```

### 3.2 Impor skema & seed

```bash
docker exec -i postgres_db psql -U admin -d pedulikasih < database/01_schema.sql
docker exec -i postgres_db psql -U admin -d pedulikasih < database/02_seed.sql
```

### 3.3 Jalankan aplikasi

`docker-compose.existing-db.yml` tidak membuat service database; ia menyambung ke
network eksternal.

```bash
DB_HOST=postgres_db \
DB_NAME=pedulikasih \
DB_USER=peduli \
DB_PASSWORD=peduli_secret \
DB_NETWORK=postgresql_default \
docker compose -f docker-compose.existing-db.yml up -d --build
```

Nilai default ada di dalam file compose; menimpanya lewat environment seperti di atas
lebih aman daripada mengedit file. Untuk melihat nilai aktual container database:

```bash
docker inspect postgres_db --format '{{range .Config.Env}}{{println .}}{{end}}' | grep -E "POSTGRES_(DB|USER)"
docker inspect postgres_db --format '{{range $k, $v := .NetworkSettings.Networks}}{{println $k}}{{end}}'
```

### 3.4 Izin tabel

Bila aplikasi gagal membaca tabel meski koneksi berhasil, pastikan user `peduli`
memiliki hak pada tabel yang dibuat oleh user lain:

```bash
docker exec -it postgres_db psql -U admin -d pedulikasih <<'SQL'
GRANT ALL ON ALL TABLES IN SCHEMA public TO peduli;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO peduli;
SQL
```

## 4. Opsi C — Dikelola lewat Dockge

[Dockge](https://github.com/louislam/dockge) adalah pengelola stack Compose berbasis
web: start/stop, edit `compose.yaml` dan `.env`, rebuild, serta log semuanya dari
browser.

Asumsi: Dockge berjalan di <http://localhost:5001> dengan `DOCKGE_STACKS_DIR=/opt/stacks`
(di host, `/opt/stacks` di-mount ke container Dockge).

### 4.1 Pasang stack

```bash
cd Project_PemwebPraktikum
mkdir -p /opt/stacks/pedulikasih
cp deploy/dockge/compose.yaml /opt/stacks/pedulikasih/compose.yaml
cp deploy/dockge/.env.example /opt/stacks/pedulikasih/.env
```

Edit `/opt/stacks/pedulikasih/.env`:

```dotenv
APP_SOURCE=/home/faiq/Project_PemwebPraktikum   # path absolut repo ini
APP_PORT=8080
DB_PASSWORD=isi_password_kuat
```

`APP_SOURCE` wajib benar: Dockge menjalankan compose dari `/opt/stacks/pedulikasih`,
sedangkan kode, `Dockerfile`, dan `database/*.sql` berada di folder repo. Nilai ini
dipakai untuk `build.context` dan bind mount.

### 4.2 Pindahkan dari stack manual

Bila aplikasi sebelumnya dijalankan dari folder repo, hentikan dulu agar nama
container dan port tidak bentrok:

```bash
cd Project_PemwebPraktikum
docker compose down          # volume pedulikasih_pgdata tetap ada
```

Nama project di stack Dockge juga `pedulikasih`, jadi volume data yang sama dipakai
ulang — donasi yang sudah ada tidak hilang.

### 4.3 Jalankan dari Dockge

1. Buka <http://localhost:5001>, stack **pedulikasih** muncul di daftar (Dockge
   memindai `/opt/stacks`).
2. Klik **Start**. Status ketiga container harus `healthy`.
3. Tab **Compose** dan **Env** dipakai untuk mengubah konfigurasi; tab **Logs** dan
   **Terminal** untuk memeriksa aplikasi.

Setara lewat CLI (tanpa UI):

```bash
docker compose -f /opt/stacks/pedulikasih/compose.yaml up -d --build
docker compose -f /opt/stacks/pedulikasih/compose.yaml ps
```

### 4.4 Yang perlu diingat

- Bind mount memakai path absolut, jadi perubahan kode di repo langsung terlihat;
  rebuild hanya perlu saat `Dockerfile` berubah.
- Jangan menjalankan stack repo dan stack Dockge bersamaan — keduanya memakai nama
  project, container, dan port yang sama.
- File `.env` di folder stack berisi kata sandi; jangan di-commit. Repo hanya
  menyimpan `deploy/dockge/.env.example`.
- Jika Dockge menampilkan stack tetapi tombol Start gagal, periksa
  `docker logs dockge-dockge-1` dan pastikan socket Docker ter-mount
  (`/var/run/docker.sock`).

## 5. Perintah harian

```bash
docker compose ps                     # status container
docker compose logs -f app            # log Apache/PHP
docker compose logs -f db             # log PostgreSQL
docker compose restart app            # restart aplikasi
docker compose down                   # hentikan (data tetap)
docker compose down -v                # hentikan + hapus volume (seed ulang)
docker compose exec app bash          # masuk ke container aplikasi
docker compose exec db psql -U peduli -d pedulikasih
```

Contoh query cepat:

```bash
docker compose exec -T db psql -U peduli -d pedulikasih -c \
  "SELECT code, status, amount FROM donations ORDER BY id DESC LIMIT 5;"
```

## 6. Mengubah konfigurasi

Semua konfigurasi aplikasi dibaca dari environment container, dengan fallback ke
`.env`. Variabel yang tersedia:

| Variabel | Default | Keterangan |
|---|---|---|
| `APP_ENV` | `local` | Label lingkungan |
| `APP_DEBUG` | `true` | `true` menampilkan pesan exception di halaman 500 |
| `APP_URL` | `http://localhost:8080` | URL publik (informasi) |
| `APP_PORT` | `8080` | Port host untuk aplikasi |
| `APP_TIMEZONE` | `Asia/Jakarta` | Timezone PHP |
| `DB_HOST` | `db` | Nama service/host database |
| `DB_PORT` | `5432` | Port di dalam network Docker |
| `DB_NAME` | `pedulikasih` | Nama database |
| `DB_USER` | `peduli` | User database |
| `DB_PASSWORD` | `peduli_secret` | Kata sandi database |
| `DB_PORT_HOST` | `5433` | Port host yang dipetakan ke PostgreSQL |
| `PGADMIN_PORT` | `5050` | Port host untuk pgAdmin |
| `PGADMIN_EMAIL` | `admin@pedulikasih.com` | Email login pgAdmin (dipakai saat inisialisasi) |
| `PGADMIN_PASSWORD` | `admin123` | Kata sandi pgAdmin |

Setelah mengubah `.env`, jalankan ulang:

```bash
docker compose up -d
```

> `APP_DEBUG=true` hanya untuk pengembangan. Saat demonstrasi/publikasi, set
> `APP_DEBUG=false` agar detail exception tidak tampil ke pengguna.

## 7. Menjalankan tanpa Docker (opsional)

Hanya untuk pengembangan cepat bila PHP 8.2 dan PostgreSQL tersedia di host:

```bash
php -m | grep pdo_pgsql          # pastikan ekstensi ada
php -S localhost:8080            # jalankan dari folder project
```

Catatan: server bawaan PHP tidak membaca `.htaccess`, tapi semua halaman dipanggil
langsung lewat nama file (`index.php`, `program.php`, dst), jadi tidak butuh router.
Aset CSS dan JS ikut terlayani dari folder `assets/`.

## 8. Troubleshooting

| Gejala | Diagnosis | Solusi |
|---|---|---|
| `could not find driver` | Ekstensi `pdo_pgsql` tidak ada | `docker compose build --no-cache app` lalu `up -d` |
| `connection refused` ke `db` | Database belum siap | Tunggu status `healthy`; cek `docker compose logs db` |
| `password authentication failed` | `DB_*` tidak cocok dengan container | Samakan nilai di `.env`/environment dengan `docker inspect` |
| `relation "programs" does not exist` | Skema tidak diimpor | Impor `01_schema.sql`, atau `down -v` lalu `up` |
| Halaman kosong / 500 | Ada error PHP | `docker compose logs app`; set `APP_DEBUG=true` sementara |
| Aset CSS 404 | Folder aset belum ada | Pastikan `assets/css/style.css` ada dan DocumentRoot mengarah ke root project |
| Port sudah dipakai | Konflik host | Ubah `APP_PORT`/`DB_PORT_HOST` di `.env` |
| Login selalu gagal | Seed belum jalan / email beda | `SELECT email FROM users;`; buat hash baru dengan `password_hash` |
| Muncul pesan "sesi formulir sudah habis" | Token CSRF kedaluwarsa / cookie diblokir | Muat ulang halaman; pastikan cookie diizinkan |
| Perubahan kode tidak terlihat | Cache opcode / mount | `docker compose restart app`; pastikan bind mount `./:/var/www/html` aktif |
| Stack tidak muncul di Dockge | Folder bukan di `/opt/stacks` atau tanpa `compose.yaml` | Pastikan `/opt/stacks/pedulikasih/compose.yaml` ada, lalu muat ulang halaman Dockge |
| Dockge: `spawn docker ENOENT` / Start gagal | Socket Docker tidak ter-mount atau direktori stack hilang | Cek `docker inspect dockge-dockge-1` bagian Mounts; `docker restart dockge-dockge-1` |
| Dockge: `APP_SOURCE` tidak ditemukan | Path repo berbeda di host | Perbaiki `APP_SOURCE` di `/opt/stacks/pedulikasih/.env`, lalu Start ulang |

## 9. Checklist sebelum diserahkan / dipublikasikan

- [ ] `APP_DEBUG=false` di `.env` produksi.
- [ ] Kata sandi admin diganti dari `admin123`.
- [ ] `DB_PASSWORD` kuat dan tidak sama dengan contoh.
- [ ] `.env` tidak ikut ter-commit (`git status` tidak menampilkannya).
- [ ] Data seed fiktif diganti data lembaga yang sebenarnya bila dipakai nyata.
- [ ] Rekening/QRIS pada halaman sukses disesuaikan dengan milik lembaga.
- [ ] Backup database: `docker compose exec -T db pg_dump -U peduli pedulikasih > backup.sql`.
