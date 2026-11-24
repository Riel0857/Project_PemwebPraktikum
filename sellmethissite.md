Kalau dilihat dari struktur dan isinya, **PeduliKasih** (`riel0857/project_pemwebpraktikum`) bukan sekadar tugas praktikum biasa yang cuma jalan di `localhost`[cite: 1, 3]. Secara arsitektur, keamanan, dan kesiapan *deployment*, repo ini udah *production-ready*[cite: 3].

Alasan kenapa web ini layak di-*publish* sekaligus nilai jual utamanya dibanding web donasi sejenis (khususnya proyek skala kampus atau komunitas):

### 1. Zero-Friction Donation & Transparansi Real-Time (`cek.php` & `is_anonymous`)
* **Tanpa Ribet Akun:** Banyak platform *crowdfunding* maksa donatur bikin akun dan verifikasi email dulu sebelum nyumbang, yang sering bikin orang batal donasi. Di sini, donatur langsung pilih program (`program.php`), isi nominal (`donasi.php`), bayar (`bayar.php`), dan dapet kode unik transaksi[cite: 3].
* **Pelacakan Transparan:** Fitur `cek.php` bikin siapa pun bisa melacak status verifikasi dan riwayat donasinya cukup modal kode transaksi[cite: 3]. Ditambah lagi ada opsi privasi (`is_anonymous` -> otomatis tampil sebagai *"Hamba Allah"* di level database), yang secara psikologis ngikutin budaya donasi di Indonesia.

### 2. Arsitektur Super Ringan & Kencang (Zero Bloatware)
* Seluruh aplikasi dibangun pakai **PHP Native dengan pola Page Controller / Embedded MVC** dan aset *custom* murni (`style.css` 49 KB, `script.js` 1.9 KB) tanpa ketergantungan framework raksasa atau ratusan *library* JavaScript pihak ketiga[cite: 3].
* **Dampaknya:** *Loading time* kilat bahkan di koneksi seluler lambat, dan konsumsi RAM/CPU server sangat minim, sehingga cocok banget buat di-host di VPS spek rendah atau *home server* sekalipun tanpa perlu *upgrade* hardware mahal.

### 3. Keamanan Standar Industri (Bukan Sekadar "Asal Jalan")
* **Anti SQL Injection:** Seluruh komunikasi database pakai **PDO Prepared Statements** (`prepare()` & `execute()`).
* **Proteksi Form & XSS:** Form autentikasi dan transaksi udah dilindungi token **CSRF** (`csrf_valid()`, `csrf_input()`) serta *output escaping* (`e()` / `htmlspecialchars`) buat nutup celah Cross-Site Scripting.
* **Kredensial Terenkripsi & Terisolasi:** Password admin di-hash pakai **Bcrypt** (`password_verify`), dan konfigurasi database dipisah ke `.env` (`.env.example`) supaya kredensial nggak bocor ke Git[cite: 3].

### 4. Kedaulatan Data & Bebas Potongan Platform (Self-Hosted Ready)
* Platform *charity* komersial biasanya memotong 5% sampai 10% dari total donasi sebagai *platform fee*. Dengan *deploy* web ini sendiri, 100% dana murni dikelola yayasan atau komunitas tanpa perantara.
* **Siap Deploy dalam Hitungan Menit:** Repo ini udah dilengkapi `Dockerfile`, `docker-compose.yml`, bahkan konfigurasi khusus untuk **Dockge** (`deploy/dockge/compose.yaml`) dan **pgAdmin** (`servers.json`), lengkap dengan skema database PostgreSQL (`01_schema.sql`) dan data awal (`02_seed.sql`)[cite: 3]. Tinggal tarik image dan jalanin container, sistem langsung *live* tanpa drama *dependency conflict*.

### 5. Dokumentasi Teknis Lengkap untuk Handover Tim
* Berbeda dari kebanyakan repo yang kosong dokumentasi, project ini punya 5 dokumen teknis terpisah di folder `docs/` (`ALUR-APLIKASI.md`, `DATABASE.md`, `DESIGN.md`, `PANDUAN-INSTALASI.md`, `STRUKTUR.md`)[cite: 3]. Kalau suatu saat web ini diserahkan ke pengurus yayasan atau developer penerus, mereka bisa langsung paham alur kerja dan skema datanya tanpa harus baca kode dari nol.

---

**Poin Pitching Singkat (Kalau buat presentasi / README):**
> *"PeduliKasih adalah platform penggalangan dana mandiri yang menggabungkan kecepatan PHP Native, ketangguhan database relasional PostgreSQL, dan keamanan berlapis (PDO, CSRF, Bcrypt). Dirancang dengan alur donasi tanpa hambatan akun serta pelacakan transaksi transparan, sistem ini sudah dikemas penuh dengan Docker dan Dockge sehingga siap di-publish secara mandiri tanpa potongan biaya platform pihak ketiga."*
