<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'laras_db');

// === AUTO-DETECT BASE_URL (FLEKSIBEL: LOCAL / SUBDOMAIN / SUBFOLDER HTTPS) ===
// Cara pakai: UBAH MANUAL HANYA JIKA rewrite / subdomain tidak terdeteksi benar.
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') $proto = 'https';
$host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$base  = $proto . '://' . $host . $uri . '/';
// Normalisasi: hapus trailing slash /index.php /\ / berulang
$base = preg_replace('#/+#', '/', str_replace('\\', '/', $base));
$base = preg_replace('#:/#', '://', $base);
if (!defined('BASE_URL')) define('BASE_URL', $base);

// ==== JIKA MAU MANUAL (MATIKAN AUTO di atas, pilih salah satu): ====
// 1. KALO ROOT SUBDOMAIN (contoh: https://laras.bpkp-diy.go.id/)
//    define('BASE_URL', 'https://laras.bpkp-diy.go.id/');
// 2. KALO SUBFOLDER DOMAIN UTAMA (contoh: https://bpkp-diy.go.id/laras/)
//    define('BASE_URL', 'https://bpkp-diy.go.id/laras/');
// 3. KALO LOCAL LARAGON (default sebelumnya):
//    define('BASE_URL', 'http://localhost/AGUSTUS/peminjaman%20mobil/');
define('APP_NAME', 'LARAS');
define('APP_DESC', 'Layanan Aplikasi Reservasi Aset & Sarana');
define('APP_INSTANSI', 'BPKP Perwakilan D.I. Yogyakarta');
define('APP_INSTANSI_SHORT', 'BPKP DIY');
define('APP_BAGIAN', 'Bagian Umum');

function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function redirect($url) {
    if (headers_sent()) {
        echo '<script>window.location.href="' . addslashes($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
        exit();
    }
    header('Location: ' . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'nip' => $_SESSION['user_nip'] ?? '',
        'nama' => $_SESSION['user_nama'] ?? 'Pengguna',
        'role' => $_SESSION['user_role'] ?? 'pegawai',
        'unit_kerja' => $_SESSION['user_unit'] ?? null
    ];
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
        redirect(base_url('login.php'));
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Akses ditolak. Halaman hanya untuk Admin.';
        redirect(base_url('dashboard.php'));
    }
}

function set_flash($key, $message) {
    $_SESSION['flash_' . $key] = $message;
}

function get_flash($key) {
    $k = 'flash_' . $key;
    if (isset($_SESSION[$k])) {
        $msg = $_SESSION[$k];
        unset($_SESSION[$k]);
        return $msg;
    }
    return null;
}

function has_flash($key) {
    return isset($_SESSION['flash_' . $key]);
}

function render_flash_metas() {
    $types = ['success', 'error', 'warning', 'info'];
    $out = '';
    foreach ($types as $t) {
        if (has_flash($t)) {
            $val = get_flash($t);
            $out .= '<meta name="flash-' . $t . '" content="' . sanitize($val) . '">' . "\n";
        }
    }
    echo $out;
}

