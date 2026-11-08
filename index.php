<?php

require 'koneksi.php';
require 'layout.php';

$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pesan'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if ($nama === '') {
        $error['nama'] = 'Nama wajib diisi.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'Email tidak valid.';
    }

    if ($subjek === '') {
        $error['subjek'] = 'Subjek wajib diisi.';
    }

    if (strlen($pesan) < 10) {
        $error['pesan'] = 'Pesan minimal 10 karakter.';
    }

    if ($error === [] && !csrf_valid()) {
        $error['umum'] = 'Sesi formulir sudah habis, silakan coba lagi.';
    }

    if ($error === []) {
        $simpan = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $simpan->execute([$nama, $email, $subjek, $pesan]);

        flash('sukses', 'Pesan Anda sudah kami terima. Terima kasih sudah menghubungi PeduliKasih.');
        redirect('index.php#kontak');
    }
}

$ringkasan = $pdo->query("SELECT COUNT(*) AS jumlah, COALESCE(SUM(amount), 0) AS total FROM donations WHERE status = 'verified'")->fetch();
$jumlah_program = (int) $pdo->query("SELECT COUNT(*) FROM programs WHERE status = 'aktif'")->fetchColumn();
$jumlah_donatur = (int) $pdo->query('SELECT COUNT(*) FROM donors')->fetchColumn();

