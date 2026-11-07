<?php

session_start();

date_default_timezone_set('Asia/Jakarta');

$db_host = getenv('DB_HOST') ?: 'db';
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'pedulikasih';
$db_user = getenv('DB_USER') ?: 'peduli';
$db_pass = getenv('DB_PASSWORD');

if ($db_pass === false || $db_pass === '') {
    die('Password database belum diatur. Salin .env.example menjadi .env lalu isi DB_PASSWORD.');
}

try {
    $pdo = new PDO("pgsql:host={$db_host};port={$db_port};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Koneksi ke database gagal. Pastikan container database sudah berjalan.');
}

function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function tanggal($waktu, $pakai_jam = false)
{
    if (empty($waktu)) {
        return '-';
    }

    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    $ts = strtotime((string) $waktu);
    $hasil = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);

    if ($pakai_jam) {
        $hasil .= ', ' . date('H:i', $ts);
    }

    return $hasil;
}

function persen($terkumpul, $target)
{
    if ((float) $target <= 0) {
        return 0;
    }

    return min(100, round(((float) $terkumpul / (float) $target) * 100));
}

function label_status($status)
{
    if ($status === 'pending') {
        return 'Menunggu pembayaran';
    }

    if ($status === 'paid') {
        return 'Sudah dibayar';
    }

    if ($status === 'verified') {
        return 'Terverifikasi';
    }

    if ($status === 'rejected') {
        return 'Ditolak';
    }

    if ($status === 'aktif') {
        return 'Aktif';
    }

    if ($status === 'selesai') {
        return 'Selesai';
    }

    if ($status === 'draft') {
        return 'Draft';
    }

    return ucfirst($status);
}

function kelas_status($status)
{
    if ($status === 'pending') {
        return 'badge badge--warning';
    }

    if ($status === 'paid') {
        return 'badge badge--info';
    }

    if ($status === 'verified' || $status === 'aktif') {
        return 'badge badge--success';
    }

    if ($status === 'rejected') {
        return 'badge badge--danger';
    }

    return 'badge badge--muted';
}

function label_metode($metode)
{
    if ($metode === 'transfer_bank') {
        return 'Transfer Bank';
    }

    if ($metode === 'qris') {
        return 'QRIS';
    }

    if ($metode === 'ewallet') {
        return 'E-Wallet';
    }

    return ucfirst($metode);
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

function csrf_input()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid()
{
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string) $_POST['csrf']);
}

function flash($tipe, $teks)
{
    $_SESSION['flash'][$tipe] = $teks;
}

function tampil_flash()
{
    $daftar = [
        'sukses' => 'success',
        'gagal'  => 'danger',
    ];

    foreach ($daftar as $kunci => $kelas) {
        if (empty($_SESSION['flash'][$kunci])) {
            continue;
        }

        $teks = $_SESSION['flash'][$kunci];
        unset($_SESSION['flash'][$kunci]);

        echo '<div class="container"><div class="alert alert--' . $kelas . '" role="status">'
            . '<span class="alert__icon">' . ($kunci === 'sukses' ? '✓' : '!') . '</span>'
            . '<p>' . e($teks) . '</p></div></div>';
    }
}

function sudah_login()
{
    return !empty($_SESSION['admin_id']);
}

function wajib_login()
{
    if (!sudah_login()) {
        redirect('login.php');
    }
}

function redirect($tujuan)
{
    header('Location: ' . $tujuan);
    exit;
}

function potong($teks, $panjang = 120)
{
    $teks = trim((string) $teks);

    if (strlen($teks) <= $panjang) {
        return $teks;
    }

    return substr($teks, 0, $panjang) . '...';
}
