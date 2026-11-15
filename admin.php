<?php

require 'koneksi.php';
require 'layout.php';

wajib_login();

$halaman = $_GET['halaman'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('gagal', 'Sesi formulir sudah habis, silakan ulangi.');
        redirect('admin.php?halaman=' . urlencode($halaman));
    }

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan_program') {
        $id = (int) ($_POST['id'] ?? 0);
        $judul_program = trim($_POST['judul'] ?? '');
        $kategori_id = (int) ($_POST['kategori_id'] ?? 0);
        $ringkasan = trim($_POST['ringkasan'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $target = (float) ($_POST['target'] ?? 0);
        $lokasi = trim($_POST['lokasi'] ?? '');
        $tenggat = $_POST['tenggat'] ?: null;
        $status = $_POST['status'] ?? 'aktif';
        $unggulan = isset($_POST['unggulan']) ? 'true' : 'false';

        if ($judul_program === '' || $kategori_id === 0 || $ringkasan === '' || $deskripsi === '' || $target <= 0) {
            flash('gagal', 'Judul, kategori, ringkasan, deskripsi, dan target wajib diisi dengan benar.');
            redirect('admin.php?halaman=program_form' . ($id ? '&id=' . $id : ''));
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $judul_program));
        $slug = trim($slug, '-');

        $cek = $pdo->prepare('SELECT COUNT(*) FROM programs WHERE slug = ? AND id <> ?');
        $cek->execute([$slug, $id]);

        if ((int) $cek->fetchColumn() > 0) {
            $slug .= '-' . random_int(10, 99);
        }

        if ($id > 0) {
            $ubah = $pdo->prepare("
                UPDATE programs
                SET category_id = ?, title = ?, slug = ?, summary = ?, description = ?,
                    target_amount = ?, location = ?, deadline = ?, status = ?, is_featured = ?
                WHERE id = ?
            ");
            $ubah->execute([
                $kategori_id, $judul_program, $slug, $ringkasan, $deskripsi,
                $target, $lokasi, $tenggat, $status, $unggulan, $id,
            ]);
            flash('sukses', 'Program berhasil diperbarui.');
        } else {
            $tambah = $pdo->prepare("
                INSERT INTO programs
                    (category_id, title, slug, summary, description, target_amount, location, deadline, status, is_featured)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $tambah->execute([
                $kategori_id, $judul_program, $slug, $ringkasan, $deskripsi,
                $target, $lokasi, $tenggat, $status, $unggulan,
            ]);
            flash('sukses', 'Program baru berhasil ditambahkan.');
        }

        redirect('admin.php?halaman=program');
    }

    if ($aksi === 'hapus_program') {
        $id = (int) ($_POST['id'] ?? 0);

        $cek = $pdo->prepare('SELECT COUNT(*) FROM donations WHERE program_id = ?');
        $cek->execute([$id]);

        if ((int) $cek->fetchColumn() > 0) {
            flash('gagal', 'Program tidak bisa dihapus karena sudah memiliki donasi.');
        } else {
            $pdo->prepare('DELETE FROM programs WHERE id = ?')->execute([$id]);
            flash('sukses', 'Program berhasil dihapus.');
        }

        redirect('admin.php?halaman=program');
    }

    if ($aksi === 'simpan_kategori') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama_kategori = trim($_POST['nama'] ?? '');
        $deskripsi_kategori = trim($_POST['deskripsi'] ?? '');
        $kembali = 'admin.php?halaman=kategori' . ($id > 0 ? '&edit=' . $id : '');

        if ($nama_kategori === '') {
            flash('gagal', 'Nama kategori wajib diisi.');
            redirect($kembali);
        }

        $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $nama_kategori)), '-');

        $cek = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE (name = ? OR slug = ?) AND id <> ?');
        $cek->execute([$nama_kategori, $slug, $id]);

        if ((int) $cek->fetchColumn() > 0) {
            flash('gagal', 'Nama kategori itu sudah ada. Pakai nama lain.');
            redirect($kembali);
        }

        if ($id > 0) {
            $ubah = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?');
            $ubah->execute([$nama_kategori, $slug, $deskripsi_kategori, $id]);
            flash('sukses', 'Kategori berhasil diperbarui.');
        } else {
            $tambah = $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)');
            $tambah->execute([$nama_kategori, $slug, $deskripsi_kategori]);
            flash('sukses', 'Kategori baru berhasil ditambahkan.');
        }

        redirect('admin.php?halaman=kategori');
    }

    if ($aksi === 'hapus_kategori') {
        $id = (int) ($_POST['id'] ?? 0);

        $cek = $pdo->prepare('SELECT COUNT(*) FROM programs WHERE category_id = ?');
        $cek->execute([$id]);
        $dipakai = (int) $cek->fetchColumn();

        if ($dipakai > 0) {
            flash('gagal', 'Kategori tidak bisa dihapus karena masih dipakai ' . $dipakai . ' program.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('sukses', 'Kategori berhasil dihapus.');
        }

        redirect('admin.php?halaman=kategori');
    }

    if ($aksi === 'verifikasi' || $aksi === 'tolak') {
        $id = (int) ($_POST['id'] ?? 0);
        $catatan = trim($_POST['catatan'] ?? '');
        $status_baru = $aksi === 'verifikasi' ? 'verified' : 'rejected';

        $ubah = $pdo->prepare("
            UPDATE donations
            SET status = ?, admin_note = ?, verified_at = now(), verified_by = ?
            WHERE id = ? AND status = 'paid'
        ");
        $ubah->execute([$status_baru, $catatan, $_SESSION['admin_id'], $id]);

        flash('sukses', $aksi === 'verifikasi' ? 'Donasi berhasil diverifikasi.' : 'Donasi ditolak.');
        redirect('admin.php?halaman=donasi&status=paid');
    }

    if ($aksi === 'baca_pesan') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE contact_messages SET is_read = TRUE WHERE id = ?')->execute([$id]);
        flash('sukses', 'Pesan ditandai sudah dibaca.');
        redirect('admin.php?halaman=pesan');
    }
}

