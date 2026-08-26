<?php
$page_title = 'Form Reservasi Ruangan';
$active_menu = 'ruangan';
require_once __DIR__ . '/../partials/header.php';

$user = current_user();
$isAdmin = is_admin();

$editId = (int)($_GET['id'] ?? 0);
$preselected_ruangan = (int)($_GET['ruangan_id'] ?? 0);
$data = null;
$tanggal_min = date('Y-m-d');

if ($editId > 0) {
    $data = db()->fetchOne("SELECT * FROM reservasi_ruangan WHERE id = ?", [$editId]);
    if (!$data || (!$isAdmin && $data['user_id'] != $user['id'])) {
        set_flash('error', 'Reservasi tidak ditemukan atau tidak sah.');
        redirect(base_url('ruangan/index.php'));
    }
    if (!in_array($data['status'], ['pending', 'ditolak'])) {
        set_flash('error', 'Reservasi sudah tidak dapat diubah.');
        redirect(base_url('ruangan/index.php'));
    }
} elseif ($preselected_ruangan > 0) {
    $cek_ruang = db()->fetchOne("SELECT id, nama_ruangan FROM ruangan WHERE id = ? AND status = 'tersedia'", [$preselected_ruangan]);
    if (!$cek_ruang) {
        set_flash('warning', 'Ruangan yang dipilih tidak tersedia, silakan pilih ruangan lain.');
        $preselected_ruangan = 0;
    } else {
        $data = ['ruangan_id' => $preselected_ruangan];
    }
}

$user_data = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
$ruangan_list = db()->fetchAll("SELECT * FROM ruangan WHERE status = 'tersedia' ORDER BY lantai, nama_ruangan");
$fasilitas_pendukung = get_fasilitas_pendukung_list();
$fasilitas_terpilih = ($data && $editId > 0) ? json_decode($data['fasilitas_pendukung'] ?? '[]', true) : [];
$jam_options = [];
for ($h = 7; $h <= 18; $h++) {
    foreach ([0, 30] as $m) {
        $jam_options[] = sprintf('%02d:%02d:00', $h, $m);
    }
}
$jam_options[] = '19:00:00';
$jam_options[] = '20:00:00';
$jam_options[] = '21:00:00';

if ($editId > 0 && $data) {
    $_nama_pemohon = sanitize(db()->fetchOne("SELECT nama_lengkap FROM users WHERE id = ?", [$data['user_id']])['nama_lengkap'] ?? '');
    $_nip_pemohon  = sanitize(db()->fetchOne("SELECT nip FROM users WHERE id = ?", [$data['user_id']])['nip'] ?? '');
    $_unit_default = $data['unit_kerja'] ?? '';
    $_no_hp_default = sanitize(db()->fetchOne("SELECT no_hp FROM users WHERE id = ?", [$data['user_id']])['no_hp'] ?? '');
} else {
    $_nama_pemohon = sanitize($user_data['nama_lengkap'] ?? '');
    $_nip_pemohon  = sanitize($user_data['nip'] ?? '');
    $_unit_default = $user_data['unit_kerja'] ?? '';
    $_no_hp_default = sanitize($user_data['no_hp'] ?? '');
}
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap:14px">
    <div>
        <h4><i class="bi bi-door-open-fill me-2" style="color:#2563eb"></i><?= $editId ? 'Edit Reservasi Ruangan' : 'Ajukan Reservasi Ruangan Rapat' ?></h4>
        <nav class="breadcrumb mb-0">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <a class="breadcrumb-item" href="<?= base_url('ruangan/index.php') ?>">Reservasi Ruangan</a>
            <span class="breadcrumb-item active"><?= $editId ? 'Edit' : 'Baru' ?></span>
        </nav>
    </div>
    <a href="<?= base_url('ruangan/index.php') ?>" class="btn btn-light fw-semibold border" style="border-radius:11px;padding:8px 18px;font-size:11.5px;border-color:#dbe5f1;color:#475569">
        <i class="bi bi-arrow-left me-1.5"></i> Kembali ke Daftar
    </a>
