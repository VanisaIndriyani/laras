<?php
require_once __DIR__ . '/../db.php';
require_login();

$user = current_user();
$isAdmin = is_admin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = base_url('kendaraan/index.php');

try {
    switch ($action) {
        case 'create':
            $kendaraan_id = (int)($_POST['kendaraan_id'] ?? 0);
            $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
            $tujuan = sanitize($_POST['tujuan'] ?? '');
            $keperluan = sanitize($_POST['keperluan'] ?? '');
            $estimasi = !empty($_POST['estimasi_peserta']) ? (int)$_POST['estimasi_peserta'] : 1;
            if ($estimasi < 1) $estimasi = 1;
            $fasilitas = isset($_POST['fasilitas_tambahan']) && is_array($_POST['fasilitas_tambahan'])
                ? json_encode(array_values(array_filter($_POST['fasilitas_tambahan'])))
                : null;
            $tgl_pinjam = sanitize($_POST['tanggal_pinjam'] ?? '');
            $jam_mulai = sanitize($_POST['jam_mulai'] ?? '');
            $tgl_kembali = sanitize($_POST['tanggal_kembali'] ?? '');
            $jam_selesai = sanitize($_POST['jam_selesai'] ?? '');

            if (!$kendaraan_id || !$tujuan || !$keperluan || !$tgl_pinjam || !$tgl_kembali) {
                throw new Exception('Mohon lengkapi semua kolom wajib.');
            }

            $kendaraan = db()->fetchOne("SELECT id FROM kendaraan WHERE id = ? AND status = 'tersedia'", [$kendaraan_id]);
            if (!$kendaraan) throw new Exception('Kendaraan tidak tersedia.');

            if ($tgl_kembali < $tgl_pinjam) {
                throw new Exception('Tanggal selesai tidak valid.');
            }

            $conflict = db()->fetchOne(
                "SELECT COUNT(*) AS c FROM reservasi_kendaraan
                 WHERE kendaraan_id = ? AND status IN ('pending','disetujui')
                   AND NOT (
                        (tanggal_kembali < ?) OR
                        (tanggal_pinjam > ?) OR
                        (tanggal_kembali = ? AND jam_selesai <= ?) OR
                        (tanggal_pinjam = ? AND jam_mulai >= ?)
                   )",
                [
                    $kendaraan_id,
                    $tgl_pinjam,
                    $tgl_kembali,
                    $tgl_pinjam, $jam_mulai,
                    $tgl_kembali, $jam_selesai
                ]
            );
            if ((int)($conflict['c'] ?? 0) > 0) {
                throw new Exception('Maaf, kendaraan ini SUDAH DIBOOKING pada jadwal yang sama (bertabrakan dengan pengajuan Pending / Disetujui lain). Silakan pilih unit / jam / tanggal lain.');
            }

            $kode = generate_kode_reservasi('MOBIL');
            $now = date('Y-m-d H:i:s');

            $newId = db()->insert('reservasi_kendaraan', [
                'kode_reservasi' => $kode,
                'user_id' => $user['id'],
                'kendaraan_id' => $kendaraan_id,
                'driver_id' => $driver_id,
                'keperluan' => $keperluan,
                'tujuan' => $tujuan,
                'estimasi_peserta' => $estimasi,
                'fasilitas_tambahan' => $fasilitas,
                'tanggal_pinjam' => $tgl_pinjam,
                'jam_mulai' => $jam_mulai,
                'tanggal_kembali' => $tgl_kembali,
                'jam_selesai' => $jam_selesai,
                'status' => 'pending',
                'created_at' => $now
            ]);

            if ($driver_id !== null) {
                db()->getConnection()->prepare("UPDATE driver SET status = 'bertugas' WHERE id = ? LIMIT 1")->execute([$driver_id]);
            }

            set_flash('success', "Pengajuan reservasi kendaraan berhasil dikirim. Kode: {$kode}");
            $redirect_url = base_url('kendaraan/detail.php?id=' . $newId);
            break;

        case 'update':
            $editId = (int)($_POST['id'] ?? 0);
            $data = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ?", [$editId]);
            if (!$data || (!$isAdmin && $data['user_id'] != $user['id'])) throw new Exception('Data tidak sah.');
            if (!in_array($data['status'], ['pending', 'ditolak'])) throw new Exception('Status tidak dapat diubah.');

            $kendaraan_id = (int)($_POST['kendaraan_id'] ?? 0);
            $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
            $tujuan = sanitize($_POST['tujuan'] ?? '');
            $keperluan = sanitize($_POST['keperluan'] ?? '');
            $estimasi = !empty($_POST['estimasi_peserta']) ? (int)$_POST['estimasi_peserta'] : 1;
            if ($estimasi < 1) $estimasi = 1;
            $fasilitas = isset($_POST['fasilitas_tambahan']) && is_array($_POST['fasilitas_tambahan'])
                ? json_encode(array_values(array_filter($_POST['fasilitas_tambahan'])))
                : null;
            $tgl_pinjam = sanitize($_POST['tanggal_pinjam'] ?? '');
            $jam_mulai = sanitize($_POST['jam_mulai'] ?? '');
            $tgl_kembali = sanitize($_POST['tanggal_kembali'] ?? '');
            $jam_selesai = sanitize($_POST['jam_selesai'] ?? '');

            if (!$kendaraan_id || !$tujuan || !$keperluan) throw new Exception('Lengkapi kolom wajib.');

            $conflict = db()->fetchOne(
                "SELECT COUNT(*) AS c FROM reservasi_kendaraan
                 WHERE kendaraan_id = ? AND id != ? AND status IN ('pending','disetujui')
                   AND NOT (
                        (tanggal_kembali < ?) OR
                        (tanggal_pinjam > ?) OR
                        (tanggal_kembali = ? AND jam_selesai <= ?) OR
                        (tanggal_pinjam = ? AND jam_mulai >= ?)
                   )",
                [
                    $kendaraan_id, $editId,
                    $tgl_pinjam,
                    $tgl_kembali,
                    $tgl_pinjam, $jam_mulai,
                    $tgl_kembali, $jam_selesai
                ]
            );
            if ((int)($conflict['c'] ?? 0) > 0) {
                throw new Exception('Maaf, kendaraan ini SUDAH DIBOOKING pada jadwal yang sama (bertabrakan dengan pengajuan Pending / Disetujui lain). Silakan pilih unit / jam / tanggal lain.');
            }

            db()->update('reservasi_kendaraan', [
                'kendaraan_id' => $kendaraan_id,
                'driver_id' => $driver_id,
                'keperluan' => $keperluan,
                'tujuan' => $tujuan,
                'estimasi_peserta' => $estimasi,
                'fasilitas_tambahan' => $fasilitas,
                'tanggal_pinjam' => $tgl_pinjam,
                'jam_mulai' => $jam_mulai,
                'tanggal_kembali' => $tgl_kembali,
                'jam_selesai' => $jam_selesai,
                'status' => 'pending'
            ], 'id = ?', [$editId]);

            set_flash('success', 'Data reservasi berhasil diperbarui dan dikirim ulang.');
            $redirect_url = base_url('kendaraan/detail.php?id=' . $editId);
            break;

        case 'batal':
            $id = (int)($_GET['id'] ?? 0);
            $data = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ?", [$id]);
            if (!$data || (!$isAdmin && $data['user_id'] != $user['id'])) throw new Exception('Data tidak sah.');

            db()->update('reservasi_kendaraan', ['status' => 'dibatalkan'], 'id = ?', [$id]);
            if (!empty($data['driver_id'])) {
                $activeCount = db()->fetchOne(
                    "SELECT COUNT(*) AS c FROM reservasi_kendaraan WHERE driver_id = ? AND status IN ('pending','disetujui') AND id != ?",
                    [(int)$data['driver_id'], $id]
                )['c'];
                if ($activeCount == 0) {
                    db()->getConnection()->prepare("UPDATE driver SET status = 'tersedia' WHERE id = ? LIMIT 1")->execute([(int)$data['driver_id']]);
                }
            }
            set_flash('success', 'Reservasi berhasil dibatalkan.');
            break;

        case 'approve':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            $keputusan = sanitize($_POST['keputusan'] ?? '');
            $catatan = sanitize($_POST['catatan'] ?? '');
            if (!in_array($keputusan, ['disetujui', 'ditolak'])) throw new Exception('Keputusan tidak valid.');

            $upd = [
                'status' => $keputusan,
                'catatan_approval' => $catatan,
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ];

            $current = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ?", [$id]);
            if (!$current) throw new Exception('Data reservasi tidak ditemukan.');

            if ($keputusan === 'disetujui') {
                $conflict = db()->fetchOne(
                    "SELECT COUNT(*) AS c FROM reservasi_kendaraan
                     WHERE kendaraan_id = ? AND id != ? AND status IN ('pending','disetujui')
                       AND NOT (
                            (tanggal_kembali < ?) OR
                            (tanggal_pinjam > ?) OR
                            (tanggal_kembali = ? AND jam_selesai <= ?) OR
                            (tanggal_pinjam = ? AND jam_mulai >= ?)
                       )",
                    [
                        (int)$current['kendaraan_id'], $id,
                        $current['tanggal_pinjam'],
                        $current['tanggal_kembali'],
                        $current['tanggal_pinjam'], $current['jam_mulai'],
                        $current['tanggal_kembali'], $current['jam_selesai']
                    ]
                );
                if ((int)($conflict['c'] ?? 0) > 0) {
                    throw new Exception('Tidak dapat disetujui: Kendaraan sudah ada jadwal pemakaian lain yang bertabrakan pada jam & tanggal yang sama.');
                }
            }

            db()->update('reservasi_kendaraan', $upd, 'id = ?', [$id]);

            if ($keputusan === 'disetujui') {
                $r = db()->fetchOne("SELECT kendaraan_id, driver_id FROM reservasi_kendaraan WHERE id = ?", [$id]);
                db()->update('kendaraan', ['status' => 'digunakan'], 'id = ?', [$r['kendaraan_id']]);
                if (!empty($r['driver_id'])) {
                    db()->getConnection()->prepare("UPDATE driver SET status = 'bertugas' WHERE id = ? LIMIT 1")->execute([(int)$r['driver_id']]);
                }
            } elseif ($keputusan === 'ditolak') {
                $r = db()->fetchOne("SELECT driver_id FROM reservasi_kendaraan WHERE id = ?", [$id]);
                if (!empty($r['driver_id'])) {
                    $activeCount = db()->fetchOne(
                        "SELECT COUNT(*) AS c FROM reservasi_kendaraan WHERE driver_id = ? AND status IN ('pending','disetujui') AND id != ?",
                        [(int)$r['driver_id'], $id]
                    )['c'];
                    if ($activeCount == 0) {
                        db()->getConnection()->prepare("UPDATE driver SET status = 'tersedia' WHERE id = ? LIMIT 1")->execute([(int)$r['driver_id']]);
                    }
                }
            }

            set_flash('success', "Reservasi berhasil di-{$keputusan}.");
            $redirect_url = base_url('kendaraan/detail.php?id=' . $id);
            break;

        case 'setujui':
            require_admin();
            $id = (int)($_GET['id'] ?? 0);
            $data = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ? AND status = 'pending'", [$id]);
            if (!$data) throw new Exception('Data reservasi tidak sah untuk disetujui.');

            $conflict = db()->fetchOne(
                "SELECT COUNT(*) AS c FROM reservasi_kendaraan
                 WHERE kendaraan_id = ? AND id != ? AND status IN ('pending','disetujui')
                   AND NOT (
                        (tanggal_kembali < ?) OR
                        (tanggal_pinjam > ?) OR
                        (tanggal_kembali = ? AND jam_selesai <= ?) OR
                        (tanggal_pinjam = ? AND jam_mulai >= ?)
                   )",
                [
                    (int)$data['kendaraan_id'], $id,
                    $data['tanggal_pinjam'],
                    $data['tanggal_kembali'],
                    $data['tanggal_pinjam'], $data['jam_mulai'],
                    $data['tanggal_kembali'], $data['jam_selesai']
                ]
            );
            if ((int)($conflict['c'] ?? 0) > 0) {
                throw new Exception('Tidak dapat disetujui: Kendaraan sudah ada jadwal pemakaian lain yang bertabrakan pada jam & tanggal yang sama.');
            }

            $upd = [
                'status' => 'disetujui',
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ];
            db()->update('reservasi_kendaraan', $upd, 'id = ?', [$id]);

            db()->update('kendaraan', ['status' => 'digunakan'], 'id = ?', [(int)$data['kendaraan_id']]);
            if (!empty($data['driver_id'])) {
                db()->getConnection()->prepare("UPDATE driver SET status = 'bertugas' WHERE id = ? LIMIT 1")->execute([(int)$data['driver_id']]);
            }
            set_flash('success', 'Reservasi berhasil disetujui.');
            $redirect_url = base_url('kendaraan/detail.php?id=' . $id);
            break;

        case 'tolak':
            require_admin();
            $id = (int)($_GET['id'] ?? 0);
            $data = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ? AND status = 'pending'", [$id]);
            if (!$data) throw new Exception('Data reservasi tidak sah untuk ditolak.');

            db()->update('reservasi_kendaraan', [
                'status' => 'ditolak',
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            if (!empty($data['driver_id'])) {
                $activeCount = db()->fetchOne(
                    "SELECT COUNT(*) AS c FROM reservasi_kendaraan WHERE driver_id = ? AND status IN ('pending','disetujui') AND id != ?",
                    [(int)$data['driver_id'], $id]
                )['c'];
                if ($activeCount == 0) {
                    db()->getConnection()->prepare("UPDATE driver SET status = 'tersedia' WHERE id = ? LIMIT 1")->execute([(int)$data['driver_id']]);
                }
            }
            set_flash('success', 'Reservasi ditolak.');
            $redirect_url = base_url('kendaraan/detail.php?id=' . $id);
            break;

        case 'selesai':
            require_admin();
            $id = (int)($_GET['id'] ?? 0);
            $r = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ?", [$id]);
            if (!$r || $r['status'] !== 'disetujui') throw new Exception('Data tidak valid.');

            db()->update('reservasi_kendaraan', ['status' => 'selesai'], 'id = ?', [$id]);
            db()->update('kendaraan', ['status' => 'tersedia'], 'id = ?', [$r['kendaraan_id']]);
            if (!empty($r['driver_id'])) {
                $activeCount = db()->fetchOne(
                    "SELECT COUNT(*) AS c FROM reservasi_kendaraan WHERE driver_id = ? AND status IN ('pending','disetujui') AND id != ?",
                    [(int)$r['driver_id'], $id]
                )['c'];
                if ($activeCount == 0) {
                    db()->getConnection()->prepare("UPDATE driver SET status = 'tersedia' WHERE id = ? LIMIT 1")->execute([(int)$r['driver_id']]);
                }
            }
            set_flash('success', 'Reservasi ditandai SELESAI. Kendaraan & Driver kembali tersedia.');
            $redirect_url = base_url('kendaraan/detail.php?id=' . $id);
            break;

        default:
            throw new Exception('Aksi tidak diketahui.');
    }
} catch (Exception $e) {
    set_flash('error', $e->getMessage());
    if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
}

redirect($redirect_url);
