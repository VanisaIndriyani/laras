<?php
$page_title = 'Dashboard';
$active_menu = 'dashboard';
require_once __DIR__ . '/partials/header.php';

$user = current_user();
$uid = $user['id'];
$isAdmin = is_admin();

$total_kendaraan = db()->count('kendaraan');
$kendaraan_tersedia = db()->count('kendaraan', "status = 'tersedia'");
$kendaraan_digunakan = db()->count('kendaraan', "status = 'digunakan'");

$total_ruangan = db()->count('ruangan');
$ruangan_tersedia = db()->count('ruangan', "status = 'tersedia'");

$total_reservasi_mobil = $isAdmin ? db()->count('reservasi_kendaraan') : db()->count('reservasi_kendaraan', 'user_id = ?', [$uid]);
$pending_mobil = db()->count('reservasi_kendaraan', $isAdmin ? "status = 'pending'" : "user_id = ? AND status = 'pending'", $isAdmin ? [] : [$uid]);
$disetujui_mobil = db()->count('reservasi_kendaraan', $isAdmin ? "status = 'disetujui'" : "user_id = ? AND status = 'disetujui'", $isAdmin ? [] : [$uid]);
$selesai_mobil = db()->count('reservasi_kendaraan', $isAdmin ? "status = 'selesai'" : "user_id = ? AND status = 'selesai'", $isAdmin ? [] : [$uid]);
$ditolak_mobil = db()->count('reservasi_kendaraan', $isAdmin ? "status = 'ditolak'" : "user_id = ? AND status = 'ditolak'", $isAdmin ? [] : [$uid]);

$total_reservasi_ruang = $isAdmin ? db()->count('reservasi_ruangan') : db()->count('reservasi_ruangan', 'user_id = ?', [$uid]);
$pending_ruang = db()->count('reservasi_ruangan', $isAdmin ? "status = 'pending'" : "user_id = ? AND status = 'pending'", $isAdmin ? [] : [$uid]);
$disetujui_ruang = db()->count('reservasi_ruangan', $isAdmin ? "status = 'disetujui'" : "user_id = ? AND status = 'disetujui'", $isAdmin ? [] : [$uid]);
$selesai_ruang = db()->count('reservasi_ruangan', $isAdmin ? "status = 'selesai'" : "user_id = ? AND status = 'selesai'", $isAdmin ? [] : [$uid]);
$ditolak_ruang = db()->count('reservasi_ruangan', $isAdmin ? "status = 'ditolak'" : "user_id = ? AND status = 'ditolak'", $isAdmin ? [] : [$uid]);

$total_disetujui = $disetujui_mobil + $disetujui_ruang;
$total_selesai = $selesai_mobil + $selesai_ruang;
$total_ditolak = $ditolak_mobil + $ditolak_ruang;

$total_pending = $pending_mobil + $pending_ruang;
$total_users = db()->count('users', "role = 'pegawai'");