function render_flash_alerts() {
    $icons = [
        'success' => 'bi-check-circle-fill',
        'error'   => 'bi-x-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info'    => 'bi-info-circle-fill'
    ];
    $variants = [
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
        'info'    => 'primary'
    ];
    $types = ['success', 'error', 'warning', 'info'];
    $out = '';
    foreach ($types as $t) {
        if (has_flash($t)) {
            $msg = get_flash($t);
            $v = $variants[$t];
            $i = $icons[$t];
            $out .= '<div class="alert alert-' . $v . ' alert-dismissible fade show rounded-4 border-0 shadow-sm py-2 px-3" role="alert" style="font-size:12px;">';
            $out .= '<i class="bi ' . $i . ' me-2"></i>';
            $out .= sanitize($msg);
            $out .= '<button type="button" class="btn-close p-1" data-bs-dismiss="alert" aria-label="Close" style="font-size:10px;"></button>';
            $out .= '</div>';
        }
    }
    echo $out;
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function format_date($date, $with_day = true) {
    if (!$date) return '-';
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($date);
    $result = '';
    if ($with_day) {
        $result .= $days[date('w', $t)] . ', ';
    }
    $result .= date('j', $t) . ' ' . $months[date('n', $t)] . ' ' . date('Y', $t);
    return $result;
}

function format_time($time) {
    if (!$time) return '-';
    return date('H:i', strtotime($time)) . ' WIB';
}

function format_datetime($datetime) {
    if (!$datetime) return '-';
    return format_date($datetime, true) . ' ' . format_time(date('H:i', strtotime($datetime)));
}

function selisih_hari($date_str) {
    if (!$date_str) return null;
    $today = new DateTime(date('Y-m-d'));
    $target = DateTime::createFromFormat('Y-m-d', substr($date_str, 0, 10));
    if (!$target) return null;
    $diff = $today->diff($target);
    $invert = (int)$diff->invert;
    $days = (int)$diff->days;
    return $invert ? -$days : $days;
}

function pajak_status_info($stnk_date, $tnkb_date = null) {
    $ref = $stnk_date ?: $tnkb_date;
    $sisa = selisih_hari($ref);
    if ($sisa === null) return ['key' => 'aman', 'sisa' => null, 'label' => 'Belum Ditetapkan', 'cls' => 'secondary'];
    if ($sisa < 0) return ['key' => 'lewat', 'sisa' => $sisa, 'label' => 'Lewat ' . abs($sisa) . ' Hari', 'cls' => 'danger'];
    if ($sisa <= 30) return ['key' => 'warning', 'sisa' => $sisa, 'label' => ($sisa === 0 ? 'Hari Ini Jatuh Tempo' : $sisa . ' Hari Lagi'), 'cls' => 'warning'];
    return ['key' => 'aman', 'sisa' => $sisa, 'label' => 'Berlaku (' . $sisa . ' Hari)', 'cls' => 'success'];
}

function service_status_info($service_next_date, $last_service_date = null) {
    if (!$service_next_date) {
        if (!$last_service_date) return ['key' => 'aman', 'sisa' => null, 'label' => 'Belum Ada Jadwal', 'cls' => 'secondary'];
        $t = DateTime::createFromFormat('Y-m-d', substr($last_service_date, 0, 10));
        if (!$t) return ['key' => 'aman', 'sisa' => null, 'label' => '-', 'cls' => 'secondary'];
        $t->modify('+6 months');
        $sisa = selisih_hari($t->format('Y-m-d'));
    } else {
        $sisa = selisih_hari($service_next_date);
    }
    if ($sisa < 0) return ['key' => 'lewat', 'sisa' => $sisa, 'label' => 'Terlambat ' . abs($sisa) . ' Hari', 'cls' => 'danger'];
    if ($sisa <= 30) return ['key' => 'warning', 'sisa' => $sisa, 'label' => ($sisa === 0 ? 'Service Hari Ini' : $sisa . ' Hari Lagi'), 'cls' => 'warning'];
    return ['key' => 'aman', 'sisa' => $sisa, 'label' => 'Jadwal ' . $sisa . ' Hari Lagi', 'cls' => 'success'];
}

function status_badge($status) {
    $map = [
        'pending'         => ['bg' => 'warning',       'label' => 'Menunggu Approval'],
        'disetujui'       => ['bg' => 'success',       'label' => 'Disetujui'],
        'ditolak'         => ['bg' => 'danger',        'label' => 'Ditolak'],
        'selesai'         => ['bg' => 'primary',       'label' => 'Selesai'],
        'dibatalkan'      => ['bg' => 'secondary',     'label' => 'Dibatalkan'],
        'tersedia'        => ['bg' => 'success',       'label' => 'Tersedia'],
        'digunakan'       => ['bg' => 'primary',       'label' => 'Sedang Digunakan'],
        'perawatan'       => ['bg' => 'warning',       'label' => 'Perawatan'],
        'tidak_tersedia'  => ['bg' => 'danger',        'label' => 'Tidak Tersedia']
    ];
    if (!isset($map[$status])) return sanitize($status);
    $bg = $map[$status]['bg'];
    return '<span class="badge status-badge text-' . $bg . ' bg-' . $bg . '-subtle border border-' . $bg . '-subtle rounded-pill px-3 py-1.5 fw-semibold shadow-sm">' . $map[$status]['label'] . '</span>';
}

function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

function generate_kode_reservasi($prefix = 'RES') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

function get_unit_kerja_list() {
    return [
        'Perwakilan BPKP DIY (Pimpinan)',
        'Bagian Umum',
        'Bidang IPP (Instansi Pemerintah Pusat)',
        'Bidang APD (Akuntabilitas Pemerintah Daerah)',
        'Bidang AN (Akuntan Negara)',
        'Bidang Investigasi',
        'Subbag Kepegawaian & Tata Usaha',
        'Subbag Keuangan'
    ];
}

function get_fasilitas_pendukung_list() {
    return [
        'Sound System & Wireless Mic',
        'Proyektor LCD & Screen',
        'Standing TV 65 Inch (Smart Display)',
        'Kabel Rol Listrik / Ekstensi',
        'Pointer / Clicker Presentasi',
        'Papan Tulis / Whiteboard & Spidol',
        'Podium Sambutan',
        'Meja Rapat U-Shape',
        'AC Central',
        'Bantuan Setting Zoom / Hybrid Meeting'
    ];
}

function foto_kendaraan_dir() {
    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'kendaraan' . DIRECTORY_SEPARATOR;
}

function foto_kendaraan_url($foto = null, $fallback_merk = 'Mobil') {
    if ($foto && file_exists(foto_kendaraan_dir() . $foto)) {
        $base = rtrim(base_url('uploads/kendaraan'), '/') . '/';
        return $base . rawurlencode($foto);
    }
    $merk_clean = htmlspecialchars($fallback_merk ?: 'Mobil', ENT_QUOTES);
    $w=420; $h=240;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'">
      <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#f1f5f9"/><stop offset="100%" stop-color="#e2e8f0"/></linearGradient></defs>
      <rect width="100%" height="100%" fill="url(#g)"/>
      <g transform="translate(' . ($w/2 - 55) . ','. ($h/2 - 35) . '">
        <path d="M8 35C8 25.059 16.059 17 26 17h60c9.941 0 18 8.059 18 18v11a5 5 0 0 1-5 5h-3v-3a6 6 0 0 0-12 0v3H29v-3a6 6 0 0 0-12 0v3h-3a5 5 0 0 1-5-5V35Z" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linejoin="round"/>
        <circle cx="23" cy="48" r="6" fill="#cbd5e1"/>
        <circle cx="89" cy="48" r="6" fill="#cbd5e1"/>
        <rect x="32" y="20" width="46" height="15" rx="3" fill="#cbd5e1" opacity="0.6"/>
      </g>
      <text x="50%" y="84%" text-anchor="middle" font-family="Inter,sans-serif" font-size="15" font-weight="700" fill="#64748b" letter-spacing="0.3">'.$merk_clean.'</text>
    </svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function proses_upload_foto_kendaraan($fieldName, $oldFoto = null) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldFoto;
    }
    $err = $_FILES[$fieldName]['error'];
    if ($err !== UPLOAD_ERR_OK) {
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) throw new Exception('Ukuran file foto terlalu besar (maks. 2MB).');
        if ($oldFoto) return $oldFoto;
        return null;
    }
    $finfo = $_FILES[$fieldName];
    $maxSize = 2 * 1024 * 1024;
    if ($finfo['size'] > $maxSize) throw new Exception('Ukuran foto maksimal 2MB.');
    $allowedExts = ['jpg','jpeg','png','webp','gif'];
    $ext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) throw new Exception('Format foto tidak valid (hanya JPG, PNG, WEBP, GIF).');
    $tmp = $finfo['tmp_name'];
    $imgInfo = @getimagesize($tmp);
    if (!$imgInfo) throw new Exception('File yang diunggah bukan gambar.');
    $dir = foto_kendaraan_dir();
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); @file_put_contents($dir . 'index.html', ''); }
    $newName = 'mbl_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(5)), 0, 8) . '.' . $ext;
    $dest = $dir . $newName;
    if (!move_uploaded_file($tmp, $dest)) throw new Exception('Gagal menyimpan foto (permisi folder).');
    @chmod($dest, 0664);
    if ($oldFoto && $oldFoto !== $newName) {
        $oldPath = $dir . $oldFoto;
        if (file_exists($oldPath) && is_file($oldPath)) @unlink($oldPath);
    }
    return $newName;
}

function hapus_foto_kendaraan($foto) {
    if (!$foto) return;
    $p = foto_kendaraan_dir() . $foto;
    if (file_exists($p) && is_file($p)) @unlink($p);
}