$program = $pdo->query("
    SELECT p.*, c.name AS kategori, s.collected_amount, s.donation_count
    FROM programs p
    JOIN categories c ON c.id = p.category_id
    JOIN v_program_stats s ON s.program_id = p.id
    WHERE p.status = 'aktif'
    ORDER BY p.is_featured DESC, p.deadline ASC
    LIMIT 6
")->fetchAll();

$kategori = $pdo->query("
    SELECT c.name, c.slug, COUNT(p.id) AS jumlah
    FROM categories c
    LEFT JOIN programs p ON p.category_id = c.id AND p.status = 'aktif'
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.name
")->fetchAll();

$donasi = $pdo->query("
    SELECT d.amount, d.message, d.created_at, p.title,
           CASE WHEN d.is_anonymous THEN 'Hamba Allah' ELSE dn.name END AS nama
    FROM donations d
    JOIN programs p ON p.id = d.program_id
    JOIN donors dn ON dn.id = d.donor_id
    WHERE d.status = 'verified'
    ORDER BY d.created_at DESC
    LIMIT 6
")->fetchAll();

$judul = 'Beranda';
tampil_atas($judul, 'beranda');
?>

<section class="hero">
    <div class="container hero__grid">
        <div>
            <span class="eyebrow">Lembaga amal terverifikasi</span>
            <h1>Kebaikan kecil,<br><em>dampak yang nyata.</em></h1>
            <p class="hero__lead">
                PeduliKasih menghubungkan donatur dengan program pendidikan, kesehatan,
                kemanusiaan, dan lingkungan yang dikelola bersama relawan lapangan.
                Setiap rupiah dicatat, diverifikasi, dan dilaporkan secara terbuka.
            </p>
            <div class="hero__actions">
                <a class="btn btn--accent" href="program.php">Salurkan Donasi</a>
                <a class="btn btn--outline" href="cek.php">Cek Status Donasi</a>
            </div>
            <div class="hero__trust">
                <span>Laporan transparan</span>
                <span>Donasi terverifikasi</span>
                <span>Relawan terlatih</span>
            </div>
        </div>

        <aside class="impact-card">
            <span class="impact-card__label">Total dana tersalurkan</span>
            <div class="impact-card__value"><?= e(rupiah($ringkasan['total'])) ?></div>
            <div class="impact-card__sub">
                dari <?= e(number_format($jumlah_donatur, 0, ',', '.')) ?> donatur di seluruh Indonesia
            </div>

            <div class="impact-list">
                <?php foreach (array_slice($program, 0, 3) as $item): ?>
                    <?php $progres = persen($item['collected_amount'], $item['target_amount']); ?>
                    <div class="impact-list__item">
                        <span><?= e($item['title']) ?></span>
                        <b><?= e($progres) ?>%</b>
                        <div class="progress">
                            <span class="progress__bar" style="width: <?= e($progres) ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="impact-card__foot">
                <span><?= e($jumlah_program) ?> program aktif</span>
                <a class="link-arrow" href="program.php">Lihat semua</a>
            </div>
        </aside>
    </div>
</section>

<section class="section--tight">
    <div class="container">
        <div class="stats">
            <div class="stat">
                <div class="stat__value"><?= e(rupiah($ringkasan['total'])) ?></div>
                <div class="stat__label">Dana tersalurkan</div>
            </div>
            <div class="stat">
                <div class="stat__value"><?= e(number_format($jumlah_donatur, 0, ',', '.')) ?></div>
                <div class="stat__label">Donatur terdaftar</div>
            </div>
            <div class="stat">
                <div class="stat__value"><?= e($jumlah_program) ?></div>
                <div class="stat__label">Program berjalan</div>
            </div>
            <div class="stat">
                <div class="stat__value"><?= e($ringkasan['jumlah']) ?></div>
                <div class="stat__label">Donasi terverifikasi</div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="section-head__text">
                <h2>Program yang sedang berjalan</h2>
                <p>Pilih program yang paling dekat dengan kepedulian Anda. Setiap program memiliki target, tenggat, dan laporan penggunaan dana.</p>
            </div>
            <a class="link-arrow" href="program.php">Semua program</a>
        </div>

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
    </div>
</section>

<section class="section section--muted">
    <div class="container">
        <div class="section-head">
            <div class="section-head__text">
                <h2>Bidang yang kami kelola</h2>
                <p>Empat fokus utama dengan pendekatan yang berbeda sesuai kebutuhan penerima manfaat.</p>
            </div>
        </div>

        <div class="chips">
            <?php foreach ($kategori as $k): ?>
                <a class="chip" href="program.php?kategori=<?= e($k['slug']) ?>">
                    <?= e($k['name']) ?>
                    <small><?= e($k['jumlah']) ?> program</small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="section-head__text">
                <h2>Bagaimana donasi Anda bekerja</h2>
                <p>Tiga langkah sederhana, dari niat baik sampai laporan penggunaan dana.</p>
            </div>
        </div>

        <div class="steps">
            <article class="step">
                <h3>Pilih program</h3>
                <p>Telusuri program yang sedang berjalan, baca latar belakang, target dana, dan tenggatnya.</p>
            </article>
            <article class="step">
                <h3>Donasi &amp; bayar</h3>
                <p>Isi formulir donasi, bayar lewat halaman pembayaran simulasi, lalu simpan kode donasi Anda.</p>
            </article>
            <article class="step">
                <h3>Verifikasi &amp; laporan</h3>
                <p>Tim kami memverifikasi pembayaran. Dana disalurkan dan laporannya dipublikasikan berkala.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="container">
        <div class="section-head">
            <div class="section-head__text">
                <h2>Kebaikan yang sudah terjadi</h2>
                <p>Donasi terverifikasi terbaru dari para donatur. Sebagian memilih tampil tanpa nama.</p>
            </div>
            <a class="link-arrow" href="program.php">Ikut berkontribusi</a>
        </div>

        <div class="wall">
            <?php foreach ($donasi as $d): ?>
                <article class="wall__item">
                    <div class="wall__top">
                        <span class="wall__avatar"><?= e(strtoupper(substr($d['nama'], 0, 1))) ?></span>
                        <div>
                            <div class="wall__name"><?= e($d['nama']) ?></div>
                            <div class="wall__meta"><?= e($d['title']) ?></div>
                        </div>
                        <div class="wall__amount"><?= e(rupiah($d['amount'])) ?></div>
                    </div>
                    <?php if (!empty($d['message'])): ?>
                        <p class="wall__msg">“<?= e($d['message']) ?>”</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="tentang">
    <div class="container">
        <div class="section-head">
            <div class="section-head__text">
                <h2>Tentang PeduliKasih</h2>
                <p>Lembaga amal kecil yang percaya bahwa transparansi adalah bagian dari ibadah.</p>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-body">
                <p>
                    PeduliKasih berdiri sejak 2019 dan dikelola oleh lima orang staf serta puluhan
                    relawan lapangan di Jawa Barat. Kami mengumpulkan donasi untuk program
                    pendidikan, kesehatan, kemanusiaan, dan lingkungan.
                </p>
                <p>
                    Setiap program diverifikasi sebelum ditayangkan: latar belakang penerima manfaat,
                    anggaran, dan tenggat pelaksanaan diperiksa oleh tim program. Setelah dana masuk,
                    admin memverifikasi bukti pembayaran satu per satu sebelum donasi dihitung
                    sebagai dana terkumpul.
                </p>
                <p>
                    Laporan penggunaan dana dipublikasikan setiap tiga bulan melalui menu laporan
                    di panel admin dan bisa diminta langsung lewat formulir kontak.
                </p>
            </div>

            <aside class="info-card">
                <div class="info-card__ico">◎</div>
                <h3>Alamat sekretariat</h3>
                <p class="small muted">Jl. Cendana No. 12, Bandung 40123</p>
                <div class="info-card__ico">✆</div>
                <h3>Telepon</h3>
                <p class="small muted">(022) 555-0134 · Senin–Jumat 08.00–17.00 WIB</p>
                <div class="info-card__ico">✉</div>
                <h3>Email</h3>
                <p class="small muted">halo@pedulikasih.test</p>
            </aside>
        </div>
    </div>
</section>

<section class="section section--muted" id="kontak">
    <div class="container">
        <div class="section-head">
            <div class="section-head__text">
                <h2>Hubungi kami</h2>
                <p>Ada pertanyaan tentang program atau ingin menjadi relawan? Kirim pesan di bawah ini.</p>
            </div>
        </div>

        <div class="contact-grid">
            <form class="form-card" method="post" action="index.php#kontak" novalidate>
                <?= csrf_input() ?>

                <?php if (!empty($error['umum'])): ?>
                    <div class="alert alert--danger" role="alert" style="margin-bottom: 18px;">
                        <span class="alert__icon">!</span>
                        <p><?= e($error['umum']) ?></p>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="field <?= isset($error['nama']) ? 'field--error' : '' ?>">
                        <label for="nama">Nama</label>
                        <input class="input" type="text" id="nama" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required>
                        <?php if (isset($error['nama'])): ?><span class="error-text"><?= e($error['nama']) ?></span><?php endif; ?>
                    </div>

                    <div class="field <?= isset($error['email']) ? 'field--error' : '' ?>">
                        <label for="email">Email</label>
                        <input class="input" type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
                        <?php if (isset($error['email'])): ?><span class="error-text"><?= e($error['email']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="field <?= isset($error['subjek']) ? 'field--error' : '' ?>">
                    <label for="subjek">Subjek</label>
                    <input class="input" type="text" id="subjek" name="subjek" value="<?= e($_POST['subjek'] ?? '') ?>" required>
                    <?php if (isset($error['subjek'])): ?><span class="error-text"><?= e($error['subjek']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($error['pesan']) ? 'field--error' : '' ?>">
                    <label for="pesan">Pesan</label>
                    <textarea class="textarea" id="pesan" name="pesan" rows="5" required><?= e($_POST['pesan'] ?? '') ?></textarea>
                    <?php if (isset($error['pesan'])): ?><span class="error-text"><?= e($error['pesan']) ?></span><?php endif; ?>
                </div>

                <button class="btn btn--primary" type="submit" name="kirim_pesan" value="1">Kirim Pesan</button>
            </form>

            <aside class="info-card">
                <h3>Kantor kami</h3>
                <p class="small muted">
                    Jl. Cendana No. 12<br>
                    Bandung, Jawa Barat 40123
                </p>
                <p class="small muted">
                    Pesan yang masuk akan dibalas pada hari kerja berikutnya.
                    Untuk urusan donasi, sertakan kode donasi Anda agar lebih cepat ditelusuri.
                </p>
            </aside>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta">
            <div>
                <h2>Punya pertanyaan atau ingin menjadi relawan?</h2>
                <p>Kami terbuka untuk kolaborasi dengan individu, komunitas, maupun perusahaan yang ingin berkontribusi.</p>
            </div>
            <a class="btn btn--accent" href="index.php#kontak">Hubungi Kami</a>
        </div>
    </div>
</section>

<?php tampil_bawah(); ?>
