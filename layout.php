<?php

function tampil_atas($judul, $aktif = '')
{
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> — PeduliKasih</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#konten">Lewati ke konten</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="/index.php">
            <span class="brand__mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" width="22" height="22" fill="currentColor">
                    <path d="M16 27S5 19.9 5 13.4A5.9 5.9 0 0 1 16 9.9a5.9 5.9 0 0 1 11 3.5C27 19.9 16 27 16 27z"/>
                </svg>
            </span>
            <span class="brand__text">
                <strong>PeduliKasih</strong>
                <small>Lembaga Amal</small>
            </span>
        </a>

        <input type="checkbox" id="nav-toggle" class="nav-toggle" hidden>
        <label for="nav-toggle" class="nav-burger" aria-label="Buka menu">
            <span></span><span></span><span></span>
        </label>

        <nav class="site-nav" aria-label="Navigasi utama">
            <a class="<?= $aktif === 'beranda' ? 'is-active' : '' ?>" href="/index.php">Beranda</a>
            <a class="<?= $aktif === 'program' ? 'is-active' : '' ?>" href="/program.php">Program</a>
            <a class="<?= $aktif === 'cek' ? 'is-active' : '' ?>" href="/cek.php">Cek Donasi</a>
            <a href="/index.php#tentang">Tentang</a>
            <a href="/index.php#kontak">Kontak</a>
            <a class="btn btn--primary btn--sm" href="/program.php">Donasi Sekarang</a>
        </nav>
    </div>
</header>

<main id="konten">
<?php tampil_flash(); ?>
    <?php
}

function tampil_bawah()
{
    ?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand brand--footer" href="/index.php">
                <span class="brand__mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" width="22" height="22" fill="currentColor">
                        <path d="M16 27S5 19.9 5 13.4A5.9 5.9 0 0 1 16 9.9a5.9 5.9 0 0 1 11 3.5C27 19.9 16 27 16 27z"/>
                    </svg>
                </span>
                <span class="brand__text"><strong>PeduliKasih</strong><small>Lembaga Amal</small></span>
            </a>
            <p class="footer-desc">
                Menghubungkan kebaikan donatur dengan penerima manfaat melalui program
                yang terverifikasi, transparan, dan dapat dipertanggungjawabkan.
            </p>
        </div>
        <div>
            <h4>Program</h4>
            <ul class="footer-links">
                <li><a href="/program.php?kategori=pendidikan">Pendidikan</a></li>
                <li><a href="/program.php?kategori=kesehatan">Kesehatan</a></li>
                <li><a href="/program.php?kategori=kemanusiaan">Kemanusiaan</a></li>
                <li><a href="/program.php?kategori=lingkungan">Lingkungan</a></li>
            </ul>
        </div>
        <div>
            <h4>Informasi</h4>
            <ul class="footer-links">
                <li><a href="/index.php#tentang">Tentang Kami</a></li>
                <li><a href="/cek.php">Cek Status Donasi</a></li>
                <li><a href="/index.php#kontak">Hubungi Kami</a></li>
                <li><a href="/login.php">Masuk Admin</a></li>
            </ul>
        </div>
        <div>
            <h4>Kontak</h4>
            <ul class="footer-links footer-links--plain">
                <li>Jl. Cendana No. 12, Bandung</li>
                <li>halo@pedulikasih.test</li>
                <li>(022) 555-0134</li>
                <li>Senin–Jumat, 08.00–17.00 WIB</li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> PeduliKasih. Dibuat untuk tugas praktikum Pemrograman Web.</p>
        <p>PHP 8.2 · PostgreSQL 16 · Docker</p>
    </div>
</footer>

<script src="/assets/js/script.js" defer></script>
</body>
</html>
    <?php
}

