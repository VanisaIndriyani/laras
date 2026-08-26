<?php
require_once __DIR__ . '/assets/logo.php';

$step = $_GET['step'] ?? 1;
$msg = '';
$msgType = '';
$favicon = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect x='8' y='8' width='184' height='184' rx='44' fill='%230B1C48'/><circle cx='148' cy='52' r='11' fill='%23E5232B'/><path d='M142 52 l5 5 l9 -9' fill='none' stroke='white' stroke-width='3' stroke-linecap='round'/><g transform='translate(40,48)'><rect x='0' y='6' width='80' height='98' rx='18' fill='none' stroke='white' stroke-width='6'/><rect x='14' y='20' width='52' height='8' rx='3' fill='white'/><circle cx='58' cy='70' r='4' fill='white'/><line x1='22' y1='32' x2='22' y2='96' stroke='white' stroke-width='4' stroke-linecap='round'/><g transform='translate(6,78)'><path d='M16 20 Q20 2 40 2 Q60 2 64 20 L72 22 L72 36 L8 36 L8 22 Z' fill='%23E5232B'/><rect x='2' y='32' width='84' height='10' rx='4' fill='white'/><circle cx='22' cy='46' r='8' fill='%230B1C48'/><circle cx='66' cy='46' r='8' fill='%230B1C48'/></g></g></svg>";

