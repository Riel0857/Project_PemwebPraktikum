<?php

require 'koneksi.php';
require 'layout.php';

$id_program = (int) ($_POST['program_id'] ?? $_GET['id'] ?? 0);

$ambil = $pdo->prepare("
    SELECT p.*, c.name AS kategori, s.collected_amount
    FROM programs p
    JOIN categories c ON c.id = p.category_id
    JOIN v_program_stats s ON s.program_id = p.id
    WHERE p.id = ?
");
$ambil->execute([$id_program]);
$program = $ambil->fetch();

if (!$program || $program['status'] !== 'aktif') {
    http_response_code(404);
    $judul = 'Program tidak tersedia';
    tampil_atas($judul, 'program');
    echo '<section class="section"><div class="container"><div class="empty">'
        . '<h2>Program tidak tersedia</h2>'
        . '<p>Program ini sudah selesai atau tidak ditemukan.</p>'
        . '<a class="btn btn--primary" href="program.php">Lihat program lain</a>'
        . '</div></div></section>';
    tampil_bawah();
    exit;
}

$error = [];
$metode_boleh = ['transfer_bank', 'qris', 'ewallet'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $nominal = (int) ($_POST['nominal'] ?? 0);
    $metode = $_POST['metode'] ?? 'transfer_bank';
    $pesan = trim($_POST['pesan'] ?? '');
    $anonim = isset($_POST['anonim']) ? true : false;

    if ($nama === '') {
        $error['nama'] = 'Nama wajib diisi.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'Email tidak valid.';
    }

    if ($nominal < 10000) {
        $error['nominal'] = 'Nominal minimal Rp 10.000.';
    }

    if (!in_array($metode, $metode_boleh, true)) {
        $error['metode'] = 'Metode pembayaran tidak dikenal.';
    }

    if (strlen($pesan) > 500) {
        $error['pesan'] = 'Pesan maksimal 500 karakter.';
    }

    if (!csrf_valid()) {
        $error['umum'] = 'Sesi formulir sudah habis, silakan kirim ulang.';
    }

    if ($error === []) {
        $cari_donatur = $pdo->prepare('SELECT id FROM donors WHERE email = ?');
        $cari_donatur->execute([$email]);
        $donatur_id = $cari_donatur->fetchColumn();

        if ($donatur_id) {
            $ubah = $pdo->prepare('UPDATE donors SET name = ?, phone = ? WHERE id = ?');
            $ubah->execute([$nama, $telepon, $donatur_id]);
        } else {
            $tambah = $pdo->prepare('INSERT INTO donors (name, email, phone) VALUES (?, ?, ?) RETURNING id');
            $tambah->execute([$nama, $email, $telepon]);
            $donatur_id = $tambah->fetchColumn();
        }

        $kode = '';
        for ($i = 0; $i < 5; $i++) {
            $calon = 'DON-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            $cek = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE code = ?');
            $cek->execute([$calon]);

            if ((int) $cek->fetchColumn() === 0) {
                $kode = $calon;
                break;
            }
        }

        if ($kode === '') {
            $kode = 'DON-' . date('Ymd-His');
        }

        if ($metode === 'transfer_bank') {
            $referensi = '8808' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } elseif ($metode === 'qris') {
            $referensi = 'QR' . strtoupper(substr(md5($kode), 0, 10));
        } else {
            $referensi = '08' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        }

        $simpan = $pdo->prepare("
            INSERT INTO donations
                (code, program_id, donor_id, amount, method, message, is_anonymous, status, payment_ref)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        $simpan->execute([
            $kode,
            $program['id'],
            $donatur_id,
            $nominal,
            $metode,
            $pesan,
            $anonim ? 'true' : 'false',
            $referensi,
        ]);

        redirect('bayar.php?kode=' . urlencode($kode));
    }
}

$judul = 'Donasi — ' . $program['title'];
tampil_atas($judul, 'program');
$progres = persen($program['collected_amount'], $program['target_amount']);
?>

<section class="section section--tight">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Beranda</a><span>/</span>
            <a href="program.php">Program</a><span>/</span>
            <a href="program.php?id=<?= e($program['id']) ?>"><?= e($program['title']) ?></a><span>/</span>
            <span>Donasi</span>
        </div>

        <?php if ($error !== []): ?>
            <div class="alert alert--danger" role="alert" style="margin: 0 0 20px;">
                <span class="alert__icon">!</span>
                <div>
                    <p><strong>Donasi belum bisa diproses.</strong> Periksa kembali isian yang ditandai di bawah.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="donate-grid">
            <form class="form-card" method="post" action="donasi.php" novalidate>
                <?= csrf_input() ?>
                <input type="hidden" name="program_id" value="<?= e($program['id']) ?>">

                <h2 style="margin-bottom: 4px;">Formulir donasi</h2>
                <p class="small muted" style="margin-bottom: 24px;">
                    Isi data dengan benar agar tim kami dapat memverifikasi donasi Anda.
                </p>

                <div class="form-section-title">Data donatur</div>

                <div class="form-row">
                    <div class="field <?= isset($error['nama']) ? 'field--error' : '' ?>">
                        <label for="nama">Nama lengkap</label>
                        <input class="input" type="text" id="nama" name="nama" value="<?= e($_POST['nama'] ?? '') ?>"
                               placeholder="Nama sesuai rekening" required>
                        <?php if (isset($error['nama'])): ?><span class="error-text"><?= e($error['nama']) ?></span><?php endif; ?>
                    </div>

                    <div class="field <?= isset($error['email']) ? 'field--error' : '' ?>">
                        <label for="email">Email</label>
                        <input class="input" type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"
                               placeholder="nama@email.com" required>
                        <?php if (isset($error['email'])): ?><span class="error-text"><?= e($error['email']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <label for="telepon">Nomor telepon <span class="muted">(opsional)</span></label>
                    <input class="input" type="tel" id="telepon" name="telepon" value="<?= e($_POST['telepon'] ?? '') ?>"
                           placeholder="08xx xxxx xxxx">
                </div>

                <div class="form-section-title">Nominal &amp; metode</div>

                <div class="field <?= isset($error['nominal']) ? 'field--error' : '' ?>">
                    <label for="nominal">Nominal donasi</label>
                    <div class="input-group">
                        <span class="input-group__prefix">Rp</span>
                        <input class="input" type="number" id="nominal" name="nominal" value="<?= e($_POST['nominal'] ?? '') ?>"
                               min="10000" step="1000" placeholder="100000" required>
                    </div>
                    <?php if (isset($error['nominal'])): ?>
                        <span class="error-text"><?= e($error['nominal']) ?></span>
                    <?php else: ?>
                        <span class="hint">Minimal Rp 10.000. Nominal dicatat apa adanya tanpa biaya tambahan.</span>
                    <?php endif; ?>

                    <div class="amount-options">
                        <?php foreach ([50000, 100000, 250000, 500000, 1000000] as $nominal): ?>
                            <button class="amount-chip" type="button" data-amount="<?= e($nominal) ?>"><?= e(rupiah($nominal)) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="field <?= isset($error['metode']) ? 'field--error' : '' ?>">
                    <span class="field-label">Metode pembayaran</span>
                    <div class="method-grid">
                        <?php
                        $metode = [
                            'transfer_bank' => ['Transfer Bank', 'BCA / Mandiri / BNI'],
                            'qris'          => ['QRIS', 'Semua aplikasi bank'],
                            'ewallet'       => ['E-Wallet', 'GoPay / OVO / Dana'],
                        ];
                        $dipilih = $_POST['metode'] ?? 'transfer_bank';
                        ?>
                        <?php foreach ($metode as $nilai => $info): ?>
                            <label class="method">
                                <input type="radio" name="metode" value="<?= e($nilai) ?>" <?= $dipilih === $nilai ? 'checked' : '' ?>>
                                <strong><?= e($info[0]) ?></strong>
                                <small><?= e($info[1]) ?></small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($error['metode'])): ?><span class="error-text"><?= e($error['metode']) ?></span><?php endif; ?>
                </div>

                <div class="form-section-title">Pesan &amp; preferensi</div>

                <div class="field <?= isset($error['pesan']) ? 'field--error' : '' ?>">
                    <label for="pesan">Pesan atau doa <span class="muted">(opsional)</span></label>
                    <textarea class="textarea" id="pesan" name="pesan" maxlength="500"
                              placeholder="Tuliskan pesan singkat untuk penerima manfaat..."><?= e($_POST['pesan'] ?? '') ?></textarea>
                    <?php if (isset($error['pesan'])): ?><span class="error-text"><?= e($error['pesan']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label class="check">
                        <input type="checkbox" name="anonim" value="1" <?= isset($_POST['anonim']) ? 'checked' : '' ?>>
                        <span>Sembunyikan nama saya di halaman publik (tampil sebagai “Hamba Allah”).</span>
                    </label>
                </div>

                <button class="btn btn--primary btn--block" type="submit">Lanjutkan ke Pembayaran</button>
                <p class="small muted" style="text-align: center; margin-top: 12px;">
                    Dengan melanjutkan, Anda menyetujui dana digunakan untuk program ini sesuai laporan yang dipublikasikan.
                </p>
            </form>

            <aside class="summary-card">
                <div class="summary-card__thumb" aria-hidden="true"><?= e(strtoupper(substr($program['title'], 0, 1))) ?></div>
                <h3 style="font-size: 1rem;"><?= e($program['title']) ?></h3>
                <p class="small muted" style="margin-bottom: 16px;"><?= e($program['kategori']) ?></p>

                <div class="summary-row">
                    <span>Terkumpul</span>
                    <strong><?= e(rupiah($program['collected_amount'])) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Target</span>
                    <strong><?= e(rupiah($program['target_amount'])) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Progress</span>
                    <strong><?= e($progres) ?>%</strong>
                </div>
                <div class="summary-row">
                    <span>Tenggat</span>
                    <strong><?= e(tanggal($program['deadline'])) ?></strong>
                </div>

                <div class="trust-note">
                    Donasi Anda akan diverifikasi oleh admin maksimal 1×24 jam pada hari kerja.
                </div>
            </aside>
        </div>
    </div>
</section>

<?php tampil_bawah(); ?>
