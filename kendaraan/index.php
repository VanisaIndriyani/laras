<?php
$page_title = 'Reservasi Kendaraan';
$active_menu = 'kendaraan';
require_once __DIR__ . '/../partials/header.php';

$user = current_user();
$isAdmin = is_admin();

$tab = $_GET['tab'] ?? 'reservasi';
$valid_tabs = ['reservasi','sarana','jadwal','riwayat'];
if (!in_array($tab, $valid_tabs)) $tab = 'reservasi';

$status = $_GET['status'] ?? 'all';
$search = sanitize($_GET['search'] ?? '');
$filter_kendaraan = $_GET['kendaraan'] ?? 'all';

$kendaraan_list = [];
$migration_cols = [
    'kode_bmn'                  => "ALTER TABLE kendaraan ADD COLUMN kode_bmn VARCHAR(60) DEFAULT NULL",
    'unit_pengguna'             => "ALTER TABLE kendaraan ADD COLUMN unit_pengguna VARCHAR(120) DEFAULT NULL",
    'pajak_stnk_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_stnk_jatuh_tempo DATE DEFAULT NULL",
    'pajak_tnkb_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_tnkb_jatuh_tempo DATE DEFAULT NULL",
    'terakhir_service'          => "ALTER TABLE kendaraan ADD COLUMN terakhir_service DATE DEFAULT NULL",
    'service_berikutnya'        => "ALTER TABLE kendaraan ADD COLUMN service_berikutnya DATE DEFAULT NULL",
    'catatan_service'           => "ALTER TABLE kendaraan ADD COLUMN catatan_service TEXT DEFAULT NULL",
];
try {
    $db_name = DB_NAME;
    $existing = db()->fetchAll("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'kendaraan'", [$db_name]);
    $existing_cols = array_column($existing, 'COLUMN_NAME');
    foreach ($migration_cols as $col => $sql) {
        if (!in_array($col, $existing_cols, true)) {
            @db()->exec($sql);
        }
    }
} catch (Exception $ex) {}
try {
    $kendaraan_list = db()->fetchAll("SELECT * FROM kendaraan ORDER BY no_plat ASC");
} catch (PDOException $e) {
    $kendaraan_list = [];
}

$kode_urut = [];
$kelompok_by_unit = [];
foreach ($kendaraan_list as $idx => &$kk) {
    $uk = trim($kk['unit_pengguna'] ?? 'DEFAULT');
    if (!isset($kelompok_by_unit[$uk])) $kelompok_by_unit[$uk] = 0;
    $kelompok_by_unit[$uk]++;
    $kode_unit = array_search($uk, array_keys($kelompok_by_unit)) + 1;
    if (preg_match('/^([A-Z]{1,2})/', trim($kk['no_plat']), $m)) {
        $plat_prefix = str_pad($kode_unit, 2, '0', STR_PAD_LEFT);
    } else {
        $plat_prefix = str_pad($kode_unit, 2, '0', STR_PAD_LEFT);
    }
    $urut_per_unit = str_pad($kelompok_by_unit[$uk], 3, '0', STR_PAD_LEFT);
    $kk['_kode_sarana'] = 'MOB.' . $plat_prefix . '.' . $urut_per_unit;
}
unset($kk);

$jml = ['all'=>0,'active'=>0,'riwayat'=>0,'pending'=>0,'disetujui'=>0,'selesai'=>0,'ditolak'=>0];
$reservasi = [];

if (in_array($tab, ['reservasi','riwayat'], true)) {
    $active_st = "rk.status IN ('pending','disetujui')";
    $hist_st   = "rk.status IN ('selesai','ditolak')";

    $where_list = ['1=1'];
    $params = [];

    // Pegawai HANYA melihat data reservasi MILIK DIRINYA SENDIRI
    if (!$isAdmin) {
        $where_list[] = 'rk.user_id = ?';
        $params[] = (int)$user['id'];
    }

    if ($tab === 'reservasi' && $status !== 'all') {
        $where_list[] = 'rk.status = ?'; $params[] = $status;
    } elseif ($tab === 'reservasi') {
        $where_list[] = $active_st;
    } elseif ($tab === 'riwayat') {
        $where_list[] = $hist_st;
    }

    if ($filter_kendaraan !== 'all') { $where_list[] = 'rk.kendaraan_id = ?'; $params[] = (int)$filter_kendaraan; }
    if ($search) { $where_list[] = "(rk.kode_reservasi LIKE ? OR k.no_plat LIKE ? OR u.nama_lengkap LIKE ? OR u.nip LIKE ? OR rk.tujuan LIKE ? OR rk.keperluan LIKE ?)"; $s = "%{$search}%"; array_push($params, $s, $s, $s, $s, $s, $s); }

    $where_sql = implode(' AND ', $where_list);
    $sql = "SELECT rk.*, k.no_plat, k.merk, k.tipe, k.kapasitas, k.driver, k.no_hp_driver, k.status as status_kendaraan,
            u.nama_lengkap as pemohon, u.nip, u.unit_kerja, u.no_hp as hp_pemohon
            FROM reservasi_kendaraan rk
            LEFT JOIN kendaraan k ON rk.kendaraan_id = k.id
            LEFT JOIN users u ON rk.user_id = u.id
            WHERE {$where_sql}
            ORDER BY rk.created_at DESC";
    $reservasi = db()->fetchAll($sql, $params);

    // Filter scope count: SEMUA query count juga pakai filter user_id jika pegawai
    $scope_where = $isAdmin ? '1=1' : 'reservasi_kendaraan.user_id = ' . (int)$user['id'];
    $scope_where_rk = $isAdmin ? '1=1' : 'rk.user_id = ' . (int)$user['id'];
    $jml['all']       = db()->count('reservasi_kendaraan', $scope_where);
    $jml['active']    = db()->count('reservasi_kendaraan rk', $scope_where_rk . " AND {$active_st}");
    $jml['riwayat']   = db()->count('reservasi_kendaraan rk', $scope_where_rk . " AND {$hist_st}");
    $jml['pending']   = db()->count('reservasi_kendaraan rk', $scope_where_rk . " AND status = 'pending'");
    $jml['disetujui'] = db()->count('reservasi_kendaraan rk', $scope_where_rk . " AND status = 'disetujui'");
    $jml['selesai']   = db()->count('reservasi_kendaraan rk', $scope_where_rk . " AND status = 'selesai'");
    $jml['ditolak']   = db()->count('reservasi_kendaraan rk', $scope_where_rk . " AND status = 'ditolak'");
}