function runSqlFile($file) {
    $sql = file_get_contents($file);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $db = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $ok = 0; $err = [];
    foreach ($statements as $stmt) {
        try {
            if (!empty($stmt)) {
                $db->exec($stmt);
                $ok++;
            }
        } catch (Exception $e) {
            $err[] = $e->getMessage();
        }
    }
    return [$ok, $err];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';
    $dbName = $_POST['db_name'] ?? 'laras_db';
    $baseUrl = rtrim($_POST['base_url'] ?? 'http://localhost/peminjaman%20mobil', '/');

    $configPath = __DIR__ . '/config.php';
    $config = file_get_contents($configPath);
    $config = preg_replace("/define\('DB_HOST',\s*'[^']*'\);/", "define('DB_HOST', '{$dbHost}');", $config);
    $config = preg_replace("/define\('DB_USER',\s*'[^']*'\);/", "define('DB_USER', '{$dbUser}');", $config);
    $config = preg_replace("/define\('DB_PASS',\s*'[^']*'\);/", "define('DB_PASS', '{$dbPass}');", $config);
    $config = preg_replace("/define\('DB_NAME',\s*'[^']*'\);/", "define('DB_NAME', '{$dbName}');", $config);
    $config = preg_replace("#define\('BASE_URL',\s*'[^']*'\);#", "define('BASE_URL', '{$baseUrl}/');", $config);
    file_put_contents($configPath, $config);

    try {
        list($ok, $err) = runSqlFile(__DIR__ . '/database.sql');
        $msg = "Instalasi berhasil! {$ok} perintah SQL dijalankan.";
        $msgType = 'success';
        if (!empty($err)) {
            $msg .= "<br>Catatan: " . count($err) . " perintah dilewati (biasanya karena data/table sudah ada).";
        }
        $step = 2;
    } catch (Exception $e) {
        $msg = 'Gagal koneksi database: ' . $e->getMessage();
        $msgType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B1C48">
    <title>Instalasi - LARAS</title>
    <link rel="icon" type="image/svg+xml" href="<?= $favicon ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { font-family:'Segoe UI', Tahoma, sans-serif; font-size: 12px; }
        body { background: linear-gradient(135deg,#0B1C48 0%,#1F3A8B 55%,#233E90 100%); min-height:100vh; display:flex; align-items:center; padding:20px;}
        .install-card { background:#fff; border-radius:22px; box-shadow:0 18px 50px -15px rgba(11, 28, 72,.5); max-width:640px; width:100%; margin:0 auto; overflow:hidden; border:1px solid rgba(59, 95, 199,.2);}
        .install-header { background: linear-gradient(120deg,#0B1C48,#1F3A8B 55%,#233E90); color:#fff; padding:26px 30px; display:flex; gap:14px; align-items:center;}
        .install-header .logo { width:62px; height:62px; border-radius:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background: rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); padding: 4px;}
        .install-header .hd { min-width:0; flex:1;}
        .install-header h3 { font-size:20px; font-weight:800; margin:0; letter-spacing:-.2px;}
        .install-header small { opacity:.82; font-size:11px;}
        .install-body { padding:32px; }
        .form-label { font-size:11px; font-weight:600; color:#334155; margin-bottom:5px;}
        .form-control, .form-select { font-size:12px; padding:10px 14px; border-radius:12px; border:1.5px solid #e2e8f0;}
        .form-control:focus { box-shadow:0 0 0 4px rgba(35, 62, 144,.12); border-color:#233E90;}
        .btn-primary { background:linear-gradient(135deg,#1F3A8B,#0B1C48); border:none; padding:10px 24px; border-radius:12px; font-size:12px; font-weight:600;}
        .btn-primary:hover { box-shadow:0 6px 18px rgba(35, 62, 144,.32);}
        .steps { display:flex; gap:10px; margin-bottom:26px;}
        .step { flex:1; text-align:center; padding:12px 8px; border-radius:14px; background:#f1f5f9; color:#64748b; font-size:11px; font-weight:600;}
        .step.active { background:linear-gradient(135deg,#1F3A8B,#0B1C48); color:#fff; box-shadow:0 6px 18px rgba(35, 62, 144,.28);}
        .step.done { background:#d1fae5; color:#065f46;}
        .alert { border-radius:13px; border:none; font-size:11.5px;}
        .cred-box { background:#f5f8ff; border-radius:14px; padding:16px; border-left:3px solid #233E90;}
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-header">
            <div class="logo"><?php laras_logo(54); ?></div>
            <div class="hd">
                <h3>LARAS - Instalasi Aplikasi</h3>
                <small>Layanan Aplikasi Reservasi Aset & Sarana</small>
            </div>
        </div>
        <div class="install-body">
            <div class="step-indicator">
                <div class="step <?= $step == 1 ? 'active' : 'done' ?>"><i class="bi bi-gear me-1"></i>Konfigurasi</div>
                <div class="step <?= $step == 2 ? 'active' : '' ?>"><i class="bi bi-database me-1"></i>Database</div>
                <div class="step"><i class="bi bi-check2-circle me-1"></i>Selesai</div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?> mb-3"><i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i><?= $msg ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Database Host</label>
                            <input type="text" class="form-control" name="db_host" value="localhost" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Database</label>
                            <input type="text" class="form-control" name="db_name" value="laras_db" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Database Username</label>
                            <input type="text" class="form-control" name="db_user" value="root" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Database Password</label>
                            <input type="text" class="form-control" name="db_pass" value="">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Base URL Aplikasi</label>
                            <input type="text" class="form-control" name="base_url" value="http://localhost/peminjaman%20mobil" required>
                            <div class="form-text">Sesuaikan dengan URL hosting Anda jika akan di-hosting online.</div>
                        </div>
                    </div>
                    <hr style="border-color:#e2e8f0;margin:22px 0">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-lightning-charge-fill me-2"></i>Jalankan Instalasi & Import Database</button>
                </form>
            <?php elseif ($step == 2): ?>
                <div class="cred-box mb-3">
                    <div style="font-weight:700;font-size:12px;color:#0f172a;margin-bottom:10px"><i class="bi bi-info-circle-fill me-1" style="color:#2563eb"></i>Akun Default Demo:</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small style="color:#64748b"><i class="bi bi-shield-lock me-1"></i>Login Admin</small>
                            <div style="font-size:12px;font-weight:600">NIP: <code>1001</code></div>
                            <div style="font-size:12px;font-weight:600">Password: <code>password</code></div>
                        </div>
                        <div class="col-md-6">
                            <small style="color:#64748b"><i class="bi bi-person-vcard me-1"></i>Login Pegawai (NIP Saja)</small>
                            <div style="font-size:12px;font-weight:600">NIP: <code>2001</code> / <code>2002</code> / <code>2003</code></div>
                            <div style="font-size:12px;font-weight:600">Password: <em>(kosongkan, hanya NIP)</em></div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-primary flex-fill"><i class="bi bi-box-arrow-in-right me-2"></i>Buka Aplikasi & Login</a>
                    <a href="install.php?step=1" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
