<?php
// ===== EXPORT CSV (PALING ATAS, SEBELUM OUTPUT APA PUN) =====
if (isset($_GET['export']) && $_GET['export'] === '1') {
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../config.php';
    require_admin();
    $isAdmin = true;

    $migration_cols = [
        'kode_bmn'                  => "ALTER TABLE kendaraan ADD COLUMN kode_bmn VARCHAR(60) DEFAULT NULL",
        'unit_pengguna'             => "ALTER TABLE kendaraan ADD COLUMN unit_pengguna VARCHAR(120) DEFAULT NULL",
        'pajak_stnk_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_stnk_jatuh_tempo DATE DEFAULT NULL",
        'pajak_tnkb_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_tnkb_jatuh_tempo DATE DEFAULT NULL",
        'terakhir_service'          => "ALTER TABLE kendaraan ADD COLUMN terakhir_service DATE DEFAULT NULL",
        'service_berikutnya'        => "ALTER TABLE kendaraan ADD COLUMN service_berikutnya DATE DEFAULT NULL",
        'catatan_service'           => "ALTER TABLE kendaraan ADD COLUMN catatan_service TEXT DEFAULT NULL",
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
        // === AUTO SEED DATA PAJAK DEFAULT BERDASARKAN 7 KENDARAAN EXCEL ===
        try {
            $allK = db()->fetchAll("SELECT id, no_plat FROM kendaraan ORDER BY id");
            $today = new DateTime();
            // jadwal default per index: [stnk_offset_hari, tnkb_offset_hari, svc_terakhir_offset_mundur, svc_berikutnya_offset_maju]
            $jadwals = [
                [60, 365*4, 30,  90],   // AB 1325 UB: stnk 2 bulan lagi, tnkb 4th, svc 1bln lalu → 3bln lagi (kuning)
                [20, 365*3, 45,  45],   // AB 1432 UB: stnk 20hari (kuning), tnkb 3th, svc 1.5bln lalu → 1.5bln lagi
                [5,  365*2, 7,   21],   // AB 1449 UB: stnk 5 hari (merah tua!!), tnkb 2th, svc seminggu lalu → 3 minggu
                [-3, 365,   120, 60],   // AB 1769 UA: STNK SUDAH LEWAT 3 HARI 🔴!!! tnkb 1th, svc 4bln lalu → 2bln lagi
                [120,720,   60,  120],  // AB 1180 UB: stnk 4bulan (hijau aman), tnkb ~2th, svc 2bln → 4bln (hijau)
                [180,1095,  10,  80],   // B 1247 TQO Reborn: stnk 6bulan hijau, tnkb 3th, svc 10hr lalu → 80hr lagi
                [-15, 540,  200, 15],   // B 1248 TQO Reborn: STNK LEWAT 15 HARI 🔴, tnkb 1.5th, svc lama → servis 15hr lagi (kuning)
            ];
            $kode_bmn = ['BMN-0101-2023-001','BMN-0101-2023-002','BMN-0101-2023-003','BMN-0101-2023-004',
                         'BMN-0202-2022-011','BMN-0303-2024-201','BMN-0303-2024-202'];
            foreach ($allK as $i => $k) {
                $j = $jadwals[$i % count($jadwals)];
                $row = db()->fetchOne("SELECT pajak_stnk_jatuh_tempo s, pajak_tnkb_jatuh_tempo t, terakhir_service l, service_berikutnya b, kode_bmn k FROM kendaraan WHERE id=?", [$k['id']]);
                $needUpdate = false;
                $sets = $vals = [];
                if (empty($row['s'])) { $d = clone $today; $d->modify('+'.$j[0].' days'); $sets[]='pajak_stnk_jatuh_tempo=?'; $vals[]=$d->format('Y-m-d'); $needUpdate=true; }
                if (empty($row['t'])) { $d = clone $today; $d->modify('+'.$j[1].' days'); $sets[]='pajak_tnkb_jatuh_tempo=?'; $vals[]=$d->format('Y-m-d'); $needUpdate=true; }
                if (empty($row['l'])) { $d = clone $today; $d->modify('-'.$j[2].' days'); $sets[]='terakhir_service=?'; $vals[]=$d->format('Y-m-d'); $needUpdate=true; }
                if (empty($row['b'])) { $d = clone $today; $d->modify('+'.$j[3].' days'); $sets[]='service_berikutnya=?'; $vals[]=$d->format('Y-m-d'); $needUpdate=true; }
                if (empty($row['k'])) { $sets[]='kode_bmn=?'; $vals[]=$kode_bmn[$i % count($kode_bmn)]; $needUpdate=true; }
                if ($needUpdate) {
                    $vals[] = $k['id'];
                    @db()->exec("UPDATE kendaraan SET ".implode(', ',$sets)." WHERE id=?", $vals);
                }
            }
        } catch(Exception $e){}
    } catch (Exception $ex) {}

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

    $where_sql = 'WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where_sql .= ' AND (no_plat LIKE ? OR merk LIKE ? OR tipe LIKE ? OR kode_bmn LIKE ? OR unit_pengguna LIKE ? OR driver LIKE ?)';
        $term = "%$search%";
        $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
    }

    $sql = "SELECT * FROM kendaraan $where_sql ORDER BY no_plat ASC";
    try { $kendaraan_all = db()->fetchAll($sql, $params); }
    catch (PDOException $e) { $kendaraan_all = []; }

    $items = [];
    foreach ($kendaraan_all as $k) {
        $info = pajak_status_info($k['pajak_stnk_jatuh_tempo'] ?? null, $k['pajak_tnkb_jatuh_tempo'] ?? null);
        $k['_status'] = $info;
        $svc_info = service_status_info($k['service_berikutnya'] ?? null, $k['terakhir_service'] ?? null);
        $k['_svc_status'] = $svc_info;
        $k['_stnk_hari'] = selisih_hari($k['pajak_stnk_jatuh_tempo'] ?? null);
        $k['_tnkb_hari'] = selisih_hari($k['pajak_tnkb_jatuh_tempo'] ?? null);
        $k['_svc_hari']  = selisih_hari($k['service_berikutnya'] ?? null);
        if ($filter === 'all' || $filter === $info['key'] || ($filter === 'service' && $svc_info['key'] !== 'success' && $svc_info['key'] !== 'secondary')) {
            $items[] = $k;
        }
    }

    $filename = 'LAPORAN_PAJAK_KENDARAAN_' . date('YmdHis') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $cols = ['No Plat', 'Merk / Tipe', 'Tahun', 'Kode BMN', 'Unit Pengguna', 'STNK Jatuh Tempo', 'Hari Lagi STNK', 'TNKB Jatuh Tempo', 'Hari Lagi TNKB', 'Status Pajak', 'Driver', 'Status Kendaraan'];
    if ($isAdmin) {
        $cols = array_merge($cols, ['Terakhir Service', 'Service Berikutnya', 'Hari Lagi Service', 'Status Service']);
    }

    $instansi = defined('APP_INSTANSI_SHORT') ? APP_INSTANSI_SHORT : 'BPKP DIY';
    $tgl_cetak = date('d M Y H:i');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Borders/>
   <Font ss:FontName="Calibri" ss:Size="11"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="TitleBold">
   <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#0B1C48"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="Subtitle">
   <Font ss:FontName="Calibri" ss:Size="10" ss:Color="#475569"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="HeaderTh">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#0B1C48" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
  </Style>
  <Style ss:ID="CellStr">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0f172a"/>
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
   </Borders>
  </Style>
  <Style ss:ID="CellDanger">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#dc2626"/>
   <Interior ss:Color="#fef2f2" ss:Pattern="Solid"/>
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
   </Borders>
  </Style>
  <Style ss:ID="CellWarning">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#b45309"/>
   <Interior ss:Color="#fffbeb" ss:Pattern="Solid"/>
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
   </Borders>
  </Style>
  <Style ss:ID="CellSuccess">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#047857"/>
   <Interior ss:Color="#f0fdf4" ss:Pattern="Solid"/>
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
   </Borders>
  </Style>
 </Styles>
 <Worksheet ss:Name="Laporan Pajak">
  <Table ss:DefaultColumnWidth="90" x:DefaultColumnWidth="90">
   <Column ss:Width="110"/>
   <Column ss:Width="200"/>
   <Column ss:Width="60"/>
   <Column ss:Width="150"/>
   <Column ss:Width="220"/>
   <Column ss:Width="140"/>
   <Column ss:Width="120"/>
   <Column ss:Width="140"/>
   <Column ss:Width="120"/>
   <Column ss:Width="150"/>
   <Column ss:Width="110"/>
   <Column ss:Width="120"/>
   <?php if ($isAdmin): ?>
   <Column ss:Width="140"/>
   <Column ss:Width="140"/>
   <Column ss:Width="120"/>
   <Column ss:Width="130"/>
   <?php endif; ?>

   <!-- ROW 1: Title -->
   <Row ss:Height="30">
    <Cell ss:MergeAcross="<?= count($cols) - 1 ?>" ss:StyleID="TitleBold">
     <Data ss:Type="String">LAPORAN PAJAK &amp; SERVICE KENDARAAN - <?= strtoupper($instansi) ?></Data>
    </Cell>
   </Row>
   <!-- ROW 2: Subtitle tanggal cetak -->
   <Row ss:Height="22">
    <Cell ss:MergeAcross="<?= count($cols) - 1 ?>" ss:StyleID="Subtitle">
     <Data ss:Type="String">Dicetak pada: <?= $tgl_cetak ?> • Total data: <?= count($items) ?> kendaraan</Data>
    </Cell>
   </Row>
   <!-- ROW 3: Spacer -->
   <Row ss:Height="6"></Row>

   <!-- ROW 4: Header -->
   <Row ss:Height="28">
    <?php foreach ($cols as $c): ?>
    <Cell ss:StyleID="HeaderTh"><Data ss:Type="String"><?= htmlspecialchars($c) ?></Data></Cell>
    <?php endforeach; ?>
   </Row>

   <!-- ROW DATA -->
   <?php $no = 0; foreach ($items as $k): $no++;
        $info = $k['_status'];
        $svc  = $k['_svc_status'];
        $stnk_hari = $k['_stnk_hari'];
        $tnkb_hari = $k['_tnkb_hari'];
        $svc_hari  = $k['_svc_hari'];
        // Style per row berdasarkan status pajak global
        $keyCls = $info['key'] ?? 'secondary';
        if ($keyCls === 'danger') $rowStyle = 'CellDanger';
        elseif ($keyCls === 'warning') $rowStyle = 'CellWarning';
        elseif ($keyCls === 'success') $rowStyle = 'CellSuccess';
        else $rowStyle = 'CellStr';

        $rowData = [
            strtoupper($k['no_plat']),
            trim(($k['merk'] ?? '') . ' ' . ($k['tipe'] ?? '')),
            $k['tahun'] ?? '-',
            $k['kode_bmn'] ?? '-',
            $k['unit_pengguna'] ?? '-',
            !empty($k['pajak_stnk_jatuh_tempo']) ? format_date($k['pajak_stnk_jatuh_tempo'], false) : '-',
            $stnk_hari !== null ? ($stnk_hari < 0 ? 'Lewat '.($stnk_hari * -1).' hari' : ($stnk_hari.' hari lagi')) : '-',
            !empty($k['pajak_tnkb_jatuh_tempo']) ? format_date($k['pajak_tnkb_jatuh_tempo'], false) : '-',
            $tnkb_hari !== null ? ($tnkb_hari < 0 ? 'Lewat '.($tnkb_hari * -1).' hari' : ($tnkb_hari.' hari lagi')) : '-',
            $info['label'],
            $k['driver'] ?? '-',
            ucfirst($k['status'] ?? 'aktif'),
        ];
        if ($isAdmin) {
            $rowData[] = !empty($k['terakhir_service']) ? format_date($k['terakhir_service'], false) : '-';
            $rowData[] = !empty($k['service_berikutnya']) ? format_date($k['service_berikutnya'], false) : '-';
            $rowData[] = $svc_hari !== null ? ($svc_hari < 0 ? 'Lewat '.($svc_hari * -1).' hari' : ($svc_hari.' hari lagi')) : '-';
            $rowData[] = $svc['label'];
        }
   ?>
   <Row ss:Height="26">
    <?php foreach ($rowData as $cellVal): ?>
    <Cell ss:StyleID="<?= $rowStyle ?>"><Data ss:Type="String"><?= htmlspecialchars($cellVal) ?></Data></Cell>
    <?php endforeach; ?>
   </Row>
   <?php endforeach; ?>

   <!-- Footer row kosong -->
   <Row ss:Height="8"></Row>
   <Row ss:Height="22">
    <Cell ss:MergeAcross="<?= count($cols) - 1 ?>" ss:StyleID="Subtitle">
     <Data ss:Type="String">— End of Report • Generated by Sistem Informasi BPKP DIY —</Data>
    </Cell>
   </Row>
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <FreezePanes/>
   <FrozenNoSplit/>
   <SplitHorizontal>4</SplitHorizontal>
   <TopRowBottomPane>4</TopRowBottomPane>
   <ActivePane>2</ActivePane>
   <Print>
    <ValidPrinterInfo/>
    <PaperSizeIndex>9</PaperSizeIndex>
    <HorizontalResolution>-4</HorizontalResolution>
    <VerticalResolution>-4</VerticalResolution>
   </Print>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