function detect_status_operasional($r) {
    if ($r['status'] === 'pending')   return ['key'=>'pending','label'=>'Menunggu Approval','cls'=>'warning','icon'=>'bi-clock-history'];
    if ($r['status'] === 'ditolak')   return ['key'=>'ditolak','label'=>'Ditolak','cls'=>'danger','icon'=>'bi-x-circle-fill'];
    if ($r['status'] === 'selesai')   return ['key'=>'selesai','label'=>'Sudah Dikembalikan','cls'=>'success','icon'=>'bi-check-circle-fill'];
    $today = date('Y-m-d H:i:s');
    $start = ($r['tanggal_pinjam'] ?? '').' '.($r['jam_mulai'] ?? '00:00:00');
    $end   = ($r['tanggal_kembali'] ?? '').' '.($r['jam_selesai'] ?? '23:59:59');
    if ($today >= $start && $today <= $end) return ['key'=>'digunakan','label'=>'Sedang Digunakan','cls'=>'primary','icon'=>'bi-car-front-fill'];
    if ($today > $end)                          return ['key'=>'lewat','label'=>'Belum Kembali (Overdue)','cls'=>'danger','icon'=>'bi-exclamation-triangle-fill'];
    return ['key'=>'akan','label'=>'Akan Digunakan','cls'=>'info','icon'=>'bi-calendar-check-fill'];
}
?>
<style>
    .ken-page-header {
        position: relative;
        overflow: hidden;
    }
    .ken-page-header::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, #3B5FC7 0%, #0B1C48 50%, #1F3A8B 100%);
    }
    .ken-page-header::after {
        content: "";
        position: absolute;
        top: -40px; right: -30px;
        width: 260px; height: 260px;
        background: radial-gradient(circle, rgba(59,95,199,0.12) 0%, transparent 68%);
        pointer-events: none;
    }
    .ken-tabs-modern {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 0 20px 18px 20px;
    }
    .tab-modern {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 18px;
        border-radius: 12px;
        text-decoration: none;
        color: #475569;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: 0.2px;
        transition: all .18s ease;
    }
    .tab-modern:hover {
        border-color: #bfdbfe;
        color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px -10px rgba(11,28,72,0.2);
    }
    .tab-modern.active {
        background: linear-gradient(135deg, #3B5FC7 0%, #0B1C48 100%);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 16px -6px rgba(11,28,72,0.3);
    }
    .tab-modern.active .badge {
        background: rgba(255,255,255,0.22) !important;
        color: #fff !important;
    }
    .reservasi-row-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #3B5FC7, #0B1C48);
        border-radius: 16px 0 0 16px;
    }
    .ken-filter-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, #2563eb, #0B1C48, #1F3A8B);
    }
    .ken-sarana-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, #7c3aed, #0B1C48, #2563eb);
    }
    .ken-jadwal-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, #f59e0b, #7c3aed, #0B1C48);
    }
    .ken-sarana-hero::after {
        content: "";
        position: absolute;
        right: -50px; bottom: -50px;
        width: 220px; height: 220px;
        background: radial-gradient(circle, rgba(124,58,237,0.12) 0%, transparent 70%);
        pointer-events: none;
    }
