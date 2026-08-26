<?php
$page_title = 'Kalender Peminjaman';
$active_menu = 'kalender';
require_once __DIR__ . '/partials/header.php';

$user = current_user();
$isAdmin = is_admin();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$tipe = sanitize($_GET['tipe'] ?? 'all');

$jmlHari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$firstDay = mktime(0, 0, 0, $bulan, 1, $tahun);
$firstDayWeek = date('w', $firstDay);

$hariIni = date('Y-m-d');
$bulanLalu = $bulan - 1 < 1 ? 12 : $bulan - 1;
$tahunLalu = $bulan - 1 < 1 ? $tahun - 1 : $tahun;
$bulanDepan = $bulan + 1 > 12 ? 1 : $bulan + 1;
$tahunDepan = $bulan + 1 > 12 ? $tahun + 1 : $tahun;

$namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

$whereKend = "rk.status IN ('disetujui','pending','selesai')";
$whereRuang = "rr.status IN ('disetujui','pending','selesai')";
$paramsK = $paramsR = [];
if ($tipe === 'kendaraan') { $whereRuang .= ' AND 1=0'; }
if ($tipe === 'ruangan') { $whereKend .= ' AND 1=0'; }

$startMonth = sprintf('%04d-%02d-01', $tahun, $bulan);
$endMonth = sprintf('%04d-%02d-%02d', $tahun, $bulan, $jmlHari);

$events = [];

$ARMADA_PALET = [
    ['bg'=>'linear-gradient(135deg,#0B1C48,#1F3A8B)','border'=>'#1F3A8B'],
    ['bg'=>'linear-gradient(135deg,#3B5FC7,#1F3A8B)','border'=>'#3B5FC7'],
    ['bg'=>'linear-gradient(135deg,#0d9488,#0f766e)','border'=>'#0f766e'],
    ['bg'=>'linear-gradient(135deg,#0891b2,#0e7490)','border'=>'#0e7490'],
    ['bg'=>'linear-gradient(135deg,#7c3aed,#5b21b6)','border'=>'#5b21b6'],
    ['bg'=>'linear-gradient(135deg,#d97706,#b45309)','border'=>'#b45309'],
    ['bg'=>'linear-gradient(135deg,#475569,#334155)','border'=>'#334155'],
    ['bg'=>'linear-gradient(135deg,#2563eb,#1d4ed8)','border'=>'#1d4ed8'],
    ['bg'=>'linear-gradient(135deg,#059669,#047857)','border'=>'#047857'],
    ['bg'=>'linear-gradient(135deg,#c026d3,#a21caf)','border'=>'#a21caf']
];
$RUANG_PALET = [
    ['bg'=>'linear-gradient(135deg,#8b5cf6,#6d28d9)','border'=>'#6d28d9'],
    ['bg'=>'linear-gradient(135deg,#a855f7,#7e22ce)','border'=>'#7e22ce'],
    ['bg'=>'linear-gradient(135deg,#6366f1,#4338ca)','border'=>'#4338ca']
];
function armada_key($tipe,$id) { return $tipe.'-'.$id; }
function armada_warna($palet,$idx){ global $ARMADA_PALET,$RUANG_PALET; $arr = $palet==='armada' ? $ARMADA_PALET : $RUANG_PALET; return $arr[$idx % count($arr)]; }

$armadaDaftar = [];

$mobilSql = "SELECT rk.id, rk.kode_reservasi, rk.tanggal_pinjam, rk.tanggal_kembali, rk.jam_mulai, rk.jam_selesai,
                    rk.status, rk.tujuan, k.id as kendaraan_id, k.no_plat, k.merk, k.tipe, u.nama_lengkap
             FROM reservasi_kendaraan rk LEFT JOIN kendaraan k ON rk.kendaraan_id=k.id LEFT JOIN users u ON rk.user_id=u.id
             WHERE {$whereKend}
               AND ((rk.tanggal_pinjam BETWEEN ? AND ?) OR (rk.tanggal_kembali BETWEEN ? AND ?)
                    OR (rk.tanggal_pinjam <= ? AND rk.tanggal_kembali >= ?))";