$whereRecentMobil = $isAdmin ? '1=1' : 'rk.user_id = ?';
$paramsRecentMobil = $isAdmin ? [] : [$uid];
$recent_mobil = db()->fetchAll("SELECT rk.*, k.no_plat, k.merk, k.tipe, u.nama_lengkap as pemohon 
    FROM reservasi_kendaraan rk 
    LEFT JOIN kendaraan k ON rk.kendaraan_id = k.id 
    LEFT JOIN users u ON rk.user_id = u.id 
    WHERE {$whereRecentMobil}
    ORDER BY rk.created_at DESC LIMIT 5", $paramsRecentMobil);

$whereRecentRuang = $isAdmin ? '1=1' : 'rr.user_id = ?';
$paramsRecentRuang = $isAdmin ? [] : [$uid];
$recent_ruang = db()->fetchAll("SELECT rr.*, r.nama_ruangan, r.lantai, u.nama_lengkap as pemohon 
    FROM reservasi_ruangan rr 
    LEFT JOIN ruangan r ON rr.ruangan_id = r.id 
    LEFT JOIN users u ON rr.user_id = u.id 
    WHERE {$whereRecentRuang}
    ORDER BY rr.created_at DESC LIMIT 5", $paramsRecentRuang);
?>

<div class="page-header">
    <h4>Dashboard</h4>
    <nav class="breadcrumb">
        <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
        <span class="breadcrumb-item active">Dashboard</span>
    </nav>
</div>

<!-- ============= MODERN HERO WELCOME CARD ============= -->
<div class="hero-card-modern">
    <span class="hero-badge">
        <i class="bi bi-shield-check-fill"></i>
        SISTEM INFORMASI BMN & SARANA — <?= APP_INSTANSI ?>
    </span>

    <h1 class="hero-title">
        Selamat Datang di <strong>LARAS</strong>, <?= sanitize(explode(' ', $user['nama'])[0]) ?>!
    </h1>

    <p class="hero-desc">
        Layanan Administrasi Reservasi Aset dan Sarana resmi untuk memudahkan pengajuan peminjaman kendaraan dinas,
        koordinasi driver, serta pemesanan ruang rapat secara <b>transparan</b>, <b>terjadwal</b>, dan <b>akuntabel</b>.
    </p>

    <div class="hero-actions">
        <a href="<?= base_url('kendaraan/form.php') ?>" class="hero-btn primary">
            <i class="bi bi-car-front-fill"></i> Ajukan Reservasi Kendaraan
        </a>
        <a href="<?= base_url('ruangan/form.php') ?>" class="hero-btn ghost">
            <i class="bi bi-door-open-fill"></i> Ajukan Reservasi Ruangan
        </a>
        <?php if ($total_pending > 0): ?>
            <a href="<?= base_url($isAdmin ? 'master/approvals.php' : 'kendaraan/index.php') ?>" class="hero-btn ghost">
                <i class="bi bi-clock-history"></i> <?= $total_pending ?> Menunggu Approval
            </a>
        <?php endif; ?>
    </div>

    <!-- ===== SVG ILLUSTRASI (mobil + kalender + jam + city) ===== -->
    <svg class="hero-illustration" viewBox="0 0 340 220" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs>
            <linearGradient id="city1" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#ffffff" stop-opacity="0.18"/>
                <stop offset="100%" stop-color="#ffffff" stop-opacity="0.06"/>
            </linearGradient>
            <linearGradient id="carBody" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#ffffff"/>
                <stop offset="100%" stop-color="#dbeafe"/>
            </linearGradient>
            <linearGradient id="glass" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#60a5fa" stop-opacity="0.95"/>
                <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.85"/>
            </linearGradient>
            <linearGradient id="wheelBg" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#0f172a"/>
                <stop offset="100%" stop-color="#1e293b"/>
            </linearGradient>
        </defs>

        <!-- City silhouette -->
        <g fill="url(#city1)" opacity="0.9">
            <rect x="12" y="68" width="24" height="116" rx="2"/>
            <rect x="40" y="82" width="18" height="102" rx="2"/>
            <rect x="62" y="52" width="28" height="132" rx="2"/>
            <rect x="94" y="74" width="22" height="110" rx="2"/>
            <rect x="120" y="96" width="18" height="88" rx="2"/>
            <rect x="142" y="62" width="32" height="122" rx="2"/>
            <rect x="178" y="86" width="20" height="98" rx="2"/>
            <rect x="202" y="104" width="14" height="80" rx="2"/>
            <rect x="220" y="78" width="24" height="106" rx="2"/>
            <rect x="248" y="92" width="18" height="92" rx="2"/>
            <rect x="270" y="58" width="26" height="126" rx="2"/>
            <rect x="300" y="78" width="22" height="106" rx="2"/>
        </g>
        <g fill="#ffffff" opacity="0.2">
            <rect x="17" y="80" width="3" height="3"/>
            <rect x="24" y="80" width="3" height="3"/>
            <rect x="17" y="90" width="3" height="3"/>
            <rect x="24" y="90" width="3" height="3"/>
            <rect x="68" y="64" width="4" height="4"/>
            <rect x="78" y="64" width="4" height="4"/>
            <rect x="68" y="76" width="4" height="4"/>
            <rect x="78" y="76" width="4" height="4"/>
            <rect x="68" y="88" width="4" height="4"/>
            <rect x="78" y="88" width="4" height="4"/>
            <rect x="148" y="74" width="4" height="4"/>
            <rect x="160" y="74" width="4" height="4"/>
            <rect x="148" y="88" width="4" height="4"/>
            <rect x="160" y="88" width="4" height="4"/>
            <rect x="276" y="70" width="4" height="4"/>
            <rect x="286" y="70" width="4" height="4"/>
            <rect x="276" y="82" width="4" height="4"/>
            <rect x="286" y="82" width="4" height="4"/>
        </g>

        <!-- Cloud 1 -->
        <g fill="#ffffff" opacity="0.85">
            <ellipse cx="50" cy="28" rx="24" ry="8"/>
            <ellipse cx="62" cy="22" rx="16" ry="7"/>
            <ellipse cx="38" cy="25" rx="13" ry="6"/>
        </g>
        <!-- Cloud 2 -->
        <g fill="#ffffff" opacity="0.75">
            <ellipse cx="300" cy="40" rx="22" ry="7"/>
            <ellipse cx="312" cy="34" rx="14" ry="6"/>
            <ellipse cx="288" cy="37" rx="12" ry="5"/>
        </g>

        <!-- ===== KALENDER ===== -->
        <g transform="translate(244, 22)">
            <rect x="0" y="8" width="78" height="78" rx="8" fill="#ffffff"/>
            <rect x="0" y="0" width="78" height="18" rx="6" fill="#1e3a8a"/>
            <rect x="12" y="-2" width="4" height="8" rx="2" fill="#ef4444"/>
            <rect x="34" y="-2" width="4" height="8" rx="2" fill="#ef4444"/>
            <rect x="56" y="-2" width="4" height="8" rx="2" fill="#ef4444"/>
            <rect x="0" y="12" width="78" height="4" fill="rgba(239,68,68,0.2)"/>
            <g fill="#cbd5e1">
                <rect x="8" y="28" width="8" height="8" rx="2"/>
                <rect x="20" y="28" width="8" height="8" rx="2"/>
                <rect x="32" y="28" width="8" height="8" rx="2"/>
                <rect x="44" y="28" width="8" height="8" rx="2"/>
                <rect x="56" y="28" width="8" height="8" rx="2"/>
                <rect x="68" y="28" width="4" height="8" rx="2"/>
            </g>
            <g fill="#e2e8f0">
                <rect x="8" y="42" width="8" height="8" rx="2"/>
                <rect x="20" y="42" width="8" height="8" rx="2"/>
                <rect x="32" y="42" width="8" height="8" rx="2"/>
                <rect x="44" y="42" width="8" height="8" rx="2"/>
                <rect x="56" y="42" width="8" height="8" rx="2"/>
                <rect x="68" y="42" width="4" height="8" rx="2"/>
            </g>
            <g fill="#e2e8f0">
                <rect x="8" y="56" width="8" height="8" rx="2"/>
                <rect x="20" y="56" width="8" height="8" rx="2"/>
            </g>
            <g fill="#1e3a8a">
                <rect x="32" y="56" width="8" height="8" rx="2"/>
            </g>
            <g fill="#e2e8f0">
                <rect x="44" y="56" width="8" height="8" rx="2"/>
                <rect x="56" y="56" width="8" height="8" rx="2"/>
                <rect x="68" y="56" width="4" height="8" rx="2"/>
            </g>
            <g fill="#1e3a8a">
                <rect x="8" y="70" width="8" height="8" rx="2"/>
                <rect x="20" y="70" width="8" height="8" rx="2"/>
            </g>
            <circle cx="60" cy="34" r="4.5" fill="#ef4444"/>
            <circle cx="60" cy="34" r="2.2" fill="#ffffff"/>
        </g>

        <!-- ===== JAM ===== -->
        <g transform="translate(292, 118)">
            <circle cx="26" cy="26" r="26" fill="#ffffff"/>
            <circle cx="26" cy="26" r="22" fill="none" stroke="#1e3a8a" stroke-width="3"/>
            <circle cx="26" cy="26" r="2.5" fill="#1e3a8a"/>
            <line x1="26" y1="26" x2="26" y2="10" stroke="#1e3a8a" stroke-width="2.5" stroke-linecap="round"/>
            <line x1="26" y1="26" x2="37" y2="28" stroke="#1e3a8a" stroke-width="2.2" stroke-linecap="round"/>
            <g fill="#1e3a8a">
                <rect x="24.5" y="6" width="3" height="4" rx="1"/>
                <rect x="46" y="24.5" width="4" height="3" rx="1"/>
                <rect x="24.5" y="42" width="3" height="4" rx="1"/>
                <rect x="2" y="24.5" width="4" height="3" rx="1"/>
            </g>
            <circle cx="16" cy="16" r="2" fill="#3b82f6"/>
            <circle cx="40" cy="48" r="1.6" fill="#3b82f6"/>
        </g>

        <!-- ===== MOBIL (main element) ===== -->
        <g transform="translate(14, 110)">
            <!-- Shadow mobil -->
            <ellipse cx="132" cy="86" rx="112" ry="7" fill="#000000" opacity="0.18"/>
            <!-- Body bawah -->
            <rect x="6" y="44" width="252" height="32" rx="10" fill="url(#carBody)"/>
            <!-- Body atas (atap) -->
            <path d="M 52 44 L 74 12 Q 82 4 92 4 L 174 4 Q 186 4 194 14 L 218 44 Z" fill="url(#carBody)"/>
            <!-- Kaca depan kiri -->
            <path d="M 80 44 L 96 18 L 130 18 L 130 44 Z" fill="url(#glass)"/>
            <line x1="130" y1="18" x2="130" y2="44" stroke="rgba(255,255,255,0.6)" stroke-width="1.2"/>
            <!-- Kaca belakang kanan -->
            <path d="M 138 44 L 138 18 L 172 18 L 190 44 Z" fill="url(#glass)"/>
            <!-- Body outline -->
            <path d="M 6 70 L 6 56 Q 6 46 16 44 L 52 44 L 74 12 Q 82 4 92 4 L 174 4 Q 186 4 194 14 L 218 44 L 248 44 Q 258 46 258 56 L 258 70"
                  fill="none" stroke="#93c5fd" stroke-width="1.5" opacity="0.7"/>
            <!-- Door line -->
            <line x1="132" y1="44" x2="138" y2="76" stroke="rgba(59,130,246,0.5)" stroke-width="1.4"/>
            <!-- Door handle kiri -->
            <rect x="88" y="56" width="14" height="3" rx="1.5" fill="#93c5fd"/>
            <!-- Door handle kanan -->
            <rect x="170" y="56" width="14" height="3" rx="1.5" fill="#93c5fd"/>
            <!-- Grill -->
            <rect x="10" y="54" width="14" height="14" rx="3" fill="#1e3a8a" opacity="0.85"/>
            <line x1="12" y1="58" x2="22" y2="58" stroke="rgba(255,255,255,0.35)" stroke-width="1"/>
            <line x1="12" y1="62" x2="22" y2="62" stroke="rgba(255,255,255,0.35)" stroke-width="1"/>
            <!-- Headlight kiri -->
            <ellipse cx="22" cy="52" rx="5" ry="3.5" fill="#fef3c7"/>
            <ellipse cx="22" cy="52" rx="3" ry="2" fill="#ffffff"/>
            <!-- Taillight kanan -->
            <rect x="240" y="50" width="14" height="10" rx="3" fill="#ef4444"/>
            <!-- Logo emblem tengah -->
            <circle cx="132" cy="50" r="5" fill="#1e3a8a"/>
            <path d="M 128.5 50 L 130.5 52 L 136 47.5" stroke="none" fill="none" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
            <!-- Roda kiri -->
            <g>
                <circle cx="60" cy="76" r="16" fill="url(#wheelBg)"/>
                <circle cx="60" cy="76" r="11" fill="#334155"/>
                <circle cx="60" cy="76" r="4" fill="#94a3b8"/>
                <g stroke="#64748b" stroke-width="1.6" stroke-linecap="round">
                    <line x1="60" y1="66" x2="60" y2="70"/>
                    <line x1="60" y1="82" x2="60" y2="86"/>
                    <line x1="50" y1="76" x2="54" y2="76"/>
                    <line x1="66" y1="76" x2="70" y2="76"/>
                    <line x1="53" y1="69" x2="55.5" y2="71.5"/>
                    <line x1="64.5" y1="80.5" x2="67" y2="83"/>
                    <line x1="53" y1="83" x2="55.5" y2="80.5"/>
                    <line x1="64.5" y1="71.5" x2="67" y2="69"/>
                </g>
            </g>
            <!-- Roda kanan -->
            <g>
                <circle cx="204" cy="76" r="16" fill="url(#wheelBg)"/>
                <circle cx="204" cy="76" r="11" fill="#334155"/>
                <circle cx="204" cy="76" r="4" fill="#94a3b8"/>
                <g stroke="#64748b" stroke-width="1.6" stroke-linecap="round">
                    <line x1="204" y1="66" x2="204" y2="70"/>
                    <line x1="204" y1="82" x2="204" y2="86"/>
                    <line x1="194" y1="76" x2="198" y2="76"/>
                    <line x1="210" y1="76" x2="214" y2="76"/>
                    <line x1="197" y1="69" x2="199.5" y2="71.5"/>
                    <line x1="208.5" y1="80.5" x2="211" y2="83"/>
                    <line x1="197" y1="83" x2="199.5" y2="80.5"/>
                    <line x1="208.5" y1="71.5" x2="211" y2="69"/>
                </g>
            </g>
            <!-- Red badge di pintu -->
            <circle cx="214" cy="40" r="5" fill="#ef4444"/>
            <path d="M 211.5 40 L 213 41.5 L 216.5 38" fill="none" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
        </g>
    </svg>
</div>

<!-- ============= 4 MODERN STAT CARDS ============= -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="stat-card-modern blue">
            <i class="bi bi-car-front-fill stat-icon-bg"></i>
            <div class="sq-icon"><i class="bi bi-car-front-fill"></i></div>
            <div class="stat-title">Total Armada</div>
            <div class="stat-number"><?= $total_kendaraan ?></div>
            <div class="stat-foot">Unit kendaraan terdaftar</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="stat-card-modern green">
            <i class="bi bi-check-circle-fill stat-icon-bg"></i>
            <div class="sq-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-title">Siap Digunakan</div>
            <div class="stat-number"><?= $kendaraan_tersedia ?></div>
            <div class="stat-foot">Kendaraan tersedia</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="stat-card-modern purple">
            <i class="bi bi-building-fill stat-icon-bg"></i>
            <div class="sq-icon"><i class="bi bi-door-open-fill"></i></div>
            <div class="stat-title">Total Ruangan</div>
            <div class="stat-number"><?= $total_ruangan ?></div>
            <div class="stat-foot">Ruangan tersedia: <?= $ruangan_tersedia ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="stat-card-modern amber">
            <i class="bi bi-hourglass-split stat-icon-bg"></i>
            <div class="sq-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-title">Menunggu Approval</div>
            <div class="stat-number"><?= $total_pending ?></div>
            <div class="stat-foot">Pengajuan menunggu • Mobil <?= $pending_mobil ?> • Ruang <?= $pending_ruang ?></div>
        </div>
    </div>
</div>

<!-- ============= RINGKASAN DATA APPROVED / AKTIVITAS ============= -->
<div class="section-title"><?= $isAdmin ? 'Ringkasan Keseluruhan' : 'Ringkasan Aktivitas Saya' ?></div>
<div class="row g-3 mb-4">
    <!-- Card 1: Mobil Disetujui -->
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 h-100" style="border-radius:18px;overflow:hidden;position:relative;background:#fff;border:1.5px solid #e2e8f0;box-shadow:0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15);transition:all .2s ease" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 18px 42px -18px rgba(11,28,72,0.25)'" onmouseout="this.style.transform='';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15)'">
            <div style="position:absolute;top:0;left:0;bottom:0;width:4px;background:linear-gradient(180deg,#2563eb,#0B1C48)"></div>
            <div class="px-4 py-3.5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#3B5FC7,#0B1C48);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(11,28,72,0.22);flex-shrink:0">
                        <i class="bi bi-car-front-fill" style="font-size:15px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:10px;font-weight:800;color:#1e40af;letter-spacing:0.5px;text-transform:uppercase">Mobil Disetujui</div>
                        <div style="font-size:22px;font-weight:900;color:#0B1C48;letter-spacing:-0.4px;line-height:1.1;margin-top:1px"><?= $disetujui_mobil ?></div>
                    </div>
                </div>
                <div style="font-size:9.5px;color:#64748b;line-height:1.45">
                    Reservasi kendaraan <strong style="color:#0B1C48">sudah disetujui</strong> admin • <span style="font-weight:700;color:#1e40af"><?= $selesai_mobil ?></span> selesai
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Ruangan Disetujui -->
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 h-100" style="border-radius:18px;overflow:hidden;position:relative;background:#fff;border:1.5px solid #e2e8f0;box-shadow:0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15);transition:all .2s ease" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 18px 42px -18px rgba(11,28,72,0.25)'" onmouseout="this.style.transform='';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15)'">
            <div style="position:absolute;top:0;left:0;bottom:0;width:4px;background:linear-gradient(180deg,#7c3aed,#0B1C48)"></div>
            <div class="px-4 py-3.5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(124,58,237,0.25);flex-shrink:0">
                        <i class="bi bi-door-open-fill" style="font-size:15px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:10px;font-weight:800;color:#6d28d9;letter-spacing:0.5px;text-transform:uppercase">Ruangan Disetujui</div>
                        <div style="font-size:22px;font-weight:900;color:#0B1C48;letter-spacing:-0.4px;line-height:1.1;margin-top:1px"><?= $disetujui_ruang ?></div>
                    </div>
                </div>
                <div style="font-size:9.5px;color:#64748b;line-height:1.45">
                    Reservasi ruangan <strong style="color:#0B1C48">sudah disetujui</strong> admin • <span style="font-weight:700;color:#6d28d9"><?= $selesai_ruang ?></span> selesai
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Disetujui (Semua) -->
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 h-100" style="border-radius:18px;overflow:hidden;position:relative;background:#fff;border:1.5px solid #e2e8f0;box-shadow:0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15);transition:all .2s ease" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 18px 42px -18px rgba(11,28,72,0.25)'" onmouseout="this.style.transform='';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15)'">
            <div style="position:absolute;top:0;left:0;bottom:0;width:4px;background:linear-gradient(180deg,#10b981,#059669)"></div>
            <div class="px-4 py-3.5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,0.25);flex-shrink:0">
                        <i class="bi bi-check2-circle" style="font-size:15px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:10px;font-weight:800;color:#059669;letter-spacing:0.5px;text-transform:uppercase">Total Disetujui</div>
                        <div style="font-size:22px;font-weight:900;color:#0B1C48;letter-spacing:-0.4px;line-height:1.1;margin-top:1px"><?= $total_disetujui ?></div>
                    </div>
                </div>
                <div style="font-size:9.5px;color:#64748b;line-height:1.45">
                    Mobil + Ruangan yang <strong style="color:#0B1C48">sudah diapprove</strong> • <span style="font-weight:700;color:#059669"><?= $total_selesai ?></span> telah selesai
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Riwayat (Selesai + Ditolak) -->
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="card border-0 h-100" style="border-radius:18px;overflow:hidden;position:relative;background:#fff;border:1.5px solid #e2e8f0;box-shadow:0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15);transition:all .2s ease" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 18px 42px -18px rgba(11,28,72,0.25)'" onmouseout="this.style.transform='';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.9) inset,0 10px 34px -16px rgba(11,28,72,0.15)'">
            <div style="position:absolute;top:0;left:0;bottom:0;width:4px;background:linear-gradient(180deg,#f59e0b,#c2410c)"></div>
            <div class="px-4 py-3.5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#c2410c);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(245,158,11,0.28);flex-shrink:0">
                        <i class="bi bi-clock-history" style="font-size:15px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:10px;font-weight:800;color:#c2410c;letter-spacing:0.5px;text-transform:uppercase">Riwayat Status</div>
                        <div style="font-size:22px;font-weight:900;color:#0B1C48;letter-spacing:-0.4px;line-height:1.1;margin-top:1px"><?= $total_selesai + $total_ditolak ?></div>
                    </div>
                </div>
                <div style="font-size:9.5px;color:#64748b;line-height:1.45">
                    <span style="font-weight:700;color:#1d4ed8"><?= $total_selesai ?> selesai</span> • <span style="font-weight:700;color:#b91c1c"><?= $total_ditolak ?> ditolak</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============= MODUL CEPAT SECTION ============= -->
<div class="section-title">Modul Cepat</div>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="modul-card blue">
            <div class="modul-icon"><i class="bi bi-calendar2-check-fill"></i></div>
            <div class="modul-name">Reservasi Kendaraan</div>
            <div class="modul-desc">Ajukan peminjaman kendaraan dinas dengan mudah dan cepat.</div>
            <a href="<?= base_url('kendaraan/form.php') ?>" class="modul-link">
                Buat Pengajuan <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="modul-card green">
            <div class="modul-icon"><i class="bi bi-buildings-fill"></i></div>
            <div class="modul-name">Reservasi Ruangan</div>
            <div class="modul-desc">Pesan ruang rapat sesuai jadwal yang Anda butuhkan.</div>
            <a href="<?= base_url('ruangan/form.php') ?>" class="modul-link">
                Buat Pengajuan <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="modul-card purple">
            <div class="modul-icon"><i class="bi bi-bar-chart-fill"></i></div>
            <div class="modul-name">Rekapitulasi & Laporan</div>
            <div class="modul-desc">Lihat ringkasan dan laporan peminjaman aset & sarana.</div>
            <a href="<?= base_url('laporan.php') ?>" class="modul-link">
                Lihat Laporan <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-6">
        <div class="modul-card amber">
            <div class="modul-icon"><i class="bi bi-people-fill"></i></div>
            <div class="modul-name">Approval Pengajuan</div>
            <div class="modul-desc">Review dan setujui pengajuan dari pengguna.</div>
            <a href="<?= base_url($isAdmin ? 'master/approvals.php' : 'kendaraan/index.php') ?>" class="modul-link">
                Lihat Pengajuan <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- ============= RECENT TRANSACTIONS TABLES ============= -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title"><i class="bi bi-car-front-fill me-2" style="color:#2563eb"></i>Reservasi Kendaraan Terbaru</h6>
                <a href="<?= base_url('kendaraan/index.php') ?>" class="btn btn-sm btn-secondary">Lihat Semua <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_mobil)): ?>
                    <div class="text-center py-5 text-muted" style="font-size:11px">
                        <i class="bi bi-inbox display-5 d-block mb-2" style="opacity:0.3"></i>
                        Belum ada reservasi kendaraan.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Pemohon</th>
                                    <th>Kendaraan</th>
                                    <th>Tgl. Pinjam</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_mobil as $m): ?>
                                <tr>
                                    <td style="font-size:10px;font-weight:700;color:#1e40af"><?= $m['kode_reservasi'] ?></td>
                                    <td>
                                        <div style="font-weight:600;font-size:11px"><?= $m['pemohon'] ?></div>
                                        <div style="font-size:9.5px;color:#64748b"><?= $m['tujuan'] ?></div>
                                    </td>
                                    <td style="font-size:10.5px;font-weight:600"><?= $m['no_plat'] ?><br><span style="font-weight:500;color:#64748b"><?= $m['merk'] ?> <?= $m['tipe'] ?></span></td>
                                    <td style="font-size:10px;color:#475569"><?= format_date($m['tanggal_pinjam'], false) ?><br><?= format_time($m['jam_mulai']) ?></td>
                                    <td><?= status_badge($m['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title"><i class="bi bi-door-open-fill me-2" style="color:#7c3aed"></i>Reservasi Ruangan Terbaru</h6>
                <a href="<?= base_url('ruangan/index.php') ?>" class="btn btn-sm btn-secondary">Lihat Semua <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_ruang)): ?>
                    <div class="text-center py-5 text-muted" style="font-size:11px">
                        <i class="bi bi-inbox display-5 d-block mb-2" style="opacity:0.3"></i>
                        Belum ada reservasi ruangan.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Pemohon</th>
                                    <th>Ruangan</th>
                                    <th>Acara</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_ruang as $r): ?>
                                <tr>
                                    <td style="font-size:10px;font-weight:700;color:#6d28d9"><?= $r['kode_reservasi'] ?></td>
                                    <td>
                                        <div style="font-weight:600;font-size:11px"><?= $r['pemohon'] ?></div>
                                        <div style="font-size:9.5px;color:#64748b"><?= $r['unit_kerja'] ?></div>
                                    </td>
                                    <td style="font-size:10.5px;font-weight:600"><?= $r['nama_ruangan'] ?><br><span style="font-weight:500;color:#64748b"><?= $r['lantai'] ?></span></td>
                                    <td style="font-size:10.5px"><?= $r['nama_acara'] ?></td>
                                    <td><?= status_badge($r['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/partials/footer.php' ?>
