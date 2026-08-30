<?php
$page_title = 'Approval Pengajuan';
$active_menu = 'approvals';
require_once __DIR__ . '/../partials/header.php';
require_admin();

$user_id = $user['id'];
$now = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $tipe = sanitize($_POST['tipe'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $keputusan = sanitize($_POST['keputusan'] ?? '');
    try {
        if (!in_array($keputusan, ['disetujui', 'ditolak'])) throw new Exception('Keputusan tidak valid.');
        $upd = ['status' => $keputusan, 'catatan_approval' => $catatan, 'approved_by' => $user_id, 'approved_at' => $now];
        if ($tipe === 'kendaraan') {
            $kendaraan_baru = !empty($_POST['kendaraan_baru']) ? (int)$_POST['kendaraan_baru'] : 0;
            $current = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ?", [$id]);
            if (!$current) throw new Exception('Data reservasi tidak ditemukan.');
            $final_kendaraan_id = $kendaraan_baru > 0 ? $kendaraan_baru : (int)$current['kendaraan_id'];

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
                        $final_kendaraan_id, $id,
                        $current['tanggal_pinjam'],
                        $current['tanggal_kembali'],
                        $current['tanggal_pinjam'], $current['jam_mulai'],
                        $current['tanggal_kembali'], $current['jam_selesai']
                    ]
                );
                if ((int)($conflict['c'] ?? 0) > 0) {
                    throw new Exception('Tidak dapat disetujui: Kendaraan sudah ada jadwal pemakaian lain yang bertabrakan pada jam & tanggal yang sama. Silakan ganti unit kendaraan melalui dropdown atau tolak pengajuan ini.');
                }
            }

            $upd['kendaraan_id'] = $final_kendaraan_id;
            db()->update('reservasi_kendaraan', $upd, 'id = ?', [$id]);
            if ($keputusan === 'disetujui') {
                db()->update('kendaraan', ['status' => 'digunakan'], 'id = ?', [$final_kendaraan_id]);
            }
        } else {
            db()->update('reservasi_ruangan', $upd, 'id = ?', [$id]);
        }
        set_flash('success', "Berhasil di-{$keputusan}!");
    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
    }
    redirect(base_url('master/approvals.php'));
}