$paramsMobil = array_merge($paramsK, [$startMonth, $endMonth, $startMonth, $endMonth, $startMonth, $endMonth]);
$mobil = db()->fetchAll($mobilSql, $paramsMobil);
$armadaCounter = 0;
foreach ($mobil as $m) {
    $key = armada_key('m', $m['kendaraan_id']);
    if (!isset($armadaDaftar[$key])) {
        $warna = armada_warna('armada', $armadaCounter++);
        $armadaDaftar[$key] = [
            'tipe' => 'kendaraan',
            'id' => $m['kendaraan_id'],
            'nama' => trim($m['merk'] . ' ' . ($m['tipe'] ?? '')),
            'no_plat' => $m['no_plat'],
            'label' => trim($m['merk'] . ' ' . ($m['tipe'] ?? '')) . ' (' . $m['no_plat'] . ')',
            'warna' => $warna,
            'count' => 0
        ];
    }
    $armadaDaftar[$key]['count']++;
    $d1 = new DateTime($m['tanggal_pinjam']);
    $d2 = new DateTime($m['tanggal_kembali']);
    for ($d = clone $d1; $d <= $d2; $d->modify('+1 day')) {
        $tgl = $d->format('Y-m-d');
        if (substr($tgl, 0, 7) !== sprintf('%04d-%02d', $tahun, $bulan)) continue;
        $events[$tgl][] = [
            'tipe' => 'kendaraan',
            'armada_key' => $key,
            'id' => 'm-'.$m['id'],
            'kode' => $m['kode_reservasi'],
            'judul' => trim($m['merk'] . ' ' . ($m['tipe'] ?? '')) . ' • ' . $m['no_plat'],
            'judul_pendek' => (strlen(trim($m['merk'].' '.($m['tipe']??'')))>0 ? trim($m['merk'].' '.($m['tipe']??'')) : $m['no_plat']),
            'tujuan' => $m['tujuan'],
            'status' => $m['status'],
            'sub' => $m['nama_lengkap'] . ' | ' . date('H:i', strtotime($m['jam_mulai'])) . '-' . date('H:i', strtotime($m['jam_selesai'])),
            'link' => base_url('kendaraan/detail.php?id=' . $m['id']),
            'warna' => $armadaDaftar[$key]['warna']
        ];
    }
}

$ruangCounter = 0;
$ruangSql = "SELECT rr.id, rr.kode_reservasi, rr.tanggal_mulai, rr.tanggal_selesai, rr.jam_mulai, rr.jam_selesai,
                    rr.status, rr.nama_acara, r.id as ruangan_id, r.nama_ruangan, u.nama_lengkap
             FROM reservasi_ruangan rr LEFT JOIN ruangan r ON rr.ruangan_id=r.id LEFT JOIN users u ON rr.user_id=u.id
             WHERE {$whereRuang}
               AND ((rr.tanggal_mulai BETWEEN ? AND ?) OR (rr.tanggal_selesai BETWEEN ? AND ?)
                    OR (rr.tanggal_mulai <= ? AND rr.tanggal_selesai >= ?))";
$paramsRuang = array_merge($paramsR, [$startMonth, $endMonth, $startMonth, $endMonth, $startMonth, $endMonth]);
$ruang = db()->fetchAll($ruangSql, $paramsRuang);
foreach ($ruang as $r) {
    $key = armada_key('r', $r['ruangan_id']);
    if (!isset($armadaDaftar[$key])) {
        $warna = armada_warna('ruang', $ruangCounter++);
        $armadaDaftar[$key] = [
            'tipe' => 'ruangan',
            'id' => $r['ruangan_id'],
            'nama' => $r['nama_ruangan'],
            'no_plat' => '',
            'label' => '🏢 ' . $r['nama_ruangan'],
            'warna' => $warna,
            'count' => 0
        ];
    }
    $armadaDaftar[$key]['count']++;
    $d1 = new DateTime($r['tanggal_mulai']);
    $d2 = new DateTime($r['tanggal_selesai']);
    for ($d = clone $d1; $d <= $d2; $d->modify('+1 day')) {
        $tgl = $d->format('Y-m-d');
        if (substr($tgl, 0, 7) !== sprintf('%04d-%02d', $tahun, $bulan)) continue;
        $events[$tgl][] = [
            'tipe' => 'ruangan',
            'armada_key' => $key,
            'id' => 'r-'.$r['id'],
            'kode' => $r['kode_reservasi'],
            'judul' => $r['nama_ruangan'] . ' • ' . $r['nama_acara'],
            'judul_pendek' => $r['nama_ruangan'],
            'tujuan' => $r['nama_acara'],
            'status' => $r['status'],
            'sub' => $r['nama_lengkap'] . ' | ' . date('H:i', strtotime($r['jam_mulai'])) . '-' . date('H:i', strtotime($r['jam_selesai'])),
            'link' => base_url('ruangan/detail.php?id=' . $r['id']),
            'warna' => $armadaDaftar[$key]['warna']
        ];
    }
}