if ($halaman === 'program_form') {
    $id = (int) ($_GET['id'] ?? 0);
    $program = [
        'id' => 0, 'category_id' => 0, 'title' => '', 'summary' => '', 'description' => '',
        'target_amount' => '', 'location' => '', 'deadline' => '', 'status' => 'aktif', 'is_featured' => false,
    ];

    if ($id > 0) {
        $ambil = $pdo->prepare('SELECT * FROM programs WHERE id = ?');
        $ambil->execute([$id]);
        $data = $ambil->fetch();

        if (!$data) {
            flash('gagal', 'Program tidak ditemukan.');
            redirect('admin.php?halaman=program');
        }

        $program = $data;
    }

    $kategori = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

    $judul = $id > 0 ? 'Edit Program' : 'Tambah Program';
    tampil_admin_atas($judul, 'program');
    ?>

<div class="page-head">
    <div class="page-head__inner">
        <div>
            <h2><?= $id > 0 ? 'Edit program' : 'Program baru' ?></h2>
            <p class="muted">Isi data program dengan lengkap agar donatur memahami tujuannya.</p>
        </div>
        <a class="btn btn--outline btn--sm" href="admin.php?halaman=program">Kembali</a>
    </div>
</div>

<form class="form-card admin-form" method="post" action="admin.php?halaman=program_form">
    <?= csrf_input() ?>
    <input type="hidden" name="aksi" value="simpan_program">
    <input type="hidden" name="id" value="<?= e($program['id']) ?>">

    <div class="form-row">
        <div class="field">
            <label for="judul">Judul program</label>
            <input class="input" type="text" id="judul" name="judul" value="<?= e($program['title']) ?>" required>
        </div>

        <div class="field">
            <label for="kategori_id">Kategori</label>
            <select class="select" id="kategori_id" name="kategori_id" required>
                <option value="">Pilih kategori</option>
                <?php foreach ($kategori as $k): ?>
                    <option value="<?= e($k['id']) ?>" <?= (int) $program['category_id'] === (int) $k['id'] ? 'selected' : '' ?>>
                        <?= e($k['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field">
        <label for="ringkasan">Ringkasan singkat</label>
        <input class="input" type="text" id="ringkasan" name="ringkasan" maxlength="255" value="<?= e($program['summary']) ?>" required>
    </div>

    <div class="field">
        <label for="deskripsi">Deskripsi</label>
        <textarea class="textarea" id="deskripsi" name="deskripsi" rows="6" required><?= e($program['description']) ?></textarea>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="target">Target dana (Rp)</label>
            <input class="input" type="number" id="target" name="target" min="100000" step="100000"
                   value="<?= e($program['target_amount']) ?>" required>
        </div>

        <div class="field">
            <label for="tenggat">Tenggat</label>
            <input class="input" type="date" id="tenggat" name="tenggat" value="<?= e($program['deadline']) ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="lokasi">Lokasi</label>
            <input class="input" type="text" id="lokasi" name="lokasi" value="<?= e($program['location']) ?>">
        </div>

        <div class="field">
            <label for="status">Status</label>
            <select class="select" id="status" name="status">
                <option value="aktif" <?= $program['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="draft" <?= $program['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="selesai" <?= $program['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
            </select>
        </div>
    </div>

    <div class="field">
        <label class="check">
            <input type="checkbox" name="unggulan" value="1" <?= $program['is_featured'] ? 'checked' : '' ?>>
            <span>Tampilkan sebagai program pilihan di beranda.</span>
        </label>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Simpan Program</button>
        <a class="btn btn--outline" href="admin.php?halaman=program">Batal</a>
    </div>
</form>

    <?php
    tampil_admin_bawah();
    exit;
}

if ($halaman === 'program') {
    $cari = trim($_GET['q'] ?? '');
    $parameter = [];
    $kondisi = '';

    if ($cari !== '') {
        $kondisi = 'WHERE p.title ILIKE ?';
        $parameter[] = '%' . $cari . '%';
    }

    $ambil = $pdo->prepare("
        SELECT p.*, c.name AS kategori, s.collected_amount, s.donation_count
        FROM programs p
        JOIN categories c ON c.id = p.category_id
        JOIN v_program_stats s ON s.program_id = p.id
        {$kondisi}
        ORDER BY p.created_at DESC
    ");
    $ambil->execute($parameter);
    $program = $ambil->fetchAll();

    $judul = 'Kelola Program';
    tampil_admin_atas($judul, 'program');
    ?>

<div class="page-head">
    <div class="page-head__inner">
        <div>
            <h2>Daftar program</h2>
            <p class="muted"><?= e(count($program)) ?> program terdaftar.</p>
        </div>
        <a class="btn btn--primary btn--sm" href="admin.php?halaman=program_form">+ Tambah Program</a>
    </div>
</div>

<form class="search" method="get" action="admin.php" style="margin-bottom: 16px; max-width: 360px;">
    <input type="hidden" name="halaman" value="program">
    <input type="search" name="q" value="<?= e($cari) ?>" placeholder="Cari judul program...">
    <button type="submit">Cari</button>
</form>

<div class="card">
    <?php if ($program === []): ?>
        <div class="card__body"><p class="muted" style="margin: 0;">Belum ada program yang cocok.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Kategori</th>
                        <th class="ta-right">Terkumpul</th>
                        <th class="ta-right">Target</th>
                        <th>Status</th>
                        <th class="ta-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($program as $p): ?>
                        <tr>
                            <td>
                                <span class="strong"><?= e($p['title']) ?></span>
                                <span class="sub"><?= e($p['donation_count']) ?> donasi · <?= e($p['location'] ?: '-') ?></span>
                            </td>
                            <td><?= e($p['kategori']) ?></td>
                            <td class="ta-right strong nowrap"><?= e(rupiah($p['collected_amount'])) ?></td>
                            <td class="ta-right nowrap"><?= e(rupiah($p['target_amount'])) ?></td>
                            <td><span class="<?= e(kelas_status($p['status'])) ?>"><?= e(label_status($p['status'])) ?></span></td>
                            <td class="ta-right nowrap">
                                <a class="btn btn--outline btn--sm" href="admin.php?halaman=program_form&id=<?= e($p['id']) ?>">Edit</a>
                                <form method="post" action="admin.php?halaman=program" style="display: inline;"
                                      data-confirm="Hapus program <?= e($p['title']) ?>?">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="aksi" value="hapus_program">
                                    <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                    <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

    <?php
    tampil_admin_bawah();
    exit;
}

if ($halaman === 'kategori') {
    $edit_id = (int) ($_GET['edit'] ?? 0);
    $kategori_edit = ['id' => 0, 'name' => '', 'description' => ''];

    if ($edit_id > 0) {
        $ambil = $pdo->prepare('SELECT id, name, description FROM categories WHERE id = ?');
        $ambil->execute([$edit_id]);
        $data = $ambil->fetch();

        if (!$data) {
            flash('gagal', 'Kategori tidak ditemukan.');
            redirect('admin.php?halaman=kategori');
        }

        $kategori_edit = $data;
    }

    $daftar = $pdo->query("
        SELECT c.id, c.name, c.slug, c.description, COUNT(p.id) AS jumlah_program
        FROM categories c
        LEFT JOIN programs p ON p.category_id = c.id
        GROUP BY c.id, c.name, c.slug, c.description
        ORDER BY c.name
    ")->fetchAll();

    $judul = 'Kelola Kategori';
    tampil_admin_atas($judul, 'kategori');
    ?>

<div class="page-head">
    <div class="page-head__inner">
        <div>
            <h2>Kategori program</h2>
            <p class="muted"><?= e(count($daftar)) ?> kategori terdaftar. Kategori dipakai saat menambah program.</p>
        </div>
    </div>
</div>

<div class="admin-columns">
    <div class="card">
        <div class="card__head"><h3>Daftar kategori</h3></div>
        <?php if ($daftar === []): ?>
            <div class="card__body"><p class="muted" style="margin: 0;">Belum ada kategori. Tambahkan lewat form di samping.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th class="ta-right">Program</th>
                            <th class="ta-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $k): ?>
                            <tr>
                                <td>
                                    <span class="strong"><?= e($k['name']) ?></span>
                                    <span class="sub"><?= e($k['slug']) ?></span>
                                </td>
                                <td><?= e($k['description'] ?: '-') ?></td>
                                <td class="ta-right nowrap"><?= e($k['jumlah_program']) ?></td>
                                <td class="ta-right nowrap">
                                    <a class="btn btn--outline btn--sm" href="admin.php?halaman=kategori&edit=<?= e($k['id']) ?>">Edit</a>
                                    <form method="post" action="admin.php?halaman=kategori" style="display: inline;"
                                          data-confirm="Hapus kategori <?= e($k['name']) ?>?">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="aksi" value="hapus_kategori">
                                        <input type="hidden" name="id" value="<?= e($k['id']) ?>">
                                        <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card__head"><h3><?= $edit_id > 0 ? 'Edit kategori' : 'Tambah kategori' ?></h3></div>
        <div class="card__body">
            <form class="admin-form" method="post" action="admin.php?halaman=kategori">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="simpan_kategori">
                <input type="hidden" name="id" value="<?= e($kategori_edit['id']) ?>">

                <div class="field">
                    <label for="nama">Nama kategori</label>
                    <input class="input" type="text" id="nama" name="nama" maxlength="80"
                           value="<?= e($kategori_edit['name']) ?>" required>
                    <p class="hint">Slug dibuat otomatis dari nama.</p>
                </div>

                <div class="field">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea class="textarea" id="deskripsi" name="deskripsi" rows="3"><?= e($kategori_edit['description']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit"><?= $edit_id > 0 ? 'Simpan Perubahan' : 'Tambah Kategori' ?></button>
                    <?php if ($edit_id > 0): ?>
                        <a class="btn btn--outline" href="admin.php?halaman=kategori">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

    <?php
    tampil_admin_bawah();
    exit;
}

if ($halaman === 'donasi') {
    $status_filter = $_GET['status'] ?? '';
    $cari = trim($_GET['q'] ?? '');

    $ringkasan = $pdo->query("
        SELECT
            COUNT(*) FILTER (WHERE status = 'pending')  AS pending_count,
            COUNT(*) FILTER (WHERE status = 'paid')     AS paid_count,
            COUNT(*) FILTER (WHERE status = 'verified') AS verified_count,
            COALESCE(SUM(amount) FILTER (WHERE status = 'pending'), 0)  AS total_pending,
            COALESCE(SUM(amount) FILTER (WHERE status = 'paid'), 0)     AS total_paid,
            COALESCE(SUM(amount) FILTER (WHERE status = 'verified'), 0) AS total_verified
        FROM donations
    ")->fetch();

    $kondisi = [];
    $parameter = [];

    if (in_array($status_filter, ['pending', 'paid', 'verified', 'rejected'], true)) {
        $kondisi[] = 'd.status = ?';
        $parameter[] = $status_filter;
    }

    if ($cari !== '') {
        $kondisi[] = '(d.code ILIKE ? OR dn.name ILIKE ? OR p.title ILIKE ?)';
        $parameter[] = '%' . $cari . '%';
        $parameter[] = '%' . $cari . '%';
        $parameter[] = '%' . $cari . '%';
    }

    $where = $kondisi === [] ? '' : 'WHERE ' . implode(' AND ', $kondisi);

    $ambil = $pdo->prepare("
        SELECT d.*, p.title AS program_title, dn.name AS donor_name, dn.email AS donor_email
        FROM donations d
        JOIN programs p ON p.id = d.program_id
        JOIN donors dn ON dn.id = d.donor_id
        {$where}
        ORDER BY d.created_at DESC
        LIMIT 100
    ");
    $ambil->execute($parameter);
    $donasi = $ambil->fetchAll();

    $tab = [
        ''         => ['Semua', null],
        'pending'  => ['Belum dibayar', $ringkasan['pending_count']],
        'paid'     => ['Perlu verifikasi', $ringkasan['paid_count']],
        'verified' => ['Terverifikasi', $ringkasan['verified_count']],
        'rejected' => ['Ditolak', null],
    ];

    $judul = 'Verifikasi Donasi';
    tampil_admin_atas($judul, 'donasi');
    ?>

<div class="admin-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="metric">
        <div class="metric__label">Perlu diverifikasi</div>
        <div class="metric__value"><?= e(rupiah($ringkasan['total_paid'])) ?></div>
        <div class="metric__foot"><?= e($ringkasan['paid_count']) ?> sudah dibayar</div>
    </div>
    <div class="metric">
        <div class="metric__label">Belum dibayar</div>
        <div class="metric__value"><?= e(rupiah($ringkasan['total_pending'])) ?></div>
        <div class="metric__foot"><?= e($ringkasan['pending_count']) ?> transaksi</div>
    </div>
    <div class="metric">
        <div class="metric__label">Terverifikasi</div>
        <div class="metric__value"><?= e(rupiah($ringkasan['total_verified'])) ?></div>
        <div class="metric__foot"><?= e($ringkasan['verified_count']) ?> transaksi</div>
    </div>
    <div class="metric">
        <div class="metric__label">Total donasi</div>
        <div class="metric__value"><?= e(rupiah((float) $ringkasan['total_verified'] + (float) $ringkasan['total_paid'] + (float) $ringkasan['total_pending'])) ?></div>
        <div class="metric__foot">Seluruh transaksi tercatat</div>
    </div>
</div>

<div class="filter-bar">
    <div class="tabs">
        <?php foreach ($tab as $nilai => $info): ?>
            <a class="tab <?= $status_filter === $nilai ? 'is-active' : '' ?>"
               href="admin.php?halaman=donasi<?= $nilai !== '' ? '&status=' . urlencode($nilai) : '' ?>">
                <?= e($info[0]) ?><?php if ($info[1] !== null): ?> <small>(<?= e($info[1]) ?>)</small><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form class="search" method="get" action="admin.php" style="min-width: 260px;">
        <input type="hidden" name="halaman" value="donasi">
        <?php if ($status_filter !== ''): ?><input type="hidden" name="status" value="<?= e($status_filter) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($cari) ?>" placeholder="Kode, donatur, atau program...">
        <button type="submit">Cari</button>
    </form>
</div>

<div class="card">
    <?php if ($donasi === []): ?>
        <div class="card__body"><p class="muted" style="margin: 0;">Tidak ada donasi yang sesuai filter.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode &amp; waktu</th>
                        <th>Donatur</th>
                        <th>Program</th>
                        <th>Metode</th>
                        <th class="ta-right">Nominal</th>
                        <th>Status</th>
                        <th class="ta-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donasi as $d): ?>
                        <tr>
                            <td>
                                <span class="strong nowrap"><?= e($d['code']) ?></span>
                                <span class="sub"><?= e(tanggal($d['created_at'], true)) ?></span>
                            </td>
                            <td>
                                <?= e($d['donor_name']) ?>
                                <span class="sub"><?= e($d['donor_email']) ?></span>
                            </td>
                            <td><?= e($d['program_title']) ?></td>
                            <td class="nowrap"><?= e(label_metode($d['method'])) ?></td>
                            <td class="ta-right strong nowrap"><?= e(rupiah($d['amount'])) ?></td>
                            <td>
                                <span class="<?= e(kelas_status($d['status'])) ?>"><?= e(label_status($d['status'])) ?></span>
                                <?php if (!empty($d['admin_note'])): ?>
                                    <span class="sub">“<?= e($d['admin_note']) ?>”</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($d['status'] === 'paid'): ?>
                                    <details class="note-toggle">
                                        <summary>Verifikasi / tolak</summary>
                                        <div style="margin-top: 8px; min-width: 220px;">
                                            <form method="post" action="admin.php?halaman=donasi">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="aksi" value="verifikasi">
                                                <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                                <input class="note-input" type="text" name="catatan" maxlength="255" placeholder="Catatan (opsional)">
                                                <button class="btn btn--primary btn--sm" type="submit" style="width: 100%; margin-top: 8px;">Verifikasi</button>
                                            </form>
                                            <form method="post" action="admin.php?halaman=donasi" data-confirm="Tolak donasi <?= e($d['code']) ?>?">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="aksi" value="tolak">
                                                <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                                <input class="note-input" type="text" name="catatan" maxlength="255" placeholder="Alasan penolakan">
                                                <button class="btn btn--danger btn--sm" type="submit" style="width: 100%; margin-top: 8px;">Tolak</button>
                                            </form>
                                        </div>
                                    </details>
                                <?php elseif ($d['status'] === 'pending'): ?>
                                    <span class="small muted">Belum dibayar</span>
                                <?php else: ?>
                                    <span class="small muted"><?= e($d['verified_at'] ? tanggal($d['verified_at'], true) : '-') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

    <?php
    tampil_admin_bawah();
    exit;
}

if ($halaman === 'laporan') {
    $per_program = $pdo->query("
        SELECT p.title, c.name AS kategori, s.target_amount, s.collected_amount, s.donation_count
        FROM programs p
        JOIN categories c ON c.id = p.category_id
        JOIN v_program_stats s ON s.program_id = p.id
        ORDER BY s.collected_amount DESC
    ")->fetchAll();

    $per_bulan = $pdo->query("
        SELECT to_char(created_at, 'YYYY-MM') AS bulan,
               COUNT(*) AS jumlah,
               SUM(amount) AS total
        FROM donations
        WHERE status = 'verified'
        GROUP BY 1
        ORDER BY 1 DESC
        LIMIT 12
    ")->fetchAll();

    $total = $pdo->query("SELECT COUNT(*) AS jumlah, COALESCE(SUM(amount), 0) AS total FROM donations WHERE status = 'verified'")->fetch();

    $judul = 'Laporan Donasi';
    tampil_admin_atas($judul, 'laporan');
    ?>

<div class="report-summary">
    <div class="stat">
        <div class="stat__value"><?= e(rupiah($total['total'])) ?></div>
        <div class="stat__label">Total dana terverifikasi</div>
    </div>
    <div class="stat">
        <div class="stat__value"><?= e($total['jumlah']) ?></div>
        <div class="stat__label">Jumlah donasi</div>
    </div>
    <div class="stat">
        <div class="stat__value"><?= e(count($per_program)) ?></div>
        <div class="stat__label">Program terdaftar</div>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="card__head">
        <h2>Rekap per program</h2>
        <a class="btn btn--outline btn--sm" href="admin.php?halaman=export">Unduh CSV</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Kategori</th>
                    <th class="ta-right">Donasi</th>
                    <th class="ta-right">Terkumpul</th>
                    <th class="ta-right">Target</th>
                    <th class="ta-right">Capaian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($per_program as $p): ?>
                    <tr>
                        <td class="strong"><?= e($p['title']) ?></td>
                        <td><?= e($p['kategori']) ?></td>
                        <td class="ta-right"><?= e($p['donation_count']) ?></td>
                        <td class="ta-right strong nowrap"><?= e(rupiah($p['collected_amount'])) ?></td>
                        <td class="ta-right nowrap"><?= e(rupiah($p['target_amount'])) ?></td>
                        <td class="ta-right"><?= e(persen($p['collected_amount'], $p['target_amount'])) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card__head">
        <h2>Rekap per bulan</h2>
        <span class="small muted">12 bulan terakhir</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="ta-right">Jumlah donasi</th>
                    <th class="ta-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($per_bulan as $b): ?>
                    <tr>
                        <td class="strong"><?= e($b['bulan']) ?></td>
                        <td class="ta-right"><?= e($b['jumlah']) ?></td>
                        <td class="ta-right strong nowrap"><?= e(rupiah($b['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

    <?php
    tampil_admin_bawah();
    exit;
}

if ($halaman === 'export') {
    $data = $pdo->query("
        SELECT d.code, d.created_at, d.paid_at, d.verified_at, d.amount, d.method,
               d.status, dn.name AS donor, dn.email, p.title AS program
        FROM donations d
        JOIN donors dn ON dn.id = d.donor_id
        JOIN programs p ON p.id = d.program_id
        WHERE d.status = 'verified'
        ORDER BY d.verified_at DESC
    ")->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan-donasi-' . date('Ymd') . '.csv"');

    $keluaran = fopen('php://output', 'w');
    fputcsv($keluaran, ['Kode', 'Tanggal dibuat', 'Dibayar', 'Diverifikasi', 'Nominal', 'Metode', 'Status', 'Donatur', 'Email', 'Program']);

    foreach ($data as $baris) {
        fputcsv($keluaran, [
            $baris['code'],
            $baris['created_at'],
            $baris['paid_at'],
            $baris['verified_at'],
            $baris['amount'],
            label_metode($baris['method']),
            label_status($baris['status']),
            $baris['donor'],
            $baris['email'],
            $baris['program'],
        ]);
    }

    fclose($keluaran);
    exit;
}

if ($halaman === 'pesan') {
    $pesan = $pdo->query('SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC')->fetchAll();

    $judul = 'Pesan Masuk';
    tampil_admin_atas($judul, 'pesan');
    ?>

<div class="page-head">
    <div class="page-head__inner">
        <div>
            <h2>Pesan dari pengunjung</h2>
            <p class="muted"><?= e(count($pesan)) ?> pesan masuk.</p>
        </div>
    </div>
</div>

<?php if ($pesan === []): ?>
    <div class="empty">
        <h3>Belum ada pesan</h3>
        <p>Pesan yang dikirim lewat formulir kontak akan muncul di sini.</p>
    </div>
<?php else: ?>
    <?php foreach ($pesan as $m): ?>
        <article class="message-card <?= $m['is_read'] ? '' : 'message-card--unread' ?>">
            <div class="message-card__head">
                <div>
                    <strong><?= e($m['subject']) ?></strong>
                    <span class="sub"><?= e($m['name']) ?> · <?= e($m['email']) ?> · <?= e(tanggal($m['created_at'], true)) ?></span>
                </div>
                <?php if (!$m['is_read']): ?>
                    <form method="post" action="admin.php?halaman=pesan">
                        <?= csrf_input() ?>
                        <input type="hidden" name="aksi" value="baca_pesan">
                        <input type="hidden" name="id" value="<?= e($m['id']) ?>">
                        <button class="btn btn--outline btn--sm" type="submit">Tandai dibaca</button>
                    </form>
                <?php else: ?>
                    <span class="badge badge--muted">Sudah dibaca</span>
                <?php endif; ?>
            </div>
            <p class="small"><?= nl2br(e($m['message'])) ?></p>
        </article>
    <?php endforeach; ?>
<?php endif; ?>

    <?php
    tampil_admin_bawah();
    exit;
}

$ringkasan = $pdo->query("
    SELECT
        COUNT(*) FILTER (WHERE status = 'pending')  AS pending_count,
        COUNT(*) FILTER (WHERE status = 'paid')     AS paid_count,
        COUNT(*) FILTER (WHERE status = 'verified') AS verified_count,
        COALESCE(SUM(amount) FILTER (WHERE status = 'pending'), 0)  AS total_pending,
        COALESCE(SUM(amount) FILTER (WHERE status = 'paid'), 0)     AS total_paid,
        COALESCE(SUM(amount) FILTER (WHERE status = 'verified'), 0) AS total_verified,
        COALESCE(SUM(amount) FILTER (WHERE status = 'verified' AND created_at >= date_trunc('month', now())), 0) AS bulan_ini
    FROM donations
")->fetch();

$grafik = [];
$nilai_maks = 1;

for ($i = 5; $i >= 0; $i--) {
    $awal = date('Y-m-01', strtotime("-{$i} month"));
    $akhir = date('Y-m-t', strtotime($awal));

    $hitung = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM donations
        WHERE status = 'verified' AND created_at::date BETWEEN ? AND ?
    ");
    $hitung->execute([$awal, $akhir]);
    $total_bulan = (float) $hitung->fetchColumn();

    $grafik[] = [
        'label' => date('M', strtotime($awal)),
        'total' => $total_bulan,
    ];

    if ($total_bulan > $nilai_maks) {
        $nilai_maks = $total_bulan;
    }
}

$perlu_verifikasi = $pdo->query("
    SELECT d.code, d.amount, d.created_at, dn.name AS donor_name, p.title AS program_title
    FROM donations d
    JOIN donors dn ON dn.id = d.donor_id
    JOIN programs p ON p.id = d.program_id
    WHERE d.status = 'paid'
    ORDER BY d.paid_at ASC
    LIMIT 5
")->fetchAll();

$program_terbaru = $pdo->query("
    SELECT p.title, p.status, c.name AS kategori, s.collected_amount
    FROM programs p
    JOIN categories c ON c.id = p.category_id
    JOIN v_program_stats s ON s.program_id = p.id
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();

$pesan_baru = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = FALSE')->fetchColumn();

$judul = 'Dashboard';
tampil_admin_atas($judul, 'dashboard');
?>

<div class="admin-grid">
    <div class="metric metric--brand">
        <div class="metric__label">Dana terverifikasi</div>
        <div class="metric__value"><?= e(rupiah($ringkasan['total_verified'])) ?></div>
        <div class="metric__foot"><?= e($ringkasan['verified_count']) ?> donasi terverifikasi</div>
    </div>
    <div class="metric">
        <div class="metric__label">Menunggu verifikasi</div>
        <div class="metric__value"><?= e($ringkasan['paid_count']) ?></div>
        <div class="metric__foot"><?= e(rupiah($ringkasan['total_paid'])) ?> sudah dibayar</div>
    </div>
    <div class="metric">
        <div class="metric__label">Belum dibayar</div>
        <div class="metric__value"><?= e($ringkasan['pending_count']) ?></div>
        <div class="metric__foot"><?= e(rupiah($ringkasan['total_pending'])) ?> menunggu pembayaran</div>
    </div>
    <div class="metric">
        <div class="metric__label">Bulan ini</div>
        <div class="metric__value"><?= e(rupiah($ringkasan['bulan_ini'])) ?></div>
        <div class="metric__foot">Donasi terverifikasi bulan berjalan</div>
    </div>
    <div class="metric">
        <div class="metric__label">Pesan belum dibaca</div>
        <div class="metric__value"><?= e($pesan_baru) ?></div>
        <div class="metric__foot"><a href="admin.php?halaman=pesan">Buka kotak masuk →</a></div>
    </div>
</div>

<div class="admin-columns">
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card__head">
                <h2>Tren donasi 6 bulan</h2>
                <span class="small muted">Hanya donasi terverifikasi</span>
            </div>
            <div class="card__body">
                <div class="chart">
                    <?php foreach ($grafik as $g): ?>
                        <?php $tinggi = max(4, round(($g['total'] / $nilai_maks) * 100, 1)); ?>
                        <div class="chart__col">
                            <div class="chart__bar" style="height: <?= e($tinggi) ?>%">
                                <span><?= e(rupiah($g['total'])) ?></span>
                            </div>
                            <div class="chart__label"><?= e($g['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__head">
                <h2>Perlu diverifikasi</h2>
                <a class="link-arrow small" href="admin.php?halaman=donasi&status=paid">Lihat semua</a>
            </div>
            <?php if ($perlu_verifikasi === []): ?>
                <div class="card__body">
                    <p class="small muted" style="margin: 0;">Tidak ada donasi yang menunggu verifikasi.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Donatur</th>
                                <th>Program</th>
                                <th class="ta-right">Nominal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perlu_verifikasi as $row): ?>
                                <tr>
                                    <td class="strong nowrap"><?= e($row['code']) ?></td>
                                    <td>
                                        <?= e($row['donor_name']) ?>
                                        <span class="sub"><?= e(tanggal($row['created_at'], true)) ?></span>
                                    </td>
                                    <td><?= e($row['program_title']) ?></td>
                                    <td class="ta-right strong nowrap"><?= e(rupiah($row['amount'])) ?></td>
                                    <td class="ta-right">
                                        <a class="btn btn--outline btn--sm" href="admin.php?halaman=donasi&status=paid">Periksa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card__head">
                <h2>Program terbaru</h2>
                <a class="link-arrow small" href="admin.php?halaman=program">Kelola</a>
            </div>
            <div class="card__body" style="padding: 8px 22px;">
                <?php foreach ($program_terbaru as $p): ?>
                    <div class="donation-row" style="align-items: center;">
                        <div style="flex: 1; min-width: 0;">
                            <div class="donation-row__name"><?= e($p['title']) ?></div>
                            <div class="donation-row__msg">
                                <?= e($p['kategori']) ?> · <?= e(rupiah($p['collected_amount'])) ?> terkumpul
                            </div>
                        </div>
                        <span class="<?= e(kelas_status($p['status'])) ?>"><?= e(label_status($p['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card__head"><h2>Aksi cepat</h2></div>
            <div class="card__body" style="display: grid; gap: 10px;">
                <a class="btn btn--primary btn--sm" href="admin.php?halaman=program_form">+ Tambah Program</a>
                <a class="btn btn--outline btn--sm" href="admin.php?halaman=donasi&status=paid">Verifikasi Donasi</a>
                <a class="btn btn--outline btn--sm" href="admin.php?halaman=laporan">Lihat Laporan</a>
            </div>
        </div>
    </div>
</div>

<?php tampil_admin_bawah(); ?>
