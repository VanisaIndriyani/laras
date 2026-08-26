<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_admin();

$page_title = 'Master Kendaraan';
$active_menu = 'master-kendaraan';

$migration_cols = [
    'kode_bmn'                  => "ALTER TABLE kendaraan ADD COLUMN kode_bmn VARCHAR(60) DEFAULT NULL",
    'unit_pengguna'             => "ALTER TABLE kendaraan ADD COLUMN unit_pengguna VARCHAR(120) DEFAULT NULL",
    'pajak_stnk_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_stnk_jatuh_tempo DATE DEFAULT NULL",
    'pajak_tnkb_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_tnkb_jatuh_tempo DATE DEFAULT NULL",
    'terakhir_service'          => "ALTER TABLE kendaraan ADD COLUMN terakhir_service DATE DEFAULT NULL",
    'service_berikutnya'        => "ALTER TABLE kendaraan ADD COLUMN service_berikutnya DATE DEFAULT NULL",
    'catatan_service'           => "ALTER TABLE kendaraan ADD COLUMN catatan_service TEXT DEFAULT NULL",
    'driver_id'                 => "ALTER TABLE kendaraan ADD COLUMN driver_id INT DEFAULT NULL",
    'foto'                      => "ALTER TABLE kendaraan ADD COLUMN foto VARCHAR(255) DEFAULT NULL",
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    try {
        $data = [
            'no_plat' => sanitize($_POST['no_plat']),
            'merk' => sanitize($_POST['merk']),
            'tipe' => sanitize($_POST['tipe']),
            'tahun' => (int)$_POST['tahun'],
            'kapasitas' => (int)$_POST['kapasitas'],
            'status' => sanitize($_POST['status']),
            'driver' => sanitize($_POST['driver']),
            'no_hp_driver' => sanitize($_POST['no_hp_driver']),
            'driver_id' => !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null,
            'kode_bmn' => sanitize($_POST['kode_bmn'] ?? null),
            'unit_pengguna' => sanitize($_POST['unit_pengguna'] ?? null),
            'pajak_stnk_jatuh_tempo' => !empty($_POST['pajak_stnk_jatuh_tempo']) ? sanitize($_POST['pajak_stnk_jatuh_tempo']) : null,
            'pajak_tnkb_jatuh_tempo' => !empty($_POST['pajak_tnkb_jatuh_tempo']) ? sanitize($_POST['pajak_tnkb_jatuh_tempo']) : null,
            'terakhir_service' => !empty($_POST['terakhir_service']) ? sanitize($_POST['terakhir_service']) : null,
            'service_berikutnya' => !empty($_POST['service_berikutnya']) ? sanitize($_POST['service_berikutnya']) : null,
            'catatan_service' => sanitize($_POST['catatan_service'] ?? null),
        ];
        if (!$data['no_plat'] || !$data['merk'] || !$data['tipe']) throw new Exception('Lengkapi data wajib (*).');
        if ($act === 'tambah') {
            db()->insert('kendaraan', $data);
            set_flash('success', 'Kendaraan ditambahkan beserta data pajak & service.');
        } elseif ($act === 'edit') {
            $id = (int)$_POST['id'];
            db()->update('kendaraan', $data, 'id = ?', [$id]);
            set_flash('success', 'Data kendaraan (termasuk pajak & service) diperbarui.');
        } elseif ($act === 'hapus') {
            $id = (int)$_POST['id'];
            db()->delete('kendaraan', 'id = ?', [$id]);
            set_flash('success', 'Data kendaraan dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
    }
    redirect(base_url('master/kendaraan.php'));
}

require_once __DIR__ . '/../partials/header.php';

$kendaraan = db()->fetchAll("SELECT * FROM kendaraan ORDER BY no_plat");
$drivers_list = [];
try { $drivers_list = db()->fetchAll("SELECT id, nama_driver, no_wa, status FROM driver ORDER BY nama_driver"); } catch (Exception $ex) { $drivers_list = []; }
?>

<style>
.section-card {
    border-radius:12px;
    border:1.5px solid #e5edf8;
    background:linear-gradient(180deg,#fafcff,#ffffff);
    padding:10px 13px 3px;
    margin-bottom:10px;
}
.section-card-title {
    font-size:10px;
    font-weight:800;
    letter-spacing:0.5px;
    text-transform:uppercase;
    color:#0B1C48;
    margin:0 0 7px 0;
    display:flex;
    align-items:center;
    gap:6px;
    padding-bottom:6px;
    border-bottom:1px dashed #dbe6f5;
}
.section-dot {
    width:8px;height:8px;border-radius:50%;
    background:linear-gradient(135deg,#0B1C48,#3B5FC7);
    box-shadow:0 0 0 3px rgba(59,95,199,0.12);
}
.section-dot-amber { background:linear-gradient(135deg,#b45309,#f59e0b); box-shadow:0 0 0 3px rgba(245,158,11,0.12); }
.section-dot-purple { background:linear-gradient(135deg,#5b21b6,#7c3aed); box-shadow:0 0 0 3px rgba(124,58,237,0.12); }
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
.floating-label-wrap textarea.form-control + label.float-label {
    top:20px;
    transform:none;
}
.floating-label-wrap.with-icon textarea.form-control + label.float-label {
    left: 34px !important;
}
.floating-label-wrap .form-control:not(:placeholder-shown) + label.float-label,
.floating-label-wrap .form-control:focus + label.float-label,
.floating-label-wrap .form-control.is-filled + label.float-label,
.floating-label-wrap .form-select:focus + label.float-label,
.floating-label-wrap .form-select.is-filled + label.float-label,
.floating-label-wrap textarea.form-control:not(:placeholder-shown) + label.float-label,
.floating-label-wrap textarea.form-control:focus + label.float-label,
.floating-label-wrap textarea.form-control.is-filled + label.float-label {
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
.pill-badge-mini {
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:3px 8px;
    border-radius:999px;
    font-size:8.5px;
    font-weight:800;
    letter-spacing:0.2px;
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
    max-width: 860px;
}
.modal.fade.show .modal-dialog {
    transform: none;
    opacity: 1;
}
.modal-content {
    background-clip: padding-box;
    width: 100%;
    max-width: 860px;
    margin: 0 auto;
}
.modal-dialog-scrollable {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    margin: 22px auto !important;
    width: calc(100% - 16px);
    max-width: 860px;
    padding: 0;
}
.modal-dialog { margin: 22px auto !important; width: calc(100% - 16px); max-width: 860px; }
.modal-header {
    position: sticky; top: 0; z-index: 10; flex-shrink: 0;
    padding: 14px 20px !important;
}
.modal-footer {
    position: sticky; bottom: 0; z-index: 10;
    backdrop-filter: blur(10px);
    background: rgba(255,255,255,0.94) !important;
    flex-shrink: 0;
    padding: 10px 20px !important;
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
    padding: 13px 20px 2px !important;
    max-width: 860px;
    width: 100%;
    margin: 0 auto;
    position: relative;
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
.section-card { scroll-margin-top: 70px; width: 100%; max-width: 820px; margin: 0 auto 10px auto; }
.section-card .row { max-width: 820px; width: 100%; margin: 0 auto; }
.section-card-title { width: 100%; }
.floating-label-wrap { margin-bottom: 8px !important; width: 100%; }
.floating-label-wrap .form-control,
.floating-label-wrap .form-select { width: 100%; }
body.modal-open { overflow: hidden !important; }
body.modal-open .sidebar, body.modal-open .topbar { padding-right: 0 !important; }
</style>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
    <div>
        <h4><i class="bi bi-truck me-2" style="color:#10b981"></i>Master Data Kendaraan</h4>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <a class="breadcrumb-item" href="#">Master Data</a>
            <span class="breadcrumb-item active">Kendaraan</span>
        </nav>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahModal" style="background:linear-gradient(135deg,#059669,#047857);border:none;box-shadow:0 4px 12px rgba(5,150,105,0.25);border-radius:13px;font-size:11.5px;padding:10px 20px;font-weight:700">
        <i class="bi bi-plus-lg me-1.5"></i>Tambah Kendaraan
    </button>
</div>

<div class="row g-3 mb-3">
    <?php
        $jml = [
            'total' => count($kendaraan),
            'tersedia' => count(array_filter($kendaraan, fn($k)=>$k['status']==='tersedia')),
            'digunakan' => count(array_filter($kendaraan, fn($k)=>$k['status']==='digunakan')),
            'perawatan' => count(array_filter($kendaraan, fn($k)=>$k['status']==='perawatan')),
            'belum_atur_pajak' => count(array_filter($kendaraan, fn($k)=>empty($k['pajak_stnk_jatuh_tempo']) || empty($k['unit_pengguna'])))
        ];
    ?>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon blue"><i class="bi bi-car-front-fill"></i></div><div class="stat-label">Total</div><div class="stat-value"><?= $jml['total'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div><div class="stat-label">Tersedia</div><div class="stat-value"><?= $jml['tersedia'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon cyan"><i class="bi bi-car-front"></i></div><div class="stat-label">Digunakan</div><div class="stat-value"><?= $jml['digunakan'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon amber"><i class="bi bi-tools"></i></div><div class="stat-label">Perawatan</div><div class="stat-value"><?= $jml['perawatan'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#dc2626);color:#fff"><i class="bi bi-exclamation-triangle-fill"></i></div><div class="stat-label">Belum Lengkap</div><div class="stat-value"><?= $jml['belum_atur_pajak'] ?></div></div></div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:18px;border:1.5px solid #e5edf8;overflow:hidden">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:linear-gradient(180deg,#fafcff,#fff);border-bottom:1.5px solid #eef3fb;padding:14px 22px">
        <h6 class="card-title mb-0 d-flex align-items-center gap-2" style="font-size:12px;letter-spacing:0.3px;color:#0f172a;font-weight:800">
            <i class="bi bi-list-columns-reverse me-1" style="color:#3B5FC7"></i>Daftar Unit Kendaraan Dinas
        </h6>
        <div style="font-size:9.5px;color:#64748b;font-weight:600">
            <i class="bi bi-info-circle me-1" style="color:#3B5FC7"></i>Klik <strong>Edit</strong> untuk mengisi data pajak, kode BMN, unit pengguna & jadwal service
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle" style="min-width:1550px">
                <thead style="background:linear-gradient(180deg,#f8fafc,#f1f5f9);position:sticky;top:0;z-index:2">
                    <tr>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0">No. Plat</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0">Merk / Tipe</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0;min-width:90px">Thn</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0;min-width:130px">Kode BMN</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0;min-width:170px">Unit Pengguna</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0;min-width:110px">Kapasitas</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0;min-width:130px">Driver</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#0B1C48;border-bottom:1.5px solid #e2e8f0;background:rgba(59,95,199,0.04)">Status Pajak</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#6d28d9;border-bottom:1.5px solid #e2e8f0;background:rgba(124,58,237,0.04)">Service</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0;min-width:100px">Status</th>
                        <th style="padding:14px 18px;font-size:10.5px;font-weight:800;color:#475569;border-bottom:1.5px solid #e2e8f0;width:130px">Aksi</th>
                    </tr>
                </thead>
                <tbody style="background:#fff">
                    <?php foreach ($kendaraan as $k):
                        $p_info = pajak_status_info($k['pajak_stnk_jatuh_tempo'] ?? null, $k['pajak_tnkb_jatuh_tempo'] ?? null);
                        $s_info = service_status_info($k['service_berikutnya'] ?? null, $k['terakhir_service'] ?? null);
                        $p_cls = [
                            'danger'  => 'background:rgba(239,68,68,0.09);color:#dc2626;border:1px solid rgba(239,68,68,0.22)',
                            'warning' => 'background:rgba(245,158,11,0.09);color:#b45309;border:1px solid rgba(245,158,11,0.22)',
                            'success' => 'background:rgba(16,185,129,0.08);color:#047857;border:1px solid rgba(16,185,129,0.22)',
                            'secondary' => 'background:#f8fafc;color:#64748b;border:1px solid #e2e8f0'
                        ];
                        $p_icon = [
                            'danger'  => 'bi-x-circle-fill',
                            'warning' => 'bi-exclamation-triangle-fill',
                            'success' => 'bi-check-circle-fill',
                            'secondary' => 'bi-question-circle-fill'
                        ];
                        $s_cls = [
                            'danger'  => 'background:rgba(139,92,246,0.1);color:#6d28d9;border:1px solid rgba(139,92,246,0.24)',
                            'warning' => 'background:rgba(139,92,246,0.07);color:#7c3aed;border:1px solid rgba(139,92,246,0.2)',
                            'success' => 'background:rgba(16,185,129,0.07);color:#047857;border:1px solid rgba(16,185,129,0.2)',
                            'secondary' => 'background:#faf5ff;color:#94a3b8;border:1px solid #ddd6fe'
                        ];
                    ?>
                    <tr style="border-bottom:1px solid #f1f5f9" onmouseover="this.style.background='#fbfcff'" onmouseout="this.style.background='#ffffff'">
                        <td style="padding:15px 18px">
                            <span style="font-weight:800;color:#065f46;font-size:12px;letter-spacing:0.3px"><?= $k['no_plat'] ?></span>
                        </td>
                        <td style="padding:15px 18px">
                            <div style="font-size:11.5px"><strong style="color:#0f172a"><?= sanitize($k['merk']) ?></strong> <?= sanitize($k['tipe']) ?></div>
                        </td>
                        <td style="padding:15px 18px;font-size:11px;color:#475569;font-weight:700"><?= $k['tahun'] ?: '-' ?></td>
                        <td style="padding:15px 18px">
                            <?php if (!empty($k['kode_bmn'])): ?>
                                <span style="font-size:10.5px;color:#1F3A8B;font-weight:800;letter-spacing:0.3px;text-transform:uppercase"><?= sanitize($k['kode_bmn']) ?></span>
                            <?php else: ?>
                                <span class="pill-badge-mini" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a">
                                    <i class="bi bi-dash-circle-dotted" style="font-size:8px"></i>Belum diisi
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:15px 18px">
                            <?php if (!empty($k['unit_pengguna'])): ?>
                                <div style="font-size:10.5px;color:#475569;font-weight:700;line-height:1.4"><?= sanitize($k['unit_pengguna']) ?></div>
                            <?php else: ?>
                                <span class="pill-badge-mini" style="background:rgba(239,68,68,0.08);color:#dc2626;border:1px solid rgba(239,68,68,0.2)">
                                    <i class="bi bi-person-x-fill" style="font-size:8px"></i>Belum ditetapkan
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:15px 18px;font-size:10.5px;color:#475569;font-weight:700"><?= (int)$k['kapasitas'] ?> org</td>
                        <td style="padding:15px 18px">
                            <?php if (!empty($k['driver'])): ?>
                                <div style="font-size:10.5px;color:#0f172a;font-weight:700"><?= sanitize($k['driver']) ?></div>
                                <?php if (!empty($k['no_hp_driver'])): ?>
                                    <div style="font-size:9px;color:#64748b;margin-top:1px"><i class="bi bi-whatsapp me-1" style="color:#10b981"></i><?= sanitize($k['no_hp_driver']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:9.5px;color:#94a3b8;font-style:italic">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:15px 18px;background:rgba(59,95,199,0.02)">
                            <span class="pill-badge-mini" style="<?= $p_cls[$p_info['cls']] ?? $p_cls['secondary'] ?>">
                                <i class="bi <?= $p_icon[$p_info['cls']] ?? $p_icon['secondary'] ?>" style="font-size:8px"></i><?= $p_info['label'] ?>
                            </span>
                            <?php if (!empty($k['pajak_stnk_jatuh_tempo'])): ?>
                                <div style="font-size:8.5px;color:#64748b;margin-top:4px">
                                    <i class="bi bi-stopwatch me-0.5"></i>STNK: <?= format_date($k['pajak_stnk_jatuh_tempo'], false) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:15px 18px;background:rgba(124,58,237,0.02)">
                            <span class="pill-badge-mini" style="<?= $s_cls[$s_info['cls']] ?? $s_cls['secondary'] ?>">
                                <i class="bi bi-wrench-adjustable" style="font-size:8px"></i><?= $s_info['label'] ?>
                            </span>
                            <?php if (!empty($k['service_berikutnya'])): ?>
                                <div style="font-size:8.5px;color:#7c3aed;margin-top:4px;font-weight:700">
                                    <i class="bi bi-arrow-up-right-circle me-0.5"></i>Next: <?= format_date($k['service_berikutnya'], false) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:15px 18px"><?= status_badge($k['status']) ?></td>
                        <td style="padding:15px 18px">
                            <div class="d-flex gap-1.5 align-items-center justify-content-start">
                                <button class="btn btn-sm fw-bold" onclick='editK(<?= json_encode($k, JSON_NUMERIC_CHECK) ?>)'
                                    style="background:rgba(31,58,139,0.08);color:#1F3A8B;border:none;border-radius:9px;padding:6px 10px;font-size:10px"
                                    title="Edit data termasuk pajak, BMN, unit, service">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </button>
                                <form method="POST" action="<?= base_url('master/kendaraan.php') ?>" onsubmit="return bsConfirm('Hapus kendaraan <?= sanitize($k['no_plat']) ?>? Data reservasi terkait tidak ikut terhapus.')" style="display:inline">
                                    <input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <button class="btn btn-sm fw-bold" style="background:rgba(239,68,68,0.08);color:#dc2626;border:none;border-radius:9px;padding:6px 10px;font-size:10px" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$basicFields = [
    ['name'=>'no_plat','label'=>'Nomor Plat *','type'=>'text','ph'=>'Contoh: AB 1234 CD','icon'=>'bi-signpost-split-fill'],
    ['name'=>'merk','label'=>'Merk *','type'=>'text','ph'=>'Contoh: Toyota','icon'=>'bi-bag-fill'],
    ['name'=>'tipe','label'=>'Tipe / Model *','type'=>'text','ph'=>'Contoh: Innova Zenix Q Hybrid','icon'=>'bi-car-front-fill'],
    ['name'=>'tahun','label'=>'Tahun Produksi','type'=>'number','ph'=>'2024','icon'=>'bi-calendar3'],
    ['name'=>'kapasitas','label'=>'Kapasitas Penumpang (org)','type'=>'number','ph'=>'7','icon'=>'bi-people-fill'],
];
$bmnFields = [
    ['name'=>'kode_bmn','label'=>'Kode BMN (Barang Milik Negara)','type'=>'text','ph'=>'Contoh: 3100102001xxx','icon'=>'bi-upc-scan'],
    ['name'=>'unit_pengguna','label'=>'Unit Pengguna / Bidang','type'=>'text','ph'=>'Contoh: Bidang Investigasi','icon'=>'bi-building-fill-check'],
];
$pajakFields = [
    ['name'=>'pajak_stnk_jatuh_tempo','label'=>'STNK 1 Tahunan — Jatuh Tempo','type'=>'date','ph'=>'YYYY-MM-DD','icon'=>'bi-stopwatch'],
    ['name'=>'pajak_tnkb_jatuh_tempo','label'=>'TNKB 5 Tahunan — Jatuh Tempo','type'=>'date','ph'=>'YYYY-MM-DD','icon'=>'bi-calendar2-x-fill'],
];
$serviceFields = [
    ['name'=>'terakhir_service','label'=>'Tanggal Service Terakhir','type'=>'date','ph'=>'YYYY-MM-DD','icon'=>'bi-wrench-adjustable-circle-fill'],
    ['name'=>'service_berikutnya','label'=>'Jadwal Service Berikutnya','type'=>'date','ph'=>'YYYY-MM-DD','icon'=>'bi-arrow-repeat'],
    ['name'=>'catatan_service','label'=>'Catatan Service (sparepart / oli / kilometer)','type'=>'textarea','ph'=>'Contoh: Ganti oli shell helix 5w30 + filter udara pada KM 45.000','icon'=>'bi-journal-text','fullwidth'=>true],
];
$statusOps = ['tersedia','digunakan','perawatan'];

function fl_input($prefix, $f, $value = '') {
    $id = "{$prefix}_{$f['name']}";
    $isTextarea = ($f['type'] ?? 'text') === 'textarea';
    $col = !empty($f['fullwidth']) ? 'col-md-12' : 'col-md-6';
    $placeholderAttr = "placeholder=' '";
    $valueAttr = '';
    if (!$isTextarea) {
        $v = htmlspecialchars($value ?? '', ENT_QUOTES);
        $valueAttr = "value='{$v}'";
    }
    $hasIcon = !empty($f['icon']);
    $wrapCls = $hasIcon ? 'floating-label-wrap with-icon' : 'floating-label-wrap no-icon';
    $icon = $hasIcon ? "<i class='bi {$f['icon']}' style='font-size:11px;color:#3B5FC7;position:absolute;top:50%;left:12px;transform:translateY(-50%);pointer-events:none;opacity:0.75;z-index:2'></i>" : '';
    $textareaContent = $isTextarea ? htmlspecialchars($value ?? '', ENT_QUOTES) : '';
    $height = $isTextarea ? "min-height:68px;padding-top:1.05rem" : "";
    $filledInit = ($value !== '' && $value !== null) ? "is-filled" : "";
    $baseInput = (!$isTextarea)
        ? "padding-top:1rem;padding-bottom:0.38rem;font-size:11px;border-radius:10px;border:1.5px solid #dbe6f5"
        : "padding-top:1.05rem;padding-bottom:0.4rem;font-size:11px;border-radius:10px;border:1.5px solid #dbe6f5";
    if ($hasIcon && $isTextarea) {
        $textareaIcon = "<i class='bi {$f['icon']}' style='font-size:11px;color:#3B5FC7;position:absolute;top:22px;left:12px;pointer-events:none;opacity:0.75;z-index:2'></i>";
        $icon = $textareaIcon;
    }
    echo "<div class='{$col}'>
        <div class='{$wrapCls}' style='margin-bottom:8px'>
            {$icon}
            " . ($isTextarea
                ? "<textarea class='form-control {$filledInit}' name='{$f['name']}' id='{$id}' rows='3' style='{$height};{$baseInput}' {$placeholderAttr}>{$textareaContent}</textarea>"
                : "<input type='{$f['type']}' class='form-control {$filledInit}' name='{$f['name']}' id='{$id}' style='{$height};{$baseInput}' {$placeholderAttr} {$valueAttr}>")
            . "<label class='float-label' for='{$id}' style='font-size:10.5px'>" . htmlspecialchars($f['label']) . "</label>
        </div>
    </div>";
}

function renderCustomForm($prefix, $basicFields, $bmnFields, $pajakFields, $serviceFields, $statusOps, $submitBtn, $drivers_list) {
    echo '<div class="modal-body" style="padding:13px 16px 2px;background:linear-gradient(180deg,#fafcff 0%,#ffffff 100%)">';

    // SECTION 1: DATA DASAR KENDARAAN
    echo '<div class="section-card">
        <div class="section-card-title"><span class="section-dot"></span>Data Dasar Kendaraan <span style="margin-left:auto;font-weight:700;color:#dc2626;font-size:8.5px;letter-spacing:0.3px">* WAJIB</span></div>
        <div class="row g-1.5" style="row-gap:4px">';
    foreach ($basicFields as $f) { fl_input($prefix, $f); }
    echo '</div></div>';

    // SECTION DRIVER: TERPISAH (RELASI MASTER DRIVER)
    $did = "{$prefix}_driver_id";
    $dna = "{$prefix}_driver";
    $dwa = "{$prefix}_no_hp_driver";
    echo '<div class="section-card">
        <div class="section-card-title"><span class="section-dot" style="background:linear-gradient(135deg,#0369a1,#0ea5e9);box-shadow:0 0 0 3px rgba(14,165,233,0.12)"></span>Data Driver &amp; Penugasan <span style="margin-left:auto;font-size:8px;background:rgba(14,165,233,0.1);color:#0369a1;padding:2px 8px;border-radius:99px;font-weight:800;letter-spacing:0.2px">TERHUBUNG MASTER DRIVER</span></div>
        <div class="row g-1.5" style="row-gap:4px">
            <div class="col-md-12">
                <div class="floating-label-wrap with-icon" style="margin-bottom:8px">
                    <i class="bi bi-person-lines-fill" style="font-size:11px;color:#0ea5e9;position:absolute;top:50%;left:12px;transform:translateY(-50%);pointer-events:none;opacity:0.8;z-index:2"></i>
                    <select class="form-select" name="driver_id" id="' . $did . '" onchange="driverAutoFill(this.value,\''.$prefix.'\')" style="padding-top:1rem;padding-bottom:0.38rem;font-size:11px;border-radius:10px;border:1.5px solid #dbe6f5">
                        <option value="" selected>&nbsp;</option>
                        <optgroup label="Pilih Driver dari Master Data">';
    foreach ($drivers_list as $d) {
        $st = ucfirst($d['status'] ?? 'tersedia');
        $badge = ($d['status'] === 'bertugas') ? '🔵 ' : (($d['status'] === 'tersedia') ? '🟢 ' : '🟡 ');
        echo '<option value="'.(int)$d['id'].'" data-nama="'.htmlspecialchars($d['nama_driver']).'" data-wa="'.htmlspecialchars($d['no_wa'] ?? '').'">'.$badge.htmlspecialchars($d['nama_driver']).' — '.$st.'</option>';
    }
    echo '        </optgroup>
                        <option value="">— Input Driver Manual (tidak ada di master) —</option>
                    </select>
                    <label class="float-label" for="'.$did.'" style="font-size:10.5px">Assign Driver dari Master Data (pilih disini)</label>
                </div>
            </div>';
    fl_input($prefix, ['name'=>'driver','label'=>'Nama Driver (bisa edit manual)','type'=>'text','ph'=>'Nama driver / supir','icon'=>'bi-person-gear']);
    fl_input($prefix, ['name'=>'no_hp_driver','label'=>'Kontak WA Driver','type'=>'text','ph'=>'08xxxxxxxxxx (auto isi dari master)','icon'=>'bi-whatsapp']);
    echo '</div></div>';

    // SECTION STATUS
    echo '<div class="section-card">
        <div class="section-card-title"><span class="section-dot section-dot-amber"></span>Status &amp; Ketersediaan Unit</div>
        <div class="row g-1.5" style="row-gap:4px">';
    echo '<div class="col-md-6"><div class="floating-label-wrap with-icon" style="margin-bottom:8px">
            <i class="bi bi-shield-check" style="font-size:11px;color:#059669;position:absolute;top:50%;left:12px;transform:translateY(-50%);pointer-events:none;opacity:0.85;z-index:2"></i>
            <select class="form-select" name="status" id="' . $prefix . '_status" style="padding-top:1rem;padding-bottom:0.38rem;font-size:11px;border-radius:10px;border:1.5px solid #dbe6f5">
                <option value="" disabled selected hidden>&nbsp;</option>';
    foreach ($statusOps as $s) echo "<option value='{$s}'>" . ucfirst($s) . "</option>";
    echo '  </select>
            <label class="float-label" for="' . $prefix . '_status" style="font-size:10.5px">Status Unit Kendaraan</label>
          </div></div>';
    echo '</div></div>';

    // SECTION 3: BMN & UNIT PENGGUNA
    echo '<div class="section-card">
        <div class="section-card-title"><span class="section-dot section-dot-amber"></span>Kode BMN &amp; Penempatan Unit</div>
        <div class="row g-1.5" style="row-gap:4px">';
    foreach ($bmnFields as $f) { fl_input($prefix, $f); }
    echo '</div></div>';

    // SECTION 4: PAJAK & PENGINGAT
    echo '<div class="section-card">
        <div class="section-card-title"><span class="section-dot section-dot-amber"></span>Pengingat Pajak (Otomatis Muncul di Menu Informasi Pajak)</div>
        <div class="row g-1.5" style="row-gap:4px">';
    foreach ($pajakFields as $f) { fl_input($prefix, $f); }
    echo '</div></div>';

    // SECTION 5: SERVICE RUTIN
    echo '<div class="section-card">
        <div class="section-card-title"><span class="section-dot section-dot-purple"></span>Jadwal Service Rutin <span style="margin-left:auto;font-weight:700;color:#6d28d9;font-size:8.5px">Admin Only</span></div>
        <div class="row g-1.5" style="row-gap:4px">';
    foreach ($serviceFields as $f) { fl_input($prefix, $f); }
    echo '</div></div>';

    echo '</div><div class="modal-footer" style="background:linear-gradient(180deg,#fff,#fafcff);border-top:1px solid #eef3fb;padding:10px 16px;gap:8px">
        <button type="button" class="btn fw-semibold" data-bs-dismiss="modal" style="border-radius:10px;font-size:10.5px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;padding:7.5px 16px">
            <i class="bi bi-x-lg me-1"></i>Batal
        </button>
        <button type="submit" class="btn fw-bold" style="border-radius:10px;font-size:10.5px;background:linear-gradient(135deg,#059669,#047857);border:none;color:#fff;padding:7.5px 18px;box-shadow:0 3px 10px rgba(5,150,105,0.22)">
            <i class="bi bi-save-fill me-1"></i>' . $submitBtn . '
        </button>
    </div>';
}
?>

<div class="modal fade" id="tambahModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content" style="border-radius:18px;border:1.5px solid #e5edf8;box-shadow:0 20px 60px rgba(11,28,72,0.16);overflow:hidden">
    <div class="modal-header d-flex align-items-center" style="background:linear-gradient(135deg,#0B1C48,#1F3A8B 50%,#3B5FC7);color:#fff;border:none;padding:16px 22px">
        <div class="d-flex align-items-center gap-3" style="flex:1">
            <div style="width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.18)">
                <i class="bi bi-truck" style="font-size:17px;color:#fff"></i>
            </div>
            <div>
                <h5 class="modal-title mb-0" style="font-size:14px;font-weight:800;letter-spacing:0.2px">Tambah Kendaraan Baru</h5>
                <div style="font-size:9.5px;color:rgba(255,255,255,0.7);margin-top:2px;font-weight:600;letter-spacing:0.15px">Isi data dasar, BMN, penempatan, pajak & jadwal service sekaligus</div>
            </div>
        </div>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form method="POST" action="<?= base_url('master/kendaraan.php') ?>"><input type="hidden" name="act" value="tambah">
        <?php renderCustomForm('t', $basicFields, $bmnFields, $pajakFields, $serviceFields, $statusOps, 'Simpan Data Kendaraan', $drivers_list); ?>
    </form>
</div></div></div>

<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content" style="border-radius:18px;border:1.5px solid #e5edf8;box-shadow:0 20px 60px rgba(11,28,72,0.16);overflow:hidden">
    <div class="modal-header d-flex align-items-center" style="background:linear-gradient(135deg,#1F3A8B,#3B5FC7 50%,#0ea5e9);color:#fff;border:none;padding:16px 22px">
        <div class="d-flex align-items-center gap-3" style="flex:1">
            <div style="width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.18)">
                <i class="bi bi-pencil-square" style="font-size:17px;color:#fff"></i>
            </div>
            <div>
                <h5 class="modal-title mb-0" style="font-size:14px;font-weight:800;letter-spacing:0.2px">Edit Data Kendaraan</h5>
                <div style="font-size:9.5px;color:rgba(255,255,255,0.72);margin-top:2px;font-weight:600;letter-spacing:0.15px">Perbarui pajak jatuh tempo, jadwal service & unit kerja</div>
            </div>
        </div>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form method="POST" action="<?= base_url('master/kendaraan.php') ?>"><input type="hidden" name="act" value="edit"><input type="hidden" name="id" id="e_id" value="0">
        <?php renderCustomForm('e', $basicFields, $bmnFields, $pajakFields, $serviceFields, $statusOps, 'Perbarui Data Kendaraan', $drivers_list); ?>
    </form>
</div></div></div>

<script>
function _q(id) { return document.getElementById(id); }

function driverAutoFill(driverId, prefix) {
    const sel = _q(prefix + '_driver_id');
    const nama = _q(prefix + '_driver');
    const wa = _q(prefix + '_no_hp_driver');
    if (sel && nama && wa) {
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.getAttribute('data-nama') !== null && driverId) {
            nama.value = opt.getAttribute('data-nama') || '';
            wa.value = opt.getAttribute('data-wa') || '';
        }
        [nama, wa, sel].forEach(el => refreshFilled(el));
    }
}
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

function editK(k) {
    const fields = [
        'no_plat','merk','tipe','tahun','kapasitas','driver','no_hp_driver','status',
        'kode_bmn','unit_pengguna',
        'pajak_stnk_jatuh_tempo','pajak_tnkb_jatuh_tempo',
        'terakhir_service','service_berikutnya','catatan_service'
    ];
    fields.forEach(f => {
        const el = _q('e_' + f);
        if (el) {
            el.value = k[f] ?? '';
            setTimeout(function(){ refreshFilled(el); }, 0);
        }
    });
    const sel = _q('e_status');
    if (sel) {
        sel.selectedIndex = 0;
        if (k['status']) {
            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === k['status']) { sel.selectedIndex = i; break; }
            }
        }
        setTimeout(function(){ refreshFilled(sel); }, 0);
    }
    const selDrv = _q('e_driver_id');
    if (selDrv) {
        selDrv.value = '';
        if (k['driver_id']) {
            for (let i = 0; i < selDrv.options.length; i++) {
                if (String(selDrv.options[i].value) === String(k['driver_id'])) { selDrv.selectedIndex = i; break; }
            }
        }
        setTimeout(function(){ refreshFilled(selDrv); }, 0);
    }
    _q('e_id').value = k.id;
    const instance = bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal'));
    instance.show();
}

document.addEventListener('DOMContentLoaded', function(){
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    if (editId) {
        let rows = <?php
            $rowsOut = [];
            foreach ($kendaraan as $kk) $rowsOut[] = $kk;
            echo json_encode($rowsOut, JSON_NUMERIC_CHECK);
        ?>;
        const target = rows.find(r => String(r.id) === String(editId));
        if (target) { setTimeout(function(){ editK(target); }, 120); }
    }
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
