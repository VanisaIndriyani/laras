<?php
$page_title = 'Form Reservasi Kendaraan';
$active_menu = 'kendaraan';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_login();

// AUTO MIGRATION TAMBAH KOLOM DRIVER & FASILITAS TAMBAHAN JIKA BELUM ADA
try {
    $db_name = DB_NAME;
    $existing = db()->fetchAll("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reservasi_kendaraan'", [$db_name]);
    $existing_cols = array_column($existing, 'COLUMN_NAME');
    $migration_cols = [
        'driver_id'          => "ALTER TABLE reservasi_kendaraan ADD COLUMN driver_id INT DEFAULT NULL",
        'fasilitas_tambahan' => "ALTER TABLE reservasi_kendaraan ADD COLUMN fasilitas_tambahan VARCHAR(255) DEFAULT NULL",
    ];
    foreach ($migration_cols as $col => $sql) {
        if (!in_array($col, $existing_cols, true)) {
            @db()->exec($sql);
        }
    }
    // Pastikan tabel driver ada juga (untuk fresh install tanpa install.php)
    $tbl_driver = db()->fetchOne("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'driver'", [$db_name]);
    if (!$tbl_driver) {
        @db()->exec("CREATE TABLE IF NOT EXISTS driver (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_driver VARCHAR(120) NOT NULL,
            no_wa VARCHAR(20) DEFAULT NULL,
            status ENUM('tersedia','bertugas','libur') NOT NULL DEFAULT 'tersedia',
            foto VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // === AUTO SEED IDEMPOTENT (COUNT KURANG → INSERT MISSING DATA EXCEL USER, DUPLIKAT DIHINDARI UNIQUE KEY) ===
    try {
        // Add UNIQUE KEY kalau belum ada (hindari duplikat no_plat / nama_ruangan)
        $idx_k = @db()->fetchAll("SHOW INDEX FROM kendaraan WHERE Key_name LIKE 'uniq_kendaraan_%'");
        if (empty($idx_k)) { @db()->exec("ALTER IGNORE TABLE kendaraan ADD UNIQUE KEY uniq_kendaraan_plat (no_plat)"); }
        $idx_r = @db()->fetchAll("SHOW INDEX FROM ruangan WHERE Key_name LIKE 'uniq_ruangan_%'");
        if (empty($idx_r)) { @db()->exec("ALTER IGNORE TABLE ruangan ADD UNIQUE KEY uniq_ruangan_nama (nama_ruangan)"); }
        $idx_d = @db()->fetchAll("SHOW INDEX FROM driver WHERE Key_name LIKE 'uniq_driver_%'");
        if (empty($idx_d)) { @db()->exec("ALTER IGNORE TABLE driver ADD UNIQUE KEY uniq_driver_nama (nama_driver)"); }

        // (A) Driver 4 default → INSERT IGNORE (tetap ID 1-4)
        $cnt_d = (int)db()->fetchOne("SELECT COUNT(*) AS c FROM driver")['c'];
        if ($cnt_d < 4) {
            $drivers = [
                [1,'Driver 1','081227889901','tersedia'],
                [2,'Driver 2','081392114402','tersedia'],
                [3,'Driver 3','081804556603','bertugas'],
                [4,'Driver 4','085729334404','tersedia'],
            ];
            foreach ($drivers as $d) @db()->exec("INSERT IGNORE INTO driver(id,nama_driver,no_wa,status,created_at) VALUES (?,?,?,?,NOW())", $d);
        }

        // (B) Kendaraan 7 unit sesuai EXCEL → INSERT IGNORE by no_plat
        $cnt_k = (int)db()->fetchOne("SELECT COUNT(*) AS c FROM kendaraan")['c'];
        if ($cnt_k < 7) {
            $cars = [
                ['AB 1325 UB','Toyota','Innova',2023,7,'tersedia',1,'Driver 1','081227889901','Bagian Umum'],
                ['AB 1432 UB','Toyota','Innova',2023,7,'tersedia',2,'Driver 2','081392114402','Bagian Umum'],
                ['AB 1449 UB','Toyota','Avanza',2023,7,'tersedia',3,'Driver 3','081804556603','Bagian Umum'],
                ['AB 1769 UA','Toyota','Kijang',2022,7,'tersedia',4,'Driver 4','085729334404','Bagian Umum'],
                ['AB 1180 UB','Toyota','Krista',2022,7,'tersedia',1,'Driver 1','081227889901','Bidang Investigasi'],
                ['B 1247 TQO','Toyota','Innova Reborn',2024,7,'tersedia',2,'Driver 2','081392114402','Bidang APD'],
                ['B 1248 TQO','Toyota','Innova Reborn',2024,7,'tersedia',4,'Driver 4','085729334404','Bidang IPP'],
            ];
            foreach ($cars as $c) @db()->exec("INSERT IGNORE INTO kendaraan(no_plat,merk,tipe,tahun,kapasitas,status,driver_id,driver,no_hp_driver,unit_pengguna,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())", $c);
        }

        // (C) Ruangan 11 sesuai EXCEL → INSERT IGNORE by nama_ruangan + perbaiki typo "LLantai" → lantai INT
        $cnt_r = (int)db()->fetchOne("SELECT COUNT(*) AS c FROM ruangan")['c'];
        // Perbaiki kolom lantai jika masih VARCHAR typo "LLantai X" → INT lantai
        try {
            $col_r = db()->fetchAll("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='ruangan' AND COLUMN_NAME='lantai'",[$db_name]);
            if (!empty($col_r) && strtolower($col_r[0]['DATA_TYPE']) !== 'int') {
                // Ubah yang berawalan LLantai / Lantai X ke integer
                $all = db()->fetchAll("SELECT id, lantai FROM ruangan");
                foreach ($all as $row) {
                    if (preg_match('/(\d+)/', (string)$row['lantai'], $m)) {
                        @db()->exec("UPDATE ruangan SET lantai = ? WHERE id = ?", [(int)$m[1], $row['id']]);
                    }
                }
                @db()->exec("ALTER TABLE ruangan MODIFY COLUMN lantai INT NOT NULL DEFAULT 1");
            }
        } catch(Exception $e){}
        if ($cnt_r < 11) {
            $rugs = [
                ['R001','Aula Bawana',1,200,'Audio Mic Sound System, Video LCD Proyektor, Standing TV'],
                ['R002','R. Workshop',2,25,'LCD Proyektor, Whiteboard, Kabel Rol opsional'],
                ['R003','R. DWP',2,15,'Meja bundar, 15 kursi, Proyektor mini'],
                ['R004','R. Smart Workshop',2,15,'Smart TV Touchscreen, Standing TV, Kabel Rol'],
                ['R005','R. Rapat Bagian Umum',2,10,'Meja panjang, 10 kursi, Proyektor'],
                ['R006','R. Rapat Kepegawaian',2,8,'Meja rapat, 8 kursi, LCD Proyektor'],
                ['R007','R. Mitra',2,8,'Rapat tamu, Sofa + meja tamu, Standing TV opsional'],
                ['R008','R. Perpus',3,50,'Rak buku, Sound system, Kursi 50, Pojok baca'],
                ['R009','R. Kelas Barat',3,30,'Training, 30 meja kursi, Proyektor, Whiteboard'],
                ['R010','R. Kelas Timur',3,30,'Training, 30 meja kursi, Proyektor, Whiteboard'],
                ['R011','R. Fitnes',3,15,'Alat fitnes lengkap, Cermin besar, Karpet karet'],
            ];
            foreach ($rugs as $r) @db()->exec("INSERT IGNORE INTO ruangan(kode_ruangan,nama_ruangan,lantai,kapasitas,fasilitas,status,created_at) VALUES (?,?,?,?,?, 'tersedia', NOW())", $r);
        }
    } catch(Exception $ex) {}
} catch (Exception $ex) {}

require_once __DIR__ . '/../partials/header.php';

$user = current_user();
$isAdmin = is_admin();

$editId = (int)($_GET['id'] ?? 0);
$data = null;
$tanggal_min = date('Y-m-d');

if ($editId > 0) {
    $data = db()->fetchOne("SELECT * FROM reservasi_kendaraan WHERE id = ?", [$editId]);
    if (!$data || (!$isAdmin && $data['user_id'] != $user['id'])) {
        set_flash('error', 'Reservasi tidak ditemukan atau tidak sah.');
        redirect(base_url('kendaraan/index.php'));
    }
    if (!in_array($data['status'], ['pending', 'ditolak'])) {
        set_flash('error', 'Reservasi sudah tidak dapat diubah.');
        redirect(base_url('kendaraan/index.php'));
    }
}

$user_data = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
$kendaraan_list = db()->fetchAll("SELECT * FROM kendaraan WHERE status = 'tersedia' ORDER BY merk, tipe");
try {
    $drivers_list = db()->fetchAll("SELECT id, nama_driver, no_wa, status FROM driver ORDER BY nama_driver");
} catch (Exception $e) { $drivers_list = []; }
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
    <div>
        <h4><i class="bi bi-car-front-fill me-2" style="color:#2563eb"></i><?= $editId ? 'Edit Reservasi Kendaraan' : 'Ajukan Reservasi Kendaraan Baru' ?></h4>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <a class="breadcrumb-item" href="<?= base_url('kendaraan/index.php') ?>">Reservasi Kendaraan</a>
            <span class="breadcrumb-item active"><?= $editId ? 'Edit' : 'Baru' ?></span>
        </nav>
    </div>
    <a href="<?= base_url('kendaraan/index.php') ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<form action="<?= base_url('kendaraan/action.php') ?>" method="POST" id="formKendaraan">
    <input type="hidden" name="action" value="<?= $editId ? 'update' : 'create' ?>">
    <?php if ($editId): ?>
        <input type="hidden" name="id" value="<?= $editId ?>">
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-number">1</div>
                    <div class="section-text">
                        <strong>Identitas Penanggung Jawab Kegiatan</strong>
                        <small>Data pemohon penanggung jawab peminjaman kendaraan dinas</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama Lengkap Pemohon *</label>
                        <input type="text" class="form-control" name="nama_lengkap" required value="<?= sanitize($data ? db()->fetchOne("SELECT nama_lengkap FROM users WHERE id = ?", [$data['user_id']])['nama_lengkap'] : $user_data['nama_lengkap']) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIP Pemohon *</label>
                        <input type="text" class="form-control" name="nip" required value="<?= sanitize($data ? db()->fetchOne("SELECT nip FROM users WHERE id = ?", [$data['user_id']])['nip'] : $user_data['nip']) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit Kerja *</label>
                        <select class="form-select" name="unit_kerja" required>
                            <?php foreach (get_unit_kerja_list() as $u): ?>
                                <option value="<?= $u ?>" <?= ($u === ($data ? db()->fetchOne("SELECT unit_kerja FROM users WHERE id = ?", [$data['user_id']])['unit_kerja'] : $user_data['unit_kerja'])) ? 'selected' : '' ?>><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nomor HP / WA <span style="color:#94a3b8">(Opsional)</span></label>
                        <input type="text" class="form-control" name="no_hp" value="<?= sanitize($data ? db()->fetchOne("SELECT no_hp FROM users WHERE id = ?", [$data['user_id']])['no_hp'] : $user_data['no_hp']) ?>" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-number">2</div>
                    <div class="section-text">
                        <strong>Detail Kendaraan, Supir & Tujuan Perjalanan Dinas</strong>
                        <small>Pilih armada kendaraan, driver (opsional fasilitas tambahan), dan isi detail keperluan perjalanan</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Pilih Kendaraan *</label>
                        <select class="form-select" name="kendaraan_id" id="kendaraan_id" onchange="onCarChange(this)" required>
                            <option value="">-- Pilih Unit Kendaraan --</option>
                            <?php foreach ($kendaraan_list as $k): ?>
                                <?php
                                    $selected = $data && $data['kendaraan_id'] == $k['id'] ? 'selected' : '';
                                    $kapasitas = $k['kapasitas'] ? " ({$k['kapasitas']} Penumpang)" : '';
                                    $drvid = (int)($k['driver_id'] ?? 0);
                                ?>
                                <option value="<?= $k['id'] ?>" data-kapasitas="<?= $k['kapasitas'] ?>"
                                        data-defaultdriver="<?= $drvid ?>"
                                        data-driver="<?= sanitize($k['driver']) ?>" data-hp="<?= sanitize($k['no_hp_driver']) ?>" <?= $selected ?>>
                                    <?= $k['no_plat'] ?> • <?= $k['merk'] ?> <?= $k['tipe'] ?><?= $kapasitas ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="driver_id" id="driver_id" value="<?= (int)($data['driver_id'] ?? 0) ?>">
                        <div class="form-text">Kendaraan yang tampil hanya yang berstatus Tersedia. Admin dapat menghubungi Bagian Umum untuk info ketersediaan unit lain. Driver default otomatis ter-assign sesuai unit yang dipilih (centang opsi "Dengan Driver/Supir" di bawah).</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tujuan Lokasi *</label>
                        <input type="text" class="form-control" name="tujuan" placeholder="Contoh: Kantor Gubernur DIY, Sleman" required value="<?= sanitize($data['tujuan'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fasilitas Tambahan <small class="text-muted">— Penugasan Supir</small></label>
                        <div class="border rounded-3 p-3" style="background:linear-gradient(180deg,#fafcff,#ffffff);border:1.5px solid #e5edf8">
                            <?php
                                $selFas = [];
                                if (!empty($data['fasilitas_tambahan'])) $selFas = json_decode($data['fasilitas_tambahan'], true) ?: [];
                                if (!is_array($selFas)) $selFas = $selFas ? [$selFas] : [];
                                $faOptions = [
                                    'driver' => ['label' => 'Dengan Driver / Supir', 'icon' => 'bi-person-gear', 'desc' => 'Menugaskan supir default dari Master Data sesuai unit kendaraan yang dipilih'],
                                ];
                                echo '<div class="row g-2">';
                                foreach ($faOptions as $kode => $fa) {
                                    $checked = in_array($kode, $selFas, true);
                                    $borderCol = $checked ? '#3B5FC7' : '#e2e8f0';
                                    $bgCol = $checked ? 'rgba(59,95,199,0.05)' : '#fff';
                                    $textCol = $checked ? '#1e3a8a' : '#0f172a';
                                    $attrChecked = $checked ? 'checked' : '';
                                    echo '<div class="col-12 col-md-12">'
                                        . '<label class="w-100 mb-0 d-flex gap-2 align-items-start p-2 rounded-2 border cursor-pointer" style="border:1.5px solid ' . $borderCol . '; background:' . $bgCol . '">'
                                        . '<input class="mt-1" type="checkbox" id="fa_driver_cb" name="fasilitas_tambahan[]" value="' . htmlspecialchars($kode) . '" ' . $attrChecked . '>'
                                        . '<div class="flex-grow-1">'
                                        . '<div style="font-size:11px;font-weight:700;display:flex;align-items:center;gap:5px;color:' . $textCol . '"><i class="bi ' . htmlspecialchars($fa['icon']) . '"></i>' . htmlspecialchars($fa['label']) . '</div>'
                                        . '<div class="text-muted mt-0.5" style="font-size:9.5px;line-height:1.3">' . htmlspecialchars($fa['desc']) . '</div>'
                                        . '</div></label></div>';
                                }
                                echo '</div>';
                            ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Keperluan / Agenda Perjalanan Dinas *</label>
                        <textarea class="form-control" name="keperluan" rows="3" placeholder="Jelaskan secara ringkas tujuan dan agenda kegiatan perjalanan dinas" required><?= sanitize($data['keperluan'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-number">3</div>
                    <div class="section-text">
                        <strong>Waktu Penggunaan Kendaraan</strong>
                        <small>Tentukan jadwal tanggal dan jam pemakaian kendaraan</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai Pinjam *</label>
                        <input type="date" class="form-control" name="tanggal_pinjam" id="tanggal_pinjam" min="<?= $tanggal_min ?>" required value="<?= sanitize($data['tanggal_pinjam'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jam Mulai * <small class="text-muted">(WIB)</small></label>
                        <select class="form-select" name="jam_mulai" required>
                            <?php
                            $jam = $data['jam_mulai'] ?? '08:00:00';
                            for ($h = 7; $h <= 21; $h++):
                                foreach ([0, 30] as $m):
                                    $val = sprintf('%02d:%02d:00', $h, $m);
                                    $label = sprintf('%02d:%02d WIB', $h, $m);
                                    if ($h === 21 && $m === 30) continue;
                            ?>
                                <option value="<?= $val ?>" <?= $jam === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; endfor; for ($h = 19; $h <= 21; $h++): ?>
                                <?php
                                $val = sprintf('%02d:00:00', $h);
                                $label = sprintf('%02d:00 WIB', $h);
                                if (in_array($h, [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18])) continue;
                                ?>
                                <option value="<?= $val ?>" <?= $jam === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Selesai *</label>
                        <input type="date" class="form-control" name="tanggal_kembali" id="tanggal_kembali" min="<?= $tanggal_min ?>" required value="<?= sanitize($data['tanggal_kembali'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jam Selesai * <small class="text-muted">(WIB)</small></label>
                        <select class="form-select" name="jam_selesai" required>
                            <?php
                            $jam2 = $data['jam_selesai'] ?? '16:00:00';
                            for ($h = 7; $h <= 21; $h++):
                                foreach ([0, 30] as $m):
                                    $val = sprintf('%02d:%02d:00', $h, $m);
                                    $label = sprintf('%02d:%02d WIB', $h, $m);
                                    if ($h === 21 && $m === 30) continue;
                            ?>
                                <option value="<?= $val ?>" <?= $jam2 === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; endfor; for ($h = 19; $h <= 21; $h++): ?>
                                <?php
                                $val = sprintf('%02d:00:00', $h);
                                $label = sprintf('%02d:00 WIB', $h);
                                if (in_array($h, [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18])) continue;
                                ?>
                                <option value="<?= $val ?>" <?= $jam2 === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions-sticky">
                <a href="<?= base_url('kendaraan/index.php') ?>" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i> <?= $editId ? 'Update Pengajuan' : 'Kirim Pengajuan Reservasi' ?></button>
            </div>
        </div>
    </div>
</form>

<script>
// Auto set default driver hidden value ketika user ganti pilihan kendaraan
function onCarChange(sel) {
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    const driverHidden = document.getElementById('driver_id');
    const cb = document.getElementById('fa_driver_cb');
    if (opt && driverHidden) {
        const defaultDrv = opt.getAttribute('data-defaultdriver');
        if (defaultDrv && String(defaultDrv) !== '0') {
            driverHidden.value = String(defaultDrv);
        } else {
            driverHidden.value = '';
        }
        // Jika checkbox "Dengan Driver" dicentang, ensure driverHidden sudah terisi; jika tidak ada default dan dicentang, kosongkan
        if (cb && !cb.checked) {
            driverHidden.value = '';
        }
    }
}
// Listener checkbox: jika tidak dicentang -> driver_id kosong; jika dicentang -> isi dari default kendaraan yang dipilih
document.addEventListener('DOMContentLoaded', function() {
    const cb = document.getElementById('fa_driver_cb');
    const driverHidden = document.getElementById('driver_id');
    const sel = document.getElementById('kendaraan_id');
    if (cb && driverHidden) {
        cb.addEventListener('change', function() {
            if (!cb.checked) {
                driverHidden.value = '';
            } else if (sel && sel.value) {
                // Isi ulang dari default driver option kendaraan yang sedang dipilih
                const opt = sel.options[sel.selectedIndex];
                const defaultDrv = opt ? opt.getAttribute('data-defaultdriver') : null;
                driverHidden.value = (defaultDrv && String(defaultDrv) !== '0') ? String(defaultDrv) : '';
            }
        });
    }
    // Auto trigger on page load (edit mode / prefill)
    if (sel && sel.value) onCarChange(sel);
});
document.getElementById('formKendaraan').addEventListener('submit', function(e) {
    const t1 = document.getElementById('tanggal_pinjam').value;
    const t2 = document.getElementById('tanggal_kembali').value;
    if (t2 < t1) {
        e.preventDefault();
        alert('Tanggal selesai tidak boleh sebelum tanggal mulai pinjam.');
        return false;
    }
    // Final sync driver_id dengan checkbox
    const cb = document.getElementById('fa_driver_cb');
    const driverHidden = document.getElementById('driver_id');
    if (cb && driverHidden && !cb.checked) {
        driverHidden.value = '';
    }
});
document.getElementById('tanggal_pinjam').addEventListener('change', function() {
    document.getElementById('tanggal_kembali').min = this.value;
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