<?php
    exit;
}

// ===== HALAMAN UI PAJAK (NORMAL MODE) =====
$page_title = 'Pengingat Pajak & Service Kendaraan';
$active_menu = 'pajak';
require_once __DIR__ . '/../partials/header.php';
if (!$user) redirect_login();

$isAdmin = is_admin();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$view   = isset($_GET['view']) ? $_GET['view'] : 'card';

$migration_cols = [
    'kode_bmn'                  => "ALTER TABLE kendaraan ADD COLUMN kode_bmn VARCHAR(60) DEFAULT NULL",
    'unit_pengguna'             => "ALTER TABLE kendaraan ADD COLUMN unit_pengguna VARCHAR(120) DEFAULT NULL",
    'pajak_stnk_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_stnk_jatuh_tempo DATE DEFAULT NULL",
    'pajak_tnkb_jatuh_tempo'    => "ALTER TABLE kendaraan ADD COLUMN pajak_tnkb_jatuh_tempo DATE DEFAULT NULL",
    'terakhir_service'          => "ALTER TABLE kendaraan ADD COLUMN terakhir_service DATE DEFAULT NULL",
    'service_berikutnya'        => "ALTER TABLE kendaraan ADD COLUMN service_berikutnya DATE DEFAULT NULL",
    'catatan_service'           => "ALTER TABLE kendaraan ADD COLUMN catatan_service TEXT DEFAULT NULL",
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

$where_sql = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where_sql .= ' AND (no_plat LIKE ? OR merk LIKE ? OR tipe LIKE ? OR kode_bmn LIKE ? OR unit_pengguna LIKE ? OR driver LIKE ?)';
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
}

