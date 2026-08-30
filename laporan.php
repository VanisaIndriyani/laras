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

// ===== EXPORT PRINT / PDF READY FORMAT =====
if (isset($_GET['print']) && $_GET['print'] === '1') {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/assets/logo.php';
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
    $statusLabel = [
        'pending'=>'Menunggu','disetujui'=>'Disetujui','selesai'=>'Selesai','ditolak'=>'Ditolak','dibatalkan'=>'Dibatalkan'
    ];
    $tgl_sekarang = date('d/m/Y H:i:s');

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Laporan Reservasi - BPKP DIY</title>';
    echo '<style>
        * { box-sizing:border-box; }
        body { font-family: "Segoe UI", Tahoma, sans-serif; color:#0f172a; margin:0; padding:0; font-size:11px; line-height:1.45; background:#fff; }
        @page { size: A4 landscape; margin: 12mm 10mm 12mm 10mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .noprint { display: none !important; }
            .page-break { page-break-before: always; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
        .noprint-wrap { max-width:100%; margin:0 auto; padding:16px 22px 28px; }
        .noprint-bar { margin-bottom:16px; display:flex; gap:10px; align-items:center; justify-content:flex-end; }
        .btn-print { background:#2563eb; color:#fff; border:0; padding:8px 16px; border-radius:8px; font-weight:600; cursor:pointer; box-shadow:0 4px 10px rgba(37,99,235,.25);}
        .btn-print:hover { background:#1d4ed8; }
        .btn-close { background:#f1f5f9; color:#334155; border:0; padding:8px 14px; border-radius:8px; font-weight:600; cursor:pointer;}
        .kop { text-align:center; padding-bottom:12px; border-bottom:2.5px solid #0B1C48; margin-bottom:18px; position:relative;}
        .kop::after { content:""; position:absolute; left:0; right:0; bottom:-5.5px; border-bottom:1.5px solid #0B1C48; }
        .kop .instansi { font-size:15px; font-weight:800; letter-spacing:0.5px; color:#0B1C48; }
        .kop .sub { font-size:10.5px; color:#334155; margin-top:2px; }
        .kop .alamat { font-size:9.5px; color:#475569; margin-top:3px; }
        .judul { text-align:center; margin:8px 0 16px; }
        .judul h1 { margin:0; font-size:15px; font-weight:800; color:#0f172a; letter-spacing:0.4px;}
        .judul .meta { margin-top:6px; font-size:10px; color:#475569; }
        .meta-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:9px 12px; margin-bottom:16px; display:flex; flex-wrap:wrap; gap:18px; font-size:10.5px;}
        .meta-box div b { color:#0B1C48;}
        .meta-box .ml-auto { margin-left:auto; }
        .summary { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;}
        .sum-card { flex:1; min-width:130px; padding:8px 10px; background:#ffffff; border:1px solid #e2e8f0; border-left:3.5px solid #2563eb; border-radius:6px;}
        .sum-card .n { font-size:17px; font-weight:800; color:#0B1C48;}
        .sum-card .l { font-size:9.5px; color:#64748b; margin-top:1px; text-transform:uppercase; letter-spacing:0.3px;}
        .sec-title { font-size:12px; font-weight:800; color:#0B1C48; margin:10px 0 8px; padding-bottom:5px; border-bottom:1.5px solid #cbd5e1;}
        .sec-title i { margin-right:6px; color:#2563eb;}
        table.data { width:100%; border-collapse:collapse; margin-bottom:10px; background:#fff;}
        table.data th, table.data td { border:1px solid #cbd5e1; padding:6px 8px; vertical-align:top;}
        table.data thead th { background:#0B1C48; color:#ffffff; text-align:left; font-weight:700; font-size:10.5px;}
        table.data tbody tr:nth-child(even) { background:#f8fafc;}
        .kode { font-weight:700; color:#1e40af;}
        .kode.r { color:#6d28d9;}
        .st { padding:2px 7px; border-radius:10px; font-size:9.5px; font-weight:700; display:inline-block;}
        .st-pending { background:#fef3c7; color:#92400e; border:1px solid #fde68a;}
        .st-disetujui { background:#dcfce7; color:#166534; border:1px solid #bbf7d0;}
        .st-selesai { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;}
        .st-ditolak { background:#fee2e2; color:#991b1b; border:1px solid #fecaca;}
        .st-dibatalkan { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0;}
        .small { font-size:9.5px; color:#64748b;}
        .bold { font-weight:700;}
        .foot { margin-top:28px; display:flex; justify-content:space-between; gap:20px; }
        .foot .sign { width:260px; }
        .foot .sign .t { text-align:center; font-size:10.5px; color:#334155; }
        .foot .sign .space { height:64px; }
        .foot .sign .n { border-top:1px dashed #64748b; text-align:center; padding-top:4px; font-weight:700; font-size:10.5px; color:#0f172a;}
    </style>';
    echo '</head><body>';
    echo '<div class="noprint-wrap">';
    echo '<div class="noprint noprint-bar">
            <button class="btn-close" onclick="window.close()">✕ Tutup</button>
            <button class="btn-print" onclick="window.print()">🖨 Cetak / Simpan Sebagai PDF</button>
          </div>';

    $logoLarasPath = base_url('assets/logo.PNG') . '?v=' . time();
    $logoLaras = '<img src="' . $logoLarasPath . '" alt="Logo LARAS" style="width:130px;height:auto;max-height:72px;flex-shrink:0;display:block;object-fit:contain">';
    $logoKanan = '<div style="flex-shrink:0;width:130px;max-width:130px;min-width:130px"></div>';

    echo '<div class="kop" style="display:flex;align-items:center;justify-content:space-between;gap:22px">
            <div style="flex-shrink:0">' . $logoLaras . '</div>
            <div style="flex:1;min-width:0;text-align:center;padding:0 6px">
                <div class="instansi">BADAN PENGAWASAN KEUANGAN DAN PEMBANGUNAN (BPKP)</div>
                <div class="instansi" style="font-size:13px;margin-top:2px">PERWAKILAN PROVINSI DAERAH ISTIMEWA YOGYAKARTA</div>
                <div class="alamat">Jl. Laksda Adisucipto No. 48, Yogyakarta 55281, Telp. (0274) 489448, 489205, Fax. (0274) 489121</div>
            </div>
            ' . $logoKanan . '
          </div>';
    echo '<div class="judul">
            <h1>LAPORAN REKAPITULASI RESERVASI KENDARAAN &amp; RUANGAN</h1>
            <div class="meta">
                Periode filter laporan: <b>' . format_date($tgl_awal,false) . '</b> s.d. <b>' . format_date($tgl_akhir,false) . '</b> &nbsp;|&nbsp;
                Status: <b>' . ($status === 'all' ? 'Semua Status' : ucwords($status)) . '</b> &nbsp;|&nbsp;
                Tipe: <b>' . ($tipe === 'all' ? 'Kendaraan &amp; Ruangan' : ucwords($tipe)) . '</b><br>
                Dokumen dicetak pada: <b>' . $tgl_sekarang . '</b>
            </div>
          </div>';
    echo '<div class="meta-box">
            <div><b>📊 Total Data :</b> ' . $totalData . ' transaksi</div>
            <div><b>⏳ Menunggu :</b> ' . ($nilaiStatus($kendaraan,'pending') + $nilaiStatus($ruangan,'pending')) . '</div>
            <div><b>✅ Disetujui :</b> ' . ($nilaiStatus($kendaraan,'disetujui') + $nilaiStatus($ruangan,'disetujui')) . '</div>
            <div><b>🏳 Selesai :</b> ' . ($nilaiStatus($kendaraan,'selesai') + $nilaiStatus($ruangan,'selesai')) . '</div>
            <div><b>❌ Ditolak :</b> ' . ($nilaiStatus($kendaraan,'ditolak') + $nilaiStatus($ruangan,'ditolak')) . '</div>
            <div><b>↩ Dibatalkan :</b> ' . ($nilaiStatus($kendaraan,'dibatalkan') + $nilaiStatus($ruangan,'dibatalkan')) . '</div>
            <div class="ml-auto"><b>Halaman :</b> 1 dari 1</div>
          </div>';
    echo '<div class="summary">';
    echo '  <div class="sum-card" style="border-left-color:#0ea5e9"><div class="n">' . $totalData . '</div><div class="l">Total</div></div>';
    echo '  <div class="sum-card" style="border-left-color:#f59e0b"><div class="n">' . ($nilaiStatus($kendaraan,'pending') + $nilaiStatus($ruangan,'pending')) . '</div><div class="l">Menunggu</div></div>';
    echo '  <div class="sum-card" style="border-left-color:#10b981"><div class="n">' . ($nilaiStatus($kendaraan,'disetujui') + $nilaiStatus($ruangan,'disetujui')) . '</div><div class="l">Disetujui</div></div>';
    echo '  <div class="sum-card" style="border-left-color:#2563eb"><div class="n">' . ($nilaiStatus($kendaraan,'selesai') + $nilaiStatus($ruangan,'selesai')) . '</div><div class="l">Selesai</div></div>';
    echo '  <div class="sum-card" style="border-left-color:#ef4444"><div class="n">' . ($nilaiStatus($kendaraan,'ditolak') + $nilaiStatus($ruangan,'ditolak')) . '</div><div class="l">Ditolak</div></div>';
    echo '</div>';

    if ($tipe === 'all' || $tipe === 'kendaraan') {
        echo '<div class="sec-title">🚗 DATA RESERVASI KENDARAAN — ' . count($kendaraan) . ' DATA</div>';
        if (empty($kendaraan)) {
            echo '<div style="padding:26px;text-align:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;color:#64748b;font-size:10.5px">Tidak ada data reservasi kendaraan pada periode filter.</div>';
        } else {
            echo '<table class="data"><thead><tr>
                <th style="width:42px">No</th>
                <th>Kode</th>
                <th>Tgl Pengajuan</th>
                <th>Pemohon / NIP</th>
                <th>Unit Kerja</th>
                <th>Kendaraan</th>
                <th>Tujuan</th>
                <th>Tanggal &amp; Jam Pakai</th>
                <th>Status</th>
            </tr></thead><tbody>';
            $no = 1;
            foreach ($kendaraan as $m) {
                echo '<tr>
                    <td style="text-align:center">' . $no++ . '</td>
                    <td class="kode">' . $m['kode_reservasi'] . '</td>
                    <td>' . format_date($m['created_at'], false) . '</td>
                    <td><div class="bold">' . $m['nama_lengkap'] . '</div><div class="small">NIP. ' . $m['nip'] . '</div></td>
                    <td>' . $m['unit_kerja'] . '</td>
                    <td><div class="bold">' . $m['no_plat'] . '</div><div class="small">' . $m['merk'] . ' ' . $m['tipe'] . '</div></td>
                    <td>' . $m['tujuan'] . '</td>
                    <td>
                        <div class="bold">' . format_date($m['tanggal_pinjam'],false) . ' - ' . format_date($m['tanggal_kembali'],false) . '</div>
                        <div class="small">' . date('H:i',strtotime($m['jam_mulai'])) . ' - ' . date('H:i',strtotime($m['jam_selesai'])) . ' WIB</div>
                    </td>
                    <td><span class="st st-' . $m['status'] . '">' . ($statusLabel[$m['status']] ?? $m['status']) . '</span></td>
                </tr>';
            }
            echo '</tbody></table>';
        }
    }

    if (($tipe === 'all' || $tipe === 'ruangan') && count($kendaraan) > 0 && count($ruangan) > 0) {
        echo '<div class="page-break"></div>';
    }

    if ($tipe === 'all' || $tipe === 'ruangan') {
        echo '<div class="sec-title">🏢 DATA RESERVASI RUANGAN — ' . count($ruangan) . ' DATA</div>';
        if (empty($ruangan)) {
            echo '<div style="padding:26px;text-align:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;color:#64748b;font-size:10.5px">Tidak ada data reservasi ruangan pada periode filter.</div>';
        } else {
            echo '<table class="data"><thead><tr>
                <th style="width:42px">No</th>
                <th>Kode</th>
                <th>Tgl Pengajuan</th>
                <th>Pemohon / NIP</th>
                <th>Unit Kerja</th>
                <th>Ruangan</th>
                <th>Nama Acara</th>
                <th>Tanggal &amp; Jam Pakai</th>
                <th>Peserta</th>
                <th>Status</th>
            </tr></thead><tbody>';
            $no = 1;
            foreach ($ruangan as $r) {
                echo '<tr>
                    <td style="text-align:center">' . $no++ . '</td>
                    <td class="kode r">' . $r['kode_reservasi'] . '</td>
                    <td>' . format_date($r['created_at'], false) . '</td>
                    <td><div class="bold">' . $r['nama_lengkap'] . '</div><div class="small">NIP. ' . $r['nip'] . '</div></td>
                    <td>' . $r['unit_kerja'] . '</td>
                    <td><div class="bold">' . $r['nama_ruangan'] . '</div><div class="small">' . $r['lantai'] . '</div></td>
                    <td>' . $r['nama_acara'] . '</td>
                    <td>
                        <div class="bold">' . format_date($r['tanggal_mulai'],false) . ($r['tanggal_mulai'] != $r['tanggal_selesai'] ? ' - '.format_date($r['tanggal_selesai'],false) : '') . '</div>
                        <div class="small">' . date('H:i',strtotime($r['jam_mulai'])) . ' - ' . date('H:i',strtotime($r['jam_selesai'])) . ' WIB</div>
                    </td>
                    <td style="text-align:center">' . ($r['estimasi_peserta'] ?? '-') . ' org</td>
                    <td><span class="st st-' . $r['status'] . '">' . ($statusLabel[$r['status']] ?? $r['status']) . '</span></td>
                </tr>';
            }
            echo '</tbody></table>';
        }
    }

    echo '<div class="foot">
            <div class="sign">
                <div class="t">Mengetahui,</div>
                <div class="t" style="margin-top:2px">Kepala Bagian Umum</div>
                <div class="space"></div>
                <div class="n">(..................................................)</div>
                <div class="t" style="margin-top:2px">NIP. .........................................</div>
            </div>
            <div class="sign" style="margin-left:auto">
                <div class="t">Yogyakarta, ' . date('d') . ' ' . getBulanIndo(date('m')) . ' ' . date('Y') . '</div>
                <div class="t" style="margin-top:2px">Admin Reservasi</div>
                <div class="space"></div>
                <div class="n">(..................................................)</div>
                <div class="t" style="margin-top:2px">NIP. .........................................</div>
            </div>
          </div>';
    echo '</div>';
    echo '<script>
        window.addEventListener("load", function(){
            if (window.matchMedia && window.matchMedia("print").matches === false) {
                /* auto trigger print — user can then choose Save as PDF */
                setTimeout(function(){
                    try { window.focus(); window.print(); } catch(e){}
                }, 600);
            }
        });
    </script>';
    echo '</body></html>';
    exit;
}

function getBulanIndo($n) {
    $arr = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $arr[(int)$n] ?? $arr[1];
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
    <div class="d-flex gap-2">
        <a href="<?= base_url('laporan.php') ?>?<?= http_build_query(array_merge($_GET, ['export' => '1'])) ?>" class="btn btn-success" target="_blank" style="background:linear-gradient(135deg,#059669,#047857);border:none;box-shadow:0 4px 12px rgba(5,150,105,0.3);transition:all .2s ease" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 16px rgba(5,150,105,0.38)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <i class="bi bi-file-earmark-excel-fill me-1" style="font-size:13px"></i> Export CSV Excel
        </a>
        <a href="<?= base_url('laporan.php') ?>?<?= http_build_query(array_merge($_GET, ['print' => '1'])) ?>" class="btn btn-primary" target="_blank" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);border:none;box-shadow:0 4px 12px rgba(37,99,235,0.3);transition:all .2s ease" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 16px rgba(37,99,235,0.38)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <i class="bi bi-file-earmark-pdf-fill me-1" style="font-size:13px"></i> Export PDF / Print
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header flex-wrap gap-2">
        <h6 class="card-title"><i class="bi bi-funnel-fill me-2" style="color:#f59e0b"></i>Filter Laporan</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= base_url('laporan.php') ?>" class="row g-3 align-items-end">
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
