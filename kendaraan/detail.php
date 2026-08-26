<?php
$page_title = 'Detail Reservasi Kendaraan';
$active_menu = 'kendaraan';
require_once __DIR__ . '/../partials/header.php';

$user = current_user();
$isAdmin = is_admin();

$id = (int)($_GET['id'] ?? 0);
$r = db()->fetchOne("SELECT rk.*, k.no_plat, k.merk, k.tipe, k.tahun, k.kapasitas as kapasitas_mobil, k.driver, k.no_hp_driver,
    u.nama_lengkap as pemohon, u.nip, u.unit_kerja, u.no_hp as hp_pemohon
    FROM reservasi_kendaraan rk 
    LEFT JOIN kendaraan k ON rk.kendaraan_id = k.id 
    LEFT JOIN users u ON rk.user_id = u.id 
    WHERE rk.id = ?", [$id]);

if (!$r || (!$isAdmin && $r['user_id'] != $user['id'])) {
    set_flash('error', 'Data tidak ditemukan.');
    redirect(base_url('kendaraan/'));
}

// Fallback agar tidak error saat kendaraan/users data tidak lengkap
$r['no_plat'] = $r['no_plat'] ?? '-';
$r['merk'] = $r['merk'] ?? 'Kendaraan';
$r['tipe'] = $r['tipe'] ?? '';
$r['tahun'] = $r['tahun'] ?? '';
$r['kapasitas_mobil'] = $r['kapasitas_mobil'] ?? 0;
$r['driver'] = $r['driver'] ?? null;
$r['no_hp_driver'] = $r['no_hp_driver'] ?? null;
$r['pemohon'] = $r['pemohon'] ?? '-';
$r['nip'] = $r['nip'] ?? '-';
$r['unit_kerja'] = $r['unit_kerja'] ?? '-';
$r['hp_pemohon'] = $r['hp_pemohon'] ?? null;
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
    <div>
        <h4><i class="bi bi-file-earmark-text me-2" style="color:#2563eb"></i>Detail Reservasi Kendaraan</h4>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <a class="breadcrumb-item" href="<?= base_url('kendaraan/') ?>">Reservasi Kendaraan</a>
            <span class="breadcrumb-item active">Detail</span>
        </nav>
    </div>
    <a href="<?= base_url('kendaraan/') ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    <span style="background:#eff6ff;color:#1e40af;font-size:10px;font-weight:700;padding:5px 12px;border-radius:999px;margin-right:10px"><?= $r['kode_reservasi'] ?></span>
                    Form Pengajuan Peminjaman Kendaraan Dinas
                </h6>
                <div><?= status_badge($r['status']) ?></div>
            </div>
            <div class="card-body">
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="section-number">1</div>
                        <div class="section-text">
                            <strong>Identitas Penanggung Jawab / Pemohon</strong>
                            <small>Data pribadi pemohon penanggungjawab kendaraan</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Nama Lengkap</small>
                            <strong style="font-size:12px"><?= $r['pemohon'] ?></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">NIP</small>
                            <strong style="font-size:12px"><?= $r['nip'] ?></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Unit Kerja</small>
                            <strong style="font-size:12px"><?= $r['unit_kerja'] ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Kontak Pemohon</small>
                            <strong style="font-size:12px"><?= $r['hp_pemohon'] ?: '-' ?></strong>
                        </div>
                        <div class="col-md-8">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Waktu Pengajuan</small>
                            <strong style="font-size:12px"><?= format_datetime($r['created_at']) ?></strong>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <div class="section-number">2</div>
                        <div class="section-text">
                            <strong>Detail Kendaraan & Kegiatan</strong>
                            <small>Informasi unit kendaraan dan tujuan perjalanan</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="p-3" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:14px;border:1px solid #bfdbfe">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:52px;height:52px;background:linear-gradient(135deg,#2563eb,#4f46e5);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px">
                                        <i class="bi bi-car-front-fill"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:#1e3a8a"><?= $r['no_plat'] ?></div>
                                        <div style="font-size:11px;color:#1e40af"><?= $r['merk'] ?> <?= $r['tipe'] ?><?= $r['tahun'] ? " ({$r['tahun']})" : '' ?> • Kapasitas <?= $r['kapasitas_mobil'] ?> orang</div>
                                    </div>
                                </div>
                                <hr style="border-color:#93c5fd;margin:12px 0">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <small style="color:#1e40af">Driver</small><br>
                                        <strong style="font-size:11.5px;color:#1e3a8a"><?= $r['driver'] ?: '-' ?></strong>
                                    </div>
                                    <div class="col-md-6">
                                        <small style="color:#1e40af">Kontak Driver</small><br>
                                        <strong style="font-size:11.5px;color:#1e3a8a"><?= $r['no_hp_driver'] ?: '-' ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Tujuan Lokasi</small>
                            <strong style="font-size:12px"><i class="bi bi-geo-alt-fill me-1" style="color:#ef4444"></i><?= $r['tujuan'] ?></strong>
                            <hr style="border-color:#e2e8f0;margin:10px 0">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Estimasi Jumlah Penumpang</small>
                            <strong style="font-size:12px"><?= $r['estimasi_peserta'] ?> orang</strong>
                        </div>
                        <div class="col-md-12">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Keperluan / Agenda</small>
                            <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;font-size:11.5px;line-height:1.7;border:1px solid #f1f5f9"><?= nl2br(sanitize($r['keperluan'])) ?></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <div class="section-number">3</div>
                        <div class="section-text">
                            <strong>Jadwal Penggunaan Kendaraan</strong>
                            <small>Rentang waktu pemakaian kendaraan dinas</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border-radius:14px;border:1px solid #6ee7b7">
                                <small style="color:#047857;font-weight:600"><i class="bi bi-play-circle-fill me-1"></i> MULAI</small>
                                <div class="mt-1" style="font-weight:700;font-size:13px;color:#065f46"><?= format_date($r['tanggal_pinjam']) ?></div>
                                <div style="font-size:11px;color:#047857"><?= format_time($r['jam_mulai']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border-radius:14px;border:1px solid #fca5a5">
                                <small style="color:#b91c1c;font-weight:600"><i class="bi bi-stop-circle-fill me-1"></i> SELESAI</small>
                                <div class="mt-1" style="font-weight:700;font-size:13px;color:#991b1b"><?= format_date($r['tanggal_kembali']) ?></div>
                                <div style="font-size:11px;color:#b91c1c"><?= format_time($r['jam_selesai']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($r['approved_by']): ?>
                <div class="form-section" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border-color:#bbf7d0">
                    <div class="form-section-title">
                        <div class="section-number" style="background:linear-gradient(135deg,#059669,#047857)">!</div>
                        <div class="section-text">
                            <strong>Informasi Approval / Status Pengajuan</strong>
                            <small>Ditandatangani oleh Bagian Umum / Pimpinan</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Status Akhir</small>
                            <div><?= status_badge($r['status']) ?></div>
                        </div>
                        <div class="col-md-4">
                            <?php $appr = $r['approved_by'] ? db()->fetchOne("SELECT nama_lengkap, nip FROM users WHERE id = ?", [$r['approved_by']]) : null; ?>
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Di-Approval Oleh</small>
                            <strong style="font-size:12px"><?= $appr ? $appr['nama_lengkap'] . ' (NIP. ' . $appr['nip'] . ')' : '-' ?></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Waktu Approval</small>
                            <strong style="font-size:12px"><?= $r['approved_at'] ? format_datetime($r['approved_at']) : '-' ?></strong>
                        </div>
                        <?php if ($r['catatan_approval']): ?>
                        <div class="col-md-12">
                            <small class="d-block" style="color:#64748b;margin-bottom:3px">Catatan Approval</small>
                            <div style="background:#fff;border-radius:12px;padding:12px;border:1px solid #d1fae5"><?= sanitize($r['catatan_approval']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($isAdmin && $r['status'] === 'pending'): ?>
        <div class="card" style="border:2px dashed #f59e0b">
            <div class="card-header" style="background:linear-gradient(90deg,#fef3c7,#fff7ed)">
                <h6 class="card-title"><i class="bi bi-shield-check me-2" style="color:#d97706"></i>Approval Pengajuan</h6>
            </div>
            <div class="card-body">
                <p style="font-size:11px;color:#64748b;margin:0 0 12px">Sebagai Admin / Bagian Umum, silakan berikan keputusan terhadap pengajuan peminjaman kendaraan ini.</p>
                <form method="POST" action="action.php">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea class="form-control mb-3" name="catatan" rows="3" placeholder="Tuliskan catatan..."></textarea>
                    <div class="d-flex gap-2">
                        <button type="submit" name="keputusan" value="disetujui" class="btn btn-success flex-fill"><i class="bi bi-check-lg"></i> Setujui</button>
                        <button type="submit" name="keputusan" value="ditolak" class="btn btn-danger flex-fill"><i class="bi bi-x-lg"></i> Tolak</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title"><i class="bi bi-lightbulb-fill me-2" style="color:#f59e0b"></i>Aksi Lanjutan</h6>
            </div>
            <div class="card-body" style="padding:14px">
                <div class="d-flex flex-column gap-2">
                    <?php if (($r['status'] === 'pending' || $r['status'] === 'ditolak') && ($isAdmin || $r['user_id'] == $user['id'])): ?>
                        <?php if ($r['user_id'] == $user['id']): ?>
                        <a href="form.php?id=<?= $r['id'] ?>" class="btn btn-primary w-100"><i class="bi bi-pencil-fill"></i> Edit Data Reservasi</a>
                        <a href="action.php?action=batal&id=<?= $r['id'] ?>" class="btn btn-secondary w-100" onclick="return confirm('Yakin batalkan reservasi ini?')"><i class="bi bi-x-circle-fill"></i> Batalkan Reservasi</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($isAdmin && $r['status'] === 'disetujui'): ?>
                    <a href="action.php?action=selesai&id=<?= $r['id'] ?>" class="btn btn-success w-100" onclick="return confirm('Tandai reservasi ini SELESAI?')"><i class="bi bi-flag-fill"></i> Tandai Selesai</a>
                    <?php endif; ?>
                    <a href="<?= base_url('kendaraan/') ?>" class="btn btn-secondary w-100"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
