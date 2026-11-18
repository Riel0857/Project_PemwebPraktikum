<?php

require 'koneksi.php';
require 'layout.php';

if (isset($_GET['keluar'])) {
    $_SESSION = [];
    session_destroy();
    redirect('login.php');
}

if (sudah_login()) {
    redirect('admin.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Sesi formulir sudah habis, silakan coba lagi.';
    } elseif ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $cari = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $cari->execute([$email]);
        $user = $cari->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_nama'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];

            $pdo->prepare('UPDATE users SET last_login_at = now() WHERE id = ?')->execute([$user['id']]);

            redirect('admin.php');
        }

        $error = 'Email atau password salah.';
    }
}

$judul = 'Masuk Admin';
tampil_atas($judul, '');
?>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-card__head">
            <h1>Masuk ke PeduliKasih</h1>
            <p class="small muted">Halaman ini hanya untuk pengurus yayasan.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert--danger" role="alert">
                <span class="alert__icon" aria-hidden="true">!</span>
                <p><?= e($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= csrf_input() ?>

            <div class="field">
                <label for="email">Email</label>
                <input class="input" type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="admin@pedulikasih.test" required autofocus>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input class="input" type="password" id="password" name="password" required>
            </div>

            <button class="btn btn--primary btn--block" type="submit">Masuk</button>
        </form>

        <div class="demo-box">
            <strong>Akun demo</strong>
            <p class="small muted">Email: admin@pedulikasih.test · Password: admin123</p>
        </div>

        <p class="small muted" style="text-align: center; margin-top: 16px;">
            <a href="index.php">← Kembali ke beranda</a>
        </p>
    </div>
</div>

<?php tampil_bawah(); ?>
