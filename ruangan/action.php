<?php
require_once __DIR__ . '/../db.php';
require_login();

$user = current_user();
$isAdmin = is_admin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = base_url('ruangan/index.php');

try {
    switch ($action) {
        case 'create':
            $ruangan_id = (int)($_POST['ruangan_id'] ?? 0);
            $nama_acara = sanitize($_POST['nama_acara'] ?? '');
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $unit_kerja = sanitize($_POST['unit_kerja'] ?? '');
            $estimasi = (int)($_POST['estimasi_peserta'] ?? 1);
            $tgl_mulai = sanitize($_POST['tanggal_mulai'] ?? '');
            $jam_mulai = sanitize($_POST['jam_mulai'] ?? '');
            $tgl_selesai = sanitize($_POST['tanggal_selesai'] ?? '');
            $jam_selesai = sanitize($_POST['jam_selesai'] ?? '');
            $fasilitas = $_POST['fasilitas'] ?? [];

            if (!$ruangan_id || !$nama_acara || !$deskripsi || !$tgl_mulai || !$tgl_selesai || !$unit_kerja) {
                throw new Exception('Mohon lengkapi semua kolom wajib.');
            }

            $ruangan = db()->fetchOne("SELECT id FROM ruangan WHERE id = ? AND status = 'tersedia'", [$ruangan_id]);
            if (!$ruangan) throw new Exception('Ruangan tidak tersedia.');

            if ($tgl_selesai < $tgl_mulai) throw new Exception('Tanggal selesai tidak valid.');

            $kode = generate_kode_reservasi('RUANG');
            $now = date('Y-m-d H:i:s');

            $newId = db()->insert('reservasi_ruangan', [
                'kode_reservasi' => $kode,
                'user_id' => $user['id'],
                'ruangan_id' => $ruangan_id,
                'nama_acara' => $nama_acara,
                'deskripsi' => $deskripsi,
                'unit_kerja' => $unit_kerja,
                'estimasi_peserta' => $estimasi,
                'tanggal_mulai' => $tgl_mulai,
                'jam_mulai' => $jam_mulai,
                'tanggal_selesai' => $tgl_selesai,
                'jam_selesai' => $jam_selesai,
                'fasilitas_pendukung' => json_encode($fasilitas),
                'status' => 'pending',
                'created_at' => $now
            ]);

            set_flash('success', "Pengajuan reservasi ruangan berhasil dikirim. Kode: {$kode}");
            $redirect_url = base_url('ruangan/detail.php?id=' . $newId);
            break;

        case 'update':
            $editId = (int)($_POST['id'] ?? 0);
            $data = db()->fetchOne("SELECT * FROM reservasi_ruangan WHERE id = ?", [$editId]);
            if (!$data || (!$isAdmin && $data['user_id'] != $user['id'])) throw new Exception('Data tidak sah.');
            if (!in_array($data['status'], ['pending', 'ditolak'])) throw new Exception('Status tidak dapat diubah.');

            $ruangan_id = (int)($_POST['ruangan_id'] ?? 0);
            $nama_acara = sanitize($_POST['nama_acara'] ?? '');
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $unit_kerja = sanitize($_POST['unit_kerja'] ?? '');
            $estimasi = (int)($_POST['estimasi_peserta'] ?? 1);
            $tgl_mulai = sanitize($_POST['tanggal_mulai'] ?? '');
            $jam_mulai = sanitize($_POST['jam_mulai'] ?? '');
            $tgl_selesai = sanitize($_POST['tanggal_selesai'] ?? '');
            $jam_selesai = sanitize($_POST['jam_selesai'] ?? '');
            $fasilitas = $_POST['fasilitas'] ?? [];

            db()->update('reservasi_ruangan', [
                'ruangan_id' => $ruangan_id,
                'nama_acara' => $nama_acara,
                'deskripsi' => $deskripsi,
                'unit_kerja' => $unit_kerja,
                'estimasi_peserta' => $estimasi,
                'tanggal_mulai' => $tgl_mulai,
                'jam_mulai' => $jam_mulai,
                'tanggal_selesai' => $tgl_selesai,
                'jam_selesai' => $jam_selesai,
                'fasilitas_pendukung' => json_encode($fasilitas),
                'status' => 'pending'
            ], 'id = ?', [$editId]);

            set_flash('success', 'Data reservasi berhasil diperbarui dan dikirim ulang.');
            $redirect_url = base_url('ruangan/detail.php?id=' . $editId);
            break;

        case 'batal':
            $id = (int)($_GET['id'] ?? 0);
            $data = db()->fetchOne("SELECT * FROM reservasi_ruangan WHERE id = ?", [$id]);
            if (!$data || (!$isAdmin && $data['user_id'] != $user['id'])) throw new Exception('Data tidak sah.');

            db()->update('reservasi_ruangan', ['status' => 'dibatalkan'], 'id = ?', [$id]);
            set_flash('success', 'Reservasi berhasil dibatalkan.');
            break;

        case 'setujui':
            require_admin();
            $id = (int)($_GET['id'] ?? 0);
            $data = db()->fetchOne("SELECT kode_reservasi FROM reservasi_ruangan WHERE id = ? AND status = 'pending'", [$id]);
            if (!$data) throw new Exception('Reservasi tidak dapat disetujui.');
            db()->update('reservasi_ruangan', [
                'status' => 'disetujui',
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            set_flash('success', "Reservasi {$data['kode_reservasi']} berhasil disetujui.");
            break;

        case 'tolak':
            require_admin();
            $id = (int)($_GET['id'] ?? 0);
            $data = db()->fetchOne("SELECT kode_reservasi FROM reservasi_ruangan WHERE id = ? AND status = 'pending'", [$id]);
            if (!$data) throw new Exception('Reservasi tidak dapat ditolak.');
            db()->update('reservasi_ruangan', [
                'status' => 'ditolak',
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            set_flash('warning', "Reservasi {$data['kode_reservasi']} ditolak.");
            break;

        case 'approve':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            $keputusan = sanitize($_POST['keputusan'] ?? '');
            $catatan = sanitize($_POST['catatan'] ?? '');
            if (!in_array($keputusan, ['disetujui', 'ditolak'])) throw new Exception('Keputusan tidak valid.');

            db()->update('reservasi_ruangan', [
                'status' => $keputusan,
                'catatan_approval' => $catatan,
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            set_flash('success', "Reservasi berhasil di-{$keputusan}.");
            $redirect_url = base_url('ruangan/detail.php?id=' . $id);
            break;

        case 'selesai':
            require_admin();
            $id = (int)($_GET['id'] ?? 0);
            db()->update('reservasi_ruangan', ['status' => 'selesai'], 'id = ?', [$id]);
            set_flash('success', 'Reservasi ditandai SELESAI.');
            $redirect_url = base_url('ruangan/detail.php?id=' . $id);
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
