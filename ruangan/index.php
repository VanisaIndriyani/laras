<?php
$page_title = 'Reservasi Ruangan';
$active_menu = 'ruangan';
require_once __DIR__ . '/../partials/header.php';

$user = current_user();
$isAdmin = is_admin();

$status = $_GET['status'] ?? 'all';
$search = sanitize($_GET['search'] ?? '');
$filter_ruangan = sanitize($_GET['ruangan'] ?? 'all');
$active_tab = sanitize($_GET['tab'] ?? 'sarana'); // Default sesuai request user: ke Daftar Sarana duluan
if (!in_array($active_tab, ['reservasi','sarana','jadwal'])) $active_tab = 'sarana';

$where = '1=1';
$params = [];

if (!$isAdmin) {
    $where .= ' AND rr.user_id = ?';
    $params[] = $user['id'];
}
if ($status !== 'all') {
    $where .= ' AND rr.status = ?';
    $params[] = $status;
}
if ($filter_ruangan !== 'all') {
    $where .= ' AND rr.ruangan_id = ?';
    $params[] = (int)$filter_ruangan;
}
if ($search) {
    $where .= " AND (rr.kode_reservasi LIKE ? OR r.nama_ruangan LIKE ? OR u.nama_lengkap LIKE ? OR u.nip LIKE ? OR rr.nama_acara LIKE ? OR rr.unit_kerja LIKE ?)";
    $s = "%{$search}%";
    array_push($params, $s, $s, $s, $s, $s, $s);
}

$sql = "SELECT rr.*, r.nama_ruangan, r.lantai, r.kapasitas, r.fasilitas as fasilitas_ruangan,
        u.nama_lengkap as pemohon, u.nip, u.unit_kerja as unit_pemohon
        FROM reservasi_ruangan rr
        LEFT JOIN ruangan r ON rr.ruangan_id = r.id
        LEFT JOIN users u ON rr.user_id = u.id
        WHERE {$where}
        ORDER BY rr.created_at DESC";
$reservasi = db()->fetchAll($sql, $params);

$jml = [
    'all' => count($reservasi),
    'pending' => db()->count('reservasi_ruangan', ($isAdmin ? '1=1' : 'user_id = ' . (int)$user['id']) . " AND status = 'pending'"),
    'disetujui' => db()->count('reservasi_ruangan', ($isAdmin ? '1=1' : 'user_id = ' . (int)$user['id']) . " AND status = 'disetujui'"),
    'selesai' => db()->count('reservasi_ruangan', ($isAdmin ? '1=1' : 'user_id = ' . (int)$user['id']) . " AND status = 'selesai'"),
    'ditolak' => db()->count('reservasi_ruangan', ($isAdmin ? '1=1' : 'user_id = ' . (int)$user['id']) . " AND status = 'ditolak'")
];
$count_ruangan_tersedia = db()->count('ruangan', "status = 'tersedia'");
$ruangan_list = db()->fetchAll("SELECT id, nama_ruangan, lantai, kapasitas FROM ruangan ORDER BY lantai, nama_ruangan");

// Data untuk TAB 2: Sarana Ruangan Full
$all_ruangan = db()->fetchAll("SELECT * FROM ruangan ORDER BY FIELD(lantai,'Lantai 1','Lantai 2','Lantai 3'), id ASC");
// Generate kode RNG.XX.YYY per lantai
$rng_counters = [];
$ruangan_grid = [];
foreach ($all_ruangan as $rr) {
    $lantai_key = preg_replace('/[^0-9]/', '', $rr['lantai']) ?: '0';
    $lantai_2d = str_pad($lantai_key, 2, '0', STR_PAD_LEFT);
    if (!isset($rng_counters[$lantai_key])) $rng_counters[$lantai_key] = 0;
    $rng_counters[$lantai_key]++;
    $urut_3d = str_pad($rng_counters[$lantai_key], 3, '0', STR_PAD_LEFT);
    $rr['rng'] = "RNG.{$lantai_2d}.{$urut_3d}";
    $ruangan_grid[] = $rr;
}

