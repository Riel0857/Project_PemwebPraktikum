<?php

require 'koneksi.php';
require 'layout.php';

$kode = trim($_GET['kode'] ?? '');

$ambil = $pdo->prepare("
    SELECT d.*, p.title AS program_title, p.id AS program_id,
           CASE WHEN d.is_anonymous THEN 'Hamba Allah' ELSE dn.name END AS nama
    FROM donations d
    JOIN programs p ON p.id = d.program_id
    JOIN donors dn ON dn.id = d.donor_id
    WHERE d.code = ?
");
$ambil->execute([$kode]);
$donasi = $ambil->fetch();

if (!$donasi) {
    http_response_code(404);
    $judul = 'Kode donasi tidak ditemukan';
    tampil_atas($judul, 'program');
    echo '<section class="section"><div class="container"><div class="empty">'
        . '<h2>Kode donasi tidak ditemukan</h2>'
        . '<p>Periksa kembali kode donasi Anda, misalnya DON-20261110-A1B2.</p>'
        . '<a class="btn btn--primary" href="program.php">Kembali ke daftar program</a>'
        . '</div></div></section>';
    tampil_bawah();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar'])) {
    if (!csrf_valid()) {
        flash('gagal', 'Sesi pembayaran sudah habis, silakan coba lagi.');
        redirect('bayar.php?kode=' . urlencode($kode));
    }

    $ubah = $pdo->prepare("
        UPDATE donations
        SET status = 'paid', paid_at = now()
        WHERE code = ? AND status = 'pending'
    ");
    $ubah->execute([$kode]);

    flash('sukses', 'Pembayaran simulasi berhasil. Donasi Anda menunggu verifikasi admin.');
    redirect('bayar.php?kode=' . urlencode($kode));
}

$status = $donasi['status'];

if ($status !== 'pending') {
    $judul = 'Status Donasi';
    tampil_atas($judul, 'cek');

    $keterangan = [
        'paid'     => 'Terima kasih. Admin akan memeriksa pembayaran ini, biasanya dalam 1×24 jam kerja.',
        'verified' => 'Dana sudah masuk ke kas program dan akan disalurkan sesuai jadwal.',
        'rejected' => 'Admin menolak donasi ini. Silakan hubungi kami bila merasa ini keliru.',
    ];
    ?>

<div class="container">
    <div class="success-wrap">
        <div class="success-card">
            <div class="success-icon" aria-hidden="true"><?= $status === 'rejected' ? '!' : '✓' ?></div>
            <h1 style="font-size: 1.8rem;"><?= e(label_status($status)) ?></h1>
            <p class="muted">
                Terima kasih, <strong><?= e($donasi['nama']) ?></strong>. Donasi untuk
                <strong><?= e($donasi['program_title']) ?></strong> sebesar
                <strong><?= e(rupiah($donasi['amount'])) ?></strong>.
            </p>

            <div class="code-box">
                <span><?= e($donasi['code']) ?></span>
                <button type="button" data-copy="<?= e($donasi['code']) ?>">Salin</button>
            </div>

            <p class="small muted"><?= e($keterangan[$status] ?? '') ?></p>

            <div class="pay-box">
                <h4>Rincian pembayaran</h4>
                <div class="pay-row">
                    <span>Metode</span>
                    <strong><?= e(label_metode($donasi['method'])) ?></strong>
                </div>
                <div class="pay-row">
                    <span>Nomor referensi</span>
                    <strong><?= e($donasi['payment_ref'] ?: '-') ?></strong>
                </div>
                <div class="pay-row">
                    <span>Status</span>
                    <strong><?= e(label_status($status)) ?></strong>
                </div>
                <div class="pay-row">
                    <span>Dibayar pada</span>
                    <strong><?= e($donasi['paid_at'] ? tanggal($donasi['paid_at'], true) : 'Belum dibayar') ?></strong>
                </div>
            </div>

            <div class="success-actions">
                <a class="btn btn--outline" href="cek.php?kode=<?= e(urlencode($donasi['code'])) ?>">Cek Status Donasi</a>
                <a class="btn btn--outline" href="program.php">Donasi Program Lain</a>
            </div>
        </div>
    </div>
</div>

    <?php
    tampil_bawah();
    exit;
}

$instruksi = [
    'transfer_bank' => ['Nomor Virtual Account', $donasi['payment_ref']],
    'qris'          => ['Referensi QRIS', $donasi['payment_ref']],
    'ewallet'       => ['Nomor e-wallet tujuan', $donasi['payment_ref']],
];
[$label_ref, $nilai_ref] = $instruksi[$donasi['method']] ?? $instruksi['transfer_bank'];

$seed = md5($donasi['code']);
$bit = '';
foreach (str_split($seed) as $karakter) {
    $bit .= str_pad(base_convert($karakter, 16, 2), 4, '0', STR_PAD_LEFT);
}

$grid = [];
for ($y = 0; $y < 21; $y++) {
    for ($x = 0; $x < 21; $x++) {
        $grid[$y][$x] = (int) $bit[($y * 21 + $x) % strlen($bit)];
    }
}

foreach ([[0, 0], [14, 0], [0, 14]] as [$ox, $oy]) {
    for ($y = 0; $y < 7; $y++) {
        for ($x = 0; $x < 7; $x++) {
            $tepi = $x === 0 || $x === 6 || $y === 0 || $y === 6;
            $tengah = $x >= 2 && $x <= 4 && $y >= 2 && $y <= 4;
            $grid[$oy + $y][$ox + $x] = $tepi || $tengah ? 1 : 0;
        }
    }
}

$judul = 'Pembayaran ' . $donasi['code'];
tampil_atas($judul, 'program');
?>

<div class="container" style="padding: 40px 0;">
    <div class="gw-card">
        <div class="gw-card__head">
            <div>
                <span class="gw-label">Kode pembayaran</span>
                <h1><?= e($donasi['code']) ?></h1>
            </div>
            <span class="gw-status">Menunggu pembayaran</span>
        </div>

        <div class="gw-amount">
            <span class="gw-label">Total tagihan</span>
            <strong><?= e(rupiah($donasi['amount'])) ?></strong>
            <span class="gw-timer" id="gw-timer" data-seconds="900">15:00</span>
        </div>

        <div class="gw-rows">
            <div class="gw-row"><span>Merchant</span><strong>Yayasan PeduliKasih</strong></div>
            <div class="gw-row"><span>Program</span><strong><?= e($donasi['program_title']) ?></strong></div>
            <div class="gw-row"><span>Donatur</span><strong><?= e($donasi['nama']) ?></strong></div>
            <div class="gw-row"><span>Metode</span><strong><?= e(label_metode($donasi['method'])) ?></strong></div>
        </div>

        <div class="gw-pay">
            <?php if ($donasi['method'] === 'qris'): ?>
                <div class="gw-qr">
                    <svg viewBox="0 0 126 126" width="150" height="150" role="img" aria-label="QR simulasi">
                        <rect width="126" height="126" fill="#fff"/>
                        <?php foreach ($grid as $y => $baris): ?>
                            <?php foreach ($baris as $x => $sel): ?>
                                <?php if ($sel === 1): ?>
                                    <rect x="<?= $x * 6 ?>" y="<?= $y * 6 ?>" width="6" height="6" fill="#111"/>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </svg>
                    <p class="gw-small">Pindai QR di aplikasi pembayaran (simulasi).</p>
                </div>
            <?php endif; ?>

            <div class="gw-ref">
                <span class="gw-label"><?= e($label_ref) ?></span>
                <div class="gw-ref__value">
                    <strong><?= e($nilai_ref) ?></strong>
                    <button type="button" data-copy="<?= e($nilai_ref) ?>">Salin</button>
                </div>
                <p class="gw-small">Nominal harus sama dengan tagihan agar pembayaran dikenali.</p>
            </div>
        </div>

        <form method="post" action="bayar.php?kode=<?= e(urlencode($donasi['code'])) ?>" class="gw-form">
            <?= csrf_input() ?>
            <button class="gw-button" type="submit" name="bayar" value="1">Bayar Sekarang (Simulasi)</button>
        </form>

        <a class="gw-cancel" href="program.php?id=<?= e($donasi['program_id']) ?>">Batalkan dan kembali ke program</a>
    </div>

    <p class="gw-note">
        Halaman ini meniru tampilan payment gateway. Tombol di atas tidak menghubungi bank mana pun,
        hanya mengubah status donasi menjadi <em>sudah dibayar</em> agar bisa diverifikasi admin.
    </p>
</div>

<?php tampil_bawah(); ?>
