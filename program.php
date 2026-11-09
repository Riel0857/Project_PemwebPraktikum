<?php

require 'koneksi.php';
require 'layout.php';

if (isset($_GET['id'])) {
    $ambil = $pdo->prepare("
        SELECT p.*, c.name AS kategori, c.slug AS kategori_slug,
               s.collected_amount, s.donation_count
        FROM programs p
        JOIN categories c ON c.id = p.category_id
        JOIN v_program_stats s ON s.program_id = p.id
        WHERE p.id = ?
    ");
    $ambil->execute([(int) $_GET['id']]);
    $program = $ambil->fetch();

    if (!$program) {
        http_response_code(404);
        $judul = 'Program tidak ditemukan';
        tampil_atas($judul, 'program');
        echo '<section class="section"><div class="container"><div class="empty">'
            . '<h2>Program tidak ditemukan</h2>'
            . '<p>Program yang Anda cari sudah dihapus atau alamatnya salah.</p>'
            . '<a class="btn btn--primary" href="program.php">Lihat semua program</a>'
            . '</div></div></section>';
        tampil_bawah();
        exit;
    }

    $daftar = $pdo->prepare("
        SELECT d.amount, d.message, d.created_at,
               CASE WHEN d.is_anonymous THEN 'Hamba Allah' ELSE dn.name END AS nama
        FROM donations d
        JOIN donors dn ON dn.id = d.donor_id
        WHERE d.program_id = ? AND d.status = 'verified'
        ORDER BY d.created_at DESC
        LIMIT 10
    ");
    $daftar->execute([$program['id']]);
    $donasi = $daftar->fetchAll();

    $progres = persen($program['collected_amount'], $program['target_amount']);
    $sisa_hari = $program['deadline'] ? (int) ceil((strtotime($program['deadline']) - time()) / 86400) : null;
    $paragraf = preg_split('/\n\s*\n/', trim($program['description']));

    $judul = $program['title'];
    tampil_atas($judul, 'program');
    ?>

<section class="section section--tight">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Beranda</a><span>/</span>
            <a href="program.php">Program</a><span>/</span>
            <span><?= e($program['title']) ?></span>
        </div>

        <div class="detail-grid">
            <div>
                <div class="detail-cover">
                    <span class="detail-cover__monogram" aria-hidden="true"><?= e(strtoupper(substr($program['title'], 0, 1))) ?></span>
                    <span class="detail-cover__tag"><?= e($program['kategori']) ?></span>
                </div>

                <h1 class="detail-title"><?= e($program['title']) ?></h1>

                <div class="detail-meta">
                    <span>◉ <?= e($program['location'] ?: 'Lokasi menyusul') ?></span>
                    <span>◷ <?= $sisa_hari !== null && $sisa_hari > 0 ? $sisa_hari . ' hari lagi' : 'Tenggat ' . e(tanggal($program['deadline'])) ?></span>
                    <span>♥ <?= e($program['donation_count']) ?> donasi terverifikasi</span>
                </div>

                <div class="detail-body">
                    <?php foreach ($paragraf as $teks): ?>
                        <p><?= nl2br(e($teks)) ?></p>
                    <?php endforeach; ?>
                </div>

                <div class="card" style="margin-top: 32px;">
                    <div class="card__head">
                        <h2>Transparansi donasi</h2>
                        <span class="badge badge--success"><?= e($program['donation_count']) ?> terverifikasi</span>
                    </div>
                    <div class="card__body">
                        <?php if ($donasi === []): ?>
                            <p class="muted small">Belum ada donasi terverifikasi untuk program ini. Jadilah yang pertama.</p>
                        <?php else: ?>
                            <div class="donation-list">
                                <?php foreach ($donasi as $d): ?>
                                    <div class="donation-row">
                                        <span class="donation-row__avatar"><?= e(strtoupper(substr($d['nama'], 0, 1))) ?></span>
                                        <div class="donation-row__body">
                                            <div class="donation-row__name"><?= e($d['nama']) ?></div>
                                            <div class="donation-row__msg"><?= e($d['message'] ?: 'Berdonasi melalui PeduliKasih') ?></div>
                                        </div>
                                        <div>
                                            <div class="donation-row__amount"><?= e(rupiah($d['amount'])) ?></div>
                                            <div class="donation-row__msg ta-right"><?= e(tanggal($d['created_at'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="donasi-panel">
                <div class="donasi-panel__amount"><?= e(rupiah($program['collected_amount'])) ?></div>
                <div class="donasi-panel__target">terkumpul dari target <?= e(rupiah($program['target_amount'])) ?></div>

                <div class="progress">
                    <span class="progress__bar" style="width: <?= e($progres) ?>%"></span>
                </div>

                <div class="donasi-panel__rows">
                    <div>
                        <small>Progress</small>
                        <strong><?= e($progres) ?>%</strong>
                    </div>
                    <div>
                        <small>Donatur</small>
                        <strong><?= e($program['donation_count']) ?> orang</strong>
                    </div>
                    <div>
                        <small>Tenggat</small>
                        <strong><?= e(tanggal($program['deadline'])) ?></strong>
                    </div>
                    <div>
                        <small>Kategori</small>
                        <strong><?= e($program['kategori']) ?></strong>
                    </div>
                </div>

                <?php if ($program['status'] === 'aktif'): ?>
                    <a class="btn btn--primary btn--block" href="donasi.php?id=<?= e($program['id']) ?>">Donasi Sekarang</a>
                <?php else: ?>
                    <button class="btn btn--outline btn--block" type="button" disabled>Program sudah selesai</button>
                <?php endif; ?>

                <div class="trust-note">
                    Donasi Anda dicatat dengan kode unik dan dapat dilacak statusnya kapan saja.
                </div>
            </aside>
        </div>
    </div>
</section>

    <?php
    tampil_bawah();
    exit;
}

$cari = trim($_GET['q'] ?? '');
$slug_kategori = trim($_GET['kategori'] ?? '');
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$per_halaman = 9;
$offset = ($halaman - 1) * $per_halaman;

$kondisi = ["p.status = 'aktif'"];
$parameter = [];

if ($cari !== '') {
    $kondisi[] = '(p.title ILIKE ? OR p.summary ILIKE ? OR p.location ILIKE ?)';
    $parameter[] = '%' . $cari . '%';
    $parameter[] = '%' . $cari . '%';
    $parameter[] = '%' . $cari . '%';
}

if ($slug_kategori !== '') {
    $kondisi[] = 'c.slug = ?';
    $parameter[] = $slug_kategori;
}

$where = implode(' AND ', $kondisi);

$hitung = $pdo->prepare("SELECT COUNT(*) FROM programs p JOIN categories c ON c.id = p.category_id WHERE {$where}");
$hitung->execute($parameter);
$total = (int) $hitung->fetchColumn();
$jumlah_halaman = max(1, (int) ceil($total / $per_halaman));

$ambil = $pdo->prepare("
    SELECT p.*, c.name AS kategori, s.collected_amount, s.donation_count
    FROM programs p
    JOIN categories c ON c.id = p.category_id
    JOIN v_program_stats s ON s.program_id = p.id
    WHERE {$where}
    ORDER BY p.is_featured DESC, p.deadline ASC
    LIMIT {$per_halaman} OFFSET {$offset}
");
$ambil->execute($parameter);
$program = $ambil->fetchAll();

$kategori = $pdo->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();

$judul = 'Program Donasi';
tampil_atas($judul, 'program');
?>

<section class="section section--tight">
    <div class="container">
        <div class="page-head">
            <div class="page-head__inner">
                <div>
                    <span class="eyebrow">Program aktif</span>
                    <h1>Pilih program yang ingin Anda bantu</h1>
                    <p class="muted"><?= e($total) ?> program ditemukan<?= $slug_kategori !== '' ? ' pada kategori ' . e($slug_kategori) : '' ?>.</p>
                </div>
            </div>
        </div>

        <form class="filter-bar" method="get" action="program.php">
            <div class="chips">
                <a class="chip <?= $slug_kategori === '' ? 'is-active' : '' ?>" href="program.php">Semua</a>
                <?php foreach ($kategori as $k): ?>
                    <a class="chip <?= $slug_kategori === $k['slug'] ? 'is-active' : '' ?>"
                       href="program.php?kategori=<?= e($k['slug']) ?>"><?= e($k['name']) ?></a>
                <?php endforeach; ?>
            </div>

            <div class="search">
                <?php if ($slug_kategori !== ''): ?>
                    <input type="hidden" name="kategori" value="<?= e($slug_kategori) ?>">
                <?php endif; ?>
                <input type="search" name="q" value="<?= e($cari) ?>" placeholder="Cari program atau lokasi..." aria-label="Cari program">
                <button type="submit">Cari</button>
            </div>
        </form>

        <?php if ($program === []): ?>
            <div class="empty">
                <h2>Tidak ada program yang cocok</h2>
                <p>Coba kata kunci lain atau lihat semua program yang sedang berjalan.</p>
                <a class="btn btn--primary" href="program.php">Reset pencarian</a>
            </div>
        <?php else: ?>
            <div class="program-grid">
                <?php foreach ($program as $p): ?>
                    <?php $progres = persen($p['collected_amount'], $p['target_amount']); ?>
                    <article class="program-card">
                        <a class="program-card__cover" href="program.php?id=<?= e($p['id']) ?>">
                            <span class="program-card__cat"><?= e($p['kategori']) ?></span>
                            <span class="program-card__monogram" aria-hidden="true"><?= e(strtoupper(substr($p['title'], 0, 1))) ?></span>
                            <?php if (!empty($p['is_featured'])): ?>
                                <span class="program-card__flag">Pilihan</span>
                            <?php endif; ?>
                        </a>

                        <div class="program-card__body">
                            <h3><a href="program.php?id=<?= e($p['id']) ?>"><?= e($p['title']) ?></a></h3>
                            <p class="program-card__summary"><?= e($p['summary']) ?></p>

                            <div class="progress">
                                <span class="progress__bar" style="width: <?= e($progres) ?>%"></span>
                            </div>

                            <div class="program-card__stats">
                                <div>
                                    <small>Terkumpul</small>
                                    <strong><?= e(rupiah($p['collected_amount'])) ?></strong>
                                </div>
                                <div class="ta-right">
                                    <small>Target</small>
                                    <strong><?= e(rupiah($p['target_amount'])) ?></strong>
                                </div>
                            </div>

                            <div class="program-card__meta">
                                <span><?= e($progres) ?>% tercapai</span>
                                <span><?= e($p['donation_count']) ?> donasi</span>
                            </div>

                            <a class="btn btn--outline btn--block" href="donasi.php?id=<?= e($p['id']) ?>">Donasi Sekarang</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($jumlah_halaman > 1): ?>
                <nav class="pagination" aria-label="Navigasi halaman">
                    <?php for ($i = 1; $i <= $jumlah_halaman; $i++): ?>
                        <?php
                        $tautan = 'program.php?halaman=' . $i;
                        if ($slug_kategori !== '') {
                            $tautan .= '&kategori=' . urlencode($slug_kategori);
                        }
                        if ($cari !== '') {
                            $tautan .= '&q=' . urlencode($cari);
                        }
                        ?>
                        <a class="page <?= $i === $halaman ? 'is-active' : '' ?>" href="<?= e($tautan) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php tampil_bawah(); ?>