</style>
<div class="page-container">
    <!-- ===== PREMIUM PAGE HEADER + TAB CONTAINER ===== -->
    <div class="mb-4 ken-page-header" style="border-radius:20px;background:linear-gradient(135deg,#ffffff 0%,#f4f7ff 48%,#eef3ff 100%);border:1.5px solid #e3ebfb;overflow:hidden;box-shadow:0 1px 0 rgba(255,255,255,0.9) inset,0 10px 36px -14px rgba(11,28,72,0.18)">
        <div class="page-header d-flex flex-wrap align-items-start justify-content-between" style="gap:14px;padding:24px 28px 18px 28px">
            <div style="flex:1;min-width:0">
                <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider:'›'">
                    <ol class="breadcrumb mb-3" style="font-size:10.5px;margin:0">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard.php') ?>" class="text-decoration-none" style="color:#64748b;font-weight:600;transition:color .15s" onmouseover="this.style.color='#1F3A8B'" onmouseout="this.style.color='#64748b'">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page" style="color:#0B1C48;font-weight:700">Reservasi Kendaraan</li>
                    </ol>
                </nav>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge rounded-pill px-3 py-1.5 fw-bold" style="font-size:10px;background:linear-gradient(135deg,#0B1C48,#1F3A8B);color:#fff;letter-spacing:0.7px;box-shadow:0 2px 8px rgba(11,28,72,0.22);border:1px solid rgba(255,255,255,0.15)">MODUL 1</span>
                    <span class="badge rounded-pill px-3 py-1.5" style="font-size:10px;background:#fff;color:#1F3A8B;border:1px solid #cfdbf3;font-weight:700;letter-spacing:0.3px">PENGADAAN &amp; PEMAKAIAN KENDARAAN DINAS</span>
                </div>
                <div class="d-flex align-items-start gap-3 mb-2" style="margin-top:2px">
                    <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#3B5FC7,#0B1C48);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(11,28,72,0.18);flex-shrink:0">
                        <i class="bi bi-car-front-fill" style="font-size:20px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <h2 style="font-size:24px;color:#0B1C48;font-weight:800;letter-spacing:-0.35px;margin:0 0 6px 0;line-height:1.15">
                            Sistem Informasi Reservasi Kendaraan Dinas
                        </h2>
                        <p class="mb-0" style="font-size:11.5px;line-height:1.65;color:#475569;max-width:720px">
                            Kelola pemesanan mobil dinas, cek ketersediaan unit, dan catat pengemudi &amp; tujuan perjalanan <strong style="color:#0B1C48;font-weight:700">Bagian Umum BPKP Perwakilan DIY</strong>.
                        </p>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center" style="flex-shrink:0">
                <?php if ($tab === 'sarana'): ?>
                    <span class="badge px-4 py-2 rounded-pill" style="font-size:11px;font-weight:700;background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,0.28);border:1px solid rgba(255,255,255,0.25);letter-spacing:0.3px">
                        <i class="bi bi-collection-fill me-1.5"></i><?= count($kendaraan_list) ?> Unit Tersedia
                    </span>
                <?php else: ?>
                    <a href="<?= base_url('kendaraan/form.php') ?>" class="btn fw-semibold" style="border-radius:13px;padding:10px 22px;font-size:11.5px;background:linear-gradient(135deg,#3B5FC7,#0B1C48);border:none;color:#fff;box-shadow:0 5px 16px rgba(11,28,72,0.25);letter-spacing:0.2px">
                        <i class="bi bi-plus-lg me-1.5"></i>Buat Reservasi Baru
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== PREMIUM TABS ===== -->
        <div class="ken-tabs-modern" role="tablist">
            <a href="?<?= http_build_query(array_merge($_GET,['tab'=>'reservasi'])) ?>" class="tab-modern <?= $tab==='reservasi'?'active':'' ?>" role="tab" style="border-radius:12px;padding:10px 18px">
                <i class="bi bi-journal-text"></i>
                <span>Daftar Reservasi</span>
                <span class="badge rounded-pill ms-1" style="font-size:9.5px;padding:2px 8px"><?= $jml['active'] ?></span>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET,['tab'=>'riwayat'])) ?>" class="tab-modern <?= $tab==='riwayat'?'active':'' ?>" role="tab" style="border-radius:12px;padding:10px 18px">
                <i class="bi bi-arrow-return-left"></i>
                <span>Riwayat Pengembalian Aset</span>
                <span class="badge rounded-pill ms-1" style="font-size:9.5px;padding:2px 8px"><?= $jml['riwayat'] ?></span>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET,['tab'=>'jadwal'])) ?>" class="tab-modern <?= $tab==='jadwal'?'active':'' ?>" role="tab" style="border-radius:12px;padding:10px 18px">
                <i class="bi bi-calendar-week"></i>
                <span>Jadwal &amp; Ketersediaan Kendaraan</span>
            </a>
        </div>
    </div>

    <?php if ($tab === 'reservasi' || $tab === 'riwayat'): ?>
    <!-- ================== TAB 1 & TAB 4 : DAFTAR RESERVASI + RIWAYAT (CARD STYLE) ================== -->
    <div class="card border-0 shadow-sm mb-4 ken-filter-card position-relative" style="border-radius:18px;overflow:hidden;border:1.5px solid #e2e8f0;box-shadow:0 10px 34px -16px rgba(11,28,72,0.15)">
        <div class="card-body py-4 px-5" style="background:linear-gradient(180deg,#fafcff,#ffffff);border-bottom:1px solid #eef2f7">
            <form method="GET" action="<?= base_url('kendaraan/index.php') ?>" class="d-flex flex-wrap gap-3 align-items-center">
                <input type="hidden" name="tab" value="<?= sanitize($tab) ?>">
                <div class="search-box flex-grow-1" style="max-width:620px;min-width:260px">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" name="search" placeholder="Cari nama pegawai, NIP, kode reservasi, tujuan dinas, kendaraan..." value="<?= $search ?>">
                </div>
                <select class="form-select form-select-sm" name="status" style="width:165px;border-radius:11px;border:1px solid #dbe5f1;font-size:11.5px;padding:9px 14px;min-height:40px">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu Approval</option>
                    <option value="disetujui" <?= $status === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="ditolak" <?= $status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                </select>
                <select class="form-select form-select-sm" name="kendaraan" style="width:240px;border-radius:11px;border:1px solid #dbe5f1;font-size:11.5px;padding:9px 14px;min-height:40px">
                    <option value="all" <?= $filter_kendaraan === 'all' ? 'selected' : '' ?>>Semua Kendaraan</option>
                    <?php foreach ($kendaraan_list as $kk): ?>
                        <option value="<?= $kk['id'] ?>" <?= $filter_kendaraan == $kk['id'] ? 'selected' : '' ?>><?= $kk['no_plat'] ?> — <?= $kk['merk'] ?> <?= $kk['tipe'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm fw-semibold" style="border-radius:11px;padding:9px 18px;font-size:11.5px;background:linear-gradient(135deg,#3B5FC7,#0B1C48);border:none;color:#fff;min-height:40px;box-shadow:0 3px 10px rgba(11,28,72,0.22)">
                    <i class="bi bi-funnel-fill me-1"></i>Tampilkan
                </button>
                <a href="?tab=<?= sanitize($tab) ?>" class="btn btn-sm fw-semibold border" style="border-radius:11px;padding:9px 18px;font-size:11.5px;border-color:#e2e8f0;color:#475569;background:#fff;min-height:40px">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </a>
            </form>
        </div>
        <?php if ($tab === 'reservasi'): ?>
        <div class="px-5 pt-4 pb-3" style="background:#fff;border-bottom:1px solid #f1f5f9">
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'all','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold" style="font-size:11px;padding:8px 16px;<?= $status === 'all' ? 'background:#0B1C48;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569;background:#fff' ?>">
                    <i class="bi bi-stack-2 me-1"></i>Semua <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'all' ? 'background:rgba(255,255,255,0.2);color:#fff' : 'background:#0B1C48;color:#fff' ?>"><?= $jml['active'] ?></span>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'pending','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold" style="font-size:11px;padding:8px 16px;<?= $status === 'pending' ? 'background:#f59e0b;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569;background:#fff' ?>">
                    <i class="bi bi-clock-history me-1"></i>Menunggu Approval <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'pending' ? 'background:rgba(0,0,0,0.2);color:#fff' : 'background:#f59e0b;color:#fff' ?>"><?= $jml['pending'] ?></span>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'disetujui','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold" style="font-size:11px;padding:8px 16px;<?= $status === 'disetujui' ? 'background:#10b981;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569;background:#fff' ?>">
                    <i class="bi bi-check2-circle me-1"></i>Disetujui <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'disetujui' ? 'background:rgba(0,0,0,0.18);color:#fff' : 'background:#10b981;color:#fff' ?>"><?= $jml['disetujui'] ?></span>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="px-5 pt-4 pb-3" style="background:#fff;border-bottom:1px solid #f1f5f9">
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <span class="badge rounded-pill px-3 py-1.5 fw-bold" style="font-size:10px;background:linear-gradient(135deg,#475569,#334155);color:#fff;letter-spacing:0.4px">
                    <i class="bi bi-clock-history me-1.5"></i>ARSIP PENGEMBALIAN : <?= $jml['riwayat'] ?> Catatan
                </span>
                <span class="badge rounded-pill px-3 py-1.5" style="font-size:10px;background:rgba(16,185,129,0.1);color:#059669;font-weight:700">
                    <i class="bi bi-check-circle-fill me-1.5"></i>Selesai : <?= $jml['selesai'] ?>
                </span>
                <span class="badge rounded-pill px-3 py-1.5" style="font-size:10px;background:rgba(239,68,68,0.1);color:#dc2626;font-weight:700">
                    <i class="bi bi-x-circle-fill me-1.5"></i>Ditolak : <?= $jml['ditolak'] ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- ====== HEADER BARIS KOLOM ===== -->
        <div class="px-5 pt-3 pb-2" style="background:linear-gradient(180deg,#f8fafc,#f1f5f9);border-bottom:1.5px solid #e2e8f0">
            <div class="row g-4 align-items-center" style="margin:0">
                <div class="col px-3" style="min-width:190px">
                    <div style="font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;text-transform:uppercase">Kode &amp; Pemohon</div>
                </div>
                <div class="col px-3" style="min-width:250px">
                    <div style="font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;text-transform:uppercase">Tujuan &amp; Keperluan</div>
                </div>
                <div class="col px-3" style="min-width:230px">
                    <div style="font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;text-transform:uppercase">Kendaraan</div>
                </div>
                <div class="col px-3" style="min-width:230px">
                    <div style="font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;text-transform:uppercase">Waktu (Format 24 Jam)</div>
                </div>
                <div class="col px-3" style="min-width:170px">
                    <div style="font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;text-transform:uppercase">Status</div>
                </div>
                <div class="col px-3 d-flex align-items-center justify-content-end" style="min-width:200px">
                    <div style="font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;text-transform:uppercase">Aksi</div>
                </div>
            </div>
        </div>
        <!-- ====== LIST CARD BARIS ===== -->
        <div class="px-5 py-4" style="background:#fff">
            <?php if (count($reservasi) === 0): ?>
                <div style="padding:60px 20px;text-align:center;border-radius:16px;background:#fafbff;border:1.5px dashed #dbeafe">
                    <div class="mb-3"><i class="bi bi-calendar-x" style="font-size:48px;color:#cbd5e1"></i></div>
                    <div class="fw-bold mb-1" style="color:#475569;font-size:13px">Belum Ada Data Reservasi</div>
                    <p class="mb-0 text-muted" style="font-size:11px">Klik <strong>"Buat Reservasi Baru"</strong> atau pilih unit kendaraan untuk memulai pengajuan.</p>
                </div>
            <?php else: ?>
            <div class="d-flex flex-column" style="gap:14px">
            <?php foreach ($reservasi as $r): $ops = detect_status_operasional($r); ?>
                <div class="reservasi-row-card position-relative" style="padding:20px 20px;border-radius:16px;background:#fff;border:1.5px solid #e5edf8;transition:all .22s cubic-bezier(.2,.8,.2,1)" onmouseover="this.style.borderColor='#bfdbfe';this.style.boxShadow='0 8px 22px -12px rgba(11,28,72,0.18)'" onmouseout="this.style.borderColor='#e5edf8';this.style.boxShadow='none'">
                    <div class="row g-4 align-items-start" style="margin:0">
                        <!-- 1. KODE & PEMOHON -->
                        <div class="col px-0" style="min-width:190px;max-width:220px">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5" style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#3B5FC7,#1F3A8B);color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px">
                                    <i class="bi bi-person-vcard-fill"></i>
                                </div>
                                <div style="min-width:0;flex:1">
                                    <div class="badge rounded-pill mb-2 fw-bold d-inline-flex align-items-center" style="font-size:9.5px;background:rgba(37,99,235,0.1);color:#1d4ed8;padding:4px 10px;letter-spacing:0.3px">
                                        <i class="bi bi-receipt me-1" style="font-size:9px"></i><?= $r['kode_reservasi'] ?>
                                    </div>
                                    <div class="fw-bold mb-0.5" style="font-size:12.5px;color:#0f172a;line-height:1.4"><?= sanitize($r['pemohon']) ?></div>
                                    <div style="font-size:10.5px;line-height:1.65;margin-top:3px;color:#475569">
                                        <span class="d-block mb-0.5" style="color:#64748b">NIP. <?= $r['nip'] ?></span>
                                        <span class="d-block text-truncate" title="<?= sanitize($r['unit_kerja']) ?>"><i class="bi bi-briefcase me-1" style="font-size:9px;color:#3B5FC7"></i><?= sanitize($r['unit_kerja']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. TUJUAN & KEPERLUAN -->
                        <div class="col px-0" style="min-width:250px;flex:1.15">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-geo-alt-fill flex-shrink-0 mt-0.5" style="font-size:14px;color:#1d4ed8"></i>
                                <div style="min-width:0;flex:1">
                                    <div class="fw-bold mb-1" style="font-size:12.5px;color:#0f172a;line-height:1.4"><?= sanitize($r['tujuan']) ?></div>
                                </div>
                            </div>
                            <div style="font-size:10.5px;color:#64748b;line-height:1.6;margin-bottom:10px;padding-left:22px">
                                <span style="color:#475569"><strong style="color:#334155">Keperluan :</strong> <?= sanitize($r['keperluan']) ?></span>
                            </div>
                            <div class="d-flex flex-wrap gap-2" style="padding-left:22px">
                                <span class="badge rounded-pill" style="font-size:10px;padding:4px 11px;background:#f1f5f9;color:#475569;font-weight:700">
                                    <i class="bi bi-calendar3 me-1"></i>Mulai: <?= format_date($r['tanggal_pinjam'], false) ?>
                                </span>
                            </div>
                        </div>

                        <!-- 3. KENDARAAN & DRIVER -->
                        <div class="col px-0" style="min-width:230px;max-width:250px">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5" style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,rgba(59,130,246,0.12),rgba(37,99,235,0.06));color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:15px">
                                    <i class="bi bi-car-front-fill"></i>
                                </div>
                                <div style="min-width:0;flex:1">
                                    <div class="fw-bold mb-1" style="font-size:12px;color:#0f172a;line-height:1.45">
                                        <?= sanitize($r['merk']) ?> <?= sanitize($r['tipe']) ?> <span class="text-muted" style="font-weight:500">(<?= $r['no_plat'] ?>)</span>
                                    </div>
                                    <div class="d-inline-flex align-items-center gap-1.5" style="background:linear-gradient(180deg,#0b1c48,#1F3A8B);color:#fff;border-radius:7px;padding:3px 9px">
                                        <span class="fw-extrabold text-uppercase" style="font-size:10.5px;letter-spacing:0.5px"><?= $r['no_plat'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. WAKTU 24 JAM -->
                        <div class="col px-0" style="min-width:230px;max-width:260px">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-start gap-2.5" style="padding:10px 13px;border-radius:12px;background:rgba(16,185,129,0.07);border:1.5px solid rgba(16,185,129,0.17)">
                                    <i class="bi bi-play-circle-fill text-success mt-0.5 flex-shrink-0" style="font-size:15px"></i>
                                    <div style="min-width:0;flex:1">
                                        <div style="font-size:9px;color:#059669;font-weight:800;letter-spacing:0.5px;margin-bottom:3px;text-transform:uppercase">Mulai:</div>
                                        <div style="font-size:11.5px;color:#0f172a;font-weight:700;line-height:1.55">
                                            <?= format_date($r['tanggal_pinjam'], false) ?> <span style="color:#1d4ed8"><?= format_time($r['jam_mulai']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2.5" style="padding:10px 13px;border-radius:12px;background:rgba(239,68,68,0.06);border:1.5px solid rgba(239,68,68,0.16)">
                                    <i class="bi bi-stop-circle-fill text-danger mt-0.5 flex-shrink-0" style="font-size:15px"></i>
                                    <div style="min-width:0;flex:1">
                                        <div style="font-size:9px;color:#dc2626;font-weight:800;letter-spacing:0.5px;margin-bottom:3px;text-transform:uppercase">Rencana Selesai:</div>
                                        <div style="font-size:11.5px;color:#0f172a;font-weight:700;line-height:1.55">
                                            <?= format_date($r['tanggal_kembali'], false) ?> <span style="color:#1d4ed8"><?= format_time($r['jam_selesai']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. STATUS -->
                        <div class="col px-0" style="min-width:170px;max-width:180px">
                            <?php
                                $statusMap = [
                                    'primary'   => 'background:rgba(37,99,235,0.12);color:#1d4ed8;border:1.5px solid rgba(37,99,235,0.3)',
                                    'warning'   => 'background:rgba(245,158,11,0.14);color:#b45309;border:1.5px solid rgba(245,158,11,0.35)',
                                    'danger'    => 'background:rgba(239,68,68,0.1);color:#dc2626;border:1.5px solid rgba(239,68,68,0.3)',
                                    'success'   => 'background:rgba(16,185,129,0.12);color:#047857;border:1.5px solid rgba(16,185,129,0.3)',
                                    'info'      => 'background:rgba(124,58,237,0.1);color:#6d28d9;border:1.5px solid rgba(124,58,237,0.28)',
                                    'secondary' => 'background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0'
                                ];
                                $clsNow = $statusMap[$ops['cls']] ?? $statusMap['secondary'];
                                $peny = ($r['status'] === 'selesai') ? 'Sudah Selesai' : 'Belum Selesai';
                                $penyCls = ($r['status'] === 'selesai') ? 'color:#059669' : 'color:#64748b';
                            ?>
                            <div class="d-flex flex-column align-items-start gap-2">
                                <span class="badge rounded-pill px-3 py-1.5 d-inline-flex align-items-center fw-bold" style="font-size:10.5px;padding:7px 13px;<?= $clsNow ?>;letter-spacing:0.2px">
                                    <i class="bi <?= $ops['icon'] ?> me-1.5"></i><?= $ops['label'] ?>
                                </span>
                                <div style="font-size:9.5px;font-weight:600;<?= $penyCls ?>;letter-spacing:0.2px">
                                    Penyelesaian: <strong style="font-weight:800"><?= $peny ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- 6. AKSI -->
                        <div class="col px-0 d-flex align-items-start justify-content-end" style="min-width:200px">
                            <div class="d-flex flex-column gap-2 align-items-stretch" style="width:100%;max-width:168px">
                                <a href="detail.php?id=<?= $r['id'] ?>" class="btn btn-sm fw-semibold" style="border-radius:11px;padding:8px 14px;font-size:10.8px;background:#fff;color:#1F3A8B;border:1.5px solid #dbeafe;font-weight:700">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>
                                <?php if ($isAdmin && $r['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm fw-semibold" style="border-radius:11px;padding:8px 14px;font-size:10.8px;background:linear-gradient(135deg,#d97706,#b45309);border:none;color:#fff;font-weight:700;box-shadow:0 3px 10px rgba(180,83,9,0.22)"
                                        onclick="bsConfirm({
                                            title:'Verifikasi / Setujui Reservasi',
                                            message:'Setujui reservasi <?= $r['kode_reservasi'] ?> atas nama <?= sanitize($r['pemohon']) ?>?',
                                            variant:'success',
                                            onConfirm:function(){ location.href='<?= base_url('kendaraan/action.php?action=setujui&id=' . $r['id']) ?>'; }
                                        })">
                                        <i class="bi bi-check2-all me-1"></i>Verifikasi / Setujui
                                    </button>
                                <?php elseif (($isAdmin || $r['status'] === 'disetujui' || $ops['key'] === 'digunakan' || $ops['key'] === 'lewat') && $r['status'] !== 'selesai'): ?>
                                    <button type="button" class="btn btn-sm fw-semibold" style="border-radius:11px;padding:8px 14px;font-size:10.8px;background:linear-gradient(135deg,#10b981,#059669);border:none;color:#fff;font-weight:700;box-shadow:0 3px 10px rgba(16,185,129,0.25)"
                                        onclick="bsConfirm({
                                            title:'Catat Pengembalian Kendaraan',
                                            message:'Tandai reservasi <?= $r['kode_reservasi'] ?> sudah selesai dan kendaraan dikembalikan?',
                                            variant:'success',
                                            onConfirm:function(){ location.href='<?= base_url('kendaraan/action.php?action=selesai&id=' . $r['id']) ?>'; }
                                        })">
                                        <i class="bi bi-check-circle-fill me-1"></i>Catat Kembali
                                    </button>
                                <?php endif; ?>
                                <?php if ($isAdmin && $r['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm fw-semibold" style="border-radius:11px;padding:8px 14px;font-size:10.8px;background:rgba(239,68,68,0.08);color:#dc2626;border:1.5px solid rgba(239,68,68,0.2);font-weight:700"
                                        onclick="bsConfirm({
                                            title:'Tolak Reservasi',
                                            message:'Anda yakin menolak reservasi <?= $r['kode_reservasi'] ?> ini?',
                                            variant:'danger',
                                            onConfirm:function(){ location.href='<?= base_url('kendaraan/action.php?action=tolak&id=' . $r['id']) ?>'; }
                                        })">
                                        <i class="bi bi-x-lg me-1"></i>Tolak
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if (count($reservasi) > 0): ?>
            <div class="px-5 py-3 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background:linear-gradient(180deg,#fff,#fafcff);border-top:1px solid #f1f5f9">
                <div style="font-size:10.5px;color:#64748b;font-weight:600">Menampilkan <span class="fw-bold" style="color:#0B1C48"><?= count($reservasi) ?></span> dari total <?= $jml['all'] ?> catatan reservasi.</div>
                <?php if ($isAdmin): ?>
                <a href="<?= base_url('laporan.php?type=kendaraan') ?>" class="btn btn-sm fw-semibold border" style="border-radius:10px;padding:7px 14px;font-size:10.5px;border-color:#dbeafe;color:#1d4ed8;background:#fff">
                    <i class="bi bi-printer me-1"></i>Rekapitulasi
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php elseif ($tab === 'sarana'): ?>
    <!-- ================== SARANA KENDARAAN (CARD GRID) ================== -->
    <div class="card border-0 shadow-sm mb-4 ken-sarana-card position-relative" style="border-radius:18px;overflow:hidden;border:1.5px solid #e2e8f0;border-top:none;background:linear-gradient(180deg,#fafcff,#ffffff);box-shadow:0 10px 34px -16px rgba(124,58,237,0.16)">
        <div class="card-body py-4 px-5 d-flex flex-wrap justify-content-between align-items-center" style="border-bottom:1px solid #eef2f7;gap:14px">
            <div>
                <h5 class="mb-0" style="font-size:14px;color:#0f172a;font-weight:800">
                    <i class="bi bi-garage-open me-1.5" style="color:#2563eb"></i>Daftar Unit Sarana Kendaraan Dinas
                </h5>
                <p class="mb-0 mt-1 text-muted" style="font-size:10.5px">Pilih salah satu unit di bawah untuk langsung mengajukan reservasi kendaraan tersebut.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <div class="search-box" style="max-width:340px;min-width:220px">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" id="cariSarana" placeholder="Cari plat, merk, tipe, unit, driver..." value="<?= $search ?>">
                </div>
                <span class="badge rounded-pill px-3 py-1.5" style="font-size:10px;background:rgba(37,99,235,0.1);color:#1d4ed8;font-weight:700">
                    <i class="bi bi-grid-fill me-1"></i>Grid 3 Kolom
                </span>
            </div>
        </div>
        <div class="card-body p-5">
            <?php if (count($kendaraan_list) === 0): ?>
                <div style="padding:80px 20px;text-align:center">
                    <div class="mb-3"><i class="bi bi-garage" style="font-size:50px;color:#cbd5e1"></i></div>
                    <div class="fw-bold mb-1" style="color:#475569;font-size:13px">Belum Ada Data Kendaraan</div>
                    <p class="mb-0 text-muted" style="font-size:11px">Tambahkan master kendaraan terlebih dahulu di menu Master Kendaraan.</p>
                </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($kendaraan_list as $kk):
                    $status_class = ['tersedia' => ['text'=>'Tersedia','bg'=>'rgba(16,185,129,0.1)','color'=>'#047857'],
                                     'digunakan' => ['text'=>'Sedang Digunakan','bg'=>'rgba(37,99,235,0.1)','color'=>'#1d4ed8'],
                                     'perawatan' => ['text'=>'Perawatan','bg'=>'rgba(245,158,11,0.12)','color'=>'#b45309']];
                    $sc = $status_class[$kk['status']] ?? $status_class['tersedia'];
                    $stnk_info = pajak_status_info($kk['pajak_stnk_jatuh_tempo'] ?? null, $kk['pajak_tnkb_jatuh_tempo'] ?? null);
                    $servis_info = service_status_info($kk['service_berikutnya'] ?? null, $kk['terakhir_service'] ?? null);
                ?>
                <div class="col-xl-4 col-lg-6 col-md-6 item-kendaraan" data-search="<?= strtolower(($kk['no_plat'] ?? '') . ' ' . ($kk['merk'] ?? '') . ' ' . ($kk['tipe'] ?? '') . ' ' . ($kk['unit_pengguna'] ?? '') . ' ' . ($kk['driver'] ?? '')) ?>">
                    <div class="card h-100 position-relative overflow-hidden" style="border-radius:18px;border:1.5px solid #e5edf8;background:#fff;transition:all .22s cubic-bezier(.4,0,.2,1)">
                        <div class="position-absolute top-0 start-0 end-0" style="height:150px;background:linear-gradient(135deg,#1e293b 0%,#0f172a 55%,#0B1C48 100%);overflow:hidden">
                            <div style="position:absolute;top:-24px;right:-20px;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,rgba(96,165,250,0.18),transparent 65%)"></div>
                            <div style="position:absolute;bottom:-20px;left:-10px;width:120px;height:120px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,0.08),transparent 65%)"></div>
                            <div class="d-flex align-items-start justify-content-between p-4 position-relative">
                                <div>
                                    <div class="d-inline-flex align-items-center gap-1.5 mb-3" style="background:linear-gradient(180deg,#ffffff,#f1f5f9);box-shadow:0 2px 10px rgba(0,0,0,0.25);border-radius:7px;padding:4px 11px;border:1.5px solid #fff">
                                        <span class="fw-extrabold text-uppercase" style="font-size:11.5px;letter-spacing:0.8px;color:#0B1C48"><?= $kk['no_plat'] ?></span>
                                    </div>
                                    <div style="font-size:10.5px;color:#93c5fd;letter-spacing:0.8px;font-weight:700;text-transform:uppercase;margin-bottom:3px"><?= $kk['_kode_sarana'] ?></div>
                                    <div class="fw-bold mb-0" style="font-size:17px;color:#fff;letter-spacing:-0.2px;line-height:1.35"><?= sanitize($kk['merk']) ?> <?= sanitize($kk['tipe']) ?></div>
                                    <?php if (!empty($kk['tahun'])): ?>
                                        <div style="font-size:10.5px;color:#bfdbfe;font-weight:600;margin-top:4px"><i class="bi bi-calendar3 me-1"></i>Tahun <?= $kk['tahun'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="width:70px;height:70px;border-radius:20px;background:rgba(255,255,255,0.06);border:1.5px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,0.25)">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 17L4.5 11L7.5 7H16.5L19.5 11L21 17" stroke="#93c5fd" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M5 17V19C5 19.5523 5.44772 20 6 20H8C8.55228 20 9 19.5523 9 19V17H15V19C15 19.5523 15.4477 20 16 20H18C18.5523 20 19 19.5523 19 19V17M3 17H21" stroke="#93c5fd" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="7.5" cy="13.5" r="1.2" fill="#3B5FC7"/>
                                        <circle cx="16.5" cy="13.5" r="1.2" fill="#3B5FC7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="padding:170px 20px 20px 20px">
                            <div class="d-flex justify-content-between align-items-start mb-3" style="gap:10px">
                                <div class="d-flex flex-wrap gap-1.5">
                                    <span class="badge rounded-pill px-2.5 py-1" style="font-size:9.5px;font-weight:700;background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                                        <i class="bi bi-check-circle-fill me-1"></i><?= $sc['text'] ?>
                                    </span>
                                    <?php if (!empty($kk['kapasitas'])): ?>
                                        <span class="badge rounded-pill px-2.5 py-1" style="font-size:9.5px;font-weight:700;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0">
                                            <i class="bi bi-people-fill me-1"></i><?= $kk['kapasitas'] ?> Kursi
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex flex-column gap-1 align-items-end">
                                    <?php if ($stnk_info['cls'] !== 'success' && $stnk_info['cls'] !== 'secondary'): ?>
                                        <span class="badge rounded-pill px-2 py-1" style="font-size:8.5px;background:<?= $stnk_info['cls']==='danger' ? 'rgba(239,68,68,0.1)' : 'rgba(245,158,11,0.12)' ?>;color:<?= $stnk_info['cls']==='danger' ? '#dc2626' : '#b45309' ?>;font-weight:700">
                                            <i class="bi bi-stopwatch me-1"></i>STNK <?= $stnk_info['label'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($servis_info['cls'] !== 'success' && $servis_info['cls'] !== 'secondary'): ?>
                                        <span class="badge rounded-pill px-2 py-1" style="font-size:8.5px;background:rgba(139,92,246,0.1);color:#7c3aed;font-weight:700">
                                            <i class="bi bi-wrench-adjustable me-1"></i>Service <?= $servis_info['label'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div style="font-size:10px;color:#64748b;letter-spacing:0.4px;font-weight:700;text-transform:uppercase;margin-bottom:7px">Spesifikasi Unit</div>
                                <div class="row g-2" style="row-gap:7px">
                                    <div class="col-6">
                                        <div style="display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:10px;background:#f8fafc;border:1px solid #eef2f7">
                                            <i class="bi bi-buildings-fill" style="font-size:11px;color:#3B5FC7"></i>
                                            <div style="min-width:0;flex:1">
                                                <div style="font-size:8.5px;color:#94a3b8;font-weight:700;letter-spacing:0.3px;text-transform:uppercase;line-height:1">Unit</div>
                                                <div style="font-size:10.5px;color:#0f172a;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= sanitize($kk['unit_pengguna'] ?? '-') ?>"><?= sanitize($kk['unit_pengguna'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div style="display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:10px;background:#f8fafc;border:1px solid #eef2f7">
                                            <i class="bi bi-person-fill-gear" style="font-size:11px;color:#10b981"></i>
                                            <div style="min-width:0;flex:1">
                                                <div style="font-size:8.5px;color:#94a3b8;font-weight:700;letter-spacing:0.3px;text-transform:uppercase;line-height:1">Pengemudi</div>
                                                <div style="font-size:10.5px;color:#0f172a;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= sanitize($kk['driver'] ?? '-') ?>"><?= sanitize($kk['driver'] ?? 'Belum ditetapkan') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div style="display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:10px;background:#f8fafc;border:1px solid #eef2f7">
                                            <i class="bi bi-calendar-event-fill" style="font-size:11px;color:#dc2626"></i>
                                            <div style="min-width:0;flex:1">
                                                <div style="font-size:8.5px;color:#94a3b8;font-weight:700;letter-spacing:0.3px;text-transform:uppercase;line-height:1">STNK Jth Tempo</div>
                                                <div style="font-size:10.5px;color:#0f172a;font-weight:700"><?= !empty($kk['pajak_stnk_jatuh_tempo']) ? format_date($kk['pajak_stnk_jatuh_tempo'], false) : '-' ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div style="display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:10px;background:#f8fafc;border:1px solid #eef2f7">
                                            <i class="bi bi-wrench-adjustable" style="font-size:11px;color:#7c3aed"></i>
                                            <div style="min-width:0;flex:1">
                                                <div style="font-size:8.5px;color:#94a3b8;font-weight:700;letter-spacing:0.3px;text-transform:uppercase;line-height:1">Service Berikutnya</div>
                                                <div style="font-size:10.5px;color:#0f172a;font-weight:700"><?= !empty($kk['service_berikutnya']) ? format_date($kk['service_berikutnya'], false) : (!empty($kk['terakhir_service']) ? 'Est. 6bln' : '-') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($kk['status'] === 'tersedia'): ?>
                                <a href="<?= base_url('kendaraan/form.php') ?>?kendaraan_id=<?= $kk['id'] ?>"
                                   class="btn w-100 fw-bold"
                                   style="border-radius:13px;padding:11px 14px;font-size:11.5px;background:linear-gradient(135deg,#3B5FC7,#1F3A8B);color:#fff;border:none;box-shadow:0 4px 14px rgba(59,95,199,0.25)">
                                    <i class="bi bi-calendar-plus-fill me-1.5"></i>Ajukan Reservasi Kendaraan Ini
                                </a>
                            <?php elseif ($kk['status'] === 'digunakan'): ?>
                                <button type="button" class="btn w-100 fw-bold" disabled
                                   style="border-radius:13px;padding:11px 14px;font-size:11.5px;background:linear-gradient(135deg,#64748b,#475569);color:#fff;border:none;opacity:0.9">
                                    <i class="bi bi-person-badge-fill me-1.5"></i>Sedang Digunakan — Lihat Jadwal
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn w-100 fw-bold" disabled
                                   style="border-radius:13px;padding:11px 14px;font-size:11.5px;background:linear-gradient(135deg,#b45309,#92400e);color:#fff;border:none;opacity:0.92">
                                    <i class="bi bi-tools me-1.5"></i>Dalam Perawatan — Tidak Tersedia
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($tab === 'jadwal'): ?>
    <!-- ================== TAB 3 : JADWAL KETERSEDIAAN ================== -->
    <div class="card border-0 shadow-sm mb-5 ken-jadwal-card position-relative" style="border-radius:18px;overflow:hidden;border:1.5px solid #e2e8f0;border-top:none;box-shadow:0 10px 34px -16px rgba(245,158,11,0.18)">
        <div class="card-body py-4 px-5 d-flex justify-content-between align-items-center flex-wrap" style="border-bottom:1px solid #eef2f7;gap:14px;background:linear-gradient(180deg,#fafcff,#ffffff)">
            <div>
                <h5 class="mb-0" style="font-size:14px;color:#0f172a;font-weight:800">
                    <i class="bi bi-calendar-week me-1.5" style="color:#7c3aed"></i>Jadwal Peminjaman &amp; Ketersediaan Unit
                </h5>
                <p class="mb-0 mt-1 text-muted" style="font-size:10.5px">Lihat kalender kapan kendaraan dinas dipakai, untuk menghindari bentrok jadwal.</p>
            </div>
            <a href="<?= base_url('kalender.php') ?>" class="btn fw-semibold border" style="border-radius:11px;padding:8px 16px;font-size:10.5px;border-color:#c7d2fe;background:#eef2ff;color:#4f46e5">
                <i class="bi bi-arrows-fullscreen me-1"></i>Buka Kalender Penuh
            </a>
        </div>
        <div class="card-body p-5">
            <?php
                $jadwal_scope = $isAdmin ? '' : ' AND rk.user_id = ' . (int)$user['id'];
                $jadwal_list = db()->fetchAll("SELECT rk.*, k.no_plat, k.merk, k.tipe, u.nama_lengkap as pemohon
                    FROM reservasi_kendaraan rk
                    LEFT JOIN kendaraan k ON rk.kendaraan_id = k.id
                    LEFT JOIN users u ON rk.user_id = u.id
                    WHERE rk.status IN ('pending','disetujui')" . $jadwal_scope . "
                    ORDER BY rk.tanggal_pinjam ASC LIMIT 12");
            ?>
            <?php if (count($jadwal_list) === 0): ?>
                <div style="padding:80px 20px;text-align:center">
                    <div class="mb-3"><i class="bi bi-calendar-x" style="font-size:50px;color:#cbd5e1"></i></div>
                    <div class="fw-bold mb-1" style="color:#475569;font-size:13px">Belum Ada Jadwal Peminjaman</div>
                    <p class="mb-0 text-muted" style="font-size:11px">Kembali ke Tab Sarana Kendaraan untuk membuat reservasi pertama.</p>
                </div>
            <?php else: ?>
            <div class="row g-3 mb-4">
                <?php foreach ($jadwal_list as $j): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div style="border-radius:14px;border:1.5px solid #e5edf8;background:#fff;padding:14px">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-inline-flex align-items-center gap-1.5" style="background:linear-gradient(180deg,#0b1c48,#1F3A8B);color:#fff;border-radius:5px;padding:2.5px 8px">
                                <span class="fw-extrabold text-uppercase" style="font-size:9.5px;letter-spacing:0.5px"><?= $j['no_plat'] ?></span>
                            </div>
                            <?= status_badge($j['status']) ?>
                        </div>
                        <div class="fw-bold mb-1" style="font-size:11.5px;color:#0f172a;line-height:1.4"><?= sanitize($j['merk']) ?> <?= sanitize($j['tipe']) ?></div>
                        <div class="mb-2" style="font-size:10px;color:#64748b"><i class="bi bi-person me-1"></i><?= sanitize($j['pemohon']) ?></div>
                        <div style="display:flex;align-items:center;gap:6px;padding:7px 9px;border-radius:9px;background:#f8fafc;border:1px solid #eef2f7">
                            <i class="bi bi-calendar3 text-primary" style="font-size:10px"></i>
                            <div style="font-size:9.5px;font-weight:700;color:#0f172a">
                                <div><?= format_date($j['tanggal_pinjam'], false) ?> · <?= format_time($j['jam_mulai']) ?></div>
                                <div class="mt-0.5" style="color:#64748b;font-weight:600">sd <?= format_date($j['tanggal_kembali'], false) ?> · <?= format_time($j['jam_selesai']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-center">
                <a href="<?= base_url('kalender.php') ?>" class="btn fw-semibold" style="border-radius:12px;padding:9px 22px;font-size:11px;background:rgba(124,58,237,0.1);color:#7c3aed;border:none">
                    <i class="bi bi-calendar-event-fill me-1"></i>Lihat Semua Jadwal di Kalender Bulanan
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function(){
    var input = document.getElementById('cariSarana');
    if (input) {
        input.addEventListener('input', function(){
            var q = input.value.toLowerCase().trim();
            document.querySelectorAll('.item-kendaraan').forEach(function(card){
                var txt = card.getAttribute('data-search') || '';
                card.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
            });
        });
        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter') e.preventDefault();
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
