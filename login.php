<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/assets/logo.php';

if (is_logged_in()) {
    redirect(base_url('dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = sanitize($_POST['nip_pegawai'] ?? '');

    if (empty($nip)) {
        set_flash('error', 'NIP wajib diisi.');
        redirect(base_url('login.php'));
    }

    $user = db()->fetchOne("SELECT * FROM users WHERE nip = ?", [$nip]);

    if (!$user) {
        set_flash('error', 'NIP tidak ditemukan. Silakan hubungi Admin.');
        redirect(base_url('login.php'));
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nip'] = $user['nip'];
    $_SESSION['user_nama'] = $user['nama_lengkap'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_unit'] = $user['unit_kerja'];

    set_flash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
    redirect(base_url('dashboard.php'));
}
$favicon = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'><rect x='8' y='8' width='184' height='184' rx='44' fill='%230B1C48'/><circle cx='148' cy='52' r='11' fill='%23E5232B'/><path d='M142 52 l5 5 l9 -9' fill='none' stroke='white' stroke-width='3' stroke-linecap='round'/><g transform='translate(40,48)'><rect x='0' y='6' width='80' height='98' rx='18' fill='none' stroke='white' stroke-width='6'/><rect x='14' y='20' width='52' height='8' rx='3' fill='white'/><circle cx='58' cy='70' r='4' fill='white'/><line x1='22' y1='32' x2='22' y2='96' stroke='white' stroke-width='4' stroke-linecap='round'/><g transform='translate(6,78)'><path d='M16 20 Q20 2 40 2 Q60 2 64 20 L72 22 L72 36 L8 36 L8 22 Z' fill='%23E5232B'/><rect x='2' y='32' width='84' height='10' rx='4' fill='white'/><circle cx='22' cy='46' r='8' fill='%230B1C48'/><circle cx='66' cy='46' r='8' fill='%230B1C48'/></g></g></svg>";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B1C48">
    <title>Login Pegawai - <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= $favicon ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --navy-900: #050E2C;
            --navy-800: #0B1C48;
            --navy-700: #132B65;
            --navy-600: #1F3A8B;
            --navy-500: #233E90;
            --blue-500: #3B5FC7;
            --slate-50: #F8FAFC;
            --slate-100: #F1F5F9;
            --slate-200: #E2E8F0;
            --slate-300: #CBD5E1;
            --slate-400: #94A3B8;
            --slate-500: #64748B;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1E293B;
        }
        * {
            font-family: 'Plus Jakarta Sans', 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        html, body { height: 100%; }
        body {
            background:
                radial-gradient(circle at top left, rgba(59, 95, 199, 0.16) 0%, transparent 50%),
                radial-gradient(circle at bottom right, rgba(229, 35, 43, 0.10) 0%, transparent 50%),
                linear-gradient(180deg, #FAFBFF 0%, #F1F5F9 100%);
            font-size: 14px;
            color: var(--slate-700);
            line-height: 1.55;
            letter-spacing: -0.01em;
            min-height: 100vh;
            margin: 0;
            padding: clamp(20px, 4vw, 48px) 16px clamp(28px, 6vw, 56px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .login-wrapper {
            width: 100%;
            max-width: 470px;
        }

        .login-card {
            background: #FFFFFF;
            border-radius: 28px;
            padding: clamp(30px, 5vw, 48px) clamp(24px, 4.2vw, 44px);
            box-shadow:
                0 30px 70px -22px rgba(11, 28, 72, 0.22),
                0 10px 28px -10px rgba(11, 28, 72, 0.12),
                0 0 0 1px rgba(11, 28, 72, 0.04);
            position: relative;
            overflow: hidden;
            animation: cardIn .55s cubic-bezier(.2,.8,.2,1) both;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, #0B1C48 0%, #3B5FC7 45%, #E5232B 100%);
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(14px) scale(.985); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Header Brand (logo + nama) */
        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 22px;
        }
        .login-brand .logo-wrap {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: linear-gradient(145deg, #0B1C48 0%, #1F3A8B 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px;
            box-shadow: 0 10px 24px -8px rgba(11, 28, 72, 0.4);
        }
        .login-brand .brand-title {
            text-align: left;
        }
        .login-brand .brand-title .app {
            font-size: 16px;
            font-weight: 900;
            color: var(--navy-800);
            letter-spacing: -0.01em;
            line-height: 1;
        }
        .login-brand .brand-title .sub {
            font-size: 10px;
            color: var(--slate-500);
            letter-spacing: 0.3px;
            margin-top: 3px;
            font-weight: 600;
        }

        /* Heading */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            font-size: clamp(24px, 3.2vw, 32px);
            font-weight: 800;
            color: var(--navy-800);
            letter-spacing: -0.025em;
            margin: 0 0 8px;
        }
        .login-header p {
            font-size: 14px;
            color: var(--slate-500);
            margin: 0;
        }

        /* Alert */
        .alert {
            border-radius: 14px;
            font-size: 12.5px;
            font-weight: 600;
            padding: 12px 16px;
            border: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert i { font-size: 17px; margin-top: 1px; flex-shrink: 0; }
        .alert.alert-success {
            background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
            color: #065F46;
            box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.18);
        }
        .alert.alert-danger {
            background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%);
            color: #991B1B;
            box-shadow: 0 0 0 1px rgba(229, 35, 43, 0.18);
        }

        /* FIELD NIP */
        .field {
            position: relative;
            margin-bottom: 18px;
            animation: fieldIn .5s cubic-bezier(.2,.8,.2,1) .15s both;
        }
        @keyframes fieldIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .field .input-shell { position: relative; }
        .field .ic-prefix {
            position: absolute;
            top: 50%; left: 18px;
            transform: translateY(-50%);
            width: 22px; height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--slate-400);
            font-size: 18px;
            z-index: 2;
            pointer-events: none;
            transition: color .22s ease;
        }
        .field .field-input {
            width: 100%;
            padding: 26px 20px 16px 54px;
            border: 2px solid var(--slate-200);
            background: var(--slate-50);
            border-radius: 18px;
            font-size: 15px;
            font-weight: 600;
            color: var(--slate-800);
            transition: all .22s cubic-bezier(.2,.8,.2,1);
            outline: 0;
            min-height: 62px;
            letter-spacing: -0.01em;
        }
        .field .field-input::placeholder { color: transparent; }
        .field label.lbl {
            position: absolute;
            top: 50%; left: 54px;
            transform: translateY(-50%);
            color: var(--slate-400);
            font-size: 15px;
            font-weight: 600;
            pointer-events: none;
            transition: all .22s cubic-bezier(.2,.8,.2,1);
            background: transparent;
            padding: 0 5px;
            margin: 0;
            z-index: 3;
            line-height: 1;
        }
        .field .field-input:focus,
        .field .field-input:not(:placeholder-shown) {
            border-color: var(--navy-700);
            background: #fff;
            box-shadow: 0 0 0 5px rgba(59, 95, 199, 0.11);
        }
        .field .field-input:focus ~ label.lbl,
        .field .field-input:not(:placeholder-shown) ~ label.lbl {
            top: 17px;
            left: 50px;
            transform: translateY(0);
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.3px;
            color: var(--navy-700);
            text-transform: uppercase;
        }
        .field .field-input:focus ~ .ic-prefix,
        .field .field-input:not(:placeholder-shown) ~ .ic-prefix {
            color: var(--blue-500);
        }

        /* Actions row */
        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: -2px 0 24px;
            font-size: 13px;
            animation: fieldIn .5s cubic-bezier(.2,.8,.2,1) .25s both;
        }
        .form-actions .check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--slate-600);
            font-weight: 500;
            user-select: none;
        }
        .form-actions .check input {
            width: 16px; height: 16px;
            accent-color: var(--navy-700);
        }
        .form-actions .link {
            color: var(--navy-700);
            font-weight: 700;
            text-decoration: none;
            transition: color .2s;
        }
        .form-actions .link:hover { color: var(--red-500); }

        /* BUTTON LOGIN PEGAWAI (NAVY) */
        .btn-login {
            width: 100%;
            padding: 18px 22px;
            background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-800) 45%, var(--navy-600) 100%);
            color: white;
            border: 0;
            border-radius: 18px;
            font-size: 15.5px;
            font-weight: 800;
            letter-spacing: 0.1px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 18px 34px -12px rgba(11, 28, 72, 0.55), 0 0 0 1px rgba(255,255,255,0.1) inset;
            transition: all .25s cubic-bezier(.2,.8,.2,1);
            position: relative;
            overflow: hidden;
            animation: fieldIn .5s cubic-bezier(.2,.8,.2,1) .35s both;
        }
        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 20%, rgba(255,255,255,0.2) 50%, transparent 80%);
            transform: translateX(-110%);
            transition: transform .7s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 44px -14px rgba(11, 28, 72, 0.65), 0 0 0 1px rgba(255,255,255,0.12) inset;
        }
        .btn-login:hover::before { transform: translateX(110%); }
        .btn-login:active { transform: translateY(0); }
        .btn-login i { font-size: 18px; }

        /* Admin switch CTA */
        .switch-cta {
            text-align: center;
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid var(--slate-100);
            font-size: 13px;
            color: var(--slate-500);
        }
        .switch-cta a {
            color: var(--navy-700);
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all .2s;
        }
        .switch-cta a:hover { color: #E5232B; }
        .switch-cta a:hover i { transform: translateX(3px); }
        .switch-cta a i { transition: transform .2s; }

        /* Page footer (inside card) */
        .page-footer {
            text-align: center;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--slate-100);
            font-size: 11px;
            color: var(--slate-400);
            line-height: 1.7;
        }
        .page-footer .seal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 11px;
            border: 1px solid var(--slate-200);
            border-radius: 999px;
            background: var(--slate-50);
            color: var(--slate-500);
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 10.5px;
        }
        .page-footer .seal i { color: var(--blue-500); }

        /* Small screen */
        @media (max-width: 520px) {
            body { padding: 14px 10px; }
            .login-card {
                padding: 26px 20px 28px;
                border-radius: 22px;
                box-shadow: 0 20px 50px -18px rgba(11, 28, 72, 0.2);
            }
            .field .field-input { min-height: 58px; font-size: 14.5px; padding: 24px 18px 14px 50px; }
            .field label.lbl { left: 50px; font-size: 14.5px; }
            .field .field-input:focus ~ label.lbl,
            .field .field-input:not(:placeholder-shown) ~ label.lbl { left: 46px; }
            .btn-login { padding: 16px 20px; font-size: 14.5px; border-radius: 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Brand -->
        <div class="login-brand" style="flex-direction:column; gap:10px; text-align:center;">
            <img src="<?= base_url('assets/logo.PNG') ?>" alt="Logo LARAS BPKP DIY" style="max-height:78px; width:auto; display:block; margin:0 auto;">
            <div class="brand-title">
                <div class="sub" style="font-weight:600; letter-spacing:0.2px;">BPKP PERWAKILAN D.I. YOGYAKARTA</div>
                <div class="sub" style="font-size:10.5px; opacity:0.8; margin-top:2px;">Layanan Administrasi Reservasi Aset dan Sarana</div>
            </div>
        </div>

        <!-- Header -->
        <div class="login-header">
            <h1>Selamat Datang 👋</h1>
            <p>Masuk ke akun untuk melanjutkan ke dashboard</p>
        </div>

        <?php render_flash_alerts(); ?>

        <form method="POST" action="<?= base_url('login.php') ?>" novalidate>
            <input type="hidden" name="action" value="pegawai">

            <div class="field">
                <div class="input-shell">
                    <i class="bi bi-person-badge-fill ic-prefix"></i>
                    <input
                        type="text"
                        id="inp_nip"
                        name="nip_pegawai"
                        class="field-input"
                        placeholder="NIP Pegawai"
                        autocomplete="username"
                        required
                        inputmode="numeric"
                        maxlength="30"
                        autofocus>
                    <label for="inp_nip" class="lbl">Masukkan NIP Pegawai</label>
                </div>
            </div>

            <div class="form-actions">
                <label class="check">
                    <input type="checkbox" id="remember">
                    <span>Ingat NIP saya</span>
                </label>
                <a href="#" class="link" onclick="alert('Hubungi Bagian Umum / Admin untuk bantuan reset akses.');return false;">
                    Butuh Bantuan?
                </a>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Masuk Sebagai Pegawai</span>
            </button>
        </form>

     

        <div class="page-footer">
         
            <br>
            © <?= date('Y') ?> LARAS — Bagian Umum BPKP Perwakilan DIY
        </div>
    </div>
</div>

<script>
(function(){
    try {
        const r = localStorage.getItem('laras:nip_peg');
        const inp = document.getElementById('inp_nip');
        const chk = document.getElementById('remember');
        if (r && inp) { inp.value = r; if (chk) chk.checked = true; }
        document.querySelector('form').addEventListener('submit', () => {
            if (chk && chk.checked && inp.value) localStorage.setItem('laras:nip_peg', inp.value);
            else localStorage.removeItem('laras:nip_peg');
        });
    } catch(e){}
})();
</script>
</body>
</html>