$sql = "SELECT * FROM kendaraan $where_sql ORDER BY no_plat ASC";
try {
    $kendaraan_all = db()->fetchAll($sql, $params);
} catch (PDOException $e) {
    $kendaraan_all = [];
}

$items = [];
$counts = ['all' => 0, 'lewat' => 0, 'warning' => 0, 'aman' => 0];
$svc_counts = ['all' => 0, 'lewat' => 0, 'warning' => 0, 'aman' => 0];
foreach ($kendaraan_all as $k) {
    $info = pajak_status_info($k['pajak_stnk_jatuh_tempo'] ?? null, $k['pajak_tnkb_jatuh_tempo'] ?? null);
    $k['_status'] = $info;
    $svc_info = service_status_info($k['service_berikutnya'] ?? null, $k['terakhir_service'] ?? null);
    $k['_svc_status'] = $svc_info;
    $k['_stnk_hari'] = selisih_hari($k['pajak_stnk_jatuh_tempo'] ?? null);
    $k['_tnkb_hari'] = selisih_hari($k['pajak_tnkb_jatuh_tempo'] ?? null);
    $k['_svc_hari']  = selisih_hari($k['service_berikutnya'] ?? null);
    $counts['all']++;
    $counts[$info['key']] = ($counts[$info['key']] ?? 0) + 1;
    $svc_counts['all']++;
    $svc_counts[$svc_info['key']] = ($svc_counts[$svc_info['key']] ?? 0) + 1;
    if ($filter === 'all' || $filter === $info['key'] || ($filter === 'service' && $svc_info['key'] !== 'success' && $svc_info['key'] !== 'secondary')) {
        $items[] = $k;
    }
}

function badge_hari($hari) {
    if ($hari === null) {
        return '<span class="text-muted" style="font-size:10px;font-style:italic;color:#94a3b8">—</span>';
    }
    if ($hari < 0) {
        return '<span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center fw-bold" style="font-size:9.5px;background:rgba(239,68,68,0.1);color:#dc2626;border:1px solid rgba(239,68,68,0.3)"><i class="bi bi-exclamation-octagon-fill me-1" style="font-size:9px"></i>Lewat '.($hari * -1).' hari</span>';
    } elseif ($hari <= 30) {
        return '<span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center fw-bold" style="font-size:9.5px;background:rgba(245,158,11,0.12);color:#b45309;border:1px solid rgba(245,158,11,0.3)"><i class="bi bi-exclamation-triangle-fill me-1" style="font-size:9px"></i>'.$hari.' hari lagi</span>';
    } else {
        return '<span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center fw-bold" style="font-size:9.5px;background:rgba(16,185,129,0.08);color:#047857;border:1px solid rgba(16,185,129,0.25)"><i class="bi bi-shield-check me-1" style="font-size:9px"></i>'.$hari.' hari</span>';
    }
}
?>