$pendingMobil = db()->fetchAll("SELECT rk.*, k.no_plat, k.merk, k.tipe, u.nama_lengkap, u.nip, u.unit_kerja
    FROM reservasi_kendaraan rk JOIN kendaraan k ON rk.kendaraan_id = k.id JOIN users u ON rk.user_id = u.id
    WHERE rk.status = 'pending' ORDER BY rk.created_at ASC");
$pendingRuang = db()->fetchAll("SELECT rr.*, r.nama_ruangan, r.lantai, r.kapasitas, u.nama_lengkap, u.nip, u.unit_kerja
    FROM reservasi_ruangan rr JOIN ruangan r ON rr.ruangan_id = r.id JOIN users u ON rr.user_id = u.id
    WHERE rr.status = 'pending' ORDER BY rr.created_at ASC");
$daftarKendaraan = db()->fetchAll("SELECT id, no_plat, merk, tipe, status FROM kendaraan ORDER BY no_plat ASC");
$count = count($pendingMobil) + count($pendingRuang);
?>

<div class="page-header">
    <h4><i class="bi bi-check2-square me-2" style="color:#f59e0b"></i>Approval Pengajuan</h4>
    <nav class="breadcrumb">
        <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
        <a class="breadcrumb-item" href="#">Admin Menu</a>
        <span class="breadcrumb-item active">Approval Pengajuan</span>
    </nav>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div><div class="stat-label">Menunggu Persetujuan</div><div class="stat-value"><?= $count ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon blue"><i class="bi bi-car-front-fill"></i></div><div class="stat-label">Reservasi Kendaraan</div><div class="stat-value"><?= count($pendingMobil) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon purple"><i class="bi bi-door-open-fill"></i></div><div class="stat-label">Reservasi Ruangan</div><div class="stat-value"><?= count($pendingRuang) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-icon green"><i class="bi bi-person-clock"></i></div><div class="stat-label">Proses Cepat</div><div class="stat-value">1x Klik</div><div class="stat-sub">Approval modal cepat</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header" style="border-left:4px solid #2563eb">
                <h6 class="card-title"><i class="bi bi-car-front-fill me-2" style="color:#2563eb"></i>Approval Kendaraan (<?= count($pendingMobil) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingMobil)): ?>
                    <div class="text-center py-5" style="font-size:11px;color:#94a3b8"><i class="bi bi-check2-circle display-5 d-block mb-2" style="color:#10b981"></i>Tidak ada pengajuan kendaraan menunggu approval.</div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingMobil as $m): ?>
                        <div class="list-group-item p-3 border-0 border-bottom" style="border-color:#f1f5f9">
                            <div class="d-flex gap-3 align-items-start">
                                <div style="width:42px;height:42px;flex-shrink:0;background:linear-gradient(135deg,#dbeafe,#bfdbfe);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#1e40af;font-size:18px"><i class="bi bi-car-front-fill"></i></div>
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div>
                                            <span style="font-size:10px;font-weight:700;background:#eff6ff;color:#1e40af;padding:3px 8px;border-radius:8px"><?= $m['kode_reservasi'] ?></span>
                                        </div>
                                        <span class="badge bg-warning text-dark rounded-pill">Menunggu</span>
                                    </div>
                                    <div style="font-weight:700;font-size:11.5px;margin-top:4px;color:#0f172a"><?= $m['no_plat'] ?> • <?= $m['merk'] ?> <?= $m['tipe'] ?></div>
                                    <div style="font-size:11px;color:#334155;margin-top:2px"><i class="bi bi-person me-1"></i><?= $m['nama_lengkap'] ?> <span style="color:#64748b">(<?= $m['unit_kerja'] ?>)</span></div>
                                    <div style="font-size:10.5px;color:#64748b;margin-top:3px"><i class="bi bi-geo-alt me-1"></i><?= $m['tujuan'] ?></div>
                                    <div style="font-size:10.5px;color:#64748b;margin-top:2px"><i class="bi bi-calendar me-1"></i><?= format_date($m['tanggal_pinjam'], false) ?> <?= date('H:i', strtotime($m['jam_mulai'])) ?> - <?= format_date($m['tanggal_kembali'], false) ?> <?= date('H:i', strtotime($m['jam_selesai'])) ?></div>
                                    <div class="mt-2 d-flex gap-2">
                                        <a href="<?= base_url('kendaraan/detail.php?id=' . $m['id']) ?>" class="btn btn-sm btn-secondary"><i class="bi bi-eye"></i> Detail</a>
                                        <button class="btn btn-sm btn-success" onclick='approveForm("kendaraan", <?= $m['id'] ?>, "<?= sanitize($m['kode_reservasi']) ?>", "<?= sanitize($m['no_plat'] . ' - ' . $m['tujuan']) ?>", "<?= (int)$m['kendaraan_id'] ?>", "disetujui")'><i class="bi bi-check-lg"></i> Setujui</button>
                                        <button class="btn btn-sm btn-danger" onclick='approveForm("kendaraan", <?= $m['id'] ?>, "<?= sanitize($m['kode_reservasi']) ?>", "<?= sanitize($m['no_plat'] . ' - ' . $m['tujuan']) ?>", "<?= (int)$m['kendaraan_id'] ?>", "ditolak")'><i class="bi bi-x-lg"></i> Tolak</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header" style="border-left:4px solid #7c3aed">
                <h6 class="card-title"><i class="bi bi-door-open-fill me-2" style="color:#7c3aed"></i>Approval Ruangan (<?= count($pendingRuang) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingRuang)): ?>
                    <div class="text-center py-5" style="font-size:11px;color:#94a3b8"><i class="bi bi-check2-circle display-5 d-block mb-2" style="color:#10b981"></i>Tidak ada pengajuan ruangan menunggu approval.</div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingRuang as $r): ?>
                        <div class="list-group-item p-3 border-0 border-bottom" style="border-color:#f1f5f9">
                            <div class="d-flex gap-3 align-items-start">
                                <div style="width:42px;height:42px;flex-shrink:0;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#6d28d9;font-size:18px"><i class="bi bi-door-open-fill"></i></div>
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div>
                                            <span style="font-size:10px;font-weight:700;background:#f5f3ff;color:#6d28d9;padding:3px 8px;border-radius:8px"><?= $r['kode_reservasi'] ?></span>
                                        </div>
                                        <span class="badge bg-warning text-dark rounded-pill">Menunggu</span>
                                    </div>
                                    <div style="font-weight:700;font-size:11.5px;margin-top:4px;color:#0f172a"><?= $r['nama_ruangan'] ?> <span style="color:#64748b;font-weight:500">(<?= $r['lantai'] ?> • <?= $r['kapasitas'] ?> org)</span></div>
                                    <div style="font-size:11px;color:#334155;margin-top:2px"><i class="bi bi-megaphone me-1"></i><?= $r['nama_acara'] ?></div>
                                    <div style="font-size:10.5px;color:#64748b;margin-top:3px"><i class="bi bi-person me-1"></i><?= $r['nama_lengkap'] ?> <span style="color:#64748b">(<?= $r['unit_kerja'] ?>)</span> • <?= $r['estimasi_peserta'] ?> peserta</div>
                                    <div style="font-size:10.5px;color:#64748b;margin-top:2px"><i class="bi bi-calendar me-1"></i><?= format_date($r['tanggal_mulai'], false) ?> <?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= date('H:i', strtotime($r['jam_selesai'])) ?><?= $r['tanggal_mulai'] != $r['tanggal_selesai'] ? ' s/d ' . format_date($r['tanggal_selesai'], false) : '' ?></div>
                                    <div class="mt-2 d-flex gap-2">
                                        <a href="<?= base_url('ruangan/detail.php?id=' . $r['id']) ?>" class="btn btn-sm btn-secondary"><i class="bi bi-eye"></i> Detail</a>
                                        <button class="btn btn-sm btn-success" onclick='approveForm("ruangan", <?= $r['id'] ?>, "<?= sanitize($r['kode_reservasi']) ?>", "<?= sanitize($r['nama_ruangan'] . ' - ' . $r['nama_acara']) ?>", "disetujui")'><i class="bi bi-check-lg"></i> Setujui</button>
                                        <button class="btn btn-sm btn-danger" onclick='approveForm("ruangan", <?= $r['id'] ?>, "<?= sanitize($r['kode_reservasi']) ?>", "<?= sanitize($r['nama_ruangan'] . ' - ' . $r['nama_acara']) ?>", "ditolak")'><i class="bi bi-x-lg"></i> Tolak</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header" id="apvHeader"><h5 class="modal-title"><i class="bi bi-shield-check me-2"></i>Konfirmasi Approval</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="<?= base_url('master/approvals.php') ?>">
        <input type="hidden" name="act" value="approve">
        <input type="hidden" name="tipe" id="apv_tipe">
        <input type="hidden" name="id" id="apv_id">
        <input type="hidden" name="keputusan" id="apv_keputusan">
        <div class="modal-body">
            <div id="apv_info" class="mb-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;font-size:11.5px"></div>
            <div id="apv_kendaraan_wrap" class="mb-3 d-none">
                <label class="form-label mb-1.5" style="font-size:11.5px;font-weight:700;color:#0f172a">Unit Kendaraan yang Disetujui <span class="text-danger">*</span></label>
                <div class="text-muted mb-1.5" style="font-size:10px;color:#64748b">Dapat diganti dengan unit lain apabila unit pengajuan awal tidak tersedia / bentrok jadwal.</div>
                <select class="form-select" name="kendaraan_baru" id="apv_kendaraan" style="border-radius:10px;font-size:11.5px;padding:0.6rem 2.25rem 0.6rem 0.85rem">
                    <?php foreach ($daftarKendaraan as $k): ?>
                        <option value="<?= (int)$k['id'] ?>" data-status="<?= $k['status'] ?>">
                            <?= strtoupper($k['no_plat']) ?> — <?= $k['merk'] ?> <?= $k['tipe'] ?>
                            <?php if ($k['status'] === 'tersedia'): ?>
                                [Tersedia]
                            <?php elseif ($k['status'] === 'digunakan'): ?>
                                [Sedang Dipakai]
                            <?php else: ?>
                                [Tidak Aktif]
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="form-label">Catatan (Opsional)</label>
            <textarea class="form-control" name="catatan" rows="3" placeholder="Tuliskan catatan untuk pemohon..."></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn" id="apv_btn"><i class="bi bi-check"></i> Konfirmasi</button>
        </div>
    </form>