</div>

<div class="hero-card-modern mb-4" style="background:linear-gradient(135deg,#f0f7ff 0%,#e8efff 55%,#eef4ff 100%);border:1px solid #dbeafe;border-radius:20px;padding:24px 28px;position:relative;overflow:hidden">
    <i class="bi bi-ui-checks-grid" style="position:absolute;right:44px;top:50%;transform:translateY(-50%);font-size:124px;opacity:0.05;color:#1d4ed8"></i>
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
        <div style="max-width:640px">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge rounded-pill px-3 py-1.5 text-uppercase fw-bold" style="font-size:10px;background:#0B1C48;color:#fff;letter-spacing:0.8px">MODUL 2</span>
                <span class="badge rounded-pill px-3 py-1.5" style="font-size:10px;background:rgba(124,58,237,0.1);color:#6d28d9">PENGELOLAAN SARANA &amp; RUANGAN</span>
            </div>
            <h3 class="fw-bold mb-1.5" style="font-size:19px;color:#0B1C48;margin-top:4px">Form Reservasi Ruang Rapat / Aula</h3>
            <p class="mb-0 text-muted" style="font-size:11.5px;line-height:1.65">
                Lengkapi data di bawah ini untuk mengajukan pemakaian ruang rapat / aula. Pengajuan akan diproses paling lambat 1x24 jam kerja oleh Admin Bagian Umum.
            </p>
        </div>
    </div>
</div>