function tampil_admin_atas($judul, $aktif = 'dashboard')
{
    $jumlah_pending = (int) $GLOBALS['pdo']->query("SELECT COUNT(*) FROM donations WHERE status = 'paid'")->fetchColumn();
    $jumlah_pesan = (int) $GLOBALS['pdo']->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = FALSE")->fetchColumn();
    $admin = $_SESSION['admin_nama'] ?? 'Admin';
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> — Admin PeduliKasih</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<a class="skip-link" href="#konten">Lewati ke konten</a>

<input type="checkbox" id="sidebar-toggle" class="nav-toggle" hidden>

<div class="admin-shell">
    <aside class="sidebar">
        <a class="brand brand--sidebar" href="/admin.php">
            <span class="brand__mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
                    <path d="M16 27S5 19.9 5 13.4A5.9 5.9 0 0 1 16 9.9a5.9 5.9 0 0 1 11 3.5C27 19.9 16 27 16 27z"/>
                </svg>
            </span>
            <span class="brand__text"><strong>PeduliKasih</strong><small>Panel Admin</small></span>
        </a>

        <nav class="sidebar-nav" aria-label="Navigasi admin">
            <span class="sidebar-label">Ringkasan</span>
            <a class="<?= $aktif === 'dashboard' ? 'is-active' : '' ?>" href="/admin.php">
                <span class="ico">▦</span> Dashboard
            </a>

            <span class="sidebar-label">Pengelolaan</span>
            <a class="<?= $aktif === 'program' ? 'is-active' : '' ?>" href="/admin.php?halaman=program">
                <span class="ico">▤</span> Program
            </a>
            <a class="<?= $aktif === 'kategori' ? 'is-active' : '' ?>" href="/admin.php?halaman=kategori">
                <span class="ico">▧</span> Kategori
            </a>
            <a class="<?= $aktif === 'donasi' ? 'is-active' : '' ?>" href="/admin.php?halaman=donasi">
                <span class="ico">✓</span> Verifikasi Donasi
                <?php if ($jumlah_pending > 0): ?><span class="pill"><?= $jumlah_pending ?></span><?php endif; ?>
            </a>
            <a class="<?= $aktif === 'pesan' ? 'is-active' : '' ?>" href="/admin.php?halaman=pesan">
                <span class="ico">✉</span> Pesan
                <?php if ($jumlah_pesan > 0): ?><span class="pill"><?= $jumlah_pesan ?></span><?php endif; ?>
            </a>
            <a class="<?= $aktif === 'laporan' ? 'is-active' : '' ?>" href="/admin.php?halaman=laporan">
                <span class="ico">▥</span> Laporan
            </a>

            <span class="sidebar-label">Lainnya</span>
            <a href="/index.php" target="_blank" rel="noopener"><span class="ico">↗</span> Lihat Situs</a>
        </nav>

        <div class="sidebar-user">
            <div class="avatar"><?= e(strtoupper(substr($admin, 0, 1))) ?></div>
            <div class="sidebar-user__meta">
                <strong><?= e($admin) ?></strong>
                <small><?= e($_SESSION['admin_email'] ?? '') ?></small>
            </div>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <label for="sidebar-toggle" class="nav-burger nav-burger--dark" aria-label="Buka menu">
                <span></span><span></span><span></span>
            </label>
            <div class="admin-topbar__title">
                <h1><?= e($judul) ?></h1>
                <p><?= tanggal(date('Y-m-d')) ?> · <?= date('H:i') ?> WIB</p>
            </div>
            <div class="admin-topbar__actions">
                <a class="btn btn--ghost btn--sm" href="/index.php" target="_blank" rel="noopener">Lihat Situs</a>
                <a class="btn btn--outline btn--sm" href="/login.php?keluar=1">Keluar</a>
            </div>
        </header>

        <div class="admin-content" id="konten">
            <?php tampil_flash(); ?>
    <?php
}

function tampil_admin_bawah()
{
    ?>
        </div>
    </div>
</div>

<script src="/assets/js/script.js" defer></script>
</body>
</html>
    <?php
}