$statusColor = [
    'pending' => 'background:#fef3c7;color:#92400e;border-left:3px solid #f59e0b',
    'disetujui' => 'background:#d1fae5;color:#065f46;border-left:3px solid #10b981',
    'selesai' => 'background:#dbeafe;color:#1e40af;border-left:3px solid #3b82f6',
    'ditolak' => 'background:#fee2e2;color:#991b1b;border-left:3px solid #ef4444',
    'dibatalkan' => 'background:#f1f5f9;color:#475569;border-left:3px solid #94a3b8'
];

$totalEvents = 0;
$totalMobil = 0;
$totalRuang = 0;
$totalPending = 0;
$totalDisetujui = 0;
$totalSelesai = 0;
$totalHariDenganEvent = 0;
foreach ($events as $tgl => $evs) {
    $totalHariDenganEvent++;
    foreach ($evs as $ev) {
        $totalEvents++;
        if ($ev['tipe'] === 'kendaraan') $totalMobil++;
        if ($ev['tipe'] === 'ruangan')  $totalRuang++;
        if ($ev['status'] === 'pending')    $totalPending++;
        if ($ev['status'] === 'disetujui')  $totalDisetujui++;
        if ($ev['status'] === 'selesai')    $totalSelesai++;
    }
}
?>

<!-- ===== KOMPAK HERO (MINIMAL) ===== -->
<div class="page-container">
<div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider:'›'">
        <ol class="breadcrumb mb-0" style="font-size:10.5px">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard.php') ?>" class="text-decoration-none" style="color:#64748b;font-weight:600">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page" style="color:#0B1C48;font-weight:700">Kalender Peminjaman</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="<?= base_url('kendaraan/form.php') ?>" class="btn fw-semibold" style="border-radius:11px;padding:7px 16px;font-size:10.5px;background:linear-gradient(135deg,#3B5FC7,#0B1C48);border:none;color:#fff;box-shadow:0 3px 10px rgba(11,28,72,0.22);letter-spacing:0.2px">
            <i class="bi bi-car-front-fill me-1"></i>Reservasi Mobil
        </a>
        <a href="<?= base_url('ruangan/form.php') ?>" class="btn fw-semibold border" style="border-radius:11px;padding:7px 16px;font-size:10.5px;background:#fff;border-color:#cfdbf3;color:#1F3A8B">
            <i class="bi bi-door-open-fill me-1"></i>Reservasi Ruang
        </a>
        <?php if ($isAdmin): ?>
        <a href="<?= base_url('laporan.php') ?>?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" target="_blank" class="btn fw-semibold border" style="border-radius:11px;padding:7px 16px;font-size:10.5px;background:#fff;border-color:#dbe5f1;color:#475569">
            <i class="bi bi-file-earmark-excel-fill me-1" style="color:#10b981"></i>Export
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ===== LAYOUT 2 KOLOM: KALENDER + SIDEBAR ARMADA ===== -->
<div class="row g-4 mb-4">
    <!-- ===== KOLOM KIRI: FULLCALENDAR-STYLE GRID ===== -->
    <div class="col-xl-9 col-lg-8">
        <div class="card border-0 shadow-sm h-100" style="border-radius:18px;overflow:hidden;border:1.5px solid #e2e8f0">
            <!-- ===== HEADER KALENDER FLAT (seperti referensi) ===== -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3" style="background:#fff;border-bottom:1.5px solid #eef2f7">
                <div class="d-flex align-items-center gap-2.5">
                    <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#3B5FC7,#0B1C48);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(11,28,72,0.2);flex-shrink:0">
                        <i class="bi bi-calendar-event-fill" style="font-size:14px"></i>
                    </div>
                    <h3 style="font-size:20px;color:#0B1C48;font-weight:800;letter-spacing:-0.25px;margin:0;line-height:1"><?= $namaBulan[$bulan] ?> <?= $tahun ?></h3>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select onchange="location='?bulan='+this.value+'&tahun=<?= $tahun ?>&tipe=<?= $tipe ?>'" class="form-select form-select-sm" style="width:auto;border-radius:10px;padding:6px 12px;font-size:10.5px;min-height:34px;border:1px solid #dbe5f1;background:#fff;color:#0B1C48;font-weight:600">
                        <?php for ($b = 1; $b <= 12; $b++): ?>
                            <option value="<?= $b ?>" <?= $b === $bulan ? 'selected' : '' ?>><?= $namaBulan[$b] ?></option>
                        <?php endfor; ?>
                    </select>
                    <select onchange="location='?bulan=<?= $bulan ?>&tahun='+this.value+'&tipe=<?= $tipe ?>'" class="form-select form-select-sm" style="width:auto;border-radius:10px;padding:6px 12px;font-size:10.5px;min-height:34px;border:1px solid #dbe5f1;background:#fff;color:#0B1C48;font-weight:600">
                        <?php for ($t = $tahun - 2; $t <= $tahun + 2; $t++): ?>
                            <option value="<?= $t ?>" <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endfor; ?>
                    </select>
                    <select onchange="location='?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&tipe='+this.value" class="form-select form-select-sm" style="width:auto;border-radius:10px;padding:6px 12px;font-size:10.5px;min-height:34px;border:1px solid #dbe5f1;background:#fff;color:#0B1C48;font-weight:600">
                        <option value="all" <?= $tipe === 'all' ? 'selected' : '' ?>>Semua Aset</option>
                        <option value="kendaraan" <?= $tipe === 'kendaraan' ? 'selected' : '' ?>>Kendaraan</option>
                        <option value="ruangan" <?= $tipe === 'ruangan' ? 'selected' : '' ?>>Ruangan</option>
                    </select>
                    <div class="btn-group ms-1" role="group" style="border-radius:10px;overflow:hidden;box-shadow:0 1px 0 #fff inset">
                        <a href="?bulan=<?= date('n') ?>&tahun=<?= date('Y') ?>&tipe=<?= $tipe ?>" class="btn fw-semibold" style="font-size:10.5px;padding:7px 14px;min-height:34px;background:#334155;color:#fff;border:none;border-right:1px solid rgba(255,255,255,0.12);letter-spacing:0.2px" onmouseover="this.style.background='#0B1C48'" onmouseout="this.style.background='#334155'">today</a>
                        <a href="?bulan=<?= $bulanLalu ?>&tahun=<?= $tahunLalu ?>&tipe=<?= $tipe ?>" class="btn fw-semibold" style="font-size:10.5px;padding:7px 12px;min-height:34px;background:#475569;color:#fff;border:none;border-right:1px solid rgba(255,255,255,0.1)" onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#475569'">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <a href="?bulan=<?= $bulanDepan ?>&tahun=<?= $tahunDepan ?>&tipe=<?= $tipe ?>" class="btn fw-semibold" style="font-size:10.5px;padding:7px 12px;min-height:34px;background:#475569;color:#fff;border:none" onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#475569'">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ===== BADGE SUMMARY RINGKAS ===== -->
            <div class="px-4 py-2.5 d-flex flex-wrap gap-2 align-items-center" style="background:linear-gradient(180deg,#fafcff,#fff);border-bottom:1px solid #eef2f7">
                <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center" style="font-size:9.5px;background:rgba(59,95,199,0.1);color:#1F3A8B;border:1px solid rgba(59,95,199,0.18);font-weight:700">
                    <i class="bi bi-calendar3 me-1" style="color:#3B5FC7"></i><?= $totalEvents ?> Event
                </span>
                <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center" style="font-size:9.5px;background:rgba(59,130,246,0.1);color:#1d4ed8;border:1px solid rgba(59,130,246,0.2);font-weight:700">
                    <i class="bi bi-car-front-fill me-1"></i><?= $totalMobil ?> Mobil
                </span>
                <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center" style="font-size:9.5px;background:rgba(139,92,246,0.1);color:#6d28d9;border:1px solid rgba(139,92,246,0.2);font-weight:700">
                    <i class="bi bi-door-open-fill me-1"></i><?= $totalRuang ?> Ruang
                </span>
                <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center" style="font-size:9.5px;background:rgba(16,185,129,0.1);color:#047857;border:1px solid rgba(16,185,129,0.2);font-weight:700">
                    <i class="bi bi-check2-circle me-1"></i><?= $totalDisetujui ?> Aktif
                </span>
                <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center" style="font-size:9.5px;background:rgba(245,158,11,0.1);color:#b45309;border:1px solid rgba(245,158,11,0.2);font-weight:700">
                    <i class="bi bi-clock-history me-1"></i><?= $totalPending ?> Pending
                </span>
            </div>

            <!-- ===== KALENDER GRID FULLCALENDAR STYLE ===== -->
            <div class="p-3" style="background:#fff">
                <div id="calendar-grid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:#e2e8f0;border-radius:14px;overflow:hidden;border:1.5px solid #e2e8f0">
                    <!-- ===== HEADER HARI ===== -->
                    <?php foreach ($namaHari as $idx => $h): ?>
                        <?php $isWeekendHeader = ($idx == 0 || $idx == 6); ?>
                        <div style="background:<?= $isWeekendHeader ? '#f8fafc' : '#f8fafc' ?>;padding:10px 8px;text-align:center;font-weight:800;font-size:10.5px;color:<?= $isWeekendHeader ? '#c2410c' : '#475569' ?>;letter-spacing:0.5px;border-bottom:1.5px solid <?= $isWeekendHeader ? '#fed7aa' : '#e2e8f0' ?>">
                            <?= $h ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- ===== EMPTY CELL AWAL BULAN ===== -->
                    <?php for ($i = 0; $i < $firstDayWeek; $i++): ?>
                        <div style="min-height:140px;background:#fafbfc;padding:8px;position:relative">
                            <div style="position:absolute;inset:10px;border-radius:10px;background:repeating-linear-gradient(45deg,#f1f5f9,#f1f5f9 4px,#f8fafc 4px,#f8fafc 8px);opacity:0.6"></div>
                        </div>
                    <?php endfor; ?>

                    <!-- ===== CELL HARI ===== -->
                    <?php for ($hari = 1; $hari <= $jmlHari; $hari++): ?>
                        <?php
                        $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
                        $isToday = $tglStr === $hariIni;
                        $dow = date('w', strtotime($tglStr));
                        $isWeekend = ($dow == 0 || $dow == 6);
                        $evs = $events[$tglStr] ?? [];
                        $weekendBg = $isWeekend ? 'background:#fffaf5' : 'background:#ffffff';
                        $todayStyle = $isToday ? 'box-shadow:inset 0 0 0 2.5px #0B1C48' : '';
                        ?>
                        <div style="min-height:140px;<?= $weekendBg ?>;padding:7px 6px;position:relative;display:flex;flex-direction:column;gap:3px;<?= $todayStyle ?>;transition:background .15s" onmouseover="<?= !$isToday && !$isWeekend ? 'this.style.background=\'#fbfdff\'' : '' ?>" onmouseout="<?= !$isToday && !$isWeekend ? 'this.style.background=\'#ffffff\'' : '' ?>">
                            <!-- Tanggal + Badge HARI INI (TOP LEFT kecil seperti referensi) -->
                            <div class="d-flex align-items-start justify-content-between mb-1" style="gap:3px">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isToday): ?>
                                        <span class="badge rounded-pill" style="font-size:8.5px;padding:2px 6px;background:#0B1C48;color:#fff;letter-spacing:0.3px;font-weight:800;box-shadow:0 2px 6px rgba(11,28,72,0.25)">HARI INI</span>
                                    <?php endif; ?>
                                    <span style="font-size:10px;font-weight:<?= $isToday ? '900' : '700' ?>;color:<?= $isToday ? '#0B1C48' : ($isWeekend ? '#c2410c' : '#475569') ?>;padding:1px 3px;<?= $isToday ? '' : '' ?>"><?= $hari ?></span>
                                </div>
                                <?php if (!empty($evs)): ?>
                                    <span style="font-size:8.5px;font-weight:800;color:#94a3b8;background:#f1f5f9;padding:1px 5px;border-radius:999px;letter-spacing:0.2px"><?= count($evs) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- EVENT PILLS (SOLID WARNA PER-ARMADA) -->
                            <div style="flex:1;display:flex;flex-direction:column;gap:2.5px;overflow:hidden">
                                <?php foreach (array_slice($evs, 0, 4) as $ev): ?>
                                    <?php $w = $ev['warna']; ?>
                                    <div data-armada="<?= $ev['armada_key'] ?>" data-bs-toggle="tooltip" title="<?= htmlspecialchars($ev['judul'] . "\n" . $ev['sub']) ?>" class="armada-event" style="cursor:default;display:block;background:<?= $w['bg'] ?>;color:#fff;padding:3px 7px;border-radius:6px;font-size:9px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:0.1px;line-height:1.35;border:1px solid <?= $w['border'] ?>;box-shadow:0 1px 0 rgba(255,255,255,0.25) inset, 0 1px 3px rgba(15,23,42,0.08);user-select:none">
                                        <?php if ($ev['status'] === 'pending'): ?><i class="bi bi-clock-history" style="font-size:8px;opacity:0.92;margin-right:2px"></i><?php endif; ?>
                                        <?php if ($ev['status'] === 'ditolak'): ?><i class="bi bi-x-circle-fill" style="font-size:8px;opacity:0.92;margin-right:2px"></i><?php endif; ?>
                                        <?= $ev['judul_pendek'] ?>
                                        <?php if (!empty($ev['no_plat'] ?? '')): ?><span style="font-weight:600;opacity:0.95;margin-left:2px">• <?= $ev['no_plat'] ?></span><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($evs)): ?>
                                    <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:8.5px;font-weight:600;opacity:0.55;letter-spacing:0.2px">
                                        —
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- +N reservasi lainnya (NON-CLICKABLE) -->
                            <?php if (count($evs) > 4): ?>
                                <div class="text-center" style="cursor:default;font-size:8.5px;font-weight:700;color:#1F3A8B;background:rgba(59,95,199,0.08);padding:2px 4px;border-radius:5px;letter-spacing:0.1px;user-select:none">
                                    +<?= count($evs) - 4 ?> lainnya
                                </div>
                            <?php endif; ?>

                            <!-- Quick add corner bottom (opsional icon kecil) -->
                            <div style="position:absolute;bottom:5px;right:5px;display:flex;gap:2px;opacity:0.6">
                                <a href="<?= base_url('kendaraan/form.php') ?>?tgl=<?= urlencode($tglStr) ?>" data-bs-toggle="tooltip" title="Tambah Reservasi Mobil" style="width:16px;height:16px;border-radius:5px;background:rgba(59,95,199,0.12);color:#1F3A8B;font-size:9px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none" onmouseover="this.style.background='#0B1C48';this.style.color='#fff';this.style.opacity=1" onmouseout="this.style.background='rgba(59,95,199,0.12)';this.style.color='#1F3A8B';this.style.opacity=0.6">
                                    <i class="bi bi-car-front-fill"></i>
                                </a>
                                <a href="<?= base_url('ruangan/form.php') ?>?tgl=<?= urlencode($tglStr) ?>" data-bs-toggle="tooltip" title="Tambah Reservasi Ruang" style="width:16px;height:16px;border-radius:5px;background:rgba(139,92,246,0.12);color:#7c3aed;font-size:9px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none" onmouseover="this.style.background='#7c3aed';this.style.color='#fff';this.style.opacity=1" onmouseout="this.style.background='rgba(139,92,246,0.12)';this.style.color='#7c3aed';this.style.opacity=0.6">
                                    <i class="bi bi-door-open-fill"></i>
                                </a>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== KOLOM KANAN: SIDEBAR DAFTAR ARMADA / KENDARAAN (SLIM PREMIUM) ===== -->
    <div class="col-xl-3 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;position:sticky;top:88px">
            <!-- Header Sidebar (Slim) -->
            <div class="px-3.5 py-2.5 d-flex align-items-center gap-2" style="background:#fff;border-bottom:1px solid #eef2f7">
                <div style="width:26px;height:26px;border-radius:9px;background:linear-gradient(135deg,#0B1C48,#3B5FC7);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(11,28,72,0.18);flex-shrink:0">
                    <i class="bi bi-people-fill" style="font-size:11.5px"></i>
                </div>
                <div style="min-width:0;flex:1">
                    <div style="font-size:11.5px;font-weight:800;color:#0B1C48;letter-spacing:0.05px;line-height:1.15">Daftar Armada</div>
                    <div style="font-size:8.5px;color:#64748b;font-weight:600;margin-top:1px;line-height:1.2">
                        <span class="badge rounded-pill" style="font-size:8px;padding:1px 5px;background:#0B1C48;color:#fff;margin-right:3px;letter-spacing:0.15px;font-weight:800"><?= count($armadaDaftar) ?></span>
                        Unit aktif bulan ini
                    </div>
                </div>
            </div>

            <!-- Tombol Reset + Info (Slim) -->
            <div class="px-3 py-2" style="background:#fcfdff;border-bottom:1px solid #eef2f7">
                <button onclick="resetArmadaFilter()" class="btn w-100 fw-semibold" style="border-radius:9px;padding:5px 10px;font-size:9px;min-height:30px;background:#fff;color:#0B1C48;border:1px solid #cfdbf3;letter-spacing:0.15px;box-shadow:0 1px 0 #fff inset" onmouseover="this.style.background='linear-gradient(135deg,#f5f8ff,#eef3ff)';this.style.borderColor='#b9cbf3'" onmouseout="this.style.background='#fff';this.style.borderColor='#cfdbf3'">
                    <i class="bi bi-arrow-counterclockwise me-1" style="font-size:8.5px"></i>Tampilkan Semua Jadwal
                </button>
                <div style="font-size:8px;color:#94a3b8;font-weight:600;margin-top:4px;line-height:1.35;padding:0 1px">
                    <i class="bi bi-info-circle me-0.5" style="font-size:7.5px"></i>Klik nama unit di bawah untuk <span style="color:#475569;font-weight:700">filter jadwal</span> di kalender
                </div>
            </div>

            <!-- List Armada (Slim Compact) -->
            <div class="px-2.5 py-2.5" style="background:#fff;max-height:calc(100vh - 330px);overflow-y:auto">
                <?php
                $listMobil = [];
                $listRuang = [];
                foreach ($armadaDaftar as $key => $a) {
                    if ($a['tipe'] === 'kendaraan') $listMobil[$key] = $a;
                    else                            $listRuang[$key] = $a;
                }
                ?>
                <?php if (!empty($listMobil)): ?>
                    <div class="mb-1.5 px-1 mt-1 d-flex align-items-center gap-1.5" style="font-size:8px;font-weight:700;color:#1F3A8B;letter-spacing:0.45px;text-transform:uppercase">
                        <span style="width:8px;height:8px;border-radius:3px;background:#3B5FC7"></span>Kendaraan
                        <span style="flex:1;height:1px;background:linear-gradient(90deg,rgba(59,95,199,0.22),transparent);margin-left:4px;border-radius:2px"></span>
                    </div>
                    <div class="d-flex flex-column gap-1.5 mb-3 px-0.5">
                        <?php foreach ($listMobil as $key => $a): $w = $a['warna']; ?>
                            <button onclick="toggleArmadaFilter(this,'<?= $key ?>')" class="armada-chip w-100 btn text-start d-flex align-items-center justify-content-between gap-2 px-2.5 py-1.5" data-armada="<?= $key ?>" style="border-radius:9px;background:<?= $w['bg'] ?>;color:#fff;border:1px solid <?= $w['border'] ?>;box-shadow:0 1px 0 rgba(255,255,255,0.2) inset, 0 1.5px 4px rgba(15,23,42,0.08);transition:all .12s;letter-spacing:0.05px;min-height:36px" onmouseover="this.style.transform='translateX(1.5px)';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.2) inset, 0 3px 8px rgba(15,23,42,0.16)'" onmouseout="this.style.transform='';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.2) inset, 0 1.5px 4px rgba(15,23,42,0.08)'">
                                <span class="d-flex align-items-center gap-1.5" style="min-width:0;flex:1">
                                    <i class="bi bi-car-front-fill" style="font-size:9px;opacity:0.92;flex-shrink:0"></i>
                                    <span style="font-size:9.5px;font-weight:700;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= !empty($a['nama']) ? $a['nama'] : $a['no_plat'] ?>
                                        <?php if (!empty($a['no_plat']) && !empty($a['nama'])): ?>
                                            <span style="font-weight:500;opacity:0.85;margin-left:3px;font-size:8.5px">• <?= $a['no_plat'] ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <span class="badge rounded-pill flex-shrink-0" style="font-size:7.5px;padding:1.5px 5px;background:rgba(255,255,255,0.25);color:#fff;font-weight:800;letter-spacing:0.15px;line-height:1">
                                    <?= $a['count'] ?>x
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($listRuang)): ?>
                    <div class="mb-1.5 px-1 d-flex align-items-center gap-1.5" style="font-size:8px;font-weight:700;color:#6d28d9;letter-spacing:0.45px;text-transform:uppercase">
                        <span style="width:8px;height:8px;border-radius:3px;background:#7c3aed"></span>Ruangan
                        <span style="flex:1;height:1px;background:linear-gradient(90deg,rgba(139,92,246,0.22),transparent);margin-left:4px;border-radius:2px"></span>
                    </div>
                    <div class="d-flex flex-column gap-1.5 px-0.5">
                        <?php foreach ($listRuang as $key => $a): $w = $a['warna']; ?>
                            <button onclick="toggleArmadaFilter(this,'<?= $key ?>')" class="armada-chip w-100 btn text-start d-flex align-items-center justify-content-between gap-2 px-2.5 py-1.5" data-armada="<?= $key ?>" style="border-radius:9px;background:<?= $w['bg'] ?>;color:#fff;border:1px solid <?= $w['border'] ?>;box-shadow:0 1px 0 rgba(255,255,255,0.2) inset, 0 1.5px 4px rgba(124,58,237,0.1);transition:all .12s;letter-spacing:0.05px;min-height:36px" onmouseover="this.style.transform='translateX(1.5px)';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.2) inset, 0 3px 8px rgba(124,58,237,0.18)'" onmouseout="this.style.transform='';this.style.boxShadow='0 1px 0 rgba(255,255,255,0.2) inset, 0 1.5px 4px rgba(124,58,237,0.1)'">
                                <span class="d-flex align-items-center gap-1.5" style="min-width:0;flex:1">
                                    <i class="bi bi-door-open-fill" style="font-size:9px;opacity:0.92;flex-shrink:0"></i>
                                    <span style="font-size:9.5px;font-weight:700;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= $a['nama'] ?>
                                    </span>
                                </span>
                                <span class="badge rounded-pill flex-shrink-0" style="font-size:7.5px;padding:1.5px 5px;background:rgba(255,255,255,0.25);color:#fff;font-weight:800;letter-spacing:0.15px;line-height:1">
                                    <?= $a['count'] ?>x
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($armadaDaftar)): ?>
                    <div class="text-center py-5 px-2" style="color:#94a3b8;font-size:9px;font-weight:600">
                        <i class="bi bi-inbox" style="font-size:22px;display:block;margin-bottom:6px;opacity:0.4"></i>
                        Belum ada jadwal bulan ini
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<!-- ===== SCRIPT FILTER ARMADA (TOGGLE SHOW/HIDE EVENT SLIM) ===== -->
<script>
(function(){
    const activeArmadas = new Set();
    window.toggleArmadaFilter = function(btn, key) {
        if (activeArmadas.has(key)) {
            activeArmadas.delete(key);
            btn.style.opacity = '1';
            btn.style.outline = '';
            btn.style.outlineOffset = '';
        } else {
            activeArmadas.add(key);
            btn.style.opacity = '0.55';
            btn.style.outline = '1.5px solid #fff';
            btn.style.outlineOffset = '-3px';
        }
        document.querySelectorAll('.armada-event').forEach(function(ev){
            const k = ev.getAttribute('data-armada');
            ev.style.display = (activeArmadas.size === 0 || activeArmadas.has(k)) ? '' : 'none';
        });
    };
    window.resetArmadaFilter = function(){
        activeArmadas.clear();
        document.querySelectorAll('.armada-chip').forEach(function(c){
            c.style.opacity='1';c.style.outline='';c.style.outlineOffset='';
        });
        document.querySelectorAll('.armada-event').forEach(function(ev){
            ev.style.display='';
        });
    };
})();
</script>

<?php require_once __DIR__ . '/partials/footer.php' ?>