// Data untuk TAB 3: Jadwal mendatang (pegawai hanya lihat jadwal miliknya sendiri)
$jadwal_where_scope = $isAdmin ? '1=1' : 'rr.user_id = ' . (int)$user['id'];
$jadwal_ruangan = db()->fetchAll("
    SELECT rr.*, r.nama_ruangan, r.lantai, u.nama_lengkap as pemohon
    FROM reservasi_ruangan rr
    LEFT JOIN ruangan r ON rr.ruangan_id = r.id
    LEFT JOIN users u ON rr.user_id = u.id
    WHERE {$jadwal_where_scope} AND rr.status IN ('pending','disetujui') AND rr.tanggal_mulai >= CURDATE()
    ORDER BY rr.tanggal_mulai ASC, rr.jam_mulai ASC
    LIMIT 12
");
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap:14px">
    <div>
        <h4><i class="bi bi-door-open-fill me-2" style="color:#7c3aed"></i>Reservasi Ruangan Rapat</h4>
        <nav class="breadcrumb mb-0">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <span class="breadcrumb-item active">Reservasi Ruangan</span>
        </nav>
    </div>
    <a href="<?= base_url('ruangan/form.php') ?>" class="btn fw-semibold shadow-sm" style="border-radius:12px;padding:9px 20px;font-size:12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none">
        <i class="bi bi-plus-circle-dotted me-1.5"></i> Reservasi Ruangan Baru
    </a>
</div>

<div class="hero-card-modern mb-4" style="background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 55%,#faf5ff 100%);border:1px solid #e9d5ff;border-radius:20px;padding:22px 26px;position:relative;overflow:hidden">
    <i class="bi bi-building-fill-check" style="position:absolute;right:40px;top:50%;transform:translateY(-50%);font-size:130px;opacity:0.07;color:#7c3aed"></i>
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
        <div style="max-width:680px">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge rounded-pill px-3 py-1.5 text-uppercase fw-bold" style="font-size:10px;background:#0B1C48;color:#fff;letter-spacing:0.8px">MODUL 2</span>
                <span class="badge rounded-pill px-3 py-1.5" style="font-size:10px;background:rgba(124,58,237,0.12);color:#6d28d9">SARANA &amp; PRASARANA RUANGAN</span>
            </div>
            <h3 class="fw-bold mb-1.5" style="font-size:19px;color:#0B1C48;margin-top:4px">Reservasi Sarana &amp; Ruang Rapat</h3>
            <p class="mb-0 text-muted" style="font-size:11.5px;line-height:1.7">
                Pengelolaan pemakaian ruang aula, ruang rapat lantai 1-3, fasilitas multimedia sound/LCD, dan jadwal kegiatan instansi.
            </p>
        </div>
    </div>
</div>

<!-- ============ 3 TABS INTERAKTIF ============ -->
<div class="mb-4" style="border-bottom:2px solid #f1f5f9">
    <div class="d-flex gap-2 flex-wrap align-items-stretch" role="tablist">
        <a href="?<?= http_build_query(array_merge($_GET,['tab'=>'reservasi'])) ?>" id="tab-reservasi" role="tab" class="tab-btn px-4 py-3 d-flex align-items-center gap-2 text-decoration-none cursor-pointer" style="border-radius:12px 12px 0 0;font-size:12.5px;font-weight:700;margin-bottom:-2px;<?= $active_tab==='reservasi' ? 'border-top:2.5px solid #2563eb;border-left:1.5px solid #e2e8f0;border-right:1.5px solid #e2e8f0;background:#fff;color:#1F3A8B' : 'color:#64748b' ?>">
            <i class="bi bi-calendar2-range-fill" style="font-size:15px"></i>
            <span>Daftar Reservasi Ruangan</span>
            <span class="badge rounded-pill" style="font-size:10px;padding:3px 8px;<?= $active_tab==='reservasi' ? 'background:rgba(37,99,235,0.12);color:#2563eb' : 'background:#e2e8f0;color:#475569' ?>"><?= $jml['all'] ?></span>
        </a>
        <a href="?<?= http_build_query(array_merge($_GET,['tab'=>'sarana'])) ?>" id="tab-sarana" role="tab" class="tab-btn px-4 py-3 d-flex align-items-center gap-2 text-decoration-none cursor-pointer" style="border-radius:12px 12px 0 0;font-size:12.5px;font-weight:700;margin-bottom:-2px;<?= $active_tab==='sarana' ? 'border-top:2.5px solid #9333ea;border-left:1.5px solid #e2e8f0;border-right:1.5px solid #e2e8f0;background:#fff;color:#7c3aed' : 'color:#64748b' ?>">
            <i class="bi bi-buildings-fill" style="font-size:15px"></i>
            <span>Daftar Sarana Ruangan</span>
            <span class="badge rounded-pill" style="font-size:10px;padding:3px 8px;<?= $active_tab==='sarana' ? 'background:rgba(147,51,234,0.12);color:#7c3aed' : 'background:#e2e8f0;color:#475569' ?>"><?= $count_ruangan_tersedia ?></span>
        </a>
        <a href="?<?= http_build_query(array_merge($_GET,['tab'=>'jadwal'])) ?>" id="tab-jadwal" role="tab" class="tab-btn px-4 py-3 d-flex align-items-center gap-2 text-decoration-none cursor-pointer" style="border-radius:12px 12px 0 0;font-size:12.5px;font-weight:700;margin-bottom:-2px;<?= $active_tab==='jadwal' ? 'border-top:2.5px solid #f59e0b;border-left:1.5px solid #e2e8f0;border-right:1.5px solid #e2e8f0;background:#fff;color:#b45309' : 'color:#64748b' ?>">
            <i class="bi bi-clock-history" style="font-size:15px"></i>
            <span>Jadwal &amp; Ketersediaan Ruang</span>
        </a>
    </div>
</div>

<!-- ============ TAB 1: DAFTAR RESERVASI ============ -->
<?php if ($active_tab === 'reservasi'): ?>
<div class="card border-0 shadow-sm mb-4" style="border-radius:18px;overflow:hidden;border:1.5px solid #e2e8f0;border-top:none">
    <div class="card-body py-4 px-5" style="background:linear-gradient(180deg,#fafcff,#ffffff);border-bottom:1px solid #eef2f7">
        <form method="GET" class="d-flex flex-wrap gap-3 align-items-center">
            <input type="hidden" name="tab" value="reservasi">
            <div class="search-box flex-grow-1" style="max-width:480px;min-width:260px">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" name="search" placeholder="Cari acara, pemohon, NIP, ruangan rapat..." value="<?= $search ?>">
            </div>
            <select class="form-select form-select-sm" name="status" style="width:165px;border-radius:11px;border:1px solid #dbe5f1;font-size:11.5px;padding:9px 14px;min-height:40px">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu Approval</option>
                <option value="disetujui" <?= $status === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                <option value="ditolak" <?= $status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
            </select>
            <select class="form-select form-select-sm" name="ruangan" style="width:230px;border-radius:11px;border:1px solid #dbe5f1;font-size:11.5px;padding:9px 14px;min-height:40px">
                <option value="all" <?= $filter_ruangan === 'all' ? 'selected' : '' ?>>Semua Ruangan</option>
                <?php foreach ($ruangan_list as $rrr): ?>
                    <option value="<?= $rrr['id'] ?>" <?= $filter_ruangan == $rrr['id'] ? 'selected' : '' ?>><?= $rrr['nama_ruangan'] ?> (<?= $rrr['lantai'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm fw-semibold" style="border-radius:11px;padding:9px 18px;font-size:11.5px;background:#2563eb;border:none;color:#fff;min-height:40px">
                <i class="bi bi-funnel-fill me-1"></i> Tampilkan
            </button>
            <a href="?tab=reservasi" class="btn btn-sm fw-semibold border" style="border-radius:11px;padding:9px 18px;font-size:11.5px;border-color:#e2e8f0;color:#475569;background:#fff;min-height:40px">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </a>
        </form>
    </div>

    <div class="px-5 pt-4 pb-3" style="background:#fff">
        <div class="d-flex gap-3 flex-wrap align-items-center">
            <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'all','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold <?= $status === 'all' ? '' : 'btn-light' ?>" style="font-size:11px;padding:8px 16px;<?= $status === 'all' ? 'background:#0B1C48;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569' ?>">
                <i class="bi bi-stack-2 me-1"></i> Semua <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'all' ? 'background:rgba(255,255,255,0.2);color:#fff' : 'background:#0B1C48;color:#fff' ?>"><?= $jml['all'] ?></span>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'pending','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold <?= $status === 'pending' ? '' : 'btn-light' ?>" style="font-size:11px;padding:8px 16px;<?= $status === 'pending' ? 'background:#f59e0b;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569' ?>">
                <i class="bi bi-clock-history me-1"></i> Menunggu Approval <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'pending' ? 'background:rgba(0,0,0,0.2);color:#fff' : 'background:#f59e0b;color:#fff' ?>"><?= $jml['pending'] ?></span>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'disetujui','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold <?= $status === 'disetujui' ? '' : 'btn-light' ?>" style="font-size:11px;padding:8px 16px;<?= $status === 'disetujui' ? 'background:#10b981;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569' ?>">
                <i class="bi bi-check2-circle me-1"></i> Disetujui <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'disetujui' ? 'background:rgba(0,0,0,0.18);color:#fff' : 'background:#10b981;color:#fff' ?>"><?= $jml['disetujui'] ?></span>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'selesai','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold <?= $status === 'selesai' ? '' : 'btn-light' ?>" style="font-size:11px;padding:8px 16px;<?= $status === 'selesai' ? 'background:#3B5FC7;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569' ?>">
                <i class="bi bi-flag-fill me-1"></i> Selesai <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'selesai' ? 'background:rgba(0,0,0,0.18);color:#fff' : 'background:#3B5FC7;color:#fff' ?>"><?= $jml['selesai'] ?></span>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['status'=>'ditolak','tab'=>'reservasi'])) ?>" class="btn btn-sm rounded-pill fw-semibold <?= $status === 'ditolak' ? '' : 'btn-light' ?>" style="font-size:11px;padding:8px 16px;<?= $status === 'ditolak' ? 'background:#ef4444;color:#fff;border:none' : 'border:1px solid #e2e8f0;color:#475569' ?>">
                <i class="bi bi-x-circle me-1"></i> Ditolak <span class="ms-1 badge rounded-pill" style="font-size:9.5px;padding:2px 7px;<?= $status === 'ditolak' ? 'background:rgba(0,0,0,0.18);color:#fff' : 'background:#ef4444;color:#fff' ?>"><?= $jml['ditolak'] ?></span>
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <?php if (empty($reservasi)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox display-5 d-block mb-3" style="opacity:0.25;color:#94a3b8"></i>
                <div class="fw-bold mb-1" style="font-size:13.5px;color:#0B1C48">Belum ada data reservasi ruangan</div>
                <div class="mb-3" style="font-size:11.5px;color:#64748b">Silakan buat pengajuan reservasi ruangan baru.</div>
                <a href="<?= base_url('ruangan/form.php') ?>" class="btn btn-sm fw-semibold" style="border-radius:10px;padding:8px 18px;font-size:11.5px;background:#2563eb;border:none;color:#fff">
                    <i class="bi bi-plus-circle me-1"></i> Buat Reservasi Pertama
                </a>
            </div>
        <?php else: ?>
        <table class="table mb-0 align-middle" style="min-width:1360px">
            <thead style="background:linear-gradient(180deg,#f8fafc,#f1f5f9);position:sticky;top:0;z-index:2">
                <tr>
                    <th style="padding:16px 22px;font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;border-bottom:1.5px solid #e2e8f0">Kode &amp; Pemohon</th>
                    <th style="padding:16px 22px;font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;border-bottom:1.5px solid #e2e8f0">Ruangan &amp; Lantai</th>
                    <th style="padding:16px 22px;font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;border-bottom:1.5px solid #e2e8f0;min-width:320px">Nama Acara &amp; Peserta</th>
                    <th style="padding:16px 22px;font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;border-bottom:1.5px solid #e2e8f0;min-width:260px">Waktu Pelaksanaan <span class="fw-normal opacity-75">(24 Jam)</span></th>
                    <th style="padding:16px 22px;font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;border-bottom:1.5px solid #e2e8f0;width:120px">Status</th>
                    <th style="padding:16px 22px;font-size:10.5px;letter-spacing:0.4px;color:#475569;font-weight:700;border-bottom:1.5px solid #e2e8f0;width:240px">Aksi</th>
                </tr>
            </thead>
            <tbody style="background:#fff">
                <?php foreach ($reservasi as $r):
                    $fasilitas_arr = json_decode($r['fasilitas_pendukung'] ?? '[]', true);
                    $jml_fasilitas = is_array($fasilitas_arr) ? count($fasilitas_arr) : 0;
                    $ruang_fas = $r['fasilitas_ruangan'] ?? '';
                    $jml_ruang_fas = $ruang_fas ? count(array_filter(array_map('trim', explode(',', $ruang_fas)))) : 0;
                    $total_fas = $jml_fasilitas + $jml_ruang_fas;
                ?>
                <tr style="border-bottom:1px solid #f1f5f9;transition:background .18s ease" onmouseover="this.style.background='#fbfcff'" onmouseout="this.style.background='#ffffff'">
                    <td style="padding:20px 22px">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5" style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#3B5FC7,#1F3A8B);color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px">
                                <i class="bi bi-person-vcard-fill"></i>
                            </div>
                            <div style="min-width:0">
                                <div class="badge rounded-pill mb-1.5 fw-bold d-inline-flex align-items-center" style="font-size:9.5px;background:rgba(37,99,235,0.1);color:#1d4ed8;padding:4px 10px;letter-spacing:0.3px">
                                    <i class="bi bi-receipt me-1" style="font-size:9px"></i><?= $r['kode_reservasi'] ?>
                                </div>
                                <div class="fw-bold mb-0.5" style="font-size:12.5px;color:#0f172a;line-height:1.4"><?= $r['pemohon'] ?></div>
                                <div style="font-size:10.5px;color:#64748b;line-height:1.65;margin-top:3px">
                                    <span class="d-block">NIP. <?= $r['nip'] ?></span>
                                    <span class="d-block text-truncate" style="color:#475569"><i class="bi bi-briefcase me-1" style="font-size:9px"></i><?= $r['unit_kerja'] ?: $r['unit_pemohon'] ?></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:20px 22px">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5" style="width:40px;height:40px;border-radius:12px;background:rgba(124,58,237,0.1);color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:15px">
                                <i class="bi bi-door-open"></i>
                            </div>
                            <div style="min-width:0">
                                <div class="fw-bold mb-0.5" style="font-size:12.5px;color:#0f172a;line-height:1.4"><?= $r['nama_ruangan'] ?></div>
                                <div style="font-size:10.5px;color:#64748b;line-height:1.65;margin-top:3px">
                                    <span class="d-block"><i class="bi bi-layers me-1" style="font-size:9px"></i><?= $r['lantai'] ?></span>
                                    <span class="d-block" style="color:#475569">Kapasitas: <span class="fw-semibold"><?= $r['kapasitas'] ?> orang</span></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:20px 22px;min-width:320px">
                        <div class="fw-bold mb-1.5" style="font-size:13px;color:#0f172a;line-height:1.5"><?= $r['nama_acara'] ?></div>
                        <div class="text-muted mb-3" style="font-size:10.5px;color:#64748b;line-height:1.7;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                            <?= $r['deskripsi'] ?>
                        </div>
                        <div class="d-flex gap-2.5 flex-wrap">
                            <span class="badge rounded-pill" style="font-size:10px;padding:5px 11px;background:rgba(59,130,246,0.1);color:#2563eb;font-weight:600">
                                <i class="bi bi-people-fill me-1"></i><?= $r['estimasi_peserta'] ?> Peserta
                            </span>
                            <span class="badge rounded-pill" style="font-size:10px;padding:5px 11px;background:rgba(16,185,129,0.1);color:#059669;font-weight:600">
                                <i class="bi bi-collection-play-fill me-1"></i><?= $total_fas ?> Fasilitas
                            </span>
                        </div>
                    </td>
                    <td style="padding:20px 22px">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-start gap-2.5" style="padding:11px 14px;border-radius:12px;background:rgba(16,185,129,0.08);border:1.5px solid rgba(16,185,129,0.18)">
                                <i class="bi bi-play-circle-fill text-success mt-0.5 flex-shrink-0" style="font-size:15px"></i>
                                <div style="min-width:0;flex:1">
                                    <div style="font-size:10px;color:#059669;font-weight:800;letter-spacing:0.5px;margin-bottom:4px;text-transform:uppercase">Mulai</div>
                                    <div style="font-size:12px;color:#0f172a;font-weight:700;line-height:1.55">
                                        <span class="d-block"><?= format_date($r['tanggal_mulai'], false) ?></span>
                                        <span class="d-block" style="color:#1e293b"><?= format_time($r['jam_mulai']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start gap-2.5" style="padding:11px 14px;border-radius:12px;background:rgba(239,68,68,0.07);border:1.5px solid rgba(239,68,68,0.18)">
                                <i class="bi bi-stop-circle-fill text-danger mt-0.5 flex-shrink-0" style="font-size:15px"></i>
                                <div style="min-width:0;flex:1">
                                    <div style="font-size:10px;color:#dc2626;font-weight:800;letter-spacing:0.5px;margin-bottom:4px;text-transform:uppercase">Selesai</div>
                                    <div style="font-size:12px;color:#0f172a;font-weight:700;line-height:1.55">
                                        <span class="d-block"><?= format_date($r['tanggal_selesai'], false) ?></span>
                                        <span class="d-block" style="color:#1e293b"><?= format_time($r['jam_selesai']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:20px 22px">
                        <?= status_badge($r['status']) ?>
                    </td>
                    <td style="padding:20px 22px">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="detail.php?id=<?= $r['id'] ?>" class="btn btn-sm fw-semibold" style="border-radius:10px;padding:7px 13px;font-size:11px;background:rgba(59,95,199,0.1);color:#1F3A8B;border:none">
                                <i class="bi bi-eye me-1"></i>Detail
                            </a>
                            <?php if ($isAdmin && $r['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-sm fw-semibold" style="border-radius:10px;padding:7px 13px;font-size:11px;background:rgba(16,185,129,0.1);color:#059669;border:none"
                                    onclick="bsConfirm({
                                        title:'Setujui Reservasi',
                                        message:'Setujui pengajuan reservasi ruangan <?= $r['kode_reservasi'] ?> ini?',
                                        variant:'success',
                                        onConfirm:function(){ location.href='<?= base_url('ruangan/action.php?action=setujui&id=' . $r['id']) ?>'; }
                                    })">
                                    <i class="bi bi-check-lg me-1"></i>Setujui
                                </button>
                                <button type="button" class="btn btn-sm fw-semibold" style="border-radius:10px;padding:7px 13px;font-size:11px;background:rgba(239,68,68,0.1);color:#dc2626;border:none"
                                    onclick="bsConfirm({
                                        title:'Tolak Reservasi',
                                        message:'Tolak pengajuan reservasi ruangan <?= $r['kode_reservasi'] ?> ini?',
                                        variant:'danger',
                                        onConfirm:function(){ location.href='<?= base_url('ruangan/action.php?action=tolak&id=' . $r['id']) ?>'; }
                                    })">
                                    <i class="bi bi-x-lg me-1"></i>Tolak
                                </button>
                            <?php endif; ?>
                            <?php if (!$isAdmin && $r['user_id'] == $user['id'] && in_array($r['status'], ['pending','ditolak'])): ?>
                                <a href="form.php?id=<?= $r['id'] ?>" class="btn btn-sm fw-semibold" style="border-radius:10px;padding:7px 13px;font-size:11px;background:rgba(37,99,235,0.1);color:#1d4ed8;border:none">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============ TAB 2: DAFTAR SARANA RUANGAN (Card Grid 3 Kolom) ============ -->
<?php if ($active_tab === 'sarana'): ?>
<div class="card border-0 shadow-sm mb-4" style="border-radius:18px;overflow:hidden;border:1.5px solid #e2e8f0;border-top:none;background:transparent;box-shadow:none">
    <div class="card-body" style="background:transparent;padding:0">
        <?php if (empty($ruangan_grid)): ?>
            <div class="text-center py-5 bg-white rounded-4">
                <i class="bi bi-buildings display-5 d-block mb-3 opacity-25 text-muted"></i>
                <div class="fw-bold" style="font-size:13.5px;color:#0B1C48">Data ruangan belum tersedia</div>
            </div>
        <?php else: ?>
        <div class="row g-4 mb-4">
            <?php foreach ($ruangan_grid as $rg):
                $fasil = $rg['fasilitas'];
                $fasil_arr = array_filter(array_map('trim', explode(',', $fasil)));
                $status_txt = $rg['status'] === 'tersedia' ? 'Tersedia' : ucfirst($rg['status']);
                $status_class = $rg['status'] === 'tersedia' ? 'background:rgba(16,185,129,0.12);color:#059669' : 'background:rgba(251,191,36,0.15);color:#b45309';
            ?>
            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                <div class="ruangan-card" style="background:#fff;border:1.5px solid #e5e7eb;border-radius:18px;padding:22px 22px 18px;position:relative;transition:all 0.2s cubic-bezier(0.4,0,0.2,1);height:100%"
                     onmouseover="this.style.transform='translateY(-3px)';this.style.borderColor='#c4b5fd';this.style.boxShadow='0 10px 28px -10px rgba(124,58,237,0.28)'"
                     onmouseout="this.style.transform='translateY(0)';this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div class="fw-bold" style="font-size:12px;color:#7c3aed;letter-spacing:0.8px;font-family:'Courier New',monospace"><?= $rg['rng'] ?></div>
                        <span class="badge rounded-pill px-3 py-1.5 fw-bold" style="font-size:10.5px;<?= $status_class ?>">
                            <i class="bi bi-check-circle-fill me-1" style="font-size:9.5px"></i><?= $status_txt ?>
                        </span>
                    </div>

                    <div class="mb-2.5">
                        <h5 class="fw-bold mb-1" style="font-size:16.5px;color:#0f172a;margin:0"><?= $rg['nama_ruangan'] ?></h5>
                        <div class="text-muted" style="font-size:11.5px;color:#475569;line-height:1.5;font-weight:600;margin-top:4px">
                            <i class="bi bi-layers me-1" style="color:#7c3aed;font-size:11px"></i><?= $rg['lantai'] ?>
                            <span class="mx-2 text-slate-300" style="color:#cbd5e1">•</span>
                            <i class="bi bi-people-fill me-1" style="color:#7c3aed;font-size:11px"></i>Kapasitas: <span style="font-weight:700;color:#0f172a"><?= $rg['kapasitas'] ?> Orang</span>
                        </div>
                    </div>

                    <hr style="border-color:#f1f5f9;margin:16px 0 13px">

                    <div class="mb-4">
                        <div class="text-muted fw-bold mb-2" style="font-size:10.5px;color:#475569;letter-spacing:0.25px">Fasilitas Standar:</div>
                        <div class="d-flex flex-wrap gap-1.5">
                            <?php if (empty($fasil_arr)): ?>
                                <span class="text-muted small" style="font-size:10.5px">Tidak ada data fasilitas.</span>
                            <?php else: ?>
                                <?php foreach ($fasil_arr as $f): ?>
                                <span class="badge rounded-pill fw-medium" style="font-size:10px;padding:5px 10px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155">
                                    <i class="bi bi-check-lg me-1" style="color:#10b981;font-size:9.5px"></i><?= trim($f) ?>
                                </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="<?= base_url('ruangan/form.php?ruangan_id=' . $rg['id']) ?>" class="btn w-100 fw-semibold py-2.5" style="border-radius:12px;font-size:12px;background:linear-gradient(135deg,rgba(139,92,246,0.1),rgba(124,58,237,0.14));color:#7c3aed;border:1.5px solid rgba(167,139,250,0.4);transition:all 0.18s"
                       onmouseover="this.style.background='linear-gradient(135deg,#8b5cf6,#6d28d9)';this.style.color='#fff';this.style.borderColor='#7c3aed'"
                       onmouseout="this.style.background='linear-gradient(135deg,rgba(139,92,246,0.1),rgba(124,58,237,0.14))';this.style.color='#7c3aed';this.style.borderColor='rgba(167,139,250,0.4)'">
                        <i class="bi bi-calendar-plus-fill me-1.5"></i>Ajukan Reservasi Ruangan Ini
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============ TAB 3: JADWAL & KETERSEDIAAN ============ -->
<?php if ($active_tab === 'jadwal'): ?>
<div class="row g-4 mb-4">
    <div class="col-xl-4 col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:18px;overflow:hidden;background:linear-gradient(160deg,#fff7ed 0%,#ffedd5 100%);border:1.5px solid #fed7aa;padding:26px 24px;height:100%">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                <div class="fw-bold d-flex align-items-center gap-2" style="font-size:10.5px;color:#b45309;letter-spacing:0.7px;text-transform:uppercase">
                    <i class="bi bi-calendar-event-fill" style="font-size:13px"></i>Informasi
                </div>
                <div style="width:44px;height:44px;border-radius:13px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(217,119,6,0.12)">
                    <i class="bi bi-clock-history" style="color:#d97706;font-size:20px"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-2 mt-1" style="font-size:18px;color:#78350f;line-height:1.4">Jadwal &amp; Ketersediaan Ruang</h4>
            <p class="mb-4 text-muted" style="font-size:11.5px;color:#92400e;line-height:1.65">
                Cek ketersediaan ruangan secara visual melalui kalender interaktif. Lihat semua jadwal peminjaman mendatang beserta status approvalnya.
            </p>
            <div class="mb-4 p-3" style="background:rgba(255,255,255,0.7);border:1.5px solid rgba(251,191,36,0.4);border-radius:14px">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <i class="bi bi-info-circle-fill mt-0.5 flex-shrink-0" style="color:#b45309;font-size:13px"></i>
                    <div style="font-size:11px;color:#78350f;font-weight:500;line-height:1.6">
                        Untuk melihat detail per-tanggal dan per-ruangan, klik tombol di bawah. Jadwal otomatis sync dengan data reservasi.
                    </div>
                </div>
            </div>
            <a href="<?= base_url('kalender.php') ?>" class="btn w-100 fw-semibold py-3 shadow-sm" style="border-radius:13px;font-size:12.5px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none">
                <i class="bi bi-calendar3 me-1.5"></i> Buka Kalender Peminjaman Penuh &rarr;
            </a>
        </div>
    </div>
    <div class="col-xl-8 col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:18px;overflow:hidden;border:1.5px solid #e2e8f0">
            <div class="card-header bg-transparent border-bottom" style="padding:16px 22px;display:flex;align-items:center;justify-content:space-between;gap:8px">
                <div class="d-flex align-items-center gap-2.5">
                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div>
                        <div class="fw-bold mb-0" style="font-size:13.5px;color:#0B1C48;margin:0">Jadwal Kegiatan Mendatang</div>
                        <div class="text-muted" style="font-size:10.5px;color:#64748b">Maks. 12 kegiatan mendatang yang aktif</div>
                    </div>
                </div>
            </div>
            <?php if (empty($jadwal_ruangan)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-5 d-block mb-3 opacity-25 text-muted"></i>
                    <div class="fw-bold mb-1" style="font-size:13px;color:#0B1C48">Belum ada jadwal kegiatan</div>
                    <div class="text-muted" style="font-size:11px">Semua ruangan tersedia untuk periode ini.</div>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="min-width:720px">
                    <thead style="background:linear-gradient(180deg,#fffbeb,#fef3c7)">
                        <tr>
                            <th style="padding:12px 18px;font-size:10.5px;letter-spacing:0.3px;color:#92400e;font-weight:700;border-bottom:1.5px solid #fde68a">Tanggal</th>
                            <th style="padding:12px 18px;font-size:10.5px;letter-spacing:0.3px;color:#92400e;font-weight:700;border-bottom:1.5px solid #fde68a">Ruangan</th>
                            <th style="padding:12px 18px;font-size:10.5px;letter-spacing:0.3px;color:#92400e;font-weight:700;border-bottom:1.5px solid #fde68a">Kegiatan &amp; Pemohon</th>
                            <th style="padding:12px 18px;font-size:10.5px;letter-spacing:0.3px;color:#92400e;font-weight:700;border-bottom:1.5px solid #fde68a">Jam</th>
                            <th style="padding:12px 18px;font-size:10.5px;letter-spacing:0.3px;color:#92400e;font-weight:700;border-bottom:1.5px solid #fde68a;min-width:100px">Status</th>
                        </tr>
                    </thead>
                    <tbody style="background:#fff">
                        <?php foreach ($jadwal_ruangan as $j): ?>
                        <tr style="border-bottom:1px solid #fef3c7">
                            <td style="padding:13px 18px">
                                <div class="fw-bold" style="font-size:12px;color:#78350f"><?= format_date($j['tanggal_mulai'], false) ?></div>
                                <?php if ($j['tanggal_mulai'] != $j['tanggal_selesai']): ?>
                                    <div class="text-muted" style="font-size:10px;color:#92400e">s/d <?= format_date($j['tanggal_selesai'], false) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:13px 18px">
                                <div class="fw-bold" style="font-size:11.5px;color:#0f172a;line-height:1.3"><?= $j['nama_ruangan'] ?></div>
                                <div class="text-muted" style="font-size:10px;color:#64748b"><?= $j['lantai'] ?></div>
                            </td>
                            <td style="padding:13px 18px;min-width:240px">
                                <div class="fw-bold mb-0.5" style="font-size:11.5px;color:#0f172a;line-height:1.4"><?= $j['nama_acara'] ?></div>
                                <div class="text-muted" style="font-size:10px;color:#475569"><i class="bi bi-person me-1"></i><?= $j['pemohon'] ?></div>
                            </td>
                            <td style="padding:13px 18px">
                                <div style="font-size:11.5px;color:#0f172a;font-weight:600"><?= format_time($j['jam_mulai']) ?> - <?= format_time($j['jam_selesai']) ?> WIB</div>
                            </td>
                            <td style="padding:13px 18px"><?= status_badge($j['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