<div class="page-container">
    <div class="mb-3 card border-0" style="border-radius:16px;border:1.5px solid #e5edf8;background:#fff;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,0.04)">
        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:12px;padding:13px 18px 12px 18px">
            <div style="flex:1 1 560px;min-width:0;max-width:100%">
                <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider:'›'">
                    <ol class="breadcrumb mb-2" style="font-size:9.5px;margin:0">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard.php') ?>" class="text-decoration-none" style="color:#3B5FC7;font-weight:700;opacity:0.8;transition:opacity .15s" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page" style="color:#0B1C48;font-weight:800">Pengingat Pajak &amp; Service Kendaraan</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-start gap-2" style="margin-top:0px">
                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#3B5FC7,#1F3A8B);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(59,95,199,0.2);flex-shrink:0">
                        <i class="bi bi-calendar2-week-fill" style="font-size:14px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <h2 style="font-size:15px;color:#0B1C48;font-weight:800;letter-spacing:-0.2px;margin:0 0 3px 0;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            Pengingat Jadwal Pajak &amp; Service Rutin
                        </h2>
                        <p class="mb-0" style="font-size:9.5px;line-height:1.5;color:#64748b;max-width:none;margin:0">
                            Jadwal <strong>STNK 1 Tahun</strong>, <strong>TNKB 5 Tahun</strong>, &amp; <strong>Perawatan Rutin</strong>. Koordinasikan dengan Bagian Umum untuk realisasi pembayaran &amp; booking bengkel resmi.
                        </p>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end" style="flex:0 0 auto;min-width:0">
                <?php if ($isAdmin): ?>
                <a href="<?= base_url('pajak/index.php') ?>?<?= http_build_query(array_merge($_GET, ['export'=>'1'])) ?>" target="_blank"
                   class="btn fw-bold d-inline-flex align-items-center" style="border-radius:10px;padding:7px 14px;font-size:10.5px;background:linear-gradient(135deg,#059669,#047857);border:none;color:#fff;box-shadow:0 3px 10px rgba(5,150,105,0.2);letter-spacing:0.15px;white-space:nowrap">
                    <i class="bi bi-file-earmark-excel-fill me-1.5" style="font-size:11px"></i>Ekspor Excel
                </a>
                <?php endif; ?>
                <a href="<?= base_url('master/kendaraan.php') ?>" class="btn fw-bold border d-inline-flex align-items-center" style="border-radius:10px;padding:7px 14px;font-size:10.5px;border-color:#dbe6f5;background:#fff;color:#0B1C48;box-shadow:none;white-space:nowrap">
                    <i class="bi bi-sliders me-1.5" style="font-size:11px"></i>Kelola Master
                </a>
            </div>
        </div>
    </div>

    <!-- ============ STAT CARDS PAJAK (SEMUA USER) — CLEAN SLIM ============ -->
    <div class="row g-2 mb-3">
        <div class="col-xl-4 col-md-6">
            <div class="card h-100" style="border-radius:14px;border:1.5px solid #e5edf8;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,0.03)">
                <div class="card-body" style="padding:12px 16px">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div style="min-width:0;flex:1">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <i class="bi bi-exclamation-octagon-fill" style="font-size:10px;color:#dc2626"></i>
                                <span style="font-size:9.5px;color:#64748b;font-weight:700;letter-spacing:0.25px;text-transform:uppercase">Lewat Jatuh Tempo</span>
                            </div>
                            <div class="fw-extrabold" style="font-size:24px;color:#0B1C48;letter-spacing:-0.35px;line-height:1"><?= $counts['lewat'] ?> <span style="font-size:11px;font-weight:700;color:#64748b;opacity:0.9">unit</span></div>
                        </div>
                        <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-exclamation-octagon-fill" style="font-size:17px;color:#dc2626"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card h-100" style="border-radius:14px;border:1.5px solid #e5edf8;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,0.03)">
                <div class="card-body" style="padding:12px 16px">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div style="min-width:0;flex:1">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <i class="bi bi-exclamation-triangle-fill" style="font-size:10px;color:#d97706"></i>
                                <span style="font-size:9.5px;color:#64748b;font-weight:700;letter-spacing:0.25px;text-transform:uppercase">Mendekati (≤30 hari)</span>
                            </div>
                            <div class="fw-extrabold" style="font-size:24px;color:#0B1C48;letter-spacing:-0.35px;line-height:1"><?= $counts['warning'] ?> <span style="font-size:11px;font-weight:700;color:#64748b;opacity:0.9">unit</span></div>
                        </div>
                        <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,0.09);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size:17px;color:#d97706"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card h-100" style="border-radius:14px;border:1.5px solid #e5edf8;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,0.03)">
                <div class="card-body" style="padding:12px 16px">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div style="min-width:0;flex:1">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <i class="bi bi-shield-fill-check" style="font-size:10px;color:#059669"></i>
                                <span style="font-size:9.5px;color:#64748b;font-weight:700;letter-spacing:0.25px;text-transform:uppercase">Pajak Berlaku Aktif</span>
                            </div>
                            <div class="fw-extrabold" style="font-size:24px;color:#0B1C48;letter-spacing:-0.35px;line-height:1"><?= $counts['aman'] ?> <span style="font-size:11px;font-weight:700;color:#64748b;opacity:0.9">unit</span></div>
                        </div>
                        <div style="width:42px;height:42px;border-radius:12px;background:rgba(16,185,129,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-shield-fill-check" style="font-size:17px;color:#059669"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ STAT CARDS ADMIN-ONLY : SERVICE SCHEDULE CLEAN ============ -->
    <?php if ($isAdmin): ?>
    <div class="card border-0 mb-3" style="border-radius:14px;border:1.5px solid #e5edf8;background:#fff;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,0.03)">
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-2.5" style="background:linear-gradient(180deg,#fff,#fafcff);border-bottom:1px solid #f1f5f9">
            <span style="font-size:9.5px;color:#0B1C48;font-weight:800;letter-spacing:0.35px;text-transform:uppercase">
                <i class="bi bi-wrench-adjustable-circle me-1.5" style="font-size:10px;color:#7c3aed"></i>Jadwal Service Rutin
            </span>
            <span class="text-muted ms-auto" style="font-size:8.5px;color:#94a3b8;font-weight:600;white-space:nowrap">
                <i class="bi bi-shield-lock me-1" style="font-size:8.5px"></i>Hanya Admin
            </span>
        </div>
        <div class="px-4 py-3">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2.5" style="padding:9px 12px;border-radius:11px;background:rgba(239,68,68,0.05);border:1.2px solid rgba(239,68,68,0.12)">
                    <div style="width:32px;height:32px;border-radius:9px;background:rgba(239,68,68,0.08);color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-wrench-adjustable" style="font-size:12px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:8px;color:#64748b;font-weight:700;letter-spacing:0.45px;text-transform:uppercase;margin-bottom:0px">Lewat Service</div>
                        <div style="font-size:19px;color:#0B1C48;font-weight:800;letter-spacing:-0.25px;line-height:1.2"><?= $svc_counts['lewat'] ?> <span style="font-size:9.5px;color:#64748b;font-weight:700">unit</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2.5" style="padding:9px 12px;border-radius:11px;background:rgba(245,158,11,0.05);border:1.2px solid rgba(245,158,11,0.16)">
                    <div style="width:32px;height:32px;border-radius:9px;background:rgba(245,158,11,0.09);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-hourglass-split" style="font-size:12px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:8px;color:#64748b;font-weight:700;letter-spacing:0.45px;text-transform:uppercase;margin-bottom:0px">Akan Service (≤30h)</div>
                        <div style="font-size:19px;color:#0B1C48;font-weight:800;letter-spacing:-0.25px;line-height:1.2"><?= $svc_counts['warning'] ?> <span style="font-size:9.5px;color:#64748b;font-weight:700">unit</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2.5" style="padding:9px 12px;border-radius:11px;background:rgba(16,185,129,0.05);border:1.2px solid rgba(16,185,129,0.16)">
                    <div style="width:32px;height:32px;border-radius:9px;background:rgba(16,185,129,0.08);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-shield-check" style="font-size:12px"></i>
                    </div>
                    <div style="min-width:0;flex:1">
                        <div style="font-size:8px;color:#64748b;font-weight:700;letter-spacing:0.45px;text-transform:uppercase;margin-bottom:0px">Service Terbaru</div>
                        <div style="font-size:19px;color:#0B1C48;font-weight:800;letter-spacing:-0.25px;line-height:1.2"><?= $svc_counts['aman'] ?> <span style="font-size:9.5px;color:#64748b;font-weight:700">unit</span></div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============ FILTER + SEARCH + TAB TOGGLE VIEW ============ -->
    <div class="card border-0 mb-3" style="border-radius:16px;border:1.5px solid #e5edf8;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,0.04)">
        <div class="py-3 px-4 d-flex flex-wrap gap-2.5 align-items-center justify-content-between" style="background:linear-gradient(180deg,#fff,#fafcff);border-bottom:1px solid #f1f5f9">
            <div class="d-flex gap-1.5 align-items-center flex-wrap me-auto">
                <div class="d-inline-flex p-1 rounded-pill align-items-center" style="background:#eef2ff;border:1px solid #c7d2fe">
                    <a href="?<?= http_build_query(array_merge($_GET,['view'=>'card'])) ?>"
                       class="btn rounded-pill fw-extrabold d-inline-flex align-items-center border-0"
                       style="font-size:10.5px;padding:6px 16px;gap:6px;<?= $view==='card' ? 'background:linear-gradient(135deg,#0B1C48,#3B5FC7);color:#fff;box-shadow:0 3px 10px rgba(11,28,72,0.22)' : 'background:transparent;color:#4338ca' ?>">
                        <i class="bi bi-grid-fill"></i>✨ Ringkasan Mobil
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET,['view'=>'table'])) ?>"
                       class="btn rounded-pill fw-extrabold d-inline-flex align-items-center border-0"
                       style="font-size:10.5px;padding:6px 16px;gap:6px;<?= $view==='table' ? 'background:linear-gradient(135deg,#0B1C48,#3B5FC7);color:#fff;box-shadow:0 3px 10px rgba(11,28,72,0.22)' : 'background:transparent;color:#4338ca' ?>">
                        <i class="bi bi-table"></i>📊 Tabel Lengkap
                    </a>
                </div>
            </div>
            <div class="search-box ms-auto flex-grow-1" style="max-width:420px;min-width:240px">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" id="cariPajak" placeholder="Cari nopol, merk, kode BMN, unit, driver..." value="<?= sanitize($search) ?>">
            </div>
            <div class="d-flex gap-1.5 flex-wrap align-items-center">
                <a href="?<?= http_build_query(array_merge($_GET, ['filter'=>'all'])) ?>" class="btn rounded-pill fw-extrabold d-inline-flex align-items-center" style="font-size:10px;padding:6px 14px;<?= $filter==='all' ? 'background:linear-gradient(135deg,#0B1C48,#1F3A8B);color:#fff;border:none;box-shadow:0 2px 8px rgba(11,28,72,0.25)' : 'border:1.5px solid #e2e8f0;color:#475569;background:#fff' ?>">
                    Semua <span class="ms-1.5 badge rounded-pill" style="font-size:8.5px;padding:1.5px 6px;<?= $filter==='all' ? 'background:rgba(255,255,255,0.22);color:#fff;font-weight:800' : 'background:#0B1C48;color:#fff;font-weight:800' ?>"><?= $counts['all'] ?></span>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['filter'=>'lewat'])) ?>" class="btn rounded-pill fw-extrabold d-inline-flex align-items-center" style="font-size:10px;padding:6px 14px;<?= $filter==='lewat' ? 'background:#ef4444;color:#fff;border:none;box-shadow:0 2px 8px rgba(239,68,68,0.25)' : 'border:1.5px solid #e2e8f0;color:#475569;background:#fff' ?>">
                    Lewat <span class="ms-1.5 badge rounded-pill" style="font-size:8.5px;padding:1.5px 6px;<?= $filter==='lewat' ? 'background:rgba(0,0,0,0.2);color:#fff;font-weight:800' : 'background:#ef4444;color:#fff;font-weight:800' ?>"><?= $counts['lewat'] ?></span>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['filter'=>'warning'])) ?>" class="btn rounded-pill fw-extrabold d-inline-flex align-items-center" style="font-size:10px;padding:6px 14px;<?= $filter==='warning' ? 'background:#f59e0b;color:#fff;border:none;box-shadow:0 2px 8px rgba(245,158,11,0.25)' : 'border:1.5px solid #e2e8f0;color:#475569;background:#fff' ?>">
                    Mendekati <span class="ms-1.5 badge rounded-pill" style="font-size:8.5px;padding:1.5px 6px;<?= $filter==='warning' ? 'background:rgba(0,0,0,0.2);color:#fff;font-weight:800' : 'background:#f59e0b;color:#fff;font-weight:800' ?>"><?= $counts['warning'] ?></span>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['filter'=>'aman'])) ?>" class="btn rounded-pill fw-extrabold d-inline-flex align-items-center" style="font-size:10px;padding:6px 14px;<?= $filter==='aman' ? 'background:#10b981;color:#fff;border:none;box-shadow:0 2px 8px rgba(16,185,129,0.25)' : 'border:1.5px solid #e2e8f0;color:#475569;background:#fff' ?>">
                    Aman <span class="ms-1.5 badge rounded-pill" style="font-size:8.5px;padding:1.5px 6px;<?= $filter==='aman' ? 'background:rgba(0,0,0,0.2);color:#fff;font-weight:800' : 'background:#10b981;color:#fff;font-weight:800' ?>"><?= $counts['aman'] ?></span>
                </a>
                <?php if ($isAdmin): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['filter'=>'service'])) ?>" class="btn rounded-pill fw-extrabold d-inline-flex align-items-center" style="font-size:10px;padding:6px 14px;<?= $filter==='service' ? 'background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;box-shadow:0 2px 8px rgba(124,58,237,0.25)' : 'border:1.5px dashed #c4b5fd;color:#6d28d9;background:#faf5ff' ?>">
                    <i class="bi bi-person-lock me-1" style="font-size:9.5px"></i>Service Alert
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============ RINGKASAN CARD VIEW (DEFAULT: ✨ MOBIL PER CARD) ============ -->
    <?php if ($view === 'card'): ?>
    <div class="row g-3 mb-4">
        <?php if (count($items) === 0): ?>
            <div class="col-12">
                <div class="card border-0 text-center" style="border-radius:16px;border:1.5px dashed #cbd5e1;padding:60px 20px">
                    <i class="bi bi-calendar-x" style="font-size:46px;color:#cbd5e1"></i>
                    <div class="mt-3 fw-bold" style="color:#475569;font-size:13px">Belum ada data pajak / service</div>
                    <p class="mb-0 text-muted mt-1" style="font-size:10.5px">Tambahkan tanggal pajak di <a href="<?= base_url('master/kendaraan.php') ?>" class="text-decoration-none" style="color:#3B5FC7"><b>Master Kendaraan</b></a>.</p>
                </div>
            </div>
        <?php else: ?>
        <?php foreach ($items as $k): $info = $k['_status']; $svc = $k['_svc_status']; ?>
        <?php
            $clsGlobal = $info['cls'] ?? 'secondary';
            if (!empty($k['_svc_hari']) && $k['_svc_hari'] < 0 && in_array($clsGlobal,['success','warning'])) $clsGlobal = 'danger';
            $borderMap = [
                'danger'  => 'border-color:#fecaca;box-shadow:0 6px 18px rgba(239,68,68,0.08)',
                'warning' => 'border-color:#fde68a;box-shadow:0 6px 18px rgba(245,158,11,0.07)',
                'success' => 'border-color:#a7f3d0;box-shadow:0 6px 18px rgba(16,185,129,0.06)',
                'secondary'=> 'border-color:#e2e8f0;box-shadow:0 6px 18px rgba(15,23,42,0.04)'
            ];
            $tipe = $k['tipe'] ?? '';
        ?>
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card h-100 overflow-hidden border-2" style="border-radius:18px;border:1.5px solid #e5edf8;<?= $borderMap[$clsGlobal] ?? $borderMap['secondary'] ?>">
                <!-- Header compact (tanpa foto) -->
                <div style="position:relative;background:linear-gradient(135deg,#f5f8ff 0%,#eef3ff 45%,#f4f7ff 100%);border-bottom:1.5px solid #e3ebfb;padding:14px 16px 12px 16px">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <!-- Plat nomor -->
                        <div style="min-width:0;flex:1">
                            <div style="font-size:8px;color:#64748b;font-weight:800;letter-spacing:0.45px;text-transform:uppercase;margin-bottom:2px">No. Plat</div>
                            <div class="text-uppercase fw-extrabold" style="font-size:17px;color:#0B1C48;letter-spacing:0.4px;line-height:1.1"><?= $k['no_plat'] ?></div>
                            <div class="fw-bold mt-1" style="font-size:11.5px;color:#0f172a;line-height:1.3">
                                <?= sanitize($k['merk'].' '.$tipe) ?>
                                <span class="fw-semibold text-muted ms-1" style="font-size:9.5px">(<?= $k['tahun'] ?? '-' ?>)</span>
                            </div>
                        </div>
                        <!-- Badge status -->
                        <div class="flex-shrink-0">
                            <?php
                                $bgMap = ['danger'=>'#dc2626','warning'=>'#d97706','success'=>'#059669','secondary'=>'#475569'];
                            ?>
                            <span class="badge rounded-pill px-3 py-1.5 fw-extrabold d-inline-flex align-items-center gap-1" style="background:<?= $bgMap[$clsGlobal] ?? $bgMap['secondary'] ?>;color:#fff;font-size:9.5px;box-shadow:0 3px 8px rgba(0,0,0,0.15);border:1px solid rgba(255,255,255,0.25)">
                                <i class="bi bi-<?= $clsGlobal==='danger'?'x-circle-fill':($clsGlobal==='warning'?'exclamation-triangle-fill':($clsGlobal==='success'?'shield-check-fill':'question-circle-fill')) ?>"></i>
                                <?= $info['label'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Body card -->
                <div class="card-body" style="padding:13px 14px 11px 14px">
                    <div class="d-flex align-items-start justify-content-between mb-2" style="gap:8px">
                        <div style="min-width:0;flex:1">
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <span class="badge rounded-pill" style="font-size:8.5px;background:rgba(59,95,199,0.08);color:#1e3a8a;padding:2.5px 7px;font-weight:800">
                                    <i class="bi bi-people-fill me-0.5"></i><?= $k['kapasitas'] ?? 0 ?> Penumpang
                                </span>
                                <?php if (!empty($k['driver'])): ?>
                                <span class="badge rounded-pill" style="font-size:8.5px;background:rgba(16,185,129,0.08);color:#047857;padding:2.5px 7px;font-weight:800">
                                    <i class="bi bi-person-gear me-0.5"></i><?= sanitize($k['driver']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($k['kode_bmn'])): ?>
                                <span class="badge rounded-pill" style="font-size:8.5px;background:#f1f5f9;color:#334155;padding:2.5px 7px;font-weight:800">
                                    <i class="bi bi-upc-scan me-0.5"></i><?= sanitize($k['kode_bmn']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Ikon Ringkasan Utama (STNK / TNKB / SERVICE) User Request -->
                    <div class="mt-2 pt-1" style="border-top:1px dashed #e2e8f0">
                        <div class="mb-2.5"></div>

                        <!-- 🧾 STNK -->
                        <div class="d-flex align-items-center gap-2.5 mb-2">
                            <div style="width:28px;height:28px;border-radius:9px;background:#fff7ed;border:1.2px solid #fed7aa;color:#c2410c;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-receipt-cutoff" style="font-size:12px"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-0.5">
                                    <span style="font-size:8.5px;font-weight:800;color:#9a3412;letter-spacing:0.4px;text-transform:uppercase">STNK 1 Tahunan</span>
                                    <?= badge_hari($k['_stnk_hari']) ?>
                                </div>
                                <div class="fw-semibold" style="font-size:10.5px;color:#0f172a;line-height:1.3">
                                    Jatuh tempo: <b><?= !empty($k['pajak_stnk_jatuh_tempo'])?format_date($k['pajak_stnk_jatuh_tempo'],false):'<em style="color:#94a3b8">Belum diatur</em>' ?></b>
                                </div>
                            </div>
                        </div>

                        <!-- 📋 TNKB -->
                        <div class="d-flex align-items-center gap-2.5 mb-2">
                            <div style="width:28px;height:28px;border-radius:9px;background:#eff6ff;border:1.2px solid #bfdbfe;color:#1d4ed8;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-card-checklist" style="font-size:12px"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-0.5">
                                    <span style="font-size:8.5px;font-weight:800;color:#1e40af;letter-spacing:0.4px;text-transform:uppercase">TNKB 5 Tahunan</span>
                                    <?= badge_hari($k['_tnkb_hari']) ?>
                                </div>
                                <div class="fw-semibold" style="font-size:10.5px;color:#0f172a;line-height:1.3">
                                    Ganti plat: <b><?= !empty($k['pajak_tnkb_jatuh_tempo'])?format_date($k['pajak_tnkb_jatuh_tempo'],false):'<em style="color:#94a3b8">Belum diatur</em>' ?></b>
                                </div>
                            </div>
                        </div>

                        <!-- 🔧 SERVICE Terakhir & berikutnya -->
                        <div class="d-flex align-items-center gap-2.5">
                            <div style="width:28px;height:28px;border-radius:9px;background:#faf5ff;border:1.2px solid #ddd6fe;color:#6d28d9;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-wrench-adjustable-circle" style="font-size:12px"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-0.5">
                                    <span style="font-size:8.5px;font-weight:800;color:#5b21b6;letter-spacing:0.4px;text-transform:uppercase">Service Rutin</span>
                                    <?= badge_hari($k['_svc_hari']) ?>
                                </div>
                                <div style="font-size:10.5px;color:#334155;line-height:1.5">
                                    <span class="text-muted fw-semibold" style="font-size:9px;letter-spacing:0.2px">Terakhir:</span>
                                    <b class="me-2"><?= !empty($k['terakhir_service'])?format_date($k['terakhir_service'],false):'<em style="color:#94a3b8">—</em>' ?></b>
                                    <span class="text-muted fw-semibold" style="font-size:9px;letter-spacing:0.2px">→ Kembali:</span>
                                    <b style="color:#5b21b6"><?= !empty($k['service_berikutnya'])?format_date($k['service_berikutnya'],false):'<em style="color:#94a3b8">—</em>' ?></b>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol aksi bawah -->
                    <div class="mt-3 pt-2 d-flex gap-1.5 align-items-center justify-content-between" style="border-top:1px solid #f1f5f9">
                        <div style="min-width:0;flex:1">
                            <div style="font-size:8.5px;color:#64748b;letter-spacing:0.25px;text-transform:uppercase;font-weight:800">Unit Pengguna</div>
                            <div style="font-size:10.5px;color:#0f172a;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700"><?= !empty($k['unit_pengguna'])?sanitize($k['unit_pengguna']):'<em style="color:#94a3b8;font-style:italic">Belum ditetapkan</em>' ?></div>
                        </div>
                        <div class="d-flex gap-1.5 flex-shrink-0">
                            <a href="<?= base_url('master/kendaraan.php') ?>?edit=<?= $k['id'] ?>" target="_blank" class="btn btn-sm fw-extrabold" style="border-radius:10px;padding:6px 11px;font-size:9.5px;background:linear-gradient(135deg,#3B5FC7,#1F3A8B);color:#fff;border:0;box-shadow:0 2px 6px rgba(59,95,199,0.22)">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ============ TABLE VIEW (HANYA JIKA VIEW = TABLE) ============ -->
    <?php if ($view === 'table'): ?>
    <div class="card border-0 mb-4" style="border-radius:16px;border:1.5px solid #e5edf8;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,0.04)">

        <div style="overflow-x:auto;background:#fff">
            <table class="table mb-0 align-middle" style="min-width:<?= $isAdmin ? '1790px' : '1510px' ?>">
                <thead style="background:linear-gradient(180deg,#f8fafc,#f1f5f9);position:sticky;top:0;z-index:2">
                    <tr>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#475569;font-weight:800;border-bottom:1.5px solid #e2e8f0;min-width:120px">No. Plat</th>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#475569;font-weight:800;border-bottom:1.5px solid #e2e8f0;min-width:220px">Kendaraan</th>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#475569;font-weight:800;border-bottom:1.5px solid #e2e8f0;min-width:140px">Kode BMN</th>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#475569;font-weight:800;border-bottom:1.5px solid #e2e8f0;min-width:200px">Unit Pengguna</th>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#92400e;font-weight:800;border-bottom:1.5px solid #e2e8f0" colspan="2">
                            <i class="bi bi-stopwatch me-1" style="font-size:11px"></i>STNK 1 Tahunan · Pengingat Bayar
                        </th>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#92400e;font-weight:800;border-bottom:1.5px solid #e2e8f0" colspan="2">
                            <i class="bi bi-stopwatch me-1" style="font-size:11px"></i>TNKB 5 Tahunan · Pengingat Bayar
                        </th>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#475569;font-weight:800;border-bottom:1.5px solid #e2e8f0;width:140px">Status Pajak</th>
                        <?php if ($isAdmin): ?>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#5b21b6;font-weight:800;border-bottom:1.5px solid #e2e8f0;min-width:140px;background:linear-gradient(180deg,#faf5ff,#f5f3ff)" colspan="2">
                            <i class="bi bi-wrench-adjustable me-1" style="font-size:11px"></i>Service Rutin <span class="badge rounded-pill ms-1 px-2 py-0.5" style="font-size:8px;background:#5b21b6;color:#fff;font-weight:800">ADMIN</span>
                        </th>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#475569;font-weight:800;border-bottom:1.5px solid #e2e8f0;background:linear-gradient(180deg,#faf5ff,#f5f3ff);width:140px">Status Service</th>
                        <?php endif; ?>
                        <th style="padding:13px 18px;font-size:10px;letter-spacing:0.4px;color:#475569;font-weight:800;border-bottom:1.5px solid #e2e8f0;width:130px">Aksi</th>
                    </tr>
                    <?php if ($isAdmin): ?>
                    <tr style="background:linear-gradient(180deg,#f1f5f9,#f8fafc);border-bottom:1px solid #e2e8f0">
                        <th style="padding:6px 18px" colspan="5"></th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-right:3px solid #fde68a;border-bottom:none;width:155px">Tgl. Jatuh Tempo</th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none;width:145px">⏰ Hitung Mundur</th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-right:3px solid #fde68a;border-bottom:none;width:155px">Tgl. Jatuh Tempo</th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none;width:145px">⏰ Hitung Mundur</th>
                        <th style="padding:6px 18px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none"></th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#7c3aed;font-weight:800;background:#faf5ff;border-bottom:none;border-right:2px dashed #ddd6fe;width:155px">Terakhir / Berikutnya</th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#7c3aed;font-weight:800;background:#faf5ff;border-bottom:none;width:145px">⏰ Hitung Mundur</th>
                        <th style="padding:6px 18px;font-size:8.5px;letter-spacing:0.35px;color:#7c3aed;font-weight:800;background:#faf5ff;border-bottom:none"></th>
                        <th style="padding:6px 18px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none"></th>
                    </tr>
                    <?php else: ?>
                    <tr style="background:linear-gradient(180deg,#f1f5f9,#f8fafc);border-bottom:1px solid #e2e8f0">
                        <th style="padding:6px 18px" colspan="5"></th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-right:3px solid #fde68a;border-bottom:none;width:155px">Tgl. Jatuh Tempo</th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none;width:145px">⏰ Hitung Mundur</th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-right:3px solid #fde68a;border-bottom:none;width:155px">Tgl. Jatuh Tempo</th>
                        <th style="padding:6px 12px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none;width:145px">⏰ Hitung Mundur</th>
                        <th style="padding:6px 18px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none"></th>
                        <th style="padding:6px 18px;font-size:8.5px;letter-spacing:0.35px;color:#64748b;font-weight:800;border-bottom:none"></th>
                    </tr>
                    <?php endif; ?>
                </thead>
                <tbody style="background:#fff">
                    <?php if (count($items) === 0): ?>
                        <tr>
                            <td colspan="<?= $isAdmin ? 14 : 10 ?>" style="padding:60px 20px;text-align:center">
                                <div class="mb-2"><i class="bi bi-calendar-x" style="font-size:42px;color:#cbd5e1"></i></div>
                                <div class="fw-bold mb-1" style="color:#475569;font-size:12.5px">Belum Ada Data Pengingat Pajak</div>
                                <p class="mb-0 text-muted" style="font-size:10.5px">Tambahkan data master kendaraan beserta kolom tanggal pajak di menu <strong>Master Kendaraan</strong>.</p>
                            </td>
                        </tr>
                    <?php else: foreach ($items as $k): $info = $k['_status']; $svc = $k['_svc_status'];
                    ?>
                    <tr style="border-bottom:1px solid #f1f5f9" onmouseover="this.style.background='#fbfcff'" onmouseout="this.style.background='#ffffff'">
                        <td style="padding:14px 18px">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="flex-shrink-0" style="width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,#0B1C48,#1F3A8B);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 2px 7px rgba(11,28,72,0.18)">
                                    <i class="bi bi-signpost-split-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-extrabold text-uppercase mb-0.5" style="font-size:12px;color:#0f172a;letter-spacing:0.3px"><?= $k['no_plat'] ?></div>
                                    <div style="font-size:9px;color:#64748b;font-weight:700"><?= !empty($k['status']) ? ucfirst($k['status']) : '-' ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px 18px">
                            <div class="fw-bold mb-0.5" style="font-size:12px;color:#0f172a;line-height:1.35"><?= sanitize($k['merk'] ?? '') ?> <?= sanitize($k['tipe'] ?? '') ?></div>
                            <div class="d-flex gap-1.5 flex-wrap mt-1.5">
                                <span class="badge rounded-pill" style="font-size:9px;background:#f1f5f9;color:#475569;padding:2.5px 7px;font-weight:700">
                                    <i class="bi bi-calendar3 me-0.5" style="font-size:8px"></i>Th. <?= !empty($k['tahun']) ? $k['tahun'] : '-' ?>
                                </span>
                                <span class="badge rounded-pill" style="font-size:9px;background:#f1f5f9;color:#475569;padding:2.5px 7px;font-weight:700">
                                    <i class="bi bi-people-fill me-0.5" style="font-size:8px"></i><?= !empty($k['kapasitas']) ? $k['kapasitas'] : 0 ?> kursi
                                </span>
                                <?php if (!empty($k['driver'])): ?>
                                    <span class="badge rounded-pill" style="font-size:9px;background:rgba(37,99,235,0.1);color:#1d4ed8;padding:2.5px 7px;font-weight:700">
                                        <i class="bi bi-person-gear me-0.5" style="font-size:8px"></i><?= sanitize($k['driver']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding:14px 18px">
                            <?php if (!empty($k['kode_bmn'])): ?>
                                <div class="fw-extrabold text-uppercase" style="font-size:11px;color:#1F3A8B;letter-spacing:0.35px"><?= sanitize($k['kode_bmn']) ?></div>
                            <?php else: ?>
                                <span class="pill-badge-mini" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;padding:2.5px 7px;border-radius:999px;font-size:8.5px;font-weight:800">
                                    <i class="bi bi-dash-circle-dotted me-1" style="font-size:8px"></i>Belum diisi
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 18px">
                            <?php if (!empty($k['unit_pengguna'])): ?>
                                <div class="fw-semibold" style="font-size:11px;color:#475569;line-height:1.45"><?= sanitize($k['unit_pengguna']) ?></div>
                            <?php else: ?>
                                <span class="pill-badge-mini" style="background:rgba(239,68,68,0.08);color:#dc2626;border:1px solid rgba(239,68,68,0.2);padding:2.5px 7px;border-radius:999px;font-size:8.5px;font-weight:800">
                                    <i class="bi bi-person-x-fill me-1" style="font-size:8px"></i>Belum ditetapkan
                                </span>
                            <?php endif; ?>
                        </td>
                        <!-- STNK -->
                        <td style="padding:14px 12px;border-right:3px dashed #fde68a">
                            <?php if (!empty($k['pajak_stnk_jatuh_tempo'])): ?>
                                <div class="fw-bold mb-0.5" style="font-size:11px;color:#0f172a"><?= format_date($k['pajak_stnk_jatuh_tempo'], false) ?></div>
                                <div style="font-size:8.5px;color:#92400e;font-weight:800;letter-spacing:0.35px;text-transform:uppercase">Bayar sebelum tanggal</div>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:10px;font-style:italic;color:#94a3b8">— Tidak diatur</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 12px"><?= badge_hari($k['_stnk_hari']) ?></td>
                        <!-- TNKB -->
                        <td style="padding:14px 12px;border-right:3px dashed #fde68a">
                            <?php if (!empty($k['pajak_tnkb_jatuh_tempo'])): ?>
                                <div class="fw-bold mb-0.5" style="font-size:11px;color:#0f172a"><?= format_date($k['pajak_tnkb_jatuh_tempo'], false) ?></div>
                                <div style="font-size:8.5px;color:#92400e;font-weight:800;letter-spacing:0.35px;text-transform:uppercase">Ganti plat 5 tahunan</div>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:10px;font-style:italic;color:#94a3b8">— Tidak diatur</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 12px"><?= badge_hari($k['_tnkb_hari']) ?></td>
                        <!-- Status Pajak Global -->
                        <td style="padding:14px 18px">
                            <?php
                                $cls_map = [
                                    'danger'  => 'background:rgba(239,68,68,0.1);color:#dc2626;border:1px solid rgba(239,68,68,0.25)',
                                    'warning' => 'background:rgba(245,158,11,0.1);color:#b45309;border:1px solid rgba(245,158,11,0.25)',
                                    'success' => 'background:rgba(16,185,129,0.1);color:#059669;border:1px solid rgba(16,185,129,0.25)',
                                    'secondary' => 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0'
                                ];
                                $icon_map = [
                                    'danger'  => 'bi-x-circle-fill',
                                    'warning' => 'bi-exclamation-triangle-fill',
                                    'success' => 'bi-check-circle-fill',
                                    'secondary' => 'bi-question-circle-fill'
                                ];
                            ?>
                            <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center fw-extrabold" style="font-size:9px;<?= $cls_map[$info['cls']] ?? $cls_map['secondary'] ?>">
                                <i class="bi <?= $icon_map[$info['cls']] ?? $icon_map['secondary'] ?> me-1" style="font-size:8.5px"></i><?= $info['label'] ?>
                            </span>
                        </td>
                        <!-- ADMIN-ONLY : SERVICE -->
                        <?php if ($isAdmin): ?>
                        <td style="padding:14px 12px;background:linear-gradient(180deg,#fcfaff,#ffffff);border-left:2px dashed #ddd6fe">
                            <div style="font-size:10px;line-height:1.55">
                                <div style="margin-bottom:4px">
                                    <span style="font-size:8px;color:#7c3aed;font-weight:800;letter-spacing:0.45px;text-transform:uppercase">Terakhir:</span>
                                    <span class="fw-bold ms-1" style="color:#1e293b;font-size:10px"><?= !empty($k['terakhir_service']) ? format_date($k['terakhir_service'], false) : '<em style="color:#94a3b8;font-style:italic;font-weight:600">—</em>' ?></span>
                                </div>
                                <div>
                                    <span style="font-size:8px;color:#7c3aed;font-weight:800;letter-spacing:0.45px;text-transform:uppercase">Berikutnya:</span>
                                    <span class="fw-bold ms-1" style="color:#5b21b6;font-size:10px"><?= !empty($k['service_berikutnya']) ? format_date($k['service_berikutnya'], false) : '<em style="color:#94a3b8;font-style:italic;font-weight:600">—</em>' ?></span>
                                </div>
                            </div>
                        </td>
                        <td style="padding:14px 12px;background:linear-gradient(180deg,#fcfaff,#ffffff)"><?= badge_hari($k['_svc_hari']) ?></td>
                        <td style="padding:14px 18px;background:linear-gradient(180deg,#fcfaff,#ffffff)">
                            <?php
                                $svc_cls = [
                                    'danger'  => 'background:rgba(139,92,246,0.18);color:#6d28d9;border:1px solid rgba(139,92,246,0.3)',
                                    'warning' => 'background:rgba(139,92,246,0.1);color:#7c3aed;border:1px solid rgba(139,92,246,0.25)',
                                    'success' => 'background:rgba(16,185,129,0.1);color:#047857;border:1px solid rgba(16,185,129,0.25)',
                                    'secondary' => 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0'
                                ];
                            ?>
                            <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center fw-extrabold" style="font-size:9px;<?= $svc_cls[$svc['cls']] ?? $svc_cls['secondary'] ?>">
                                <i class="bi bi-wrench-adjustable me-1" style="font-size:8.5px"></i><?= $svc['label'] ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td style="padding:14px 18px">
                            <div class="d-flex flex-wrap gap-1.5 align-items-center">
                                <a href="<?= base_url('kendaraan/index.php') ?>?tab=sarana&search=<?= urlencode($k['no_plat']) ?>" class="btn btn-sm fw-bold" style="border-radius:9px;padding:5.5px 11px;font-size:10px;background:rgba(31,58,139,0.08);color:#1F3A8B;border:none">
                                    <i class="bi bi-eye me-1" style="font-size:9px"></i>Lihat
                                </a>
                                <?php if ($isAdmin): ?>
                                <a href="<?= base_url('master/kendaraan.php') ?>?edit=<?= $k['id'] ?>" class="btn btn-sm fw-bold" style="border-radius:9px;padding:5.5px 11px;font-size:10px;background:rgba(37,99,235,0.1);color:#1d4ed8;border:none">
                                    <i class="bi bi-pencil me-1" style="font-size:9px"></i>Edit
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($items) > 0): ?>
            <div class="px-4 py-2.5 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background:linear-gradient(180deg,#fff,#fafcff);border-top:1px solid #f1f5f9">
                <div style="font-size:10px;color:#64748b;font-weight:700">
                    Menampilkan <span class="fw-extrabold text-<?= $filter==='lewat'?'danger':($filter==='warning'?'warning':($filter==='service'?'purple':'primary')) ?>"><?= count($items) ?></span> dari total <?= $counts['all'] ?> kendaraan dinas.
                </div>
                <?php if ($isAdmin): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'1'])) ?>" target="_blank" class="btn btn-sm fw-extrabold d-inline-flex align-items-center" style="border-radius:9px;padding:6px 13px;font-size:10px;background:linear-gradient(135deg,#059669,#047857);border:none;color:#fff;box-shadow:0 2px 7px rgba(5,150,105,0.22)">
                    <i class="bi bi-file-earmark-excel-fill me-1" style="font-size:10.5px"></i>Export Data Ini
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
(function(){
    var input = document.getElementById('cariPajak');
    if (!input) return;
    var t;
    input.addEventListener('keydown', function(e){
        if (e.key === 'Enter') {
            clearTimeout(t);
            var url = new URL(window.location.href);
            if (input.value.trim() !== '') url.searchParams.set('search', input.value.trim());
            else url.searchParams.delete('search');
            window.location.href = url.toString();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
