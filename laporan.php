<?php
// ===== EXPORT CSV (HARUS PALING ATAS, SEBELUM OUTPUT APA PUN) =====
if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/config.php';
    require_admin();

    $tipe = sanitize($_GET['tipe'] ?? 'all');
    $status = sanitize($_GET['status'] ?? 'all');
    $tgl_awal = sanitize($_GET['tgl_awal'] ?? date('Y-m-01'));
    $tgl_akhir = sanitize($_GET['tgl_akhir'] ?? date('Y-m-t'));

    $whereKend = '1=1';
    $whereRuang = '1=1';
    $pK = $pR = [];

    if ($status !== 'all') {
        $whereKend .= " AND rk.status = ?";
        $whereRuang .= " AND rr.status = ?";
        $pK[] = $pR[] = $status;
    }
    $whereKend .= " AND (DATE(rk.created_at) BETWEEN ? AND ?)";
    $whereRuang .= " AND (DATE(rr.created_at) BETWEEN ? AND ?)";
    array_push($pK, $tgl_awal, $tgl_akhir);
    array_push($pR, $tgl_awal, $tgl_akhir);

    $kendaraan = [];
    $ruangan = [];
    if ($tipe === 'all' || $tipe === 'kendaraan') {
        $kendaraan = db()->fetchAll("SELECT rk.*, k.no_plat, k.merk, k.tipe, u.nama_lengkap, u.nip, u.unit_kerja
            FROM reservasi_kendaraan rk JOIN kendaraan k ON rk.kendaraan_id = k.id JOIN users u ON rk.user_id = u.id
            WHERE {$whereKend} ORDER BY rk.created_at DESC", $pK);
    }
    if ($tipe === 'all' || $tipe === 'ruangan') {
        $ruangan = db()->fetchAll("SELECT rr.*, r.nama_ruangan, r.lantai, u.nama_lengkap, u.nip, u.unit_kerja
            FROM reservasi_ruangan rr JOIN ruangan r ON rr.ruangan_id = r.id JOIN users u ON rr.user_id = u.id
            WHERE {$whereRuang} ORDER BY rr.created_at DESC", $pR);
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=LAPORAN_RESERVASI_' . date('YmdHis') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['TIPE', 'KODE', 'TGL PENGAJUAN', 'PEMOHON', 'NIP', 'UNIT KERJA', 'DETAIL', 'TGL MULAI', 'JAM MULAI', 'TGL SELESAI', 'JAM SELESAI', 'STATUS']);
    foreach ($kendaraan as $m) {
        fputcsv($out, ['KENDARAAN', $m['kode_reservasi'], $m['created_at'], $m['nama_lengkap'], $m['nip'], $m['unit_kerja'],
            $m['no_plat'].' - '.$m['merk'].' '.$m['tipe'].' | '.$m['tujuan'], $m['tanggal_pinjam'], $m['jam_mulai'], $m['tanggal_kembali'], $m['jam_selesai'], $m['status']]);
    }
    foreach ($ruangan as $r) {
        fputcsv($out, ['RUANGAN', $r['kode_reservasi'], $r['created_at'], $r['nama_lengkap'], $r['nip'], $r['unit_kerja'],
            $r['nama_ruangan'].' ('.$r['lantai'].') | '.$r['nama_acara'], $r['tanggal_mulai'], $r['jam_mulai'], $r['tanggal_selesai'], $r['jam_selesai'], $r['status']]);
    }
    fclose($out);
    exit;
}

// ===== HALAMAN UI LAPORAN (NORMAL MODE) =====
$page_title = 'Rekapitulasi & Laporan';
$active_menu = 'laporan';
require_once __DIR__ . '/partials/header.php';

require_admin();

$tipe = sanitize($_GET['tipe'] ?? 'all');
$status = sanitize($_GET['status'] ?? 'all');
$tgl_awal = sanitize($_GET['tgl_awal'] ?? date('Y-m-01'));
$tgl_akhir = sanitize($_GET['tgl_akhir'] ?? date('Y-m-t'));

$whereKend = '1=1';
$whereRuang = '1=1';
$pK = $pR = [];

if ($status !== 'all') {
    $whereKend .= " AND rk.status = ?";
    $whereRuang .= " AND rr.status = ?";
    $pK[] = $pR[] = $status;
}
$whereKend .= " AND (DATE(rk.created_at) BETWEEN ? AND ?)";
$whereRuang .= " AND (DATE(rr.created_at) BETWEEN ? AND ?)";
array_push($pK, $tgl_awal, $tgl_akhir);
array_push($pR, $tgl_awal, $tgl_akhir);

$kendaraan = [];
$ruangan = [];

if ($tipe === 'all' || $tipe === 'kendaraan') {
    $kendaraan = db()->fetchAll("SELECT rk.*, k.no_plat, k.merk, k.tipe, u.nama_lengkap, u.nip, u.unit_kerja
        FROM reservasi_kendaraan rk JOIN kendaraan k ON rk.kendaraan_id = k.id JOIN users u ON rk.user_id = u.id
        WHERE {$whereKend} ORDER BY rk.created_at DESC", $pK);
}
if ($tipe === 'all' || $tipe === 'ruangan') {
    $ruangan = db()->fetchAll("SELECT rr.*, r.nama_ruangan, r.lantai, u.nama_lengkap, u.nip, u.unit_kerja
        FROM reservasi_ruangan rr JOIN ruangan r ON rr.ruangan_id = r.id JOIN users u ON rr.user_id = u.id
        WHERE {$whereRuang} ORDER BY rr.created_at DESC", $pR);
}

$totalData = count($kendaraan) + count($ruangan);
$nilaiStatus = function($arr, $st) {
    return count(array_filter($arr, fn($x) => $x['status'] === $st));
};
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
    <div>
        <h4><i class="bi bi-file-earmark-bar-graph me-2" style="color:#06b6d4"></i>Rekapitulasi & Laporan</h4>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <span class="breadcrumb-item active">Rekapitulasi & Laporan</span>
        </nav>
    </div>
    <a href="<?= base_url('laporan.php') ?>?<?= http_build_query(array_merge($_GET, ['export' => '1'])) ?>" class="btn btn-success" target="_blank" style="background:linear-gradient(135deg,#059669,#047857);border:none;box-shadow:0 4px 12px rgba(5,150,105,0.3);transition:all .2s ease" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 16px rgba(5,150,105,0.38)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <i class="bi bi-file-earmark-excel-fill me-1" style="font-size:13px"></i> Export CSV Excel
    </a>
</div>

<div class="card">
    <div class="card-header flex-wrap gap-2">
        <h6 class="card-title"><i class="bi bi-funnel-fill me-2" style="color:#f59e0b"></i>Filter Laporan</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tipe Reservasi</label>
                <select class="form-select" name="tipe">
                    <option value="all" <?= $tipe === 'all' ? 'selected' : '' ?>>Semua Tipe</option>
                    <option value="kendaraan" <?= $tipe === 'kendaraan' ? 'selected' : '' ?>>Kendaraan</option>
                    <option value="ruangan" <?= $tipe === 'ruangan' ? 'selected' : '' ?>>Ruangan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="disetujui" <?= $status === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="ditolak" <?= $status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    <option value="dibatalkan" <?= $status === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" class="form-control" name="tgl_awal" value="<?= $tgl_awal ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" name="tgl_akhir" value="<?= $tgl_akhir ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                <a href="laporan.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-2 col-6">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-stack"></i></div>
            <div class="stat-label">Total Data</div>
            <div class="stat-value"><?= $totalData ?></div>
            <div class="stat-sub">Periode filter</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
            <div class="stat-label">Menunggu</div>
            <div class="stat-value"><?= $nilaiStatus($kendaraan, 'pending') + $nilaiStatus($ruangan, 'pending') ?></div>
            <div class="stat-sub">Belum diproses</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-label">Disetujui</div>
            <div class="stat-value"><?= $nilaiStatus($kendaraan, 'disetujui') + $nilaiStatus($ruangan, 'disetujui') ?></div>
            <div class="stat-sub">Dalam proses</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="bi bi-flag-fill"></i></div>
            <div class="stat-label">Selesai</div>
            <div class="stat-value"><?= $nilaiStatus($kendaraan, 'selesai') + $nilaiStatus($ruangan, 'selesai') ?></div>
            <div class="stat-sub">Terealisasi</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card">
            <div class="stat-icon pink"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-label">Ditolak</div>
            <div class="stat-value"><?= $nilaiStatus($kendaraan, 'ditolak') + $nilaiStatus($ruangan, 'ditolak') ?></div>
            <div class="stat-sub">Ditolak admin</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-slash-circle"></i></div>
            <div class="stat-label">Dibatalkan</div>
            <div class="stat-value"><?= $nilaiStatus($kendaraan, 'dibatalkan') + $nilaiStatus($ruangan, 'dibatalkan') ?></div>
            <div class="stat-sub">Dibatalkan user</div>
        </div>
    </div>
</div>

<?php if ($tipe === 'all' || $tipe === 'kendaraan'): ?>
<div class="card">
    <div class="card-header">
        <h6 class="card-title"><i class="bi bi-car-front-fill me-2" style="color:#2563eb"></i>Data Reservasi Kendaraan (<?= count($kendaraan) ?> data)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($kendaraan)): ?>
            <div class="text-center py-5 text-muted" style="font-size:11px">Tidak ada data.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Kode</th><th>Tgl Pengajuan</th><th>Pemohon</th><th>Unit Kerja</th>
                        <th>Kendaraan</th><th>Tujuan</th><th>Jadwal</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kendaraan as $m): ?>
                    <tr>
                        <td style="font-weight:700;font-size:10.5px;color:#1e40af"><?= $m['kode_reservasi'] ?></td>
                        <td style="font-size:10.5px"><?= format_date($m['created_at'], false) ?></td>
                        <td>
                            <div style="font-weight:600;font-size:11px"><?= $m['nama_lengkap'] ?></div>
                            <div style="font-size:9.5px;color:#64748b">NIP. <?= $m['nip'] ?></div>
                        </td>
                        <td style="font-size:10.5px"><?= $m['unit_kerja'] ?></td>
                        <td>
                            <div style="font-weight:700;font-size:11px"><?= $m['no_plat'] ?></div>
                            <div style="font-size:10px;color:#64748b"><?= $m['merk'] ?> <?= $m['tipe'] ?></div>
                        </td>
                        <td style="font-size:11px;max-width:180px"><?= $m['tujuan'] ?></td>
                        <td style="font-size:10px">
                            <?= format_date($m['tanggal_pinjam'], false) ?><br>
                            <?= date('H:i', strtotime($m['jam_mulai'])) ?> - <?= date('H:i', strtotime($m['jam_selesai'])) ?>
                        </td>
                        <td><?= status_badge($m['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($tipe === 'all' || $tipe === 'ruangan'): ?>
<div class="card">
    <div class="card-header">
        <h6 class="card-title"><i class="bi bi-door-open-fill me-2" style="color:#7c3aed"></i>Data Reservasi Ruangan (<?= count($ruangan) ?> data)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ruangan)): ?>
            <div class="text-center py-5 text-muted" style="font-size:11px">Tidak ada data.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Kode</th><th>Tgl Pengajuan</th><th>Pemohon</th><th>Unit Kerja</th>
                        <th>Ruangan</th><th>Acara</th><th>Jadwal</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ruangan as $r): ?>
                    <tr>
                        <td style="font-weight:700;font-size:10.5px;color:#6d28d9"><?= $r['kode_reservasi'] ?></td>
                        <td style="font-size:10.5px"><?= format_date($r['created_at'], false) ?></td>
                        <td>
                            <div style="font-weight:600;font-size:11px"><?= $r['nama_lengkap'] ?></div>
                            <div style="font-size:9.5px;color:#64748b">NIP. <?= $r['nip'] ?></div>
                        </td>
                        <td style="font-size:10.5px"><?= $r['unit_kerja'] ?></td>
                        <td>
                            <div style="font-weight:700;font-size:11px"><?= $r['nama_ruangan'] ?></div>
                            <div style="font-size:10px;color:#64748b"><?= $r['lantai'] ?></div>
                        </td>
                        <td style="font-size:11px;max-width:180px"><?= $r['nama_acara'] ?></td>
                        <td style="font-size:10px">
                            <?= format_date($r['tanggal_mulai'], false) ?><br>
                            <?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= date('H:i', strtotime($r['jam_selesai'])) ?>
                        </td>
                        <td><?= status_badge($r['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php' ?>