<form action="<?= base_url('ruangan/action.php') ?>" method="POST" id="formRuangan" style="padding-bottom:88px">
    <input type="hidden" name="action" value="<?= $editId ? 'update' : 'create' ?>">
    <?php if ($editId): ?>
        <input type="hidden" name="id" value="<?= $editId ?>">
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4" style="border-radius:18px;overflow:hidden">
        <div class="card-body p-4">

            <div class="form-section mb-4">
                <div class="form-section-title mb-3 d-flex align-items-center gap-3 pb-2" style="border-bottom:1.5px solid #eef2f7">
                    <div class="section-number flex-shrink-0" style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,#1F3A8B,#3B5FC7);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px">1</div>
                    <div class="section-text flex-grow-1">
                        <div class="fw-bold" style="font-size:14px;color:#0B1C48">Identitas Penanggung Jawab Kegiatan</div>
                        <div class="text-muted" style="font-size:10.5px">Data pemohon penanggung jawab penggunaan ruangan &amp; kontak yang dapat dihubungi</div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="nama_lengkap" id="fl_nama" required
                                   value="<?= $_nama_pemohon ?>" readonly style="background:#f8fafc;border-radius:12px;padding:1.05rem 1rem 0.55rem">
                            <label for="fl_nama" style="font-size:11.5px;color:#475569;padding-left:1rem">Nama Lengkap Pemohon <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="nip" id="fl_nip" required
                                   value="<?= $_nip_pemohon ?>" readonly style="background:#f8fafc;border-radius:12px;padding:1.05rem 1rem 0.55rem">
                            <label for="fl_nip" style="font-size:11.5px;color:#475569;padding-left:1rem">NIP Pemohon <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" name="unit_kerja" id="fl_unit" required style="border-radius:12px;padding:1.05rem 2.25rem 0.55rem 1rem;background-position:right 0.85rem center">
                                <?php foreach (get_unit_kerja_list() as $u): ?>
                                    <option value="<?= $u ?>" <?= $u === $_unit_default ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="fl_unit" style="font-size:11.5px;color:#475569;padding-left:1rem">Unit Kerja Penyelenggara <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="no_hp" id="fl_hp" placeholder="08xxxxxxxxxx"
                                   value="<?= $_no_hp_default ?>" style="border-radius:12px;padding:1.05rem 1rem 0.55rem">
                            <label for="fl_hp" style="font-size:11.5px;color:#475569;padding-left:1rem">Nomor HP / WA Kontak <span style="color:#94a3b8">(Opsional)</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section mb-4">
                <div class="form-section-title mb-3 d-flex align-items-center gap-3 pb-2" style="border-bottom:1.5px solid #eef2f7">
                    <div class="section-number flex-shrink-0" style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px">2</div>
                    <div class="section-text flex-grow-1">
                        <div class="fw-bold" style="font-size:14px;color:#0B1C48">Pemilihan Ruangan &amp; Detail Acara</div>
                        <div class="text-muted" style="font-size:10.5px">Pilih sarana ruangan yang tersedia beserta detail informasi kegiatan</div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="form-floating">
                            <select class="form-select" name="ruangan_id" id="ruangan_id" required style="border-radius:12px;padding:1.05rem 2.25rem 0.55rem 1rem;background-position:right 0.85rem center;font-size:12px;font-weight:600">
                                <option value="">-- Pilih Sarana / Ruangan Rapat --</option>
                                <?php foreach ($ruangan_list as $r): ?>
                                    <?php $sel = $data && $data['ruangan_id'] == $r['id'] ? 'selected' : ''; ?>
                                    <option value="<?= $r['id'] ?>"
                                            data-kapasitas="<?= $r['kapasitas'] ?>"
                                            data-lantai="<?= sanitize($r['lantai']) ?>"
                                            data-fasilitas="<?= sanitize($r['fasilitas']) ?>"
                                        <?= $sel ?>>
                                        <?= $r['nama_ruangan'] ?> (<?= $r['lantai'] ?>) - Kapasitas <?= $r['kapasitas'] ?> Orang [Tersedia]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="ruangan_id" style="font-size:11.5px;color:#475569;padding-left:1rem;font-weight:500">Pilih Sarana / Ruangan Rapat <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-12" id="roomInfoBox" style="display:none">
                        <div class="room-info" style="border-radius:14px;padding:16px 18px;background:linear-gradient(135deg,#eff6ff,#eef2ff);border:1.5px solid #c7d2fe">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div class="d-flex align-items-start gap-3">
                                    <div style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;color:#4338ca;box-shadow:0 2px 8px rgba(99,102,241,0.18)">
                                        <i class="bi bi-building-fill-check" style="font-size:20px"></i>
                                    </div>
                                    <div>
                                        <div class="rn fw-bold" id="ri_nama" style="font-size:14px;color:#1e1b4b">-</div>
                                        <small class="text-muted" id="ri_lantai" style="font-size:10.5px;color:#4338ca">-</small>
                                    </div>
                                </div>
                                <div class="text-right" style="text-align:right">
                                    <div class="rn fw-bold" id="ri_kapasitas" style="font-size:22px;color:#1e1b4b;line-height:1">-</div>
                                    <small class="text-muted" style="font-size:10.5px">Kapasitas Orang</small>
                                </div>
                            </div>
                            <hr style="border-color:#c7d2fe;margin:12px 0;opacity:0.7">
                            <small style="color:#4338ca;font-size:10.5px;font-weight:700"><i class="bi bi-info-circle me-1"></i> Fasilitas Ruangan (sudah termasuk):</small><br>
                            <small style="color:#1e40af;font-size:11px;line-height:1.65" id="ri_fasilitas">-</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="estimasi_peserta" id="estimasi_peserta" min="1" max="500"
                                   value="<?= sanitize($data['estimasi_peserta'] ?? 10) ?>" required style="border-radius:12px;padding:1.05rem 3rem 0.55rem 1rem">
                            <span style="position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:600;color:#64748b;pointer-events:none;margin-top:4px">org</span>
                            <label for="estimasi_peserta" style="font-size:11.5px;color:#475569;padding-left:1rem">Estimasi Jumlah Peserta <span class="text-danger">*</span></label>
                        </div>
                        <div class="form-text mt-1.5" id="kapasitasHint" style="font-size:10px;padding-left:4px">Pastikan jumlah peserta sesuai kapasitas ruangan yang dipilih.</div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="nama_acara" id="fl_acara"
                                   placeholder="Contoh: Rapat Koordinasi Pengawasan Triwulan..."
                                   value="<?= sanitize($data['nama_acara'] ?? '') ?>" required style="border-radius:12px;padding:1.05rem 1rem 0.55rem">
                            <label for="fl_acara" style="font-size:11.5px;color:#475569;padding-left:1rem">Nama Acara / Judul Kegiatan <span class="text-danger">*</span></label>
                        </div>
                        <div class="form-text mt-1.5" style="font-size:10px;padding-left:4px">
                            Contoh: <span class="text-info">Rapat Koordinasi Pengawasan Triwulan III / Workshop Audit Digital...</span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label for="fl_deskripsi" class="form-label mb-1.5" style="font-size:11px;font-weight:600;color:#334155;padding-left:2px">Deskripsi Keperluan / Agenda Rapat <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="deskripsi" id="fl_deskripsi" rows="4"
                                  placeholder="Tuliskan tujuan dan susunan singkat acara..."
                                  required style="border-radius:12px;padding:14px 16px;font-size:12px;line-height:1.65;resize:vertical"><?= sanitize($data['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section mb-4">
                <div class="form-section-title mb-3 d-flex align-items-center gap-3 pb-2" style="border-bottom:1.5px solid #eef2f7">
                    <div class="section-number flex-shrink-0" style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px">3</div>
                    <div class="section-text flex-grow-1">
                        <div class="fw-bold" style="font-size:14px;color:#0B1C48">Waktu Penggunaan Ruangan <span style="font-weight:600;color:#64748b">(Format 24 Jam)</span></div>
                        <div class="text-muted" style="font-size:10.5px">Tentukan jadwal tanggal &amp; jam penggunaan ruangan (07:00 s/d 21:00 WIB)</div>
                    </div>
                </div>
                <div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:linear-gradient(135deg,#eff6ff,#e0e7ff);border:1px solid #c7d2fe;color:#1e40af;border-radius:12px;padding:10px 16px">
                    <i class="bi bi-info-circle-fill" style="font-size:14px"></i>
                    <div style="font-size:11px"><strong>Format 24 Jam</strong> (Tanpa AM/PM) — Pukul 07:00 s/d 21:00 WIB. Pilih tanggal mulai dan jam mulai, serta tanggal selesai dan jam selesai.</div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3" style="border-radius:14px;border:1.5px solid #bbf7d0;background:linear-gradient(135deg,#ecfdf5,#f0fdf4)">
                            <div class="d-flex align-items-center gap-2 mb-2.5">
                                <i class="bi bi-calendar2-event-fill text-success" style="font-size:14px"></i>
                                <div style="font-size:10.5px;font-weight:700;color:#065f46;letter-spacing:0.4px">TANGGAL &amp; JAM MULAI</div>
                            </div>
                            <div class="row g-2">
                                <div class="col-7">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai"
                                               min="<?= $tanggal_min ?>" required value="<?= sanitize($data['tanggal_mulai'] ?? date('Y-m-d')) ?>" style="border-radius:11px;padding:1.05rem 1rem 0.55rem;font-size:11.5px">
                                        <label for="tanggal_mulai" style="font-size:10.5px;color:#475569;padding-left:1rem">Tanggal Mulai</label>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="form-floating">
                                        <select class="form-select" name="jam_mulai" id="jam_mulai" required style="border-radius:11px;padding:1.05rem 2.25rem 0.55rem 1rem;font-size:11.5px;background-position:right 0.8rem center">
                                            <?php
                                            $jamA = $data['jam_mulai'] ?? '08:00:00';
                                            foreach ($jam_options as $val):
                                                $label = date('H:i', strtotime($val)) . ' WIB';
                                                ?>
                                                <option value="<?= $val ?>" <?= $jamA === $val ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="jam_mulai" style="font-size:10.5px;color:#475569;padding-left:1rem">Jam Mulai</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3" style="border-radius:14px;border:1.5px solid #fecaca;background:linear-gradient(135deg,#fef2f2,#fff1f2)">
                            <div class="d-flex align-items-center gap-2 mb-2.5">
                                <i class="bi bi-calendar2-x-fill text-danger" style="font-size:14px"></i>
                                <div style="font-size:10.5px;font-weight:700;color:#991b1b;letter-spacing:0.4px">TANGGAL &amp; JAM SELESAI</div>
                            </div>
                            <div class="row g-2">
                                <div class="col-7">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="tanggal_selesai" id="tanggal_selesai"
                                               min="<?= $tanggal_min ?>" required value="<?= sanitize($data['tanggal_selesai'] ?? date('Y-m-d')) ?>" style="border-radius:11px;padding:1.05rem 1rem 0.55rem;font-size:11.5px">
                                        <label for="tanggal_selesai" style="font-size:10.5px;color:#475569;padding-left:1rem">Tanggal Selesai</label>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="form-floating">
                                        <select class="form-select" name="jam_selesai" id="jam_selesai" required style="border-radius:11px;padding:1.05rem 2.25rem 0.55rem 1rem;font-size:11.5px;background-position:right 0.8rem center">
                                            <?php
                                            $jamB = $data['jam_selesai'] ?? '16:00:00';
                                            foreach ($jam_options as $val):
                                                $label = date('H:i', strtotime($val)) . ' WIB';
                                                ?>
                                                <option value="<?= $val ?>" <?= $jamB === $val ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="jam_selesai" style="font-size:10.5px;color:#475569;padding-left:1rem">Jam Selesai</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section mb-2">
                <div class="form-section-title mb-3 d-flex align-items-center gap-3 pb-2" style="border-bottom:1.5px solid #eef2f7">
                    <div class="section-number flex-shrink-0" style="width:34px;height:34px;border-radius:11px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px">4</div>
                    <div class="section-text flex-grow-1">
                        <div class="fw-bold" style="font-size:14px;color:#0B1C48">Fasilitas Sarana Pendukung yang Dibutuhkan</div>
                        <div class="text-muted" style="font-size:10.5px">Centang keperluan fasilitas tambahan untuk menunjang kegiatan (di luar fasilitas bawaan ruangan)</div>
                    </div>
                </div>
                <div class="row g-2">
                    <?php foreach ($fasilitas_pendukung as $i => $fas): ?>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-check" style="padding:12px 14px 12px 46px;border-radius:12px;border:1.5px solid #e5e7eb;background:linear-gradient(180deg,#ffffff,#fafcff);cursor:pointer;transition:all 0.18s"
                                 onmouseover="this.style.borderColor='#c7d2fe';this.style.background='linear-gradient(180deg,#f5f8ff,#eef4ff)'"
                                 onmouseout="if(!document.getElementById('fas_<?= $i ?>').checked){this.style.borderColor='#e5e7eb';this.style.background='linear-gradient(180deg,#ffffff,#fafcff)'}">
                                <input class="form-check-input" type="checkbox" name="fasilitas[]"
                                       id="fas_<?= $i ?>" value="<?= $fas ?>" style="width:17px;height:17px;margin-left:-30px;margin-top:2px"
                                    <?= in_array($fas, $fasilitas_terpilih) ? 'checked' : '' ?>
                                    onchange="if(this.checked){this.closest('.form-check').style.borderColor='#6366f1';this.closest('.form-check').style.background='linear-gradient(180deg,#eef2ff,#eef4ff)'}else{this.closest('.form-check').style.borderColor='#e5e7eb';this.closest('.form-check').style.background='linear-gradient(180deg,#ffffff,#fafcff)'}">
                                <label class="form-check-label fw-semibold" for="fas_<?= $i ?>" style="font-size:11.5px;color:#0f172a;cursor:pointer"><?= $fas ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <div class="form-actions-sticky">
        <a href="<?= base_url('ruangan/index.php') ?>" class="btn btn-light fw-semibold border" style="border-radius:12px;padding:10px 22px;font-size:12px;border-color:#dbe5f1;color:#475569">
            <i class="bi bi-x-lg me-1.5"></i> Batal
        </a>
        <button type="submit" class="btn btn-primary fw-semibold shadow-sm" style="border-radius:12px;padding:10px 26px;font-size:12px;background:linear-gradient(135deg,#1F3A8B,#3B5FC7);border:none">
            <i class="bi bi-send-fill me-1.5"></i>
            <?= $editId ? 'Update Pengajuan' : 'Kirim Pengajuan Reservasi Ruangan' ?>
        </button>
    </div>
</form>

<script>
(function() {
    const selRuangan = document.getElementById('ruangan_id');
    const infoBox = document.getElementById('roomInfoBox');
    const est = document.getElementById('estimasi_peserta');
    const hint = document.getElementById('kapasitasHint');

    function updateRoomInfo() {
        const opt = selRuangan.options[selRuangan.selectedIndex];
        if (selRuangan.value) {
            infoBox.style.display = 'block';
            document.getElementById('ri_nama').textContent = opt.textContent.split('(')[0].trim();
            document.getElementById('ri_lantai').textContent =
                (opt.dataset.lantai || '-') + ' • Fasilitas: ' + (opt.dataset.fasilitas || '-');
            document.getElementById('ri_kapasitas').textContent = (opt.dataset.kapasitas || '-') + ' Orang';
            document.getElementById('ri_fasilitas').textContent = opt.dataset.fasilitas || '-';
            const kap = parseInt(opt.dataset.kapasitas || 0);
            if (kap > 0 && parseInt(est.value) > kap) {
                hint.innerHTML = '<span style="color:#dc2626"><i class="bi bi-exclamation-triangle me-1"></i>Jumlah peserta melebihi kapasitas ruangan! Maks. ' + kap + ' orang.</span>';
            } else if (kap > 0) {
                hint.innerHTML = '<span style="color:#059669"><i class="bi bi-check-circle-fill me-1"></i>Kapasitas ruangan memadai. Sisa kuota ' + (kap - parseInt(est.value || 0)) + ' orang.</span>';
            }
        } else {
            infoBox.style.display = 'none';
        }
    }

    selRuangan.addEventListener('change', updateRoomInfo);
    est.addEventListener('input', updateRoomInfo);
    if (selRuangan.value) updateRoomInfo();

    // Terapkan style border checkbox yang sudah checked saat load
    document.querySelectorAll('.form-check-input').forEach(function(cb) {
        if (cb.checked && cb.closest('.form-check')) {
            cb.closest('.form-check').style.borderColor = '#6366f1';
            cb.closest('.form-check').style.background = 'linear-gradient(180deg,#eef2ff,#eef4ff)';
        }
    });

    document.getElementById('formRuangan').addEventListener('submit', function(e) {
        const t1 = document.getElementById('tanggal_mulai').value;
        const t2 = document.getElementById('tanggal_selesai').value;
        const j1 = document.getElementById('jam_mulai').value;
        const j2 = document.getElementById('jam_selesai').value;
        if (t2 < t1) {
            e.preventDefault();
            showToast('error','Validasi Gagal','Tanggal selesai tidak boleh sebelum tanggal mulai.');
            return false;
        }
        if (t1 === t2 && j2 <= j1) {
            e.preventDefault();
            showToast('error','Validasi Gagal','Jam selesai harus lebih besar dari jam mulai pada tanggal yang sama.');
            return false;
        }
    });
    document.getElementById('tanggal_mulai').addEventListener('change', function() {
        document.getElementById('tanggal_selesai').min = this.value;
    });
})();
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
