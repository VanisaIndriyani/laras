<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../assets/logo.php';
require_login();

$user = current_user();
$page_title = $page_title ?? 'Dashboard';
$active_menu = $active_menu ?? 'dashboard';
$isAdmin_scope = is_admin();
$uid_scope = (int)$user['id'];

// Scope WHERE: Admin = semua, Pegawai = hanya user_id sendiri
$scope_mobil = $isAdmin_scope ? "status = 'pending'" : "status = 'pending' AND user_id = {$uid_scope}";
$scope_ruang = $isAdmin_scope ? "status = 'pending'" : "status = 'pending' AND user_id = {$uid_scope}";
$count_mobil_pending = db()->count('reservasi_kendaraan', $scope_mobil);
$count_ruang_pending = db()->count('reservasi_ruangan', $scope_ruang);
$total_pending = $count_mobil_pending + $count_ruang_pending;

$notifications = [];
try {
    $sql_notif_where_user = $isAdmin_scope ? '' : ' AND rk.user_id = ' . $uid_scope;
    $sql_mobil_notif = "SELECT rk.id, rk.status, rk.created_at, rk.tanggal_pinjam as tanggal, rk.tujuan, u.nama_lengkap as peminjam, 'kendaraan' as tipe,
                            CONCAT(k.merk, ' ', k.tipe, CASE WHEN k.no_plat IS NOT NULL AND k.no_plat != '' THEN CONCAT(' (', k.no_plat, ')') ELSE '' END) as objek
                        FROM reservasi_kendaraan rk
                        LEFT JOIN users u ON u.id = rk.user_id
                        LEFT JOIN kendaraan k ON k.id = rk.kendaraan_id
                        WHERE rk.status = 'pending'" . $sql_notif_where_user . "
                        ORDER BY COALESCE(rk.created_at, rk.tanggal_pinjam) DESC LIMIT 3";
    $notif_mobil = db()->fetchAll($sql_mobil_notif);
    foreach ($notif_mobil as $n) $notifications[] = $n;

    $sql_notif_where_user_rr = $isAdmin_scope ? '' : ' AND rr.user_id = ' . $uid_scope;
    $sql_ruang_notif = "SELECT rr.id, rr.status, rr.created_at, rr.tanggal_mulai as tanggal, rr.deskripsi as tujuan, u.nama_lengkap as peminjam, 'ruangan' as tipe, r.nama_ruangan as objek
                        FROM reservasi_ruangan rr
                        LEFT JOIN users u ON u.id = rr.user_id
                        LEFT JOIN ruangan r ON r.id = rr.ruangan_id
                        WHERE rr.status = 'pending'" . $sql_notif_where_user_rr . "
                        ORDER BY COALESCE(rr.created_at, rr.tanggal_mulai) DESC LIMIT 3";
    $notif_ruang = db()->fetchAll($sql_ruang_notif);
    foreach ($notif_ruang as $n) $notifications[] = $n;

    // Fallback jika created_at NULL — set default agar sorting & relative time aman
    $nowFallback = date('Y-m-d H:i:s');
    foreach ($notifications as &$n) {
        if (empty($n['created_at'])) $n['created_at'] = $nowFallback;
        if (empty($n['peminjam'])) $n['peminjam'] = '-';
        if (empty($n['objek'])) $n['objek'] = $n['tipe'] === 'kendaraan' ? 'Kendaraan' : 'Ruangan';
        if (empty($n['tujuan'])) $n['tujuan'] = '-';
        if (empty($n['tanggal'])) $n['tanggal'] = $nowFallback;
    }
    unset($n);

    usort($notifications, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    $notifications = array_slice($notifications, 0, 5);
} catch (Exception $e) { $notifications = []; }

$count_pajak_alert = 0;
try {
    $today = date('Y-m-d');
    $next30 = date('Y-m-d', strtotime('+30 days'));
    $sql_pajak = "SELECT COUNT(*) as cnt FROM kendaraan WHERE (pajak_stnk_jatuh_tempo IS NOT NULL AND pajak_stnk_jatuh_tempo <= ?) OR (pajak_tnkb_jatuh_tempo IS NOT NULL AND pajak_tnkb_jatuh_tempo <= ?)";
    $r = db()->fetchOne($sql_pajak, [$next30, $next30]);
    $count_pajak_alert = (int)($r['cnt'] ?? 0);
} catch (Exception $e) { $count_pajak_alert = 0; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B1C48">
    <meta name="description" content="LARAS - Layanan Administrasi Reservasi Aset dan Sarana BPKP Perwakilan DIY">
    <link rel="icon" type="image/png" href="<?= base_url('assets/logo.PNG') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/logo.PNG') ?>">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
   
    <?php render_flash_metas(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --navy-900: #0B1C48;
            --navy-800: #132B65;
            --navy-700: #1F3A8B;
            --navy-600: #233E90;
            --blue-500: #3B5FC7;
            --slate-50: #F8FAFC;
            --slate-100: #F1F5F9;
            --slate-200: #E2E8F0;
            --slate-300: #CBD5E1;
            --slate-500: #64748B;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-900: #0F172A;
        }
        * { font-family: 'Plus Jakarta Sans', 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; box-sizing: border-box; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        html { scroll-behavior: smooth; scroll-padding-top: 96px; }
        body {
            background: var(--slate-50);
            font-size: 13px;
            color: var(--slate-700);
            min-height: 100vh;
            line-height: 1.55;
            letter-spacing: -0.01em;
        }

        /* Sidebar backdrop — mobile overlay */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(11, 28, 72, 0.45);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 1049;
            opacity: 0;
            visibility: hidden;
            transition: all .28s ease;
        }
        .sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        /* Sidebar — NAVY SOLID: Background gelap, text putih bersih, accent biru */
        .sidebar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            height: 100vh !important;
            width: 268px !important;
            background: linear-gradient(180deg, #0B1C48 0%, #102763 45%, #0F2253 100%);
            z-index: 1050;
            transition: transform .32s cubic-bezier(.2,.8,.2,1), margin-left .32s cubic-bezier(.2,.8,.2,1), box-shadow .3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 1px solid rgba(59, 95, 199, 0.28);
            box-shadow: 4px 0 22px -10px rgba(11, 28, 72, 0.55);
            will-change: transform;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, transparent 0%, rgba(96,165,250,0.28) 50%, transparent 100%);
            pointer-events: none;
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(96, 165, 250, 0.3); border-radius: 5px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: rgba(96, 165, 250, 0.55); }
        .sidebar::-webkit-scrollbar-track { background: transparent; }

        /* Default collapsed on page load (toggle via JS) */
        .sidebar.collapsed { margin-left: -268px; }

        /* Mobile offcanvas slide */
        .sidebar.show-mobile {
            transform: translateX(0) !important;
            box-shadow: 0 12px 48px -8px rgba(11, 28, 72, 0.6);
        }

        .brand-sidebar {
            padding: 18px 17px 15px;
            position: sticky !important;
            top: 0 !important;
            background: linear-gradient(145deg, rgba(8, 22, 58, 0.985) 0%, rgba(11, 28, 72, 0.985) 40%, rgba(15, 40, 95, 0.985) 100%);
            z-index: 2;
            border-bottom: 1px solid rgba(96, 165, 250, 0.22);
            backdrop-filter: blur(8px);
            overflow: hidden;
        }
        .brand-sidebar::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -30px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(59,95,199,0.45) 0%, rgba(59,95,199,0.2) 35%, transparent 72%);
            border-radius: 50%;
            pointer-events: none;
        }
        .brand-sidebar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 18px;
            right: 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(147, 197, 253, 0.6), transparent);
        }
        .brand-sidebar .brand-row {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin-bottom: 11px;
            position: relative;
            z-index: 1;
            padding: 3px 0 0 2px;
        }
        .brand-sidebar h6 {
            color: #ffffff;
            font-weight: 900;
            font-size: 22px;
            margin: 0;
            letter-spacing: 0.3px;
            line-height: 1;
            background: linear-gradient(135deg, #ffffff 0%, #bfdbfe 55%, #93c5fd 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.45));
        }
        .brand-sidebar .tagline {
            color: rgba(219, 234, 254, 0.92);
            font-size: 11.5px;
            margin-top: 7px;
            letter-spacing: 0.25px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .brand-sidebar .tagline::before {
            content: '';
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: linear-gradient(135deg, #3B5FC7, #22c55e);
            box-shadow: 0 0 10px rgba(34,197,94,0.7);
            flex-shrink: 0;
        }
        .brand-sidebar .bagian-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(59,95,199,0.35), rgba(31,58,139,0.55));
            color: #dbeafe;
            font-size: 10px;
            padding: 6.5px 14px;
            border-radius: 999px;
            margin-top: 0;
            font-weight: 800;
            letter-spacing: 0.5px;
            border: 1px solid rgba(147, 197, 253, 0.45);
            box-shadow: 0 5px 16px -5px rgba(59, 95, 199, 0.75),
                        inset 0 1px 0 rgba(255,255,255,0.12);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(4px);
            text-transform: uppercase;
        }
        .brand-sidebar .bagian-tag::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: radial-gradient(circle, #60a5fa 0%, #2563eb 100%);
            box-shadow: 0 0 0 2px rgba(37,99,235,0.2), 0 0 12px rgba(59,95,199,0.9);
            flex-shrink: 0;
        }
        .brand-sidebar .bagian-tag i {
            color: #bfdbfe;
            font-size: 9.8px;
        }
        .brand-sidebar .bagian-tag.admin {
            background: linear-gradient(135deg, rgba(30,58,138,0.55), rgba(11,28,72,0.8));
            border-color: rgba(147, 197, 253, 0.55);
            box-shadow: 0 5px 16px -5px rgba(30, 58, 138, 0.85),
                        inset 0 1px 0 rgba(255,255,255,0.12);
        }
        .brand-sidebar .bagian-tag.admin::before {
            background: radial-gradient(circle, #fbbf24 0%, #f59e0b 70%, #d97706 100%);
            box-shadow: 0 0 0 2px rgba(245,158,11,0.22), 0 0 10px rgba(245,158,11,0.75);
        }
        .brand-sidebar .bagian-tag.admin i { color: #bfdbfe; }

        /* Sidebar Admin — tetap NAVY (sama tema dengan Pegawai, hanya accent sedikit lebih dalam) */
        .sidebar.admin {
            background: linear-gradient(180deg, #071230 0%, #0E2458 45%, #0A1E4E 100%);
            border-right: 1px solid rgba(59, 95, 199, 0.34);
            box-shadow: 4px 0 22px -10px rgba(11, 28, 72, 0.6);
        }
        .sidebar.admin::before {
            background: linear-gradient(180deg, transparent 0%, rgba(96, 165, 250, 0.34) 50%, transparent 100%);
        }
        .sidebar.admin::-webkit-scrollbar-thumb { background: rgba(96, 165, 250, 0.35); }
        .sidebar.admin::-webkit-scrollbar-thumb:hover { background: rgba(147, 197, 253, 0.6); }
        .sidebar.admin .brand-sidebar {
            background: linear-gradient(145deg, rgba(5, 14, 40, 0.99) 0%, rgba(8, 22, 58, 0.99) 40%, rgba(10, 30, 78, 0.99) 100%);
            border-bottom: 1px solid rgba(96, 165, 250, 0.32);
            overflow: hidden;
        }
        .sidebar.admin .brand-sidebar::before {
            background: radial-gradient(circle, rgba(37,99,235,0.42) 0%, rgba(59,95,199,0.22) 38%, transparent 72%);
        }
        .sidebar.admin .brand-sidebar::after { background: linear-gradient(90deg, transparent, rgba(147,197,253,0.75), transparent); }
        .sidebar.admin .brand-sidebar h6 {
            background: linear-gradient(135deg, #ffffff 0%, #dbeafe 52%, #bfdbfe 100%);
            -webkit-background-clip: text;
            background-clip: text;
        }
        .sidebar.admin .brand-sidebar .tagline {
            color: rgba(219, 234, 254, 0.94);
        }
        .sidebar.admin .brand-sidebar .tagline::before {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            box-shadow: 0 0 10px rgba(245,158,11,0.75);
        }
        .sidebar.admin .brand-sidebar .bagian-tag.admin::before {
            background: radial-gradient(circle,#fbbf24 0%,#f59e0b 70%,#d97706 100%);
            box-shadow: 0 0 0 2px rgba(245,158,11,0.22),0 0 12px rgba(245,158,11,0.8);
        }
        .sidebar.admin .menu-link:hover {
            background: rgba(37, 99, 235, 0.26);
            color: #ffffff;
        }
        .sidebar.admin .menu-link:hover i { color: #bfdbfe; }
        .sidebar.admin .menu-link.active {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 5px 18px -5px rgba(37, 99, 235, 0.8), inset 0 1px 0 rgba(255,255,255,0.18);
            border: 1px solid rgba(147, 197, 253, 0.4);
        }
        .sidebar.admin .menu-link.active::before { background: linear-gradient(180deg, #bfdbfe, #2563eb); box-shadow: 0 0 10px rgba(147, 197, 253, 0.75); }
        .sidebar.admin .menu-link.active i { color: #ffffff; }
        .sidebar.admin .menu-parent:hover { background: rgba(37, 99, 235, 0.26); color: #ffffff; }
        .sidebar.admin .menu-parent:hover i.icon-left { color: #bfdbfe; }
        .sidebar.admin .menu-parent[aria-expanded="true"] .chevron,
        .sidebar.admin .menu-parent.parent-active .chevron { color: #bfdbfe; }
        .sidebar.admin .menu-parent.parent-active,
        .sidebar.admin .menu-parent[aria-expanded="true"] {
            background: rgba(15, 34, 83, 0.55);
            color: #ffffff;
            border: 1px solid rgba(96, 165, 250, 0.32);
        }
        .sidebar.admin .menu-parent.parent-active::before { background: #93c5fd; box-shadow: 0 0 8px rgba(147, 197, 253, 0.65); }
        .sidebar.admin .menu-parent.parent-active i.icon-left,
        .sidebar.admin .menu-parent[aria-expanded="true"] i.icon-left { color: #bfdbfe; }
        .sidebar.admin .submenu-list { border-left-color: rgba(96, 165, 250, 0.45); }
        .sidebar.admin .submenu-list .menu-link::after { background: rgba(96, 165, 250, 0.65); }
        .sidebar.admin .submenu-list .menu-link:hover { background: rgba(37, 99, 235, 0.26); color: #ffffff; }
        .sidebar.admin .submenu-list .menu-link:hover i { color: #bfdbfe; }
        .sidebar.admin .submenu-list .menu-link.active {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.95), rgba(29, 78, 216, 0.95));
            color: #ffffff;
            box-shadow: 0 3px 12px -4px rgba(37, 99, 235, 0.65), inset 0 1px 0 rgba(255,255,255,0.15);
            border: 1px solid rgba(147, 197, 253, 0.35);
        }
        .sidebar.admin .submenu-list .menu-link.active::after { background: linear-gradient(90deg, #93c5fd, #ffffff); }

        /* Sidebar Divider — 3 tone glow */
        .sidebar-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.55), rgba(147,197,253,0.25), transparent);
            margin: 14px 6px 10px;
            position: relative;
            opacity: 0.95;
        }
        .sidebar.admin .sidebar-divider { background: linear-gradient(90deg, transparent, rgba(96,165,250,0.6), rgba(147,197,253,0.3), transparent); }

        .sidebar-menu {
            padding: 15px 12px 16px;
            position: relative;
            z-index: 1;
        }
        .sidebar-menu::before {
            content:'';
            position:absolute;
            bottom:-30px;
            right:-40px;
            width:280px;
            height:280px;
            background:radial-gradient(circle,rgba(59,95,199,0.14) 0%,transparent 65%);
            pointer-events:none;
            border-radius:50%;
        }

        .sidebar-title {
            padding: 6px 10px 9px;
            margin: 3px 0 7px;
            color: rgba(148, 163, 184, 0.98);
            font-size: 9.3px;
            font-weight: 800;
            letter-spacing: 1.25px;
            text-transform: uppercase;
            position: relative;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .sidebar-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(148,163,184,0.4), transparent);
            border-radius: 2px;
            margin-left: 3px;
        }
        .sidebar-title::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #3B5FC7);
            flex-shrink: 0;
            opacity: 1;
            box-shadow: 0 0 10px rgba(96, 165, 250, 0.85),
                        0 0 0 2.5px rgba(59,95,199,0.12);
        }
        .sidebar.admin .sidebar-title::before {
            background: linear-gradient(135deg, #60a5fa, #1d4ed8);
            box-shadow: 0 0 12px rgba(96,165,250,0.9),
                        0 0 0 2.5px rgba(37,99,235,0.14);
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 10.5px 12px;
            color: rgba(203, 213, 225, 0.96);
            text-decoration: none;
            border-radius: 12.5px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 3px;
            transition: all .22s cubic-bezier(.2,.8,.2,1);
            position: relative;
            will-change: transform;
            letter-spacing: -0.01em;
            gap: 10px;
            overflow: hidden;
            border: 1px solid transparent;
        }
        .menu-link::after {
            content:'';
            position:absolute;
            inset:0;
            background: linear-gradient(90deg, rgba(59,95,199,0.05), transparent);
            opacity:0;
            transition:opacity .22s ease;
            pointer-events:none;
            z-index: 0;
        }
        .menu-link > * { position: relative; z-index: 1; }
        .menu-link i {
            font-size: 14px;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            margin: 0;
            transition: transform .23s cubic-bezier(.2,.8,.2,1), color .2s ease, background .2s ease, box-shadow .2s ease;
            flex-shrink: 0;
            color: rgba(191,219,254,0.98);
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.38), rgba(15, 40, 95, 0.58));
            border-radius: 9px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.07),
                        0 3px 8px -4px rgba(0,0,0,0.38);
            border: 1px solid rgba(96,165,250,0.2);
        }
        .menu-link:hover {
            background: linear-gradient(135deg, rgba(59, 95, 199, 0.24), rgba(30, 64, 175, 0.16));
            color: #ffffff;
            transform: translateX(2px);
            box-shadow: 0 8px 22px -10px rgba(37, 99, 235, 0.6),
                        inset 0 1px 0 rgba(255,255,255,0.08);
            border-color: rgba(147,197,253,0.28);
        }
        .menu-link:hover::after { opacity: 1; }
        .menu-link:hover i {
            transform: scale(1.07) translateY(-0.5px);
            color: #ffffff;
            background: linear-gradient(135deg, rgba(59,95,199,0.85), rgba(37,99,235,0.92));
            box-shadow: 0 6px 14px -4px rgba(37,99,235,0.75),
                        inset 0 1px 0 rgba(255,255,255,0.22);
            border-color: rgba(147,197,253,0.55);
        }
        .menu-link.active {
            background: linear-gradient(135deg, #3B5FC7 0%, #2563eb 55%, #1d4ed8 100%);
            color: #ffffff;
            transform: translateX(0);
            box-shadow: 0 10px 26px -8px rgba(37, 99, 235, 0.85),
                        inset 0 1px 0 rgba(255,255,255,0.22),
                        0 0 0 1px rgba(147, 197, 253, 0.45);
            border: 1px solid rgba(191, 219, 254, 0.55);
        }
        .menu-link.active::before {
            content: '';
            position: absolute;
            left: -11px;
            top: 50%;
            width: 3.5px;
            height: 60%;
            background: linear-gradient(180deg, #dbeafe, #60a5fa, #3B5FC7);
            border-radius: 0 4px 4px 0;
            transform: translateY(-50%);
            box-shadow: 0 0 14px rgba(96, 165, 250, 0.95);
            opacity: 1;
            pointer-events: none;
            z-index: 2;
        }
        .menu-link.active i {
            transform: scale(1.05);
            color: #0B1C48;
            background: linear-gradient(135deg, #ffffff, #dbeafe, #bfdbfe);
            box-shadow: 0 5px 14px -5px rgba(0,0,0,0.42),
                        inset 0 1px 0 rgba(255,255,255,0.95);
            border-color: rgba(255,255,255,0.8);
        }

        .menu-link .badge {
            margin-left: auto;
            font-size: 9.4px;
            padding: 3px 9px;
            border-radius: 999px;
            font-weight: 800;
            letter-spacing: 0.2px;
            background: linear-gradient(135deg, rgba(71, 85, 105, 0.75), rgba(51, 65, 85, 0.88));
            color: #ffffff;
            box-shadow: 0 3px 8px -3px rgba(0,0,0,0.4),
                        inset 0 1px 0 rgba(255,255,255,0.12);
            border: 1px solid rgba(148, 163, 184, 0.38);
        }
        .menu-link.active .badge {
            background: linear-gradient(135deg, rgba(255,255,255,0.32), rgba(255,255,255,0.18));
            color: #ffffff;
            border-color: rgba(255,255,255,0.5);
            box-shadow: 0 3px 10px -3px rgba(0,0,0,0.35),
                        inset 0 1px 0 rgba(255,255,255,0.3);
        }
        .menu-link.hidden-by-search { display: none !important; }
        .menu-link.search-match {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.28), rgba(217, 119, 6, 0.14));
            color: #ffffff;
            border: 1px solid rgba(251, 191, 36, 0.35);
        }
        .menu-link.search-match span {
            box-shadow: inset 0 -2px 0 rgba(245, 158, 11, 0.65);
            border-radius: 1px;
        }
        .menu-link.search-match i {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.85), rgba(217, 119, 6, 0.9));
            color: #ffffff;
            border-color: rgba(251, 191, 36, 0.55);
        }
        .menu-parent.parent-search-open .submenu-collapse { display: block !important; }
        .menu-parent.parent-hidden-by-search { display: none !important; }

        /* Parent Dropdown (Data Master) */
        .menu-parent {
            display: flex;
            align-items: center;
            padding: 10.5px 12px;
            color: rgba(203, 213, 225, 0.96);
            text-decoration: none;
            border-radius: 12.5px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 3px;
            transition: all .22s cubic-bezier(.2,.8,.2,1);
            position: relative;
            cursor: pointer;
            width: 100%;
            border: 1px solid transparent;
            background: transparent;
            text-align: left;
            letter-spacing: -0.01em;
            gap: 10px;
            overflow: hidden;
        }
        .menu-parent::after {
            content:'';
            position:absolute;
            inset:0;
            background: linear-gradient(90deg, rgba(59,95,199,0.05), transparent);
            opacity:0;
            transition:opacity .22s ease;
            pointer-events:none;
            z-index: 0;
        }
        .menu-parent > * { position: relative; z-index: 1; }
        .menu-parent i.icon-left {
            font-size: 14px;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            margin: 0;
            transition: transform .23s cubic-bezier(.2,.8,.2,1), color .2s ease, background .2s ease, box-shadow .2s ease;
            flex-shrink: 0;
            color: rgba(191,219,254,0.98);
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.38), rgba(15, 40, 95, 0.58));
            border-radius: 9px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.07),
                        0 3px 8px -4px rgba(0,0,0,0.38);
            border: 1px solid rgba(96,165,250,0.2);
        }
        .menu-parent .chevron {
            margin-left: auto;
            font-size: 11px;
            color: rgba(191,219,254,0.95);
            transition: transform .23s cubic-bezier(.2,.8,.2,1), color .2s ease, background .2s ease;
            flex-shrink: 0;
            width: 22px;
            height: 22px;
            line-height: 22px;
            text-align: center;
            border-radius: 7px;
            background: rgba(30, 64, 175, 0.28);
            border: 1px solid rgba(96, 165, 250, 0.18);
        }
        .menu-parent:hover {
            background: linear-gradient(135deg, rgba(59, 95, 199, 0.24), rgba(30, 64, 175, 0.16));
            color: #ffffff;
            transform: translateX(2px);
            border-color: rgba(147,197,253,0.28);
            box-shadow: 0 8px 22px -10px rgba(37, 99, 235, 0.55),
                        inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .menu-parent:hover::after { opacity: 1; }
        .menu-parent:hover i.icon-left {
            transform: scale(1.07) translateY(-0.5px);
            color: #ffffff;
            background: linear-gradient(135deg, rgba(59,95,199,0.85), rgba(37,99,235,0.92));
            box-shadow: 0 6px 14px -4px rgba(37,99,235,0.75),
                        inset 0 1px 0 rgba(255,255,255,0.22);
            border-color: rgba(147,197,253,0.55);
        }
        .menu-parent:hover .chevron {
            color: #ffffff;
            background: rgba(37,99,235,0.55);
            border-color: rgba(147,197,253,0.45);
        }
        .menu-parent[aria-expanded="true"] .chevron {
            transform: rotate(90deg);
            color: #ffffff;
            background: linear-gradient(135deg, rgba(59,95,199,0.82), rgba(37,99,235,0.92));
            border-color: rgba(147,197,253,0.55);
            box-shadow: 0 3px 10px -3px rgba(37,99,235,0.7);
        }
        .menu-parent.parent-active,
        .menu-parent[aria-expanded="true"] {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.52), rgba(37, 99, 235, 0.28));
            color: #ffffff;
            box-shadow: 0 6px 18px -8px rgba(37, 99, 235, 0.55),
                        inset 0 1px 0 rgba(255,255,255,0.1);
            border: 1px solid rgba(96, 165, 250, 0.38);
        }
        .menu-parent.parent-active::before {
            content: '';
            position: absolute;
            left: -11px;
            top: 50%;
            width: 3px;
            height: 56%;
            background: linear-gradient(180deg, #93c5fd, #3B5FC7);
            border-radius: 0 4px 4px 0;
            transform: translateY(-50%);
            box-shadow: 0 0 12px rgba(96, 165, 250, 0.85);
            z-index: 2;
        }
        .menu-parent[aria-expanded="true"]::before { content: none; }
        .menu-parent.parent-active i.icon-left,
        .menu-parent[aria-expanded="true"] i.icon-left {
            color: #ffffff;
            transform: scale(1.05);
            background: linear-gradient(135deg, rgba(59,95,199,0.82), rgba(37,99,235,0.92));
            border-color: rgba(147,197,253,0.55);
        }

        /* Dropdown collapse wrapper */
        .submenu-collapse {
            overflow: hidden;
            transition: height .3s cubic-bezier(.2,.8,.2,1);
        }
        .submenu-list {
            margin: 4px 0 8px 0;
            padding: 6px 0 5px 11px;
            list-style: none;
            border-left: 1.5px dashed rgba(96, 165, 250, 0.48);
            margin-left: 27px;
            position: relative;
        }
        .sidebar.admin .submenu-list {
            border-left-color: rgba(167,139,250,0.52);
        }
        .submenu-list li {
            margin-bottom: 2px;
            position: relative;
        }
        .submenu-list li::before {
            content: '';
            position: absolute;
            left: -15.5px;
            top: 50%;
            width: 7px;
            height: 7px;
            background: radial-gradient(circle, #60a5fa 0%, #2563eb 100%);
            border-radius: 999px;
            transform: translateY(-50%);
            box-shadow: 0 0 0 2px rgba(15,40,95,0.92),
                        0 0 10px rgba(96,165,250,0.85);
            z-index: 2;
        }
        .sidebar.admin .submenu-list li::before {
            background: radial-gradient(circle, #c4b5fd 0%, #7c3aed 100%);
            box-shadow: 0 0 0 2px rgba(10,30,78,0.92),
                        0 0 10px rgba(167,139,250,0.85);
        }
        .submenu-list .menu-link {
            padding: 8.5px 11px 8.5px 13px;
            font-size: 11.3px;
            border-radius: 10px 12px 12px 10px;
            position: relative;
            color: rgba(203, 213, 225, 0.92);
            font-weight: 500;
            gap: 8px;
            overflow: visible;
        }
        .submenu-list .menu-link::before { display: none; }
        .submenu-list .menu-link i {
            font-size: 12.5px;
            width: 24px;
            height: 24px;
            line-height: 24px;
            margin: 0;
            opacity: 1;
            color: rgba(191, 219, 254, 0.98);
            background: rgba(30, 64, 175, 0.24);
            border-radius: 7px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
            border: 1px solid rgba(96,165,250,0.2);
        }
        .submenu-list .menu-link::after {
            content: '';
            position: absolute;
            left: -13px;
            top: 50%;
            width: 12px;
            height: 2px;
            background: linear-gradient(90deg, rgba(96,165,250,0.65), rgba(96,165,250,0.2));
            transform: translateY(-50%);
            border-radius: 0 2px 2px 0;
            z-index: 1;
            opacity: 1;
            inset: auto;
        }
        .submenu-list .menu-link:hover {
            color: #ffffff;
            background: linear-gradient(135deg, rgba(59, 95, 199, 0.26), rgba(30, 64, 175, 0.15));
            border-color: rgba(147,197,253,0.3);
            box-shadow: 0 6px 16px -8px rgba(37,99,235,0.6);
        }
        .submenu-list .menu-link:hover i {
            color: #ffffff;
            background: linear-gradient(135deg, rgba(59,95,199,0.85), rgba(37,99,235,0.92));
            border-color: rgba(147,197,253,0.55);
            box-shadow: 0 4px 10px -3px rgba(37,99,235,0.7);
        }
        .submenu-list .menu-link:hover::after {
            background: linear-gradient(90deg, #93c5fd, rgba(147,197,253,0.35));
            box-shadow: 0 0 8px rgba(147,197,253,0.6);
        }
        .submenu-list .menu-link.active {
            background: linear-gradient(135deg, rgba(59, 95, 199, 0.94), rgba(37, 99, 235, 0.94));
            color: #ffffff;
            box-shadow: 0 6px 16px -5px rgba(59, 95, 199, 0.75),
                        inset 0 1px 0 rgba(255,255,255,0.18),
                        0 0 0 1px rgba(147, 197, 253, 0.45);
            border: 1px solid rgba(191,219,254,0.55);
        }
        .submenu-list .menu-link.active i {
            color: #0B1C48;
            background: linear-gradient(135deg, #ffffff, #dbeafe);
            border-color: rgba(255,255,255,0.82);
            transform: scale(1.05);
        }
        .submenu-list .menu-link.active::after {
            background: linear-gradient(90deg, #ffffff, #bfdbfe);
            box-shadow: 0 0 10px rgba(191,219,254,0.95);
        }

        /* Sidebar Admin — navy deeper variant (TANPA UNGU) */
        .sidebar.admin .menu-link i,
        .sidebar.admin .menu-parent i.icon-left {
            background: linear-gradient(135deg, rgba(15,40,95,0.55), rgba(8,22,58,0.78));
            border-color: rgba(96,165,250,0.26);
            color: rgba(219,234,254,0.98);
        }
        .sidebar.admin .menu-link.active {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #3B5FC7 100%);
            border-color: rgba(191,219,254,0.6);
            box-shadow: 0 10px 26px -8px rgba(37,99,235,0.88),
                        inset 0 1px 0 rgba(255,255,255,0.24),
                        0 0 0 1px rgba(147,197,253,0.48);
        }
        .sidebar.admin .menu-link.active::before {
            background: linear-gradient(180deg, #eff6ff, #93c5fd, #2563eb);
            box-shadow: 0 0 14px rgba(147,197,253,0.98);
        }
        .sidebar.admin .menu-link.active i {
            background: linear-gradient(135deg, #ffffff, #dbeafe, #bfdbfe);
            color: #0B1C48;
            border-color: rgba(255,255,255,0.88);
        }
        .sidebar.admin .menu-link:hover {
            background: linear-gradient(135deg, rgba(37,99,235,0.28), rgba(15,40,95,0.18));
            border-color: rgba(147,197,253,0.32);
            box-shadow: 0 8px 22px -10px rgba(37,99,235,0.68),
                        inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .sidebar.admin .menu-link:hover i {
            background: linear-gradient(135deg, rgba(37,99,235,0.9), rgba(30,64,175,0.95));
            border-color: rgba(147,197,253,0.58);
            box-shadow: 0 6px 14px -4px rgba(37,99,235,0.78);
            color: #ffffff;
        }
        .sidebar.admin .menu-parent.parent-active,
        .sidebar.admin .menu-parent[aria-expanded="true"] {
            background: linear-gradient(135deg, rgba(15,40,95,0.62), rgba(37,99,235,0.3));
            border-color: rgba(96,165,250,0.42);
            box-shadow: 0 6px 18px -8px rgba(37,99,235,0.62);
        }
        .sidebar.admin .menu-parent.parent-active::before {
            background: linear-gradient(180deg, #dbeafe, #60a5fa, #2563eb);
            box-shadow: 0 0 12px rgba(96,165,250,0.92);
        }
        .sidebar.admin .menu-parent:hover {
            background: linear-gradient(135deg, rgba(37,99,235,0.28), rgba(15,40,95,0.18));
            border-color: rgba(147,197,253,0.32);
            box-shadow: 0 8px 22px -10px rgba(37,99,235,0.62),
                        inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .sidebar.admin .menu-parent:hover i.icon-left,
        .sidebar.admin .menu-parent[aria-expanded="true"] i.icon-left,
        .sidebar.admin .menu-parent.parent-active i.icon-left {
            background: linear-gradient(135deg, rgba(37,99,235,0.9), rgba(30,64,175,0.95));
            border-color: rgba(147,197,253,0.58);
            color: #ffffff;
        }
        .sidebar.admin .menu-parent .chevron {
            background: rgba(15,40,95,0.42);
            border-color: rgba(96,165,250,0.22);
            color: rgba(219,234,254,0.96);
        }
        .sidebar.admin .menu-parent[aria-expanded="true"] .chevron,
        .sidebar.admin .menu-parent:hover .chevron {
            background: linear-gradient(135deg, rgba(37,99,235,0.88), rgba(30,64,175,0.95));
            border-color: rgba(147,197,253,0.58);
            color: #ffffff;
            box-shadow: 0 3px 10px -3px rgba(37,99,235,0.72);
        }
        .sidebar.admin .submenu-list li::before {
            background: radial-gradient(circle, #93c5fd 0%, #1d4ed8 100%);
            box-shadow: 0 0 0 2px rgba(10,30,78,0.95),
                        0 0 12px rgba(96,165,250,0.92);
        }
        .sidebar.admin .submenu-list .menu-link.active {
            background: linear-gradient(135deg, rgba(37,99,235,0.96), rgba(30,64,175,0.96));
            box-shadow: 0 6px 16px -5px rgba(37,99,235,0.78),
                        inset 0 1px 0 rgba(255,255,255,0.18),
                        0 0 0 1px rgba(147,197,253,0.48);
            border-color: rgba(191,219,254,0.6);
        }
        .sidebar.admin .submenu-list .menu-link.active i {
            color: #0B1C48;
            background: linear-gradient(135deg, #ffffff, #dbeafe);
        }

        .sidebar-footer {
            padding: 4px 0 0;
            position: relative;
            margin: auto 12px 12px;
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(30, 58, 138, 0.62), rgba(15, 34, 83, 0.72));
            border: 1px solid rgba(96, 165, 250, 0.36);
            box-shadow: 0 8px 22px -9px rgba(0,0,0,0.55),
                        inset 0 1px 0 rgba(255,255,255,0.08);
            overflow: hidden;
        }
        .sidebar.admin .sidebar-footer {
            background: linear-gradient(180deg, rgba(15, 34, 83, 0.72), rgba(10, 30, 78, 0.84));
            border: 1px solid rgba(96,165,250,0.44);
            box-shadow: 0 8px 22px -9px rgba(37,99,235,0.5),
                        inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .user-mini {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            background: linear-gradient(135deg, #ffffff, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B1C48;
            font-weight: 900;
            font-size: 14px;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 4px 14px -5px rgba(0,0,0,0.55), inset 0 1px 0 rgba(255,255,255,0.9);
            border: 1.5px solid rgba(147, 197, 253, 0.6);
        }
        .avatar.admin { background: linear-gradient(135deg, #3B5FC7, #60a5fa); color: #ffffff; }
        .avatar::after {
            content: '';
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #10b981;
            border: 3px solid rgba(30, 58, 138, 0.75);
            z-index: 2;
            box-shadow: 0 0 0 1.5px rgba(16, 185, 129, 0.55), 0 0 10px rgba(16, 185, 129, 0.55);
            animation: pulseOnline 1.8s ease-in-out infinite;
        }
        @keyframes pulseOnline {
            0%, 100% { box-shadow: 0 0 0 1.5px rgba(16, 185, 129, 0.55), 0 0 8px rgba(16, 185, 129, 0.45); }
            50% { box-shadow: 0 0 0 2.5px rgba(16, 185, 129, 0.35), 0 0 16px rgba(16, 185, 129, 0.65); }
        }
        .user-mini .info { flex: 1; min-width: 0; }
        .user-mini .name {
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
            text-shadow: 0 1px 2px rgba(0,0,0,0.4);
        }
        .user-mini .meta {
            color: rgba(191, 219, 254, 0.88);
            font-size: 9.6px;
            line-height: 1.2;
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            font-weight: 600;
        }
        .user-mini .meta .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 7px;
            border-radius: 999px;
            font-weight: 800;
            letter-spacing: 0.25px;
        }
        .user-mini .meta .role-badge.pegawai {
            background: rgba(245, 158, 11, 0.18);
            border: 1px solid rgba(245, 158, 11, 0.38);
            color: #fcd34d;
        }
        .user-mini .meta .role-badge.admin {
            background: rgba(96, 165, 250, 0.22);
            border: 1px solid rgba(96, 165, 250, 0.45);
            color: #bfdbfe;
        }
        .user-mini .meta .status {
            color: rgba(148, 163, 184, 0.9);
            font-size: 9px;
            letter-spacing: 0.1px;
        }
        .user-mini .meta::before { display: none; }
        .btn-logout {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.14);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: all .25s cubic-bezier(.2,.8,.2,1);
            flex-shrink: 0;
        }
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.28);
            color: #fecaca;
            transform: scale(1.08);
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 4px 12px -5px rgba(239, 68, 68, 0.55);
        }

        /* Main Content */
        .main-content {
            margin-left: 268px !important;
            min-height: 100vh;
            transition: margin-left .32s cubic-bezier(.2,.8,.2,1);
        }
        .main-content.expanded { margin-left: 0 !important; }

        /* Topbar — NAVY CLEAN: Putih, Border Bawah Tipis */
        .topbar {
            margin-left: 268px !important;
            background: #ffffff;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--slate-100);
            padding: 12px 26px;
            display: flex;
            align-items: center;
            gap: 14px;
            position: sticky !important;
            top: 0 !important;
            z-index: 100;
            transition: margin-left .32s cubic-bezier(.2,.8,.2,1);
        }
        .topbar.expanded { margin-left: 0 !important; }
        .btn-toggle-sidebar {
            background: var(--slate-50);
            width: 40px;
            height: 40px;
            border-radius: 12px;
            color: var(--navy-900);
            font-size: 18px;
            font-weight: 700;
            transition: all .25s cubic-bezier(.2,.8,.2,1);
            border: 1px solid var(--slate-100);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            line-height: 1;
        }
        .btn-toggle-sidebar i {
            color: var(--navy-900);
            font-size: 18px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-toggle-sidebar:hover {
            background: var(--navy-900);
            color: white !important;
            box-shadow: 0 5px 14px -6px rgba(11,28,72,0.35);
            border-color: transparent;
            transform: translateY(-1px);
        }
        .btn-toggle-sidebar:hover i { color: white !important; }
        .btn-toggle-sidebar:active { transform: translateY(0) scale(0.96); }
        .topbar-title {
            flex: 1;
            font-size: 12.5px;
            color: var(--slate-500);
            font-weight: 500;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .topbar-title strong { color: var(--navy-900); font-weight: 700; }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--slate-50);
            border: 1px solid var(--slate-100);
            color: var(--navy-900);
            font-size: 16.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all .25s cubic-bezier(.2,.8,.2,1);
            cursor: pointer;
            flex-shrink: 0;
            line-height: 1;
            text-decoration: none;
        }
        .icon-btn i {
            color: var(--slate-600);
            font-size: 16.5px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .icon-btn:hover {
            background: var(--navy-900);
            box-shadow: 0 5px 14px -6px rgba(11,28,72,0.35);
            border-color: transparent;
            transform: translateY(-1px);
        }
        .icon-btn:hover,
        .icon-btn:hover i { color: white !important; }
        .icon-btn .notif-dot {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #E5232B;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px rgba(229, 35, 43, 0.25);
            animation: pulse 1.8s ease-in-out infinite;
            z-index: 3;
        }
        .icon-btn .notif-badge {
            position: absolute;
            top: 3px;
            right: 2px;
            min-width: 17px;
            height: 17px;
            border-radius: 999px;
            background: linear-gradient(135deg, #E5232B, #991B1B);
            color: #ffffff;
            font-size: 9.2px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px -2px rgba(229, 35, 43, 0.55);
            z-index: 3;
            line-height: 1;
            letter-spacing: -0.2px;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.75; }
        }
        .notif-dropdown {
            border: none !important;
            border-radius: 18px !important;
            box-shadow: 0 18px 44px -16px rgba(15, 23, 42, 0.35) !important;
            padding: 0 !important;
            min-width: 350px !important;
            max-width: 380px !important;
            overflow: hidden;
            margin-top: 10px !important;
        }
        .notif-head {
            padding: 14px 18px 12px;
            background: linear-gradient(135deg, #0B1C48 0%, #233E90 65%, #3B5FC7 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notif-head strong { font-size: 12.5px; font-weight: 800; letter-spacing: -0.2px; }
        .notif-head .total-pending {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            color: #ffffff;
            border-radius: 999px;
            padding: 2.5px 9px;
            font-size: 10px;
            font-weight: 800;
        }
        .notif-list {
            max-height: 370px;
            overflow-y: auto;
            padding: 6px 8px;
            background: #f8fafc;
        }
        .notif-list::-webkit-scrollbar { width: 5px; }
        .notif-list::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 3px; }
        .notif-empty {
            padding: 36px 22px;
            text-align: center;
            color: var(--slate-500);
            font-size: 11.5px;
            font-weight: 500;
        }
        .notif-empty i {
            font-size: 42px;
            color: var(--slate-300);
            display: block;
            margin-bottom: 10px;
        }
        .notif-item {
            display: flex;
            gap: 12px;
            padding: 11px 12px;
            border-radius: 13px;
            margin-bottom: 4px;
            transition: all .18s ease;
            cursor: pointer;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        .notif-item:hover {
            background: linear-gradient(180deg, #ffffff, #eff6ff);
            border-color: rgba(59, 95, 199, 0.28);
            transform: translateY(-1px);
        }
        .notif-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4px 10px -5px rgba(0,0,0,0.25);
        }
        .notif-icon.kendaraan { background: linear-gradient(135deg, #233E90, #3B5FC7); }
        .notif-icon.ruangan { background: linear-gradient(135deg, #047857, #10b981); }
        .notif-icon.admin { background: linear-gradient(135deg, #7F1D1D, #DC2626); }
        .notif-body { flex: 1; min-width: 0; }
        .notif-title {
            font-size: 11.6px;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 3px;
            line-height: 1.3;
        }
        .notif-title .badge-tipe {
            display: inline-block;
            background: rgba(245, 158, 11, 0.18);
            color: #b45309;
            font-size: 8.8px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 6px;
            margin-right: 6px;
            letter-spacing: 0.2px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .notif-desc {
            font-size: 10.4px;
            color: var(--slate-500);
            line-height: 1.5;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .notif-time {
            font-size: 9.3px;
            color: var(--slate-400);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .notif-time i { font-size: 9.3px; }
        .notif-foot {
            padding: 10px 16px;
            background: #ffffff;
            border-top: 1px solid var(--slate-100);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notif-foot a {
            font-size: 11px;
            font-weight: 700;
            color: var(--navy-900);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all .18s ease;
        }
        .notif-foot a:hover { color: #3B5FC7; }
        .notif-foot a:hover i { transform: translateX(3px); }
        .notif-foot a i { font-size: 12px; transition: transform .2s ease; }

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 6px;
            background: var(--slate-50);
            border: 1px solid var(--slate-100);
            border-radius: 14px;
            cursor: pointer;
            transition: all .25s cubic-bezier(.2,.8,.2,1);
            flex-shrink: 0;
        }
        .user-dropdown:hover {
            border-color: rgba(11,28,72,0.15);
            box-shadow: 0 4px 12px -8px rgba(11,28,72,0.15);
            background: #ffffff;
        }
        .user-dropdown .ud-info { text-align: right; }
        .user-dropdown .ud-name { font-size: 11.6px; font-weight: 800; color: var(--navy-900); line-height: 1.2; }
        .user-dropdown .ud-role { font-size: 9.8px; color: var(--slate-500); line-height: 1.2; margin-top: 2px; }
        .user-dropdown .ud-role span { color: var(--navy-700); font-weight: 700; }

        /* Page Content */
        .page-content { padding: 24px 28px 48px; }
        .page-header { margin-bottom: 22px; }
        .page-header h4 {
            font-size: 22px;
            font-weight: 800;
            color: var(--slate-900);
            margin: 0;
            letter-spacing: -0.35px;
            line-height: 1.15;
        }
        .page-header .breadcrumb {
            margin: 7px 0 0;
            font-size: 11.5px;
            --bs-breadcrumb-divider: '›';
        }
        .page-header .breadcrumb a { color: var(--slate-500); text-decoration: none; font-weight: 500; }
        .page-header .breadcrumb .active { color: var(--navy-900); font-weight: 700; }

        /* Cards — NAVY CLEAN: Putih Total, Radius Besar, Shadow Lembut */
        .card {
            border: 1px solid var(--slate-100);
            border-radius: 20px;
            box-shadow: 0 1px 3px 0 rgba(15,23,42,0.05), 0 1px 2px -1px rgba(15,23,42,0.03);
            margin-bottom: 18px;
            background: #ffffff;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--slate-100);
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header .card-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--slate-900);
            margin: 0;
            letter-spacing: -0.15px;
        }
        .card-body { padding: 24px; }

        /* Stat Cards — NAVY CLEAN */
        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px 20px;
            border: 1px solid var(--slate-100);
            box-shadow: 0 1px 3px 0 rgba(15,23,42,0.05), 0 1px 2px -1px rgba(15,23,42,0.03);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 90px; height: 90px;
            background: radial-gradient(circle at top right, rgba(11,28,72,0.05), transparent 70%);
            pointer-events: none;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px -12px rgba(11,28,72,0.2);
            border-color: var(--slate-200);
        }
        .stat-card .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }
        .stat-card .stat-label {
            font-size: 10.8px;
            color: var(--slate-500);
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .stat-card .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1;
            margin-bottom: 7px;
            letter-spacing: -0.5px;
        }
        .stat-card .stat-sub {
            font-size: 11px;
            color: var(--slate-500);
            font-weight: 500;
        }
        .stat-icon.blue   { background: rgba(11,28,72,0.08); color: var(--navy-900); }
        .stat-icon.green  { background: rgba(16, 185, 129, 0.09); color: #047857; }
        .stat-icon.red    { background: rgba(229, 35, 43, 0.08); color: #dc2626; }
        .stat-icon.amber  { background: rgba(245,158,11,0.09); color: #b45309; }
        .stat-icon.purple { background: rgba(124,58,237,0.08); color: #6d28d9; }
        .stat-icon.pink   { background: rgba(236,72,153,0.08); color: #be185d; }
        .stat-icon.cyan   { background: rgba(14,165,233,0.09); color: #0369a1; }
        .stat-icon.navy   { background: rgba(11,28,72,0.09); color: var(--navy-900); }

        /* Buttons — NAVY CLEAN: Solid Navy, Radius Besar */
        .btn {
            font-size: 12px;
            font-weight: 600;
            border-radius: 13px;
            padding: 8.5px 17px;
            transition: all 0.24s cubic-bezier(.2,.8,.2,1);
            letter-spacing: -0.01em;
        }
        .btn i { margin-right: 5px; }
        .btn-sm { padding: 5.5px 12px; font-size: 11px; border-radius: 10px; }
        .btn-lg { padding: 11px 22px; font-size: 13px; border-radius: 14px; }
        .btn-primary { background: var(--navy-900); border: none; color: #fff; box-shadow: 0 4px 14px -6px rgba(11,28,72,0.4); }
        .btn-primary:hover { background: var(--navy-800); box-shadow: 0 6px 18px -6px rgba(11,28,72,0.5); transform: translateY(-1.5px); color: #fff; }
        .btn-success { background: #047857; border: none; color: #fff; box-shadow: 0 4px 14px -6px rgba(16,185,129,0.4); }
        .btn-success:hover { background: #059669; color: #fff; box-shadow: 0 6px 18px -6px rgba(16,185,129,0.45); transform: translateY(-1.5px); }
        .btn-warning { background: #b45309; border: none; color: #fff; box-shadow: 0 4px 14px -6px rgba(245,158,11,0.45); }
        .btn-warning:hover { color: #fff; background: #d97706; box-shadow: 0 6px 18px -6px rgba(245,158,11,0.5); transform: translateY(-1.5px); }
        .btn-danger  { background: #dc2626; border: none; color: #fff; box-shadow: 0 4px 14px -6px rgba(239,68,68,0.4); }
        .btn-danger:hover { background: #ef4444; color: #fff; box-shadow: 0 6px 18px -6px rgba(239,68,68,0.45); transform: translateY(-1.5px); }
        .btn-secondary { background: var(--slate-100); color: var(--slate-700); border: 1px solid var(--slate-200); }
        .btn-secondary:hover { background: var(--slate-200); color: var(--slate-900); transform: translateY(-1px); }
        .btn-purple  { background: var(--navy-700); color: #fff; border: none; box-shadow: 0 4px 14px -6px rgba(31,58,139,0.4); }
        .btn-purple:hover { background: var(--navy-600); color: #fff; box-shadow: 0 6px 18px -6px rgba(31,58,139,0.45); transform: translateY(-1.5px); }
        .btn-outline-primary {
            background: #ffffff; color: var(--navy-900); border: 1.5px solid var(--slate-200);
        }
        .btn-outline-primary:hover { background: var(--navy-900); color: #fff; border-color: var(--navy-900); transform: translateY(-1px); }

        /* Tables — NAVY CLEAN */
        .table thead th {
            font-size: 11px;
            font-weight: 800;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--slate-50);
            border: none;
            border-bottom: 1.5px solid var(--slate-200);
            padding: 13px 16px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .table tbody td {
            font-size: 12.8px;
            padding: 14px 16px;
            border-color: var(--slate-100);
            vertical-align: middle;
            color: var(--slate-700);
            line-height: 1.55;
            font-weight: 500;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-bg-type: rgba(248, 250, 252, 0.5);
        }
        .table-hover > tbody > tr:hover > * {
            --bs-table-hover-bg: rgba(11,28,72,0.04);
            background: rgba(11,28,72,0.035);
        }
        .table > :not(:first-child) {
            border-top: 2px solid var(--slate-100);
        }
        .table-responsive {
            border-radius: 18px;
            background: white;
            border: 1px solid var(--slate-100);
            box-shadow: 0 1px 3px 0 rgba(15,23,42,0.04);
        }
        .table-responsive .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-responsive .table thead th:first-child { border-top-left-radius: 18px; }
        .table-responsive .table thead th:last-child  { border-top-right-radius: 18px; }
        .table-responsive .table tbody:last-child tr:last-child td:first-child { border-bottom-left-radius: 18px; }
        .table-responsive .table tbody:last-child tr:last-child td:last-child  { border-bottom-right-radius: 18px; }
        .no-records {
            padding: 44px 20px;
            text-align: center;
            color: var(--slate-500);
            font-size: 12.5px;
            font-weight: 500;
        }
        .no-records i { font-size: 40px; color: var(--slate-200); margin-bottom: 10px; display: block; }

        /* Badges — NAVY CLEAN: Soft Pill */
        .badge { font-weight: 700; padding: 5.5px 12px; font-size: 10.5px; letter-spacing: 0.15px; border-radius: 999px; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 13px; font-size: 10.5px; font-weight: 700; border-radius: 999px; }
        .status-badge::before {
            content: '';
            width: 6.5px; height: 6.5px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.04);
        }
        .bg-success { background: rgba(16, 185, 129, 0.12) !important; color: #047857 !important; }
        .bg-warning { background: rgba(245, 158, 11, 0.12) !important; color: #b45309 !important; }
        .bg-danger  { background: rgba(239, 68, 68, 0.12) !important; color: #dc2626 !important; }
        .bg-info    { background: rgba(14, 165, 233, 0.12) !important; color: #0369a1 !important; }
        .bg-primary { background: rgba(11, 28, 72, 0.1) !important; color: var(--navy-900) !important; }
        .bg-secondary { background: var(--slate-100) !important; color: var(--slate-600) !important; }
        .text-bg-success { background: #047857 !important; color: #ffffff !important; }
        .text-bg-warning { background: #b45309 !important; color: #ffffff !important; }
        .text-bg-danger  { background: #dc2626 !important; color: #ffffff !important; }
        .text-bg-info    { background: #0369a1 !important; color: #ffffff !important; }
        .text-bg-primary { background: var(--navy-900) !important; color: #ffffff !important; }
        .text-bg-secondary { background: var(--slate-600) !important; color: #ffffff !important; }

        /* Inputs — NAVY CLEAN */
        .form-label { font-size: 11.8px; font-weight: 600; color: var(--slate-700); margin-bottom: 6px; letter-spacing: -0.01em; }
        .form-control, .form-select {
            font-size: 12.8px;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1.5px solid var(--slate-200);
            transition: all 0.22s ease;
            background: #ffffff;
            color: var(--slate-900);
            font-weight: 500;
        }
        .form-control::placeholder { color: var(--slate-400, #94a3b8); font-weight: 400; }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 4px rgba(11,28,72,0.09);
            border-color: var(--navy-900);
            background: #ffffff;
        }
        .form-text { font-size: 10.8px; color: var(--slate-500); font-weight: 500; }
        .input-group-text {
            font-size: 13px;
            border-radius: 12px 0 0 12px;
            background: var(--slate-50);
            border: 1.5px solid var(--slate-200);
            color: var(--slate-500);
            font-weight: 600;
        }
        .search-box { position: relative; }
        .search-input {
            border-radius: 999px !important;
            padding-left: 42px !important;
            background: var(--slate-50);
            border: 1.5px solid var(--slate-200);
        }
        .search-input:focus { background: #ffffff; border-color: var(--navy-900); }
        .search-wrapper { position: relative; }
        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-500);
            font-size: 13.5px;
            z-index: 2;
            pointer-events: none;
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--slate-500);
            z-index: 2;
            pointer-events: none;
        }
        .search-results {
            position: absolute;
            top: calc(100% + 6px);
            left: 0; right: 0;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 18px 44px -16px rgba(15, 23, 42, 0.28);
            border: 1px solid rgba(148, 163, 184, 0.22);
            padding: 8px;
            max-height: 380px;
            overflow-y: auto;
            z-index: 999;
            display: none;
        }
        .search-results.active { display: block; animation: fadeInUp .2s ease; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .search-results::-webkit-scrollbar { width: 5px; }
        .search-results::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 3px; }
        .search-res-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 11px;
            cursor: pointer;
            transition: all .18s ease;
            text-decoration: none;
            margin-bottom: 2px;
            border: 1px solid transparent;
        }
        .search-res-item:hover {
            background: linear-gradient(180deg, #eff6ff, #ffffff);
            border-color: rgba(59, 95, 199, 0.22);
            transform: translateX(2px);
        }
        .search-res-icon {
            width: 34px; height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #233E90, #3B5FC7);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 3px 10px -5px rgba(11, 28, 72, 0.5);
        }
        .search-res-info { flex: 1; min-width: 0; }
        .search-res-title {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .search-res-title mark {
            background: rgba(245, 158, 11, 0.28);
            color: #92400e;
            padding: 0 2px;
            border-radius: 3px;
            font-weight: 800;
        }
        .search-res-sub {
            font-size: 9.6px;
            color: var(--slate-500);
            font-weight: 500;
        }
        .search-empty {
            padding: 28px 16px;
            text-align: center;
            color: var(--slate-500);
            font-size: 11px;
            font-weight: 500;
        }
        .search-empty i {
            font-size: 30px;
            color: var(--slate-300);
            display: block;
            margin-bottom: 8px;
        }

        /* NAVY CLEAN: Sidebar footer user dropdown (chip style) */
        .user-sidebar-dropdown {
            padding: 11px 13px;
            border-radius: 13px;
            background: var(--slate-50);
            border: 1px solid var(--slate-100);
            box-shadow: none;
            transition: all .22s ease;
        }
        .user-sidebar-dropdown:hover {
            background: #ffffff;
            border-color: var(--slate-200);
            transform: translateY(-0.5px);
            box-shadow: 0 3px 10px -6px rgba(15,23,42,0.1);
        }
        .user-sidebar-dropdown .name {
            color: var(--navy-900) !important;
            font-weight: 800 !important;
            font-size: 12px !important;
            margin-bottom: 3px;
            letter-spacing: -0.1px;
        }
        .user-sidebar-dropdown .chevron {
            color: var(--slate-500) !important;
            font-size: 10.5px !important;
        }
        .user-sidebar-dropdown .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 800;
            letter-spacing: 0.2px;
            font-size: 9.4px;
        }
        .user-sidebar-dropdown .role-badge.pegawai {
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.2);
            color: #b45309;
        }
        .user-sidebar-dropdown .role-badge.admin {
            background: rgba(11,28,72,0.08);
            border: 1px solid rgba(11,28,72,0.15);
            color: var(--navy-900);
        }
        .user-sidebar-dropdown .status {
            color: var(--slate-400, #94a3b8);
            font-size: 9.2px;
            letter-spacing: 0.1px;
            font-weight: 600;
        }
        .sidebar-footer .dropdown-menu {
            background: #ffffff;
            backdrop-filter: blur(10px);
            border: 1px solid var(--slate-200);
            border-radius: 13px;
            box-shadow: 0 10px 30px -12px rgba(15,23,42,0.2);
        }
        .sidebar-footer .dropdown-menu .dropdown-item {
            color: var(--slate-700);
            padding: 8px 13px;
            border-radius: 9px;
            font-size: 11.8px;
            font-weight: 500;
        }
        .sidebar-footer .dropdown-menu .dropdown-item:hover {
            background: var(--slate-50);
            color: var(--navy-900);
        }
        .sidebar-footer .dropdown-menu .dropdown-divider {
            border-color: var(--slate-100);
        }

        /* Page Header Compact (Breadcrumb) */
        .page-content > .page-header:first-child { margin-bottom: 20px; }
        .page-content > .page-header:first-child h4 {
            font-size: 24px;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.4px;
            line-height: 1.1;
        }
        .page-content > .page-header:first-child .breadcrumb {
            margin: 6px 0 0;
            font-size: 11.8px;
        }

        /* MODERN HERO WELCOME CARD — NAVY CLEAN */
        .hero-card-modern {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            padding: 30px 38px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 12px 34px -20px rgba(11, 28, 72, 0.35), 0 2px 8px -4px rgba(15, 23, 42, 0.06);
            margin-bottom: 24px;
        }
        .hero-card-modern::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #0B1C48 0%, #3B5FC7 45%, #60a5fa 100%);
            z-index: 3;
        }
        .hero-card-modern::after {
            content: '';
            position: absolute;
            right: -80px; top: -80px;
            width: 360px; height: 360px;
            background:
                radial-gradient(circle at 30% 30%, rgba(37, 99, 235, 0.09) 0%, transparent 60%),
                radial-gradient(circle at 70% 70%, rgba(15, 34, 83, 0.07) 0%, transparent 60%);
            pointer-events: none;
            z-index: 1;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(11,28,72,0.1);
            color: var(--navy-900);
            font-size: 10.5px;
            font-weight: 800;
            padding: 5.5px 13px;
            border-radius: 999px;
            letter-spacing: 0.3px;
            border: 1px solid rgba(11,28,72,0.16);
            margin-bottom: 18px;
            position: relative;
            z-index: 2;
        }
        .hero-badge i { color: var(--navy-700); }
        .hero-title {
            font-size: 30px;
            font-weight: 900;
            background: linear-gradient(135deg, #0B1C48 0%, #1E3A8A 60%, #3B5FC7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.12;
            letter-spacing: -0.5px;
            margin: 0 0 14px;
            position: relative;
            z-index: 2;
        }
        .hero-title strong {
            -webkit-text-fill-color: inherit;
            background: inherit;
            -webkit-background-clip: text;
            background-clip: text;
        }
        .hero-desc {
            color: var(--slate-500);
            font-size: 12.4px;
            line-height: 1.75;
            max-width: 540px;
            margin: 0 0 24px;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }
        .hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }
        .hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10.5px 20px;
            border-radius: 14px;
            font-size: 11.8px;
            font-weight: 800;
            text-decoration: none;
            transition: all .22s cubic-bezier(.2,.8,.2,1);
            cursor: pointer;
            border: none;
        }
        .hero-btn.primary {
            background: linear-gradient(135deg, #0B1C48 0%, #233E90 55%, #3B5FC7 100%);
            color: #fff;
            box-shadow: 0 8px 20px -8px rgba(11,28,72,0.7), inset 0 1px 0 rgba(255,255,255,0.18);
            border: 1px solid rgba(96, 165, 250, 0.35);
        }
        .hero-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -10px rgba(11,28,72,0.8), inset 0 1px 0 rgba(255,255,255,0.22);
            color: #fff;
        }
        .hero-btn.ghost {
            background: #ffffff;
            color: var(--slate-700);
            border: 1.5px solid var(--slate-200);
            box-shadow: 0 3px 10px -6px rgba(15,23,42,0.08);
        }
        .hero-btn.ghost:hover {
            background: var(--slate-50);
            border-color: rgba(30, 58, 138, 0.3);
            transform: translateY(-2px);
            color: var(--navy-900);
        }
        .hero-btn i { font-size: 14px; }
        .hero-illustration {
            position: absolute;
            right: 32px;
            top: 50%;
            transform: translateY(-50%);
            width: 320px;
            height: 220px;
            pointer-events: none;
            z-index: 1;
            opacity: 0.75;
            filter: drop-shadow(0 12px 24px rgba(11, 28, 72, 0.12));
        }

        /* NEW MODERN STAT CARD (with faded big icon background) — NAVY CLEAN */
        .stat-card-modern {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px 22px 20px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 4px 14px -8px rgba(15,23,42,0.1);
            transition: all .28s cubic-bezier(.2,.8,.2,1);
            position: relative;
            overflow: hidden;
            min-height: 124px;
        }
        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 4px;
            background: var(--navy-900);
            z-index: 2;
            border-radius: 0 4px 4px 0;
        }
        .stat-card-modern.blue::before   { background: linear-gradient(180deg, #0B1C48, #3B5FC7); }
        .stat-card-modern.green::before  { background: linear-gradient(180deg, #047857, #10b981); }
        .stat-card-modern.purple::before { background: linear-gradient(180deg, #5b21b6, #8b5cf6); }
        .stat-card-modern.amber::before  { background: linear-gradient(180deg, #b45309, #f59e0b); }
        .stat-card-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px -14px rgba(11, 28, 72, 0.28), 0 6px 14px -8px rgba(15,23,42,0.08);
            border-color: rgba(148, 163, 184, 0.35);
        }
        .stat-card-modern .stat-icon-bg {
            position: absolute;
            right: -8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 104px;
            opacity: 0.05;
            color: var(--navy-900);
            line-height: 1;
            pointer-events: none;
            z-index: 1;
        }
        .stat-card-modern.blue .stat-icon-bg { color: var(--navy-700); opacity: 0.08; }
        .stat-card-modern.green .stat-icon-bg { color: #10b981; opacity: 0.08; }
        .stat-card-modern.purple .stat-icon-bg { color: #7c3aed; opacity: 0.08; }
        .stat-card-modern.amber .stat-icon-bg { color: #f59e0b; opacity: 0.08; }
        .stat-card-modern .sq-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            color: #fff;
            margin-bottom: 16px;
            position: relative;
            z-index: 2;
            box-shadow: 0 6px 14px -6px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.2);
        }
        .stat-card-modern.blue .sq-icon   { background: linear-gradient(135deg, #0B1C48, #3B5FC7); }
        .stat-card-modern.green .sq-icon  { background: linear-gradient(135deg, #047857, #10b981); }
        .stat-card-modern.purple .sq-icon { background: linear-gradient(135deg, #5b21b6, #8b5cf6); }
        .stat-card-modern.amber .sq-icon  { background: linear-gradient(135deg, #b45309, #f59e0b); }
        .stat-card-modern .stat-title {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--slate-500);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 7px;
            position: relative;
            z-index: 2;
        }
        .stat-card-modern .stat-number {
            font-size: 30px;
            font-weight: 900;
            background: linear-gradient(135deg, #0f172a, #1E293B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            letter-spacing: -0.6px;
            margin-bottom: 7px;
            position: relative;
            z-index: 2;
        }
        .stat-card-modern .stat-foot {
            font-size: 10.5px;
            color: var(--slate-500);
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        /* MODUL CEPAT SECTION — NAVY CLEAN */
        .section-title {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--slate-900);
            margin: 8px 2px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.1px;
        }
        .section-title::before {
            content: '';
            display: inline-block;
            width: 5px; height: 20px;
            border-radius: 5px;
            background: linear-gradient(180deg, #0B1C48 0%, #3B5FC7 100%);
            box-shadow: 0 2px 6px -2px rgba(11, 28, 72, 0.4);
        }
        .modul-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px 22px 20px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 3px 12px -7px rgba(15,23,42,0.1);
            transition: all .28s cubic-bezier(.2,.8,.2,1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .modul-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--navy-900);
            z-index: 2;
            opacity: 0.9;
        }
        .modul-card.blue::before   { background: linear-gradient(90deg, #0B1C48, #3B5FC7); }
        .modul-card.green::before  { background: linear-gradient(90deg, #047857, #10b981); }
        .modul-card.purple::before { background: linear-gradient(90deg, #5b21b6, #8b5cf6); }
        .modul-card.amber::before  { background: linear-gradient(90deg, #b45309, #f59e0b); }
        .modul-card::after {
            content: '';
            position: absolute;
            right: -60px; bottom: -60px;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(15,23,42,0.04) 0%, transparent 70%);
            pointer-events: none;
            z-index: 1;
            opacity: 0.85;
        }
        .modul-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 32px -16px rgba(11, 28, 72, 0.28), 0 6px 14px -8px rgba(15,23,42,0.08);
            border-color: rgba(148, 163, 184, 0.35);
        }
        .modul-card .modul-icon {
            width: 54px;
            height: 54px;
            border-radius: 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #fff;
            margin-bottom: 16px;
            box-shadow: 0 8px 18px -8px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.2);
            position: relative;
            z-index: 2;
        }
        .modul-card.blue .modul-icon   { background: linear-gradient(135deg, #0B1C48, #3B5FC7); }
        .modul-card.green .modul-icon  { background: linear-gradient(135deg, #047857, #10b981); }
        .modul-card.purple .modul-icon { background: linear-gradient(135deg, #5b21b6, #8b5cf6); }
        .modul-card.amber .modul-icon  { background: linear-gradient(135deg, #b45309, #f59e0b); }
        .modul-card .modul-name {
            font-size: 14px;
            font-weight: 800;
            color: var(--slate-900);
            margin: 0 0 7px;
            letter-spacing: -0.15px;
            position: relative;
            z-index: 2;
        }
        .modul-card .modul-desc {
            font-size: 11.2px;
            color: var(--slate-500);
            line-height: 1.6;
            margin: 0 0 16px;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }
        .modul-card .modul-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.2px;
            font-weight: 800;
            text-decoration: none;
            padding: 5px 0;
            transition: all .18s ease;
            position: relative;
            z-index: 2;
        }
        .modul-card.blue .modul-link   { color: var(--navy-900); }
        .modul-card.green .modul-link  { color: #059669; }
        .modul-card.purple .modul-link { color: #6d28d9; }
        .modul-card.amber .modul-link  { color: #d97706; }
        .modul-card .modul-link i {
            transition: transform .22s ease;
            font-size: 12.5px;
        }
        .modul-card:hover .modul-link i { transform: translateX(4px); }

        /* Buttons — NAVY CLEAN SOLID (NO GRADIENTS) */
        .btn {
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 12px;
            padding: 8.5px 17px;
            transition: all 0.22s cubic-bezier(.2,.8,.2,1);
            border: none;
            letter-spacing: 0.1px;
        }
        .btn i { margin-right: 5px; }
        .btn-sm { padding: 5.5px 12px; font-size: 10.5px; border-radius: 10px; }
        .btn-primary { background: var(--navy-900); color: #fff; box-shadow: 0 3px 10px -4px rgba(11,28,72,0.45); }
        .btn-primary:hover { background: var(--navy-800); color: #fff; box-shadow: 0 7px 18px -6px rgba(11,28,72,0.55); transform: translateY(-1.5px); }
        .btn-success { background: #059669; color: #fff; box-shadow: 0 3px 10px -4px rgba(5,150,105,0.45); }
        .btn-success:hover { background: #047857; color: #fff; box-shadow: 0 7px 18px -6px rgba(5,150,105,0.55); transform: translateY(-1.5px); }
        .btn-warning { background: var(--navy-700); color: #fff; box-shadow: 0 3px 10px -4px rgba(31,58,139,0.45); }
        .btn-warning:hover { background: var(--navy-600); color: #fff; box-shadow: 0 7px 18px -6px rgba(31,58,139,0.55); transform: translateY(-1.5px); }
        .btn-danger  { background: #dc2626; color: #fff; box-shadow: 0 3px 10px -4px rgba(220,38,38,0.4); }
        .btn-danger:hover { background: #b91c1c; color: #fff; box-shadow: 0 7px 18px -6px rgba(220,38,38,0.5); transform: translateY(-1.5px); }
        .btn-secondary { background: var(--slate-100); color: var(--slate-700); border: 1px solid var(--slate-200); }
        .btn-secondary:hover { background: var(--slate-200); color: var(--slate-900); transform: translateY(-1.5px); }
        .btn-purple  { background: var(--navy-900); color: #fff; box-shadow: 0 3px 10px -4px rgba(11,28,72,0.45); }
        .btn-purple:hover { background: var(--navy-800); color: #fff; box-shadow: 0 7px 18px -6px rgba(11,28,72,0.55); transform: translateY(-1.5px); }

        /* Tables */
        .table thead th {
            font-size: 10.5px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border: none;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 14px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .table tbody td {
            font-size: 11.5px;
            padding: 13px 14px;
            border-color: #f1f5f9;
            vertical-align: middle;
            color: #334155;
            line-height: 1.5;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-bg-type: rgba(248, 250, 252, 0.55);
        }
        .table-hover > tbody > tr:hover > * {
            --bs-table-hover-bg: rgba(35, 62, 144, 0.06);
            background: rgba(35, 62, 144, 0.05);
        }
        .table > :not(:first-child) {
            border-top: 2px solid #e2e8f0;
        }
        .table-responsive {
            border-radius: 16px;
            background: white;
        }
        .table-responsive .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-responsive .table thead th:first-child { border-top-left-radius: 16px; }
        .table-responsive .table thead th:last-child  { border-top-right-radius: 16px; }
        .table-responsive .table tbody:last-child tr:last-child td:first-child { border-bottom-left-radius: 16px; }
        .table-responsive .table tbody:last-child tr:last-child td:last-child  { border-bottom-right-radius: 16px; }
        .no-records {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
        }
        .no-records i { font-size: 38px; color: #cbd5e1; margin-bottom: 8px; display: block; }

        /* Badges */
        .badge { font-weight: 700; padding: 5px 11px; font-size: 9.5px; letter-spacing: 0.2px; border-radius: 999px; }
        .status-badge { display: inline-flex; align-items: center; gap: 5px; }
        .status-badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }

        /* Inputs */
        .form-label { font-size: 11px; font-weight: 600; color: #334155; margin-bottom: 5px; }
        .form-control, .form-select {
            font-size: 11.5px;
            padding: 9.5px 13px;
            border-radius: 11px;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 4px rgba(35, 62, 144, 0.12);
            border-color: #233E90;
        }
        .form-text { font-size: 10px; color: #94a3b8; }
        .input-group-text {
            font-size: 12px;
            border-radius: 11px 0 0 11px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: #64748b;
        }
        .search-input {
            border-radius: 999px !important;
            padding-left: 38px;
        }
        .search-wrapper { position: relative; }
        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
        }

        /* Modal — NAVY CLEAN */
        .modal-content {
            border-radius: 20px;
            border: 1px solid var(--slate-200);
            box-shadow: 0 20px 50px -12px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }
        .modal-header {
            background: #ffffff;
            color: var(--navy-900);
            border-radius: 0;
            padding: 16px 22px;
            border-bottom: 1px solid var(--slate-100);
        }
        .modal-header .modal-title { font-size: 13.5px; font-weight: 800; letter-spacing: -0.1px; }
        .modal-header .btn-close {
            background: var(--slate-50);
            border: 1.5px solid var(--slate-200);
            border-radius: 10px;
            padding: 6px;
            box-shadow: none;
            opacity: 1;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background-image: none !important;
            transition: all 0.2s ease;
        }
        .modal-header .btn-close::before {
            content: '\F62A';
            font-family: 'bootstrap-icons';
            font-size: 15px;
            color: var(--slate-600);
            font-weight: 900;
            line-height: 1;
        }
        .modal-header .btn-close:hover {
            background: #fef2f2;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .modal-header .btn-close:hover::before {
            color: #dc2626;
        }
        .modal-header .btn-close:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(11, 28, 72, 0.1);
        }
        .modal-footer { padding: 14px 22px; border-color: var(--slate-100); background: var(--slate-50); }

        /* Section Steps (Form) — NAVY CLEAN */
        .form-section {
            background: var(--slate-50);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid var(--slate-200);
        }
        .form-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--slate-200);
        }
        .section-number {
            width: 28px;
            height: 28px;
            background: var(--navy-900);
            color: #fff;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11.5px;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 3px 8px -3px rgba(11,28,72,0.4);
        }
        .section-text strong { font-size: 12px; color: var(--slate-900); font-weight: 800; display: block; line-height: 1.2; }
        .section-text small { font-size: 10px; color: var(--slate-500); }

        /* Checkbox group */
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 8px;
        }
        .checkbox-group .form-check {
            padding: 10px 12px 10px 36px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin: 0;
            transition: all 0.2s;
            cursor: pointer;
        }
        .checkbox-group .form-check:hover { border-color: #cbd5e1; background: #f8fafc; }
        .checkbox-group .form-check-input {
            width: 15px;
            height: 15px;
            margin-top: 2px;
            margin-left: -24px;
        }
        .checkbox-group .form-check-label {
            font-size: 10.5px;
            color: #334155;
            font-weight: 500;
            cursor: pointer;
        }

        /* Sticky footer for long forms */
        .form-actions-sticky {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            padding: 14px 20px;
            margin: 20px -20px -20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            box-shadow: 0 -5px 16px rgba(15,23,42,0.04);
            z-index: 50;
        }

        /* Hero / Welcome Card — NAVY CLEAN */
        .hero-card {
            background: #ffffff;
            color: var(--slate-900);
            border-radius: 22px;
            padding: 30px 34px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 3px 10px -4px rgba(15, 23, 42, 0.08);
            border: 1px solid var(--slate-200);
        }
        .hero-card::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -60px;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(11,28,72,0.05) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-top {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        .hero-emblem {
            flex-shrink: 0;
            background: rgba(11,28,72,0.06);
            padding: 8px;
            border-radius: 18px;
            border: 1px solid rgba(11,28,72,0.1);
        }
        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(11, 28, 72, 0.08);
            color: var(--navy-900);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.4px;
            margin-bottom: 12px;
            border: 1px solid rgba(11, 28, 72, 0.12);
        }
        .hero-card h1 { font-size: 26px; font-weight: 800; margin: 0 0 10px; line-height: 1.2; letter-spacing: -0.3px; color: var(--slate-900); }
        .hero-card p { font-size: 11.5px; max-width: 640px; line-height: 1.75; margin: 0 0 18px; color: var(--slate-500); }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 10px; position: relative; z-index: 1; }

        /* Room info card — NAVY CLEAN */
        .room-info {
            background: rgba(11, 28, 72, 0.04);
            border: 1px solid rgba(11, 28, 72, 0.1);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }
        .room-info .rn { font-size: 12px; font-weight: 800; color: var(--navy-900); }
        .room-info small { font-size: 10px; color: var(--navy-700); }

        /* Alerts — NAVY CLEAN */
        .alert { border-radius: 14px; font-size: 11.5px; border: 1px solid; font-weight: 500; }
        .alert-success { background: rgba(5,150,105,0.08); border-color: rgba(5,150,105,0.2); color: #065f46; }
        .alert-warning { background: rgba(217,119,6,0.08); border-color: rgba(217,119,6,0.2); color: #92400e; }
        .alert-danger  { background: rgba(220,38,38,0.08); border-color: rgba(220,38,38,0.2); color: #991b1b; }
        .alert-info    { background: rgba(31,58,139,0.08); border-color: rgba(31,58,139,0.2); color: var(--navy-900); }
        .alert-secondary { background: var(--slate-100); border-color: var(--slate-200); color: var(--slate-700); }

        /* DataTables custom */
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter label {
            font-size: 10.5px;
            color: var(--slate-500);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 10.5px;
            padding: 5px 10px !important;
            border-radius: 9px !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 999px;
            border: 1px solid var(--slate-200);
            padding: 6px 14px;
            font-size: 11px;
            margin-left: 6px;
        }
        table.dataTable thead th.sorting::before,
        table.dataTable thead th.sorting::after { opacity: 0.25; }

        /* Mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.45);
            backdrop-filter: blur(2px);
            z-index: 1045;
        }
        .sidebar-backdrop.show { display: block; }

        @media (max-width: 991.98px) {
            .sidebar { margin-left: -255px; }
            .sidebar.show { margin-left: 0; }
            .main-content { margin-left: 0; }
            .topbar { padding: 10px 16px; }
            .page-content { padding: 16px; }
            .hero-card { padding: 24px; }
            .hero-card h1 { font-size: 20px; }
            .user-dropdown .ud-info { display: none; }
            .topbar-title { display: none; }
        }

        @media (max-width: 575.98px) {
            .stat-card { padding: 16px; }
            .stat-card .stat-value { font-size: 20px; }
            .checkbox-group { grid-template-columns: 1fr 1fr; }
            .hero-actions .btn { flex: 1; }
        }

        /* Scrollbar — NAVY CLEAN */
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: var(--slate-100); }
        ::-webkit-scrollbar-thumb { background: var(--slate-300); border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--navy-700); }

        /* ===========================================
           RESPONSIVE MEDIA QUERIES
           =========================================== */
        /* ─── Tablet (<=1024px) ─── */
        @media (max-width: 1024px) {
            .topbar { padding: 10px 18px; gap: 12px; }
            .sidebar { width: 258px; }
            .sidebar.collapsed { margin-left: -258px; }
            .main-content { margin-left: 258px; }
        }

        /* ─── Tablet / iPad (<=991px) : Sidebar auto-collapse, topbar compact ─── */
        @media (max-width: 991px) {
            .sidebar {
                margin-left: -268px;
                transform: translateX(-100%);
            }
            .sidebar.show-mobile {
                transform: translateX(0);
                margin-left: 0 !important;
            }
            .sidebar.collapsed {
                margin-left: -268px;
                transform: translateX(-100%);
            }
            .main-content,
            .main-content.expanded {
                margin-left: 0 !important;
            }
            .topbar { padding: 9px 16px; gap: 10px; }
            .topbar-title { font-size: 11.5px; }
        }

        /* ─── Mobile (<=768px) : Offcanvas sidebar compact ─── */
        @media (max-width: 768px) {
            .sidebar {
                width: 88%;
                max-width: 320px;
                border-radius: 0 18px 18px 0;
                transform: translateX(-100%);
                margin-left: 0 !important;
            }
            .sidebar.show-mobile {
                transform: translateX(0);
            }
            .sidebar.collapsed {
                transform: translateX(-100%);
                margin-left: 0 !important;
            }
            .brand-sidebar { padding: 16px 14px 14px; }
            .brand-sidebar h6 { font-size: 14px; }
            .sidebar-menu { padding: 10px 8px; }
            .sidebar-title { padding: 12px 12px 6px; font-size: 9px; }
            .menu-link { padding: 10px 12px; font-size: 11.5px; border-radius: 10px; }
            .menu-link:hover { transform: translateX(2px); }
            .menu-parent { padding: 10px 12px; font-size: 11.5px; border-radius: 10px; }
            .menu-parent:hover { transform: translateX(2px); }
            .submenu-list { margin-left: 24px; padding-left: 8px; }
            .submenu-list .menu-link { padding: 7px 11px 7px 13px; font-size: 10.5px; }
            .sidebar-footer { margin: 0 8px 10px; padding: 12px 14px 14px; border-radius: 12px; }
            .avatar { width: 36px; height: 36px; }
            .topbar { padding: 8px 12px; gap: 8px; }
            .btn-toggle-sidebar { width: 38px; height: 38px; border-radius: 11px; }
            .icon-btn { width: 38px; height: 38px; border-radius: 11px; }
            .topbar-right { gap: 6px; }
        }

        /* ─── Mini Mobile (<=576px) : Extra compact topbar, hide infos ─── */
        @media (max-width: 576px) {
            .sidebar { width: 86%; max-width: 300px; }
            .brand-sidebar { padding: 14px 12px 12px; }
            .topbar { padding: 8px 10px; gap: 6px; }
            .topbar-title { font-size: 10.5px; }
            .topbar-title strong { display: inline; }
            .user-dropdown {
                padding: 5px 9px 5px 5px;
                border-radius: 12px;
                gap: 8px;
            }
            .user-dropdown .d-sm-block {
                display: none !important;
            }
            .page-content { padding: 16px 12px 24px; }
            .hero-card { padding: 18px 16px; border-radius: 18px; }
            .card { border-radius: 16px; }
            .card-body { padding: 14px; }
            .btn { padding: 7px 12px; font-size: 11px; }
        }

        /* ─── Micro Mobile (<=420px) : Sidebar 92%, pad everything smaller ─── */
        @media (max-width: 420px) {
            .sidebar { width: 92%; max-width: none; border-radius: 0 16px 16px 0; }
            .brand-sidebar .bagian-tag { font-size: 8.5px; padding: 3px 9px; margin-top: 8px; }
            .menu-link { padding: 9px 11px; font-size: 11px; }
            .menu-link i { font-size: 14px; }
            .menu-parent { padding: 9px 11px; font-size: 11px; }
            .menu-parent i.icon-left { font-size: 14px; }
            .submenu-list { margin-left: 20px; padding-left: 6px; }
            .submenu-list .menu-link { padding: 6px 10px 6px 12px; font-size: 10.5px; }
            .icon-btn, .btn-toggle-sidebar { width: 36px; height: 36px; font-size: 15px; border-radius: 10px; }
            .avatar { width: 34px; height: 34px; font-size: 12px; }
        }

        /* ─── Print & Reduced Motion Accessibility ─── */
        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; animation: none !important; }
        }

        /* ════════════════════════════════════════════
           PREMIUM MODULE TABS (Pill Style) — NAVY CLEAN
           ════════════════════════════════════════════ */
        .tabs-modern {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            padding: 7px;
            gap: 6px;
            background: var(--slate-50);
            border: 1.5px solid var(--slate-200);
            border-radius: 16px;
        }
        .tab-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 17px;
            font-size: 11.2px;
            font-weight: 700;
            color: var(--slate-500);
            text-decoration: none;
            border-radius: 12px;
            border: 1.5px solid transparent;
            background: transparent;
            letter-spacing: 0.1px;
            transition: all .22s cubic-bezier(.2,.8,.2,1);
            position: relative;
            white-space: nowrap;
            line-height: 1.2;
            cursor: pointer;
        }
        .tab-modern i {
            font-size: 14px;
            color: var(--slate-500);
            transition: transform .22s ease, color .2s ease;
            flex-shrink: 0;
        }
        .tab-modern:hover {
            color: var(--navy-900);
            background: #ffffff;
            border-color: var(--slate-200);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px -4px rgba(15, 23, 42, 0.1);
        }
        .tab-modern:hover i { color: var(--navy-900); transform: scale(1.08); }
        .tab-modern.active {
            background: var(--navy-900);
            color: #ffffff;
            border-color: var(--navy-900);
            box-shadow: 0 4px 14px -5px rgba(11,28,72,0.5);
            transform: translateY(0);
        }
        .tab-modern.active::before { display: none; }
        .tab-modern.active i { color: #bfdbfe; transform: scale(1.05); }
        .tab-modern .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            font-size: 9.5px;
            font-weight: 800;
            border-radius: 999px;
            letter-spacing: 0.2px;
            min-width: 20px;
            background: rgba(11, 28, 72, 0.08);
            color: var(--navy-900);
            transition: all .2s ease;
        }
        .tab-modern:hover .badge {
            background: rgba(11, 28, 72, 0.12);
            color: var(--navy-800);
        }
        .tab-modern.active .badge {
            background: rgba(255,255,255,0.18);
            color: #ffffff;
            box-shadow: none;
        }
        @media (max-width: 576px) {
            .tabs-modern { padding: 5px; gap: 4px; border-radius: 14px; }
            .tab-modern { padding: 8px 12px; font-size: 10.5px; gap: 6px; border-radius: 10px; }
            .tab-modern i { font-size: 13px; }
        }

        /* ══════════════════════════════════════════════════════════════════
         * LAYOUT FORCE OVERRIDE — Specificity TERTINGGI (html+body + #ID + sibling ~)
         * Menggunakan ADJACENT SIBLING SELECTOR `#sidebar ~ .topbar/.main-content`
         * OTOMATIS sinkron margin dengan state sidebar (.collapsed / tidak)
         * Tidak perlu sinkron class .expanded via JS!
         * ══════════════════════════════════════════════════════════════════ */
        html body #sidebar.sidebar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 268px !important;
            height: 100vh !important;
            min-height: 100vh !important;
            margin-left: 0 !important;
            display: block !important;
            z-index: 1050 !important;
        }
        /* Hanya DEKSTOP >991px: Sidebar FIXED default SELALU TERBUKA → main+topbar ML=268px (tanpa ribet wrapper/sibling) */
        @media (min-width: 992px) {
            html body #sidebar.sidebar { margin-left: 0 !important; }
            html body .topbar,
            html body div.topbar,
            html body .main-content,
            html body div.main-content,
            html body main {
                margin-left: 268px !important;
                width: auto !important;
                max-width: none !important;
            }
            html body #sidebar.sidebar.collapsed { margin-left: -268px !important; }
            /* Jika sidebar collapsed → main/topbar ML=0 (toggle via JS aktifkan) */
            html body:has(#sidebar.sidebar.collapsed) .topbar,
            html body:has(#sidebar.sidebar.collapsed) .main-content {
                margin-left: 0 !important;
            }
        }
        html body #sidebar.sidebar.show,
        html body #sidebar.sidebar.show-mobile { margin-left: 0 !important; transform: translateX(0) !important; }

        html body .topbar,
        html body div.topbar {
            position: sticky !important;
            top: 0 !important;
            z-index: 100 !important;
            width: auto !important;
            max-width: none !important;
            transition: margin-left .32s cubic-bezier(.2,.8,.2,1);
        }
        html body .main-content,
        html body div.main-content {
            min-height: 100vh !important;
            width: auto !important;
            max-width: none !important;
            transition: margin-left .32s cubic-bezier(.2,.8,.2,1);
        }

        /* Mobile/Tab <992px: Sidebar offcanvas slide, topbar + main SELALU ML 0 */
        @media (max-width: 991px) {
            html body #sidebar.sidebar { transform: translateX(-100%) !important; }
            html body #sidebar.sidebar.show,
            html body #sidebar.sidebar.show-mobile { transform: translateX(0) !important; margin-left: 0 !important; }
            html body .topbar,
            html body .main-content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="sidebar <?= is_admin() ? 'admin' : '' ?>" id="sidebar">
    <div class="brand-sidebar">
        <div class="brand-row">
            <div style="min-width:0;flex:1;padding-left:2px">
                <h6><?= APP_NAME ?></h6>
                <div class="tagline"><?= defined('APP_INSTANSI_SHORT') ? APP_INSTANSI_SHORT : (defined('APP_INSTANSI') ? APP_INSTANSI : 'BPKP DIY') ?></div>
            </div>
        </div>
        <span class="bagian-tag <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
            <?= $user['role'] === 'admin' ? 'Administrator' : 'Pegawai' ?>
        </span>
    </div>

    <div class="sidebar-menu">
        <a href="<?= base_url('dashboard.php') ?>" class="menu-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
        </a>

        <?php
        $reservasi_children = [
            ['kendaraan', 'kendaraan', 'Kendaraan', 'bi-car-front-fill'],
            ['ruangan', 'ruangan', 'Ruangan', 'bi-door-open-fill'],
        ];
        $is_reservasi_active = false;
        foreach ($reservasi_children as $c) { if ($active_menu === $c[1]) { $is_reservasi_active = true; break; } }
        ?>
        <div class="menu-parent <?= $is_reservasi_active ? 'parent-active' : '' ?>"
             data-bs-toggle="collapse"
             data-bs-target="#submenuReservasi"
             aria-expanded="<?= $is_reservasi_active ? 'true' : 'false' ?>"
             aria-controls="submenuReservasi">
            <i class="bi bi-clipboard2-check-fill icon-left"></i>
            <span>Reservasi</span>
            <?php $cnt = ($count_mobil_pending ?? 0) + ($count_ruang_pending ?? 0); if ($cnt > 0): ?>
                <span class="badge rounded-pill" style="margin-left:8px;font-size:8.5px;padding:2.5px 7px;background:#f59e0b;color:#fff;box-shadow:0 2px 6px rgba(245,158,11,0.25);letter-spacing:0.2px"><?= $cnt ?></span>
            <?php endif; ?>
            <i class="bi bi-chevron-right chevron"></i>
        </div>
        <div class="collapse submenu-collapse <?= $is_reservasi_active ? 'show' : '' ?>" id="submenuReservasi">
            <ul class="submenu-list">
                <?php foreach ($reservasi_children as $c): ?>
                <li>
                    <a href="<?= base_url($c[0] . '/index.php') ?>" class="menu-link <?= $active_menu === $c[1] ? 'active' : '' ?>">
                        <i class="bi <?= $c[3] ?>"></i>
                        <span><?= $c[2] ?></span>
                        <?php if ($c[1] === 'kendaraan' && !empty($count_mobil_pending)): ?>
                            <span class="badge rounded-pill bg-warning text-dark ms-auto" style="font-size:8px;padding:1.5px 6px"><?= $count_mobil_pending ?></span>
                        <?php elseif ($c[1] === 'ruangan' && !empty($count_ruang_pending)): ?>
                            <span class="badge rounded-pill bg-warning text-dark ms-auto" style="font-size:8px;padding:1.5px 6px"><?= $count_ruang_pending ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="<?= base_url('kalender.php') ?>" class="menu-link <?= $active_menu === 'kalender' ? 'active' : '' ?>">
            <i class="bi bi-calendar4-week"></i>
            <span>Kalender</span>
        </a>

        <?php if (is_admin()): ?>
        <a href="<?= base_url('laporan.php') ?>" class="menu-link <?= $active_menu === 'laporan' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Laporan</span>
        </a>

        <a href="<?= base_url('pajak/index.php') ?>" class="menu-link <?= $active_menu === 'pajak' ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Perpajakan</span>
            <?php if ($count_pajak_alert > 0): ?>
                <span class="badge rounded-pill" style="background:#E5232B;color:#fff;font-size:9px;padding:2.5px 7px"><?= $count_pajak_alert ?></span>
            <?php endif; ?>
        </a>

        <div class="sidebar-divider"></div>

        <?php
        $data_master_children = [
            ['users', 'master-users', 'Pengguna', 'people-fill', 'bi-people-fill'],
            ['kendaraan', 'master-kendaraan', 'Kendaraan', 'truck-front-fill', 'bi-truck-front-fill'],
            ['driver', 'master-driver', 'Driver', 'person-rolodex', 'bi-person-rolodex'],
            ['ruangan', 'master-ruangan', 'Ruangan', 'building-fill', 'bi-building-fill'],
        ];
        $is_master_active = false;
        foreach ($data_master_children as $c) { if ($active_menu === $c[1]) { $is_master_active = true; break; } }
        ?>
        <div class="menu-parent <?= $is_master_active ? 'parent-active' : '' ?>"
             data-bs-toggle="collapse"
             data-bs-target="#submenuMaster"
             aria-expanded="<?= $is_master_active ? 'true' : 'false' ?>"
             aria-controls="submenuMaster">
            <i class="bi bi-database-fill-gear icon-left"></i>
            <span>Data Master</span>
            <i class="bi bi-chevron-right chevron"></i>
        </div>
        <div class="collapse submenu-collapse <?= $is_master_active ? 'show' : '' ?>" id="submenuMaster">
            <ul class="submenu-list">
                <?php foreach ($data_master_children as $c): ?>
                <li>
                    <a href="<?= base_url('master/' . $c[0] . '.php') ?>" class="menu-link <?= $active_menu === $c[1] ? 'active' : '' ?>">
                        <i class="bi <?= $c[4] ?>"></i>
                        <span><?= $c[2] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="<?= base_url('master/approvals.php') ?>" class="menu-link <?= $active_menu === 'approvals' ? 'active' : '' ?>">
            <i class="bi bi-check2-square"></i>
            <span>Approval</span>
            <?php if ($total_pending > 0): ?>
                <span class="badge bg-warning text-dark"><?= $total_pending ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </div>

</aside>

<!-- Main -->
<div class="main-content" id="mainContent">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn-toggle-sidebar" id="toggleSidebar" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title d-none d-lg-block">
            <i class="bi bi-house-door-fill me-2" style="color:#2563eb"></i>
            <strong>SISTEM INFORMASI BMN & SARANA</strong> — <?= APP_INSTANSI ?>
        </div>
        <div class="topbar-search d-none d-md-flex flex-1 mx-2" style="max-width:540px;flex:1 1 auto;min-width:0">
            <div class="search-box w-100">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input w-100" placeholder="Cari menu atau data..." id="globalSearch" autocomplete="off">
                <div id="searchResults" class="search-results"></div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="icon-btn d-none d-sm-inline-flex" title="Layar Penuh" onclick="toggleFullscreen()">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <div class="dropdown">
                <button class="icon-btn" title="Notifikasi" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?php if ($total_pending > 0): ?>
                        <span class="notif-badge"><?= $total_pending ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notif-dropdown" id="notifDropdown">
                    <div class="notif-head">
                        <strong><i class="bi bi-bell me-2" style="font-size:13px;opacity:0.9"></i>Notifikasi</strong>
                        <?php if ($total_pending > 0): ?>
                            <span class="total-pending"><?= $total_pending ?> menunggu</span>
                        <?php else: ?>
                            <span class="total-pending" style="opacity:0.8">0 menunggu</span>
                        <?php endif; ?>
                    </div>
                    <div class="notif-list">
                        <?php if (empty($notifications)): ?>
                            <div class="notif-empty">
                                <i class="bi bi-inbox"></i>
                                Belum ada notifikasi saat ini
                                <div style="font-size:9.5px;color:var(--slate-400);margin-top:6px;font-weight:500">Ketika ada pengajuan baru, akan muncul disini</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif):
                                $link_approve = is_admin()
                                    ? base_url('master/approvals.php?tipe=' . $notif['tipe'])
                                    : ($notif['tipe'] === 'kendaraan' ? base_url('kendaraan/index.php') : base_url('ruangan/index.php'));
                                $tujuan = !empty($notif['tujuan']) ? $notif['tujuan'] : 'Tanpa keterangan tujuan';
                                $tgl = date('d M Y', strtotime($notif['tanggal']));
                                $diff = time() - strtotime($notif['created_at']);
                                if ($diff < 60) $time_ago = 'Baru saja';
                                elseif ($diff < 3600) $time_ago = floor($diff/60) . ' menit lalu';
                                elseif ($diff < 86400) $time_ago = floor($diff/3600) . ' jam lalu';
                                else $time_ago = floor($diff/86400) . ' hari lalu';
                            ?>
                            <a class="dropdown-item notif-item" style="padding:11px 12px;border-radius:13px;margin-bottom:4px" href="<?= $link_approve ?>">
                                <div class="notif-icon <?= $notif['tipe'] ?>">
                                    <i class="bi <?= $notif['tipe'] === 'kendaraan' ? 'bi-car-front-fill' : 'bi-door-open-fill' ?>"></i>
                                </div>
                                <div class="notif-body">
                                    <div class="notif-title"><span class="badge-tipe">PENDING</span>
                                        <?= htmlspecialchars(substr($notif['peminjam'] ?? '-', 0, 22)) ?>
                                        mengajukan <?= $notif['tipe'] === 'kendaraan' ? 'kendaraan' : 'ruangan' ?>
                                    </div>
                                    <div class="notif-desc">
                                        <strong style="color:var(--slate-700)"><?= htmlspecialchars($notif['objek'] ?? ($notif['tipe'] === 'kendaraan' ? 'Kendaraan' : 'Ruangan')) ?></strong>
                                        • Tanggal <?= $tgl ?><br>
                                        Tujuan: <?= htmlspecialchars(substr($tujuan, 0, 60)) ?>
                                    </div>
                                    <div class="notif-time"><i class="bi bi-clock"></i><?= $time_ago ?></div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notif-foot">
                        <a href="<?= is_admin() ? base_url('master/approvals.php') : base_url('dashboard.php') ?>">
                            Lihat semua notifikasi <i class="bi bi-arrow-right-short"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php if (is_admin()): ?>
            <a href="<?= base_url('master/approvals.php') ?>" class="icon-btn d-none d-sm-inline-flex" title="Approval Pengajuan">
                <i class="bi bi-check2-all"></i>
                <?php if ($total_pending > 0): ?>
                    <span class="notif-badge"><?= $total_pending ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <div class="dropdown">
                <div class="user-dropdown" data-bs-toggle="dropdown">
                    <div class="avatar <?= $user['role'] === 'admin' ? 'admin' : '' ?>" style="box-shadow:0 3px 10px rgba(11,28,72,0.3)">
                        <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="ud-info d-sm-block">
                        <div class="ud-name"><?= $user['nama'] ?></div>
                        <div class="ud-role"><span style="background:rgba(59,95,199,0.14);color:#233E90;padding:1px 6px;border-radius:6px;font-weight:700"><?= strtoupper($user['role']) ?></span> • NIP <?= $user['nip'] ?></div>
                    </div>
                    <i class="bi bi-chevron-down" style="color:#94a3b8;font-size:11px"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end" style="border-radius:14px;border:none;box-shadow:0 10px 30px rgba(15,23,42,0.12);min-width:240px;padding:8px">
                    <li style="padding:10px 14px;border-bottom:1px solid #f1f5f9;margin-bottom:4px;background:linear-gradient(135deg,rgba(59,95,199,0.06),rgba(11,28,72,0.03));border-radius:10px 10px 0 0">
                        <div style="font-size:12px;font-weight:700;color:#0f172a"><?= $user['nama'] ?></div>
                        <div style="font-size:10px;color:#64748b;margin-top:2px">NIP. <?= $user['nip'] ?> • <?= APP_BAGIAN ?></div>
                        <div style="font-size:10px;color:#233E90;margin-top:2px;font-weight:600"><?= APP_INSTANSI ?></div>
                    </li>
                    <li><a class="dropdown-item" href="<?= base_url('profile.php') ?>" style="font-size:11px;padding:8px 14px;border-radius:9px;color:#1e293b;font-weight:600"><i class="bi bi-key-fill me-2" style="color:#2563eb"></i>Ubah Password / Edit Profil</a></li>
                    <li style="margin:4px 6px"><hr style="margin:0;border-color:#f1f5f9"></li>
                    <li><a class="dropdown-item text-danger" href="<?= base_url('logout.php') ?>" style="font-size:11px;padding:8px 14px;border-radius:9px"><i class="bi bi-box-arrow-right me-2"></i>Keluar / Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">
        <?php if (has_flash('success')): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= get_flash('success') ?></div>
        <?php endif; ?>
        <?php if (has_flash('error')): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= get_flash('error') ?></div>
        <?php endif; ?>
