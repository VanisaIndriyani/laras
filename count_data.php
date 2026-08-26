<?php
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== COUNT DATA SAAT INI ===\n";

try {
    $ck = db()->fetchOne("SELECT COUNT(*) AS c FROM kendaraan") ?: ['c' => 0];
    echo "Kendaraan : " . (int)$ck['c'] . " (TARGET 7)\n";
    $rk = db()->fetchAll("SELECT id, no_plat, merk, tipe, kapasitas, driver_id FROM kendaraan ORDER BY id");
    foreach ($rk as $r) echo "  #{$r['id']} {$r['no_plat']} - {$r['merk']} {$r['tipe']} ({$r['kapasitas']} penumpang) driver_id={$r['driver_id']}\n";

    echo "\n";
    $cr = db()->fetchOne("SELECT COUNT(*) AS c FROM ruangan") ?: ['c' => 0];
    echo "Ruangan   : " . (int)$cr['c'] . " (TARGET 11)\n";
    $rr = db()->fetchAll("SELECT id, nama_ruangan, kapasitas, lantai FROM ruangan ORDER BY lantai, id");
    foreach ($rr as $r) echo "  #{$r['id']} L{$r['lantai']} {$r['nama_ruangan']} ({$r['kapasitas']} org)\n";

    echo "\n";
    $cd = db()->fetchOne("SELECT COUNT(*) AS c FROM driver") ?: ['c' => 0];
    echo "Driver    : " . (int)$cd['c'] . " (TARGET 4)\n";
    if ((int)$cd['c'] > 0) {
        $rd = db()->fetchAll("SELECT id, nama_driver, no_wa, status FROM driver ORDER BY id");
        foreach ($rd as $r) echo "  #{$r['id']} {$r['nama_driver']} WA {$r['no_wa']} [{$r['status']}]\n";
    } else {
        echo "  (tabel driver belum ada / kosong)\n";
    }

    echo "\n=== DATA RESERVASI ===\n";
    $cm = db()->fetchOne("SELECT COUNT(*) AS c FROM reservasi_kendaraan") ?: ['c' => 0];
    $cmp = db()->fetchOne("SELECT COUNT(*) AS c FROM reservasi_kendaraan WHERE status = 'pending'") ?: ['c' => 0];
    echo "Reservasi Kendaraan: " . (int)$cm['c'] . " total, " . (int)$cmp['c'] . " PENDING\n";
    $rm = db()->fetchAll("SELECT rk.id, rk.kode_reservasi, rk.status, rk.user_id, rk.kendaraan_id, rk.created_at, rk.tanggal_pinjam, rk.tujuan FROM reservasi_kendaraan rk ORDER BY rk.id DESC LIMIT 5");
    if ($rm) foreach ($rm as $i => $r) {
        $tag = $r['status'];
        echo "  #" . ($i+1) . ". ID={$r['id']} [{$tag}] Kode={$r['kode_reservasi']} uid={$r['user_id']} kid={$r['kendaraan_id']} dibuat=" . ($r['created_at'] ?: '(NULL)') . " tgl={$r['tanggal_pinjam']} tujuan=" . trim(mb_substr($r['tujuan'] ?? '', 0, 40)) . "\n";
    }

    echo "\n";
    $cr = db()->fetchOne("SELECT COUNT(*) AS c FROM reservasi_ruangan") ?: ['c' => 0];
    $crp = db()->fetchOne("SELECT COUNT(*) AS c FROM reservasi_ruangan WHERE status = 'pending'") ?: ['c' => 0];
    echo "Reservasi Ruangan  : " . (int)$cr['c'] . " total, " . (int)$crp['c'] . " PENDING\n";
    $rr2 = db()->fetchAll("SELECT rr.id, rr.kode_reservasi, rr.status, rr.user_id, rr.ruangan_id, rr.created_at, rr.tanggal_mulai, rr.nama_acara FROM reservasi_ruangan rr ORDER BY rr.id DESC LIMIT 5");
    if ($rr2) foreach ($rr2 as $i => $r) {
        $tag = $r['status'];
        echo "  #" . ($i+1) . ". ID={$r['id']} [{$tag}] Kode={$r['kode_reservasi']} uid={$r['user_id']} rid={$r['ruangan_id']} dibuat=" . ($r['created_at'] ?: '(NULL)') . " tgl={$r['tanggal_mulai']} acara=" . trim(mb_substr($r['nama_acara'] ?? '', 0, 40)) . "\n";
    }

    echo "\n=== SIMULASI QUERY NOTIFIKASI (SAMA DENGAN header.php) ===\n";
    $notifications = [];
    try {
        $sql_mobil_notif = "SELECT rk.id, rk.status, rk.created_at, rk.tanggal_pinjam as tanggal, rk.tujuan, u.nama_lengkap as peminjam, 'kendaraan' as tipe,
                            CONCAT(k.merk, ' ', k.tipe, CASE WHEN k.no_plat IS NOT NULL AND k.no_plat != '' THEN CONCAT(' (', k.no_plat, ')') ELSE '' END) as objek
                            FROM reservasi_kendaraan rk
                            LEFT JOIN users u ON u.id = rk.user_id
                            LEFT JOIN kendaraan k ON k.id = rk.kendaraan_id
                            WHERE rk.status = 'pending'
                            ORDER BY COALESCE(rk.created_at, rk.tanggal_pinjam) DESC LIMIT 3";
        $notif_mobil = db()->fetchAll($sql_mobil_notif);
        echo "Query mobil berhasil: " . count($notif_mobil) . " baris\n";
        foreach ($notif_mobil as $n) $notifications[] = $n;

        $sql_ruang_notif = "SELECT rr.id, rr.status, rr.created_at, rr.tanggal_mulai as tanggal, rr.deskripsi as tujuan, u.nama_lengkap as peminjam, 'ruangan' as tipe, r.nama_ruangan as objek
                            FROM reservasi_ruangan rr
                            LEFT JOIN users u ON u.id = rr.user_id
                            LEFT JOIN ruangan r ON r.id = rr.ruangan_id
                            WHERE rr.status = 'pending'
                            ORDER BY COALESCE(rr.created_at, rr.tanggal_mulai) DESC LIMIT 3";
        $notif_ruang = db()->fetchAll($sql_ruang_notif);
        echo "Query ruangan berhasil: " . count($notif_ruang) . " baris\n";
        foreach ($notif_ruang as $n) $notifications[] = $n;

        $nowFallback = date('Y-m-d H:i:s');
        foreach ($notifications as &$n) {
            if (empty($n['created_at'])) $n['created_at'] = $nowFallback;
            if (empty($n['peminjam'])) $n['peminjam'] = '-';
            if (empty($n['objek'])) $n['objek'] = $n['tipe'] === 'kendaraan' ? 'Kendaraan' : 'Ruangan';
            if (empty($n['tujuan'])) $n['tujuan'] = '-';
            if (empty($n['tanggal'])) $n['tanggal'] = $nowFallback;
        }
        unset($n);

        usort($notifications, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        $notifications = array_slice($notifications, 0, 5);
        echo "Total notif gabungan: " . count($notifications) . " baris (top 5)\n";
        foreach ($notifications as $i => $n) {
            echo "  #" . ($i+1) . ". [{$n['tipe']}] {$n['peminjam']} → {$n['objek']} | dibuat={$n['created_at']} | tgl={$n['tanggal']} | tujuan=" . mb_substr($n['tujuan'] ?? '', 0, 40) . "\n";
        }
        if (count($notifications) === 0) echo "  (tidak ada data notifikasi)\n";
    } catch (Exception $e) {
        echo "ERROR NOTIF: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
