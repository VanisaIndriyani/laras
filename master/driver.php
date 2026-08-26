<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_admin();

$page_title = 'Master Driver';
$active_menu = 'master-driver';

try {
    $db_name = DB_NAME;
    $table_exists = db()->fetchOne(
        "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver'",
        [$db_name]
    );
    if (empty($table_exists['cnt'])) {
        db()->exec("CREATE TABLE driver (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nama_driver VARCHAR(120) NOT NULL,
            no_wa VARCHAR(30) DEFAULT NULL,
            status ENUM('tersedia','bertugas','izin','sakit','libur') DEFAULT 'tersedia',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->insert('driver', ['nama_driver'=>'Driver 1','no_wa'=>'081227889901','status'=>'tersedia']);
        db()->insert('driver', ['nama_driver'=>'Driver 2','no_wa'=>'081392114402','status'=>'tersedia']);
        db()->insert('driver', ['nama_driver'=>'Driver 3','no_wa'=>'081804556603','status'=>'bertugas']);
        db()->insert('driver', ['nama_driver'=>'Driver 4','no_wa'=>'085729334404','status'=>'tersedia']);
    }
} catch (Exception $ex) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    try {
        $data = [
            'nama_driver' => sanitize($_POST['nama_driver']),
            'no_wa' => sanitize($_POST['no_wa'] ?? null),
            'status' => sanitize($_POST['status']),
        ];
        if (!$data['nama_driver']) throw new Exception('Nama Driver wajib diisi.');
        if ($act === 'tambah') {
            db()->insert('driver', $data);
            set_flash('success', 'Driver baru ditambahkan.');
        } elseif ($act === 'edit') {
            $id = (int)$_POST['id'];
            db()->update('driver', $data, 'id = ?', [$id]);
            set_flash('success', 'Data driver diperbarui.');
        } elseif ($act === 'hapus') {
            db()->delete('driver', 'id = ?', [(int)$_POST['id']]);
            set_flash('success', 'Data driver dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
    }
    redirect(base_url('master/driver.php'));
}

require_once __DIR__ . '/../partials/header.php';

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'semua';
$where = ['1=1'];
$params = [];
if (!empty($search)) {
    $where[] = "(nama_driver LIKE ? OR no_wa LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($filter !== 'semua') {
    $where[] = "status = ?";
    $params[] = $filter;
}
$whereSql = implode(' AND ', $where);
$drivers = db()->fetchAll("SELECT * FROM driver WHERE {$whereSql} ORDER BY nama_driver", $params);
$total_all = db()->count('driver');
$total_tersedia = db()->count('driver', "status = 'tersedia'");
$total_bertugas = db()->count('driver', "status = 'bertugas'");
$total_izin = db()->count('driver', "status = 'izin' OR status = 'sakit' OR status = 'libur'");
$statusOps = ['tersedia','bertugas','izin','sakit','libur'];
?>

<style>
.section-card {
    border-radius:14px;
    border:1.5px solid #e5edf8;
    background:linear-gradient(180deg,#fafcff,#ffffff);
    padding:14px 16px 6px;
    margin-bottom:12px;
}
.section-card-title {
    font-size:10.5px;
    font-weight:800;
    letter-spacing:0.5px;
    text-transform:uppercase;
    color:#0B1C48;
    margin:0 0 10px 0;
    display:flex;
    align-items:center;
    gap:7px;
    padding-bottom:8px;
    border-bottom:1px dashed #dbe6f5;
}
.section-dot {
    width:8px;height:8px;border-radius:50%;
    background:linear-gradient(135deg,#0B1C48,#3B5FC7);
    box-shadow:0 0 0 3px rgba(59,95,199,0.12);
}
.section-dot-green { background:linear-gradient(135deg,#059669,#10b981); box-shadow:0 0 0 3px rgba(16,185,129,0.12); }
.section-dot-amber { background:linear-gradient(135deg,#b45309,#f59e0b); box-shadow:0 0 0 3px rgba(245,158,11,0.12); }
.floating-label-wrap { position:relative; }
.floating-label-wrap.with-icon label.float-label { left: 34px !important; right: 14px !important; }
.floating-label-wrap .form-control,
.floating-label-wrap .form-select {
    padding-top:1.1rem;
    padding-bottom:0.5rem;
    padding-left: 34px;
    font-size:11.5px;
    border-radius:11px;
    border:1.5px solid #dbe6f5;
    background:#fff;
    transition:all .15s;
}
.floating-label-wrap.no-icon .form-control,
.floating-label-wrap.no-icon .form-select {
    padding-left: 14px;
}
.floating-label-wrap .form-control:focus,
.floating-label-wrap .form-select:focus {
    border-color:#3B5FC7;
    box-shadow:0 0 0 3px rgba(59,95,199,0.12);
}
.floating-label-wrap label.float-label {
    position:absolute;
    top:50%;
    left:14px;
    right:14px;
    transform:translateY(-50%);
    transition:all .12s ease-out;
    pointer-events:none;
    color:#94a3b8;
    font-size:11px;
    font-weight:600;
    background:transparent;
    padding:0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: auto;
}
.floating-label-wrap.no-icon label.float-label { left: 14px !important; }
.floating-label-wrap .form-control:not(:placeholder-shown) + label.float-label,
.floating-label-wrap .form-control:focus + label.float-label,
.floating-label-wrap .form-control.is-filled + label.float-label,
.floating-label-wrap .form-select:focus + label.float-label,
.floating-label-wrap .form-select.is-filled + label.float-label {
    top:5px !important;
    transform:none;
    font-size:9px;
    color:#0B1C48;
    font-weight:800;
    background:#fff;
    letter-spacing:0.3px;
    padding: 0 4px 0 2px;
    display: inline-block;
    width: max-content;
    max-width: calc(100% - 28px);
}
.modal-backdrop.show { opacity: 0.55 !important; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); background: rgba(15, 23, 42, 0.45); }
.modal { overflow-y: auto !important; }
.modal.fade:not(.show) { display: none !important; }
.modal.fade.show { display: block !important; }
.modal.fade .modal-dialog {
    transform: translate(0,-12px) scale(0.99);
    opacity: 0;
    transition: transform .18s cubic-bezier(.2,.8,.2,1), opacity .15s ease-out;
    margin: 22px auto !important;
    width: calc(100% - 16px);
    max-width: 560px;
}
.modal.fade.show .modal-dialog {
    transform: none;
    opacity: 1;
}
.modal-dialog-scrollable {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    margin: 22px auto !important;
    width: calc(100% - 16px);
    max-width: 560px;
    padding: 0;
}
.modal-dialog { margin: 22px auto !important; width: calc(100% - 16px); max-width: 560px; }
.modal-content {
    background-clip: padding-box;
    width: 100%;
    max-width: 560px;
    margin: 0 auto;
}
.modal-header {
    position: sticky; top: 0; z-index: 10; flex-shrink: 0;
    padding: 14px 18px !important;
}
.modal-footer {
    position: sticky; bottom: 0; z-index: 10;
    backdrop-filter: blur(10px);
    background: rgba(255,255,255,0.94) !important;
    flex-shrink: 0;
    padding: 10px 18px !important;
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
    gap: 10px !important;
    margin: 0;
    border-top: 1px solid #eef3fb;
}
.modal-footer > * { margin: 0 !important; }
.modal-dialog-scrollable .modal-body {
    flex: 1 1 auto;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    max-height: calc(100vh - 230px);
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
    padding: 13px 18px 2px !important;
    max-width: 560px;
    width: 100%;
    margin: 0 auto;
}
.modal-dialog-scrollable .modal-content {
    max-height: calc(100vh - 44px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    width: 100%;
}
.modal-dialog-scrollable .modal-body::-webkit-scrollbar { width: 7px; }
.modal-dialog-scrollable .modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
.section-card { scroll-margin-top: 70px; width: 100%; max-width: 520px; margin: 0 auto 10px auto; }
.section-card .row { max-width: 520px; width: 100%; margin: 0 auto; }
.section-card-title { width: 100%; }
.floating-label-wrap { margin-bottom: 8px !important; width: 100%; }
.floating-label-wrap .form-control,
.floating-label-wrap .form-select { width: 100%; }
body.modal-open { overflow: hidden !important; }
body.modal-open .sidebar, body.modal-open .topbar { padding-right: 0 !important; }
.status-tersedia { background:rgba(16,185,129,0.1);color:#047857;border:1px solid rgba(16,185,129,0.22); }
.status-bertugas { background:rgba(37,99,235,0.1);color:#1d4ed8;border:1px solid rgba(37,99,235,0.22); }
.status-izin,.status-sakit,.status-libur { background:rgba(100,116,139,0.1);color:#475569;border:1px solid rgba(100,116,139,0.22); }
.status-sakit { background:rgba(239,68,68,0.08);color:#dc2626;border:1px solid rgba(239,68,68,0.2); }
.driver-avatar {
    width:44px;height:44px;border-radius:13px;
    background:linear-gradient(135deg,#1F3A8B,#3B5FC7);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-weight:900;font-size:15px;letter-spacing:0.3px;
    box-shadow:0 3px 10px rgba(31,58,139,0.18);
    flex-shrink:0;
}
.filter-pill {
    display:inline-flex;align-items:center;gap:5px;
    padding:6px 13px;border-radius:999px;
    font-size:10px;font-weight:800;letter-spacing:0.2px;
    transition:all .15s;text-decoration:none;
    border:1.5px solid transparent;
}
.filter-pill.active {
    background:linear-gradient(135deg,#0B1C48,#1F3A8B 55%,#3B5FC7);
    color:#fff;box-shadow:0 4px 12px rgba(11,28,72,0.22);
    border-color:transparent;
}
.filter-pill:not(.active):hover {
    background:rgba(59,95,199,0.06);color:#1F3A8B;border-color:rgba(59,95,199,0.2);
}
</style>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
    <div>
        <h4><i class="bi bi-person-rolodex me-2" style="color:#3B5FC7"></i>Master Data Driver</h4>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <a class="breadcrumb-item" href="#">Master Data</a>
            <span class="breadcrumb-item active">Driver</span>
        </nav>
    </div>
    <button class="btn fw-bold" data-bs-toggle="modal" data-bs-target="#tambahModal" style="background:linear-gradient(135deg,#0B1C48,#1F3A8B 55%,#3B5FC7);border:none;box-shadow:0 4px 12px rgba(11,28,72,0.22);border-radius:13px;font-size:11.5px;padding:10px 20px;color:#fff">
        <i class="bi bi-person-add me-1.5"></i>Tambah Driver
    </button>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="padding:14px 18px;border-radius:16px">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label" style="font-size:9.5px;margin-bottom:3px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#64748b">Total Driver</div>
                    <div class="stat-value" style="font-size:26px;font-weight:900;color:#0f172a"><?= $total_all ?></div>
                </div>
                <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#1F3A8B,#3B5FC7);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(59,95,199,0.22)">
                    <i class="bi bi-people-fill" style="font-size:19px"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="padding:14px 18px;border-radius:16px">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label" style="font-size:9.5px;margin-bottom:3px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#059669">Tersedia</div>
                    <div class="stat-value" style="font-size:26px;font-weight:900;color:#047857"><?= $total_tersedia ?></div>
                </div>
                <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(16,185,129,0.22)">
                    <i class="bi bi-check2-circle" style="font-size:19px"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="padding:14px 18px;border-radius:16px">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label" style="font-size:9.5px;margin-bottom:3px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#1d4ed8">Bertugas</div>
                    <div class="stat-value" style="font-size:26px;font-weight:900;color:#1d4ed8"><?= $total_bertugas ?></div>
                </div>
                <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(37,99,235,0.22)">
                    <i class="bi bi-car-front-fill" style="font-size:19px"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="padding:14px 18px;border-radius:16px">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label" style="font-size:9.5px;margin-bottom:3px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#64748b">Izin / Sakit / Libur</div>
                    <div class="stat-value" style="font-size:26px;font-weight:900;color:#475569"><?= $total_izin ?></div>
                </div>
                <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#475569,#64748b);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(71,85,105,0.18)">
                    <i class="bi bi-calendar2-x-fill" style="font-size:19px"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:16px;border:1.5px solid #e5edf8;overflow:hidden">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3" style="background:linear-gradient(180deg,#fafcff,#fff);border-bottom:1.5px solid #eef3fb;padding:13px 18px">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <?php
                $pills = [
                    ['k'=>'semua','label'=>'Semua','count'=>$total_all,'icon'=>'bi-grid-3x3-gap-fill'],
                    ['k'=>'tersedia','label'=>'Tersedia','count'=>$total_tersedia,'icon'=>'bi-check2-circle'],
                    ['k'=>'bertugas','label'=>'Bertugas','count'=>$total_bertugas,'icon'=>'bi-car-front-fill'],
                    ['k'=>'izin','label'=>'Tidak Aktif','count'=>$total_izin,'icon'=>'bi-calendar2-x-fill'],
                ];
                foreach ($pills as $p):
                    $qs = $_GET;
                    $qs['filter'] = $p['k'];
                    unset($qs['page']);
                    $active = $filter === $p['k'];
            ?>
            <a href="?<?= http_build_query($qs) ?>" class="filter-pill <?= $active ? 'active' : '' ?>" style="<?= $active ? '' : 'color:#64748b;background:#f8fafc;border:1.5px solid #e2e8f0;' ?>">
                <i class="bi <?= $p['icon'] ?>" style="font-size:9px"></i><?= $p['label'] ?>
                <span style="padding:1.5px 7px;border-radius:999px;font-size:8.5px;font-weight:800;<?= $active ? 'background:rgba(255,255,255,0.22);color:#fff' : 'background:rgba(59,95,199,0.1);color:#1F3A8B' ?>"><?= $p['count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="position:relative;max-width:290px;flex:1 1 180px;min-width:180px">
            <i class="bi bi-search" style="position:absolute;top:50%;left:11px;transform:translateY(-50%);color:#94a3b8;font-size:12px"></i>
            <form method="GET" id="formSearch" action="<?= base_url('master/driver.php') ?>" style="margin:0">
                <?php foreach ($_GET as $k=>$v) if ($k !== 'search'): ?>
                <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
                <?php endif; ?>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama driver / nomor WA..."
                    class="form-control form-control-sm" style="padding:7px 11px 7px 31px;border-radius:11px;font-size:10.5px;border:1.5px solid #dbe6f5;background:#fff"
                    onkeydown="if(event.key==='Enter')document.getElementById('formSearch').submit()">
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle" style="min-width:980px">
                <thead style="background:linear-gradient(180deg,#f8fafc,#f1f5f9);position:sticky;top:0;z-index:2">
                    <tr>
                        <th style="padding:13px 18px;font-size:10px;font-weight:800;letter-spacing:0.3px;color:#475569;border-bottom:1.5px solid #e2e8f0">Driver</th>
                        <th style="padding:13px 18px;font-size:10px;font-weight:800;letter-spacing:0.3px;color:#475569;border-bottom:1.5px solid #e2e8f0;width:190px">Kontak WhatsApp</th>
                        <th style="padding:13px 18px;font-size:10px;font-weight:800;letter-spacing:0.3px;color:#475569;border-bottom:1.5px solid #e2e8f0;width:170px">Status Ketersediaan</th>
                        <th style="padding:13px 18px;font-size:10px;font-weight:800;letter-spacing:0.3px;color:#475569;border-bottom:1.5px solid #e2e8f0;width:160px">Ditambahkan</th>
                        <th style="padding:13px 18px;font-size:10px;font-weight:800;letter-spacing:0.3px;color:#475569;border-bottom:1.5px solid #e2e8f0;width:140px;text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody style="background:#fff">
                    <?php if (count($drivers) === 0): ?>
                    <tr>
                        <td colspan="5" style="padding:70px 20px;text-align:center">
                            <div class="mb-3"><i class="bi bi-people-x" style="font-size:48px;color:#cbd5e1"></i></div>
                            <div class="fw-bold mb-1" style="color:#475569;font-size:13px">Belum Ada Data Driver</div>
                            <p class="mb-0 text-muted" style="font-size:11px">Klik tombol "Tambah Driver" untuk memasukkan data driver.</p>
                        </td>
                    </tr>
                    <?php else: foreach ($drivers as $d):
                        $inisial = strtoupper(substr($d['nama_driver'] ?? 'D', 0, 1));
                        $wa_link = !empty($d['no_wa']) ? 'https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $d['no_wa'])) : '#';
                    ?>
                    <tr style="border-bottom:1px solid #f1f5f9" onmouseover="this.style.background='#fbfcff'" onmouseout="this.style.background='#ffffff'">
                        <td style="padding:14px 18px">
                            <div class="d-flex align-items-center gap-3">
                                <div class="driver-avatar"><?= $inisial ?></div>
                                <div style="min-width:0">
                                    <div class="fw-extrabold mb-0.5" style="font-size:12.5px;color:#0f172a;line-height:1.3"><?= sanitize($d['nama_driver']) ?></div>
                                    <div style="font-size:9.5px;color:#64748b;font-weight:600">
                                        <i class="bi bi-person-badge-fill me-1" style="color:#3B5FC7"></i>Driver ID #<?= $d['id'] ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px 18px">
                            <?php if (!empty($d['no_wa'])): ?>
                                <a href="<?= $wa_link ?>" target="_blank" class="d-inline-flex align-items-center gap-2" style="text-decoration:none">
                                    <div style="width:30px;height:30px;border-radius:9px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center">
                                        <i class="bi bi-whatsapp" style="font-size:14px"></i>
                                    </div>
                                    <div>
                                        <div style="font-size:11.5px;font-weight:700;color:#0f172a"><?= sanitize($d['no_wa']) ?></div>
                                        <div style="font-size:8.5px;color:#10b981;font-weight:700;letter-spacing:0.2px">KLIK CHAT WHATSAPP</div>
                                    </div>
                                </a>
                            <?php else: ?>
                                <span style="font-size:10px;color:#94a3b8;font-style:italic">— Tidak ada nomor WA</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 18px">
                            <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-bold px-3 py-1.5 status-<?= $d['status'] ?>" style="font-size:9px;text-transform:capitalize;letter-spacing:0.25px">
                                <i class="bi <?= $d['status']==='tersedia'?'bi-check2-circle':($d['status']==='bertugas'?'bi-car-front-fill':'bi-calendar2-x-fill') ?>"></i>
                                <?= $d['status'] === 'izin' ? 'Izin' : ($d['status'] === 'libur' ? 'Libur' : ($d['status'] === 'sakit' ? 'Sakit' : ucfirst($d['status']))) ?>
                            </span>
                        </td>
                        <td style="padding:14px 18px">
                            <div style="font-size:10.5px;color:#475569;font-weight:700"><?= !empty($d['created_at']) ? format_datetime($d['created_at']) : '-' ?></div>
                        </td>
                        <td style="padding:14px 18px;text-align:right">
                            <div class="d-flex gap-1.5 align-items-center justify-content-end">
                                <?php if (!empty($d['no_wa'])): ?>
                                <a href="<?= $wa_link ?>" target="_blank" class="btn btn-sm fw-bold" style="background:rgba(16,185,129,0.1);color:#059669;border:none;border-radius:9px;padding:5.5px 10px;font-size:9.5px" title="WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                                <button class="btn btn-sm fw-bold" onclick='editD(<?= json_encode($d, JSON_NUMERIC_CHECK) ?>)'
                                    style="background:rgba(31,58,139,0.08);color:#1F3A8B;border:none;border-radius:9px;padding:5.5px 10px;font-size:9.5px" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="<?= base_url('master/driver.php') ?>" onsubmit="return bsConfirm('Hapus driver <?= sanitize($d['nama_driver']) ?>?')" style="display:inline">
                                    <input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button class="btn btn-sm fw-bold" style="background:rgba(239,68,68,0.08);color:#dc2626;border:none;border-radius:9px;padding:5.5px 10px;font-size:9.5px" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (count($drivers) > 0): ?>
    <div class="px-4 py-2.5 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background:linear-gradient(180deg,#fff,#fafcff);border-top:1px solid #f1f5f9">
        <div style="font-size:10px;color:#64748b;font-weight:700">
            Menampilkan <span class="fw-extrabold text-primary"><?= count($drivers) ?></span> dari total <?= $total_all ?> driver.
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
function fl_input_d($prefix, $name, $label, $type, $ph='', $icon='', $value='') {
    $id = "{$prefix}_{$name}";
    $hasIcon = !empty($icon);
    $wrapCls = $hasIcon ? 'floating-label-wrap with-icon' : 'floating-label-wrap no-icon';
    $iconHtml = $hasIcon ? "<i class='bi {$icon}' style='font-size:12px;color:#3B5FC7;position:absolute;top:50%;left:12px;transform:translateY(-50%);pointer-events:none;opacity:0.8;z-index:2'></i>" : '';
    $v = htmlspecialchars($value ?? '', ENT_QUOTES);
    $filledInit = ($value !== '' && $value !== null) ? 'is-filled' : '';
    echo "<div class='col-md-12'>
        <div class='{$wrapCls}' style='margin-bottom:10px'>
            {$iconHtml}
            <input type='{$type}' class='form-control {$filledInit}' name='{$name}' id='{$id}' placeholder=' ' value='{$v}'>
            <label class='float-label' for='{$id}'>" . htmlspecialchars($label) . "</label>
        </div>
    </div>";
}
?>

<div class="modal fade" id="tambahModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content" style="border-radius:17px;border:1.5px solid #e5edf8;box-shadow:0 20px 60px rgba(11,28,72,0.16);overflow:hidden">
    <div class="modal-header d-flex align-items-center" style="background:linear-gradient(135deg,#0B1C48,#1F3A8B 55%,#3B5FC7);color:#fff;border:none;padding:15px 20px">
        <div class="d-flex align-items-center gap-3" style="flex:1">
            <div style="width:37px;height:37px;border-radius:11px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.18)">
                <i class="bi bi-person-add" style="font-size:16px;color:#fff"></i>
            </div>
            <div>
                <h5 class="modal-title mb-0" style="font-size:13.5px;font-weight:800;letter-spacing:0.2px">Tambah Driver Baru</h5>
                <div style="font-size:9.5px;color:rgba(255,255,255,0.72);margin-top:2px;font-weight:600;letter-spacing:0.15px">Masukkan data driver & nomor WhatsApp yang bisa dihubungi</div>
            </div>
        </div>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form method="POST" action="<?= base_url('master/driver.php') ?>"><input type="hidden" name="act" value="tambah">
        <div class="modal-body" style="padding:16px 20px 4px;background:linear-gradient(180deg,#fafcff 0%,#ffffff 100%)">
            <div class="section-card">
                <div class="section-card-title"><span class="section-dot section-dot-green"></span>Data Identitas Driver</div>
                <div class="row g-2">
                    <?php fl_input_d('t','nama_driver','Nama Lengkap Driver *','text','Contoh: Supriyanto','bi-person-vcard-fill'); ?>
                    <?php fl_input_d('t','no_wa','Nomor WhatsApp (Aktif)','tel','08xxxxxxxxxx','bi-whatsapp'); ?>
                    <div class="col-md-12">
                        <div class="floating-label-wrap with-icon" style="margin-bottom:10px">
                            <i class="bi bi-flag-fill" style="font-size:12px;color:#f59e0b;position:absolute;top:50%;left:12px;transform:translateY(-50%);pointer-events:none;opacity:0.85;z-index:2"></i>
                            <select class="form-select" name="status" id="t_status">
                                <option value="" disabled selected hidden>&nbsp;</option>
                                <?php foreach ($statusOps as $s) echo "<option value='{$s}'".($s==='tersedia'?' selected':'').">" . ucfirst($s) . "</option>"; ?>
                            </select>
                            <label class="float-label" for="t_status">Status Ketersediaan</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="background:linear-gradient(180deg,#fff,#fafcff);border-top:1px solid #eef3fb;padding:11px 20px">
            <button type="button" class="btn fw-semibold" data-bs-dismiss="modal" style="border-radius:10px;font-size:10.5px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;padding:7.5px 17px">
                <i class="bi bi-x-lg me-1"></i>Batal
            </button>
            <button type="submit" class="btn fw-bold" style="border-radius:10px;font-size:10.5px;background:linear-gradient(135deg,#059669,#047857);border:none;color:#fff;padding:7.5px 19px;box-shadow:0 3px 10px rgba(5,150,105,0.22)">
                <i class="bi bi-save-fill me-1"></i>Simpan Driver
            </button>
        </div>
    </form>
</div></div></div>

<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content" style="border-radius:17px;border:1.5px solid #e5edf8;box-shadow:0 20px 60px rgba(11,28,72,0.16);overflow:hidden">
    <div class="modal-header d-flex align-items-center" style="background:linear-gradient(135deg,#1F3A8B,#3B5FC7 55%,#0ea5e9);color:#fff;border:none;padding:15px 20px">
        <div class="d-flex align-items-center gap-3" style="flex:1">
            <div style="width:37px;height:37px;border-radius:11px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.18)">
                <i class="bi bi-pencil-square" style="font-size:16px;color:#fff"></i>
            </div>
            <div>
                <h5 class="modal-title mb-0" style="font-size:13.5px;font-weight:800;letter-spacing:0.2px">Edit Data Driver</h5>
                <div style="font-size:9.5px;color:rgba(255,255,255,0.72);margin-top:2px;font-weight:600;letter-spacing:0.15px">Perbarui status ketersediaan & kontak driver</div>
            </div>
        </div>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form method="POST" action="<?= base_url('master/driver.php') ?>"><input type="hidden" name="act" value="edit"><input type="hidden" name="id" id="e_id">
        <div class="modal-body" style="padding:16px 20px 4px;background:linear-gradient(180deg,#fafcff 0%,#ffffff 100%)">
            <div class="section-card">
                <div class="section-card-title"><span class="section-dot section-dot-amber"></span>Perbarui Data Driver</div>
                <div class="row g-2">
                    <?php fl_input_d('e','nama_driver','Nama Lengkap Driver *','text','','bi-person-vcard-fill'); ?>
                    <?php fl_input_d('e','no_wa','Nomor WhatsApp (Aktif)','tel','','bi-whatsapp'); ?>
                    <div class="col-md-12">
                        <div class="floating-label-wrap with-icon" style="margin-bottom:10px">
                            <i class="bi bi-flag-fill" style="font-size:12px;color:#f59e0b;position:absolute;top:50%;left:12px;transform:translateY(-50%);pointer-events:none;opacity:0.85;z-index:2"></i>
                            <select class="form-select" name="status" id="e_status">
                                <option value="" disabled hidden>&nbsp;</option>
                                <?php foreach ($statusOps as $s) echo "<option value='{$s}'>" . ucfirst($s) . "</option>"; ?>
                            </select>
                            <label class="float-label" for="e_status">Status Ketersediaan</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="background:linear-gradient(180deg,#fff,#fafcff);border-top:1px solid #eef3fb;padding:11px 20px">
            <button type="button" class="btn fw-semibold" data-bs-dismiss="modal" style="border-radius:10px;font-size:10.5px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;padding:7.5px 17px">
                <i class="bi bi-x-lg me-1"></i>Batal
            </button>
            <button type="submit" class="btn fw-bold" style="border-radius:10px;font-size:10.5px;background:linear-gradient(135deg,#3B5FC7,#1F3A8B);border:none;color:#fff;padding:7.5px 19px;box-shadow:0 3px 10px rgba(59,95,199,0.22)">
                <i class="bi bi-save-fill me-1"></i>Perbarui Driver
            </button>
        </div>
    </form>
</div></div></div>

<script>
function refreshFilled(el) {
    if (!el) return;
    let val = (el.value ?? '').toString();
    if (el.type !== 'number') val = val.trim();
    if (val.length > 0) el.classList.add('is-filled'); else el.classList.remove('is-filled');
}
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.floating-label-wrap .form-control, .floating-label-wrap .form-select').forEach(function(el){
        refreshFilled(el);
        el.addEventListener('input', function(){ refreshFilled(el); });
        el.addEventListener('change', function(){ refreshFilled(el); });
        el.addEventListener('blur', function(){ refreshFilled(el); });
    });
});

function editD(d) {
    ['nama_driver','no_wa'].forEach(f => {
        const el = document.getElementById('e_' + f);
        if (el) {
            el.value = d[f] ?? '';
            setTimeout(function(){ refreshFilled(el); }, 0);
        }
    });
    const sel = document.getElementById('e_status');
    if (sel) {
        sel.selectedIndex = 0;
        if (d['status']) {
            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === d['status']) { sel.selectedIndex = i; break; }
            }
        }
        setTimeout(function(){ refreshFilled(sel); }, 0);
    }
    document.getElementById('e_id').value = d.id;
    const instance = bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal'));
    instance.show();
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
