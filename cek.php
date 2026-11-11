<?php

require 'koneksi.php';
require 'layout.php';

$kode = trim($_GET['kode'] ?? '');
$donasi = null;

if ($kode !== '') {
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
}

$judul = 'Cek Status Donasi';
tampil_atas($judul, 'cek');
?>

<section class="page-head">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Beranda</a><span>/</span><span>Cek Donasi</span>
        </div>
        <h1>Cek status donasi</h1>
        <p class="muted" style="margin: 8px 0 0; max-width: 58ch;">
            Masukkan kode donasi yang Anda terima setelah mengisi formulir, misalnya
            <code>DON-20261110-A1B2</code>.
        </p>
    </div>
</section>

<section class="section section--tight">
    <div class="container" style="max-width: 760px;">
        <form class="card" method="get" action="cek.php" style="padding: 20px; margin-bottom: 24px;">
            <div class="field" style="margin-bottom: 12px;">
                <label for="kode">Kode donasi</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input class="input" type="text" id="kode" name="kode" value="<?= e($kode) ?>"
                           placeholder="DON-20261110-A1B2" style="flex: 1; min-width: 220px;" required>
                    <button class="btn btn--primary" type="submit">Cek Status</button>
                </div>
            </div>
            <p class="small muted" style="margin: 0;">Kode bersifat unik dan tidak mengandung data pribadi.</p>
        </form>

        <?php if ($kode !== '' && !$donasi): ?>
            <div class="empty">
                <h3>Kode donasi tidak ditemukan</h3>
                <p>Periksa kembali penulisan kode. Jika masih bermasalah, hubungi kami dengan menyertakan bukti transfer.</p>
                <a class="btn btn--outline btn--sm" href="index.php#kontak">Hubungi Admin</a>
            </div>
        <?php elseif ($donasi): ?>
            <?php $status = $donasi['status']; ?>
            <div class="card">
                <div class="card__head">
                    <div>
                        <span class="small muted">Kode donasi</span>
                        <h2 style="font-family: ui-monospace, Menlo, monospace; letter-spacing: .03em;">
                            <?= e($donasi['code']) ?>
                        </h2>
                    </div>
                    <span class="<?= e(kelas_status($status)) ?>"><?= e(label_status($status)) ?></span>
                </div>
                <div class="card__body">
                    <div class="summary-row">
                        <span>Program</span>
                        <strong><a href="program.php?id=<?= e($donasi['program_id']) ?>"><?= e($donasi['program_title']) ?></a></strong>
                    </div>
                    <div class="summary-row">
                        <span>Donatur</span>
                        <strong><?= e($donasi['nama']) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Nominal</span>
                        <strong><?= e(rupiah($donasi['amount'])) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Metode</span>
                        <strong><?= e(label_metode($donasi['method'])) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Pembayaran</span>
                        <strong><?= e($donasi['paid_at'] ? tanggal($donasi['paid_at'], true) : 'Belum dibayar') ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Dibuat</span>
                        <strong><?= e(tanggal($donasi['created_at'], true)) ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Diverifikasi</span>
                        <strong><?= e($donasi['verified_at'] ? tanggal($donasi['verified_at'], true) : 'Belum diverifikasi') ?></strong>
                    </div>

                    <?php if (!empty($donasi['admin_note'])): ?>
                        <div class="alert <?= $status === 'rejected' ? 'alert--danger' : 'alert--success' ?>" style="margin-top: 16px;">
                            <span class="alert__icon">i</span>
                            <p class="small"><strong>Catatan admin:</strong> <?= e($donasi['admin_note']) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="timeline">
                        <div class="timeline__item is-done">
                            <span class="timeline__dot"></span>
                            <div class="timeline__title">Donasi dicatat</div>
                            <div class="timeline__time"><?= e(tanggal($donasi['created_at'], true)) ?> WIB</div>
                        </div>

                        <div class="timeline__item <?= $status === 'pending' ? 'is-pending' : 'is-done' ?>">
                            <span class="timeline__dot"></span>
                            <div class="timeline__title">Pembayaran</div>
                            <div class="timeline__time">
                                <?php if ($status === 'pending'): ?>
                                    Belum dibayar —
                                    <a href="bayar.php?kode=<?= e(urlencode($donasi['code'])) ?>">lanjutkan pembayaran</a>
                                <?php else: ?>
                                    Dibayar pada <?= e(tanggal($donasi['paid_at'], true)) ?> WIB
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="timeline__item <?= in_array($status, ['verified', 'rejected'], true) ? 'is-done' : 'is-pending' ?>">
                            <span class="timeline__dot"></span>
                            <div class="timeline__title">Verifikasi admin</div>
                            <div class="timeline__time">
                                <?php if (in_array($status, ['verified', 'rejected'], true)): ?>
                                    <?= e(label_status($status)) ?> pada <?= e(tanggal($donasi['verified_at'], true)) ?> WIB
                                <?php else: ?>
                                    Menunggu pemeriksaan pembayaran (maks. 1×24 jam kerja)
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="timeline__item <?= $status === 'verified' ? 'is-done' : '' ?>">
                            <span class="timeline__dot"></span>
                            <div class="timeline__title">Penyaluran dana</div>
                            <div class="timeline__time">
                                <?= $status === 'verified'
                                    ? 'Dana masuk ke kas program dan akan disalurkan sesuai jadwal.'
                                    : 'Menunggu donasi terverifikasi.' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php tampil_bawah(); ?>