</div></div></div>

<script>
function approveForm(tipe, id, kode, detail, kendaraanId, keputusan) {
    document.getElementById('apv_tipe').value = tipe;
    document.getElementById('apv_id').value = id;
    document.getElementById('apv_keputusan').value = keputusan;
    const header = document.getElementById('apvHeader');
    const info = document.getElementById('apv_info');
    const btn = document.getElementById('apv_btn');
    const wrapK = document.getElementById('apv_kendaraan_wrap');
    const selK = document.getElementById('apv_kendaraan');
    if (tipe === 'kendaraan' && keputusan === 'disetujui') {
        wrapK.classList.remove('d-none');
        if (kendaraanId && selK) {
            selK.value = String(kendaraanId);
        }
    } else {
        wrapK.classList.add('d-none');
        if (selK) selK.value = '';
    }
    if (keputusan === 'disetujui') {
        header.style.background = 'linear-gradient(90deg,#059669,#047857)';
        btn.className = 'btn btn-success';
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> SETUJUI';
        info.innerHTML = '<div style="font-size:10px;color:#059669;font-weight:700;letter-spacing:0.5px">MENYETUJUI PENGAJUAN</div><div style="font-weight:700;margin-top:3px">' + kode + '</div><div style="margin-top:3px">' + detail + '</div>';
    } else {
        header.style.background = 'linear-gradient(90deg,#dc2626,#b91c1c)';
        btn.className = 'btn btn-danger';
        btn.innerHTML = '<i class="bi bi-x-circle-fill"></i> TOLAK';
        info.innerHTML = '<div style="font-size:10px;color:#dc2626;font-weight:700;letter-spacing:0.5px">MENOLAK PENGAJUAN</div><div style="font-weight:700;margin-top:3px">' + kode + '</div><div style="margin-top:3px">' + detail + '</div>';
    }
    header.style.color = '#fff';
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
