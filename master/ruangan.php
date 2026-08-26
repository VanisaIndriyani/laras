<?php
$page_title = 'Master Ruangan';
$active_menu = 'master-ruangan';
require_once __DIR__ . '/../partials/header.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    try {
        $data = [
            'kode_ruangan' => sanitize($_POST['kode_ruangan']),
            'nama_ruangan' => sanitize($_POST['nama_ruangan']),
            'lantai' => sanitize($_POST['lantai']),
            'kapasitas' => (int)$_POST['kapasitas'],
            'fasilitas' => sanitize($_POST['fasilitas']),
            'status' => sanitize($_POST['status'])
        ];
        if (!$data['kode_ruangan'] || !$data['nama_ruangan']) throw new Exception('Lengkapi data wajib.');
        if ($act === 'tambah') {
            db()->insert('ruangan', $data);
            set_flash('success', 'Ruangan ditambahkan.');
        } elseif ($act === 'edit') {
            $id = (int)$_POST['id'];
            db()->update('ruangan', $data, 'id = ?', [$id]);
            set_flash('success', 'Data ruangan diperbarui.');
        } elseif ($act === 'hapus') {
            db()->delete('ruangan', 'id = ?', [(int)$_POST['id']]);
            set_flash('success', 'Data dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
    }
    redirect(base_url('master/ruangan.php'));
}

$ruangan = db()->fetchAll("SELECT * FROM ruangan ORDER BY lantai, nama_ruangan");
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
    <div>
        <h4><i class="bi bi-building-fill me-2" style="color:#7c3aed"></i>Master Data Ruangan</h4>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <a class="breadcrumb-item" href="#">Master Data</a>
            <span class="breadcrumb-item active">Ruangan</span>
        </nav>
    </div>
    <button class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#tambahModal"><i class="bi bi-plus-lg"></i> Tambah Ruangan</button>
</div>

<div class="row g-3 mb-3">
    <?php
        $jml = [
            'total' => count($ruangan),
            'tersedia' => count(array_filter($ruangan, fn($k)=>$k['status']==='tersedia')),
            'perawatan' => count(array_filter($ruangan, fn($k)=>$k['status']==='perawatan')),
            'tidak' => count(array_filter($ruangan, fn($k)=>$k['status']==='tidak_tersedia'))
        ];
    ?>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon purple"><i class="bi bi-door-open-fill"></i></div><div class="stat-label">Total Ruangan</div><div class="stat-value"><?= $jml['total'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div><div class="stat-label">Tersedia</div><div class="stat-value"><?= $jml['tersedia'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon amber"><i class="bi bi-tools"></i></div><div class="stat-label">Perawatan</div><div class="stat-value"><?= $jml['perawatan'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon pink"><i class="bi bi-x-circle"></i></div><div class="stat-label">Tidak Tersedia</div><div class="stat-value"><?= $jml['tidak'] ?></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h6 class="card-title">Daftar Ruangan & Sarana Rapat</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Kode</th><th>Nama Ruangan</th><th>Lantai</th><th>Kapasitas</th><th>Fasilitas</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($ruangan as $r): ?>
                    <tr>
                        <td style="font-weight:700;color:#6d28d9;font-size:11px"><?= $r['kode_ruangan'] ?></td>
                        <td style="font-weight:600;font-size:11.5px"><?= $r['nama_ruangan'] ?></td>
                        <td style="font-size:11px"><?= $r['lantai'] ?></td>
                        <td><strong style="color:#7c3aed"><?= $r['kapasitas'] ?></strong> org</td>
                        <td style="font-size:10.5px;max-width:280px;line-height:1.45;color:#475569"><?= $r['fasilitas'] ?: '-' ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-secondary" onclick='editR(<?= json_encode($r, JSON_NUMERIC_CHECK) ?>)'><i class="bi bi-pencil"></i></button>
                                <form method="POST" onsubmit="return confirm('Hapus ruangan ini?')" style="display:inline">
                                    <input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$fields = [
    ['name'=>'kode_ruangan','label'=>'Kode Ruangan *','ph'=>'Contoh: AULA-02'],
    ['name'=>'nama_ruangan','label'=>'Nama Ruangan *','ph'=>'Contoh: Aula Serbaguna'],
    ['name'=>'lantai','label'=>'Lokasi / Lantai','ph'=>'Contoh: Lantai 3'],
    ['name'=>'kapasitas','label'=>'Kapasitas (Orang) *','ph'=>'30'],
];
?>

<div class="modal fade" id="tambahModal"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus me-2"></i>Tambah Ruangan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST"><input type="hidden" name="act" value="tambah">
        <div class="modal-body">
            <div class="row g-3">
                <?php foreach ($fields as $f): ?>
                <div class="col-md-6">
                    <label class="form-label"><?= $f['label'] ?></label>
                    <input type="<?= str_contains($f['name'],'kapasitas')?'number':'text' ?>" class="form-control" name="<?= $f['name'] ?>" id="t_<?= $f['name'] ?>" placeholder="<?= $f['ph'] ?>">
                </div>
                <?php endforeach; ?>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" id="t_status">
                        <option value="tersedia">Tersedia</option>
                        <option value="tidak_tersedia">Tidak Tersedia</option>
                        <option value="perawatan">Perawatan</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Fasilitas Ruangan</label>
                    <textarea class="form-control" name="fasilitas" id="t_fasilitas" rows="2" placeholder="Pisahkan dengan koma. Contoh: Proyektor, Sound System, AC Central"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-purple"><i class="bi bi-save"></i> Simpan</button>
        </div>
    </form>
</div></div></div>

<div class="modal fade" id="editModal"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Ruangan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST"><input type="hidden" name="act" value="edit"><input type="hidden" name="id" id="e_id">
        <div class="modal-body">
            <div class="row g-3">
                <?php foreach ($fields as $f): $tp = str_contains($f['name'],'kapasitas')?'number':'text'; ?>
                <div class="col-md-6">
                    <label class="form-label"><?= $f['label'] ?></label>
                    <input type="<?= $tp ?>" class="form-control" name="<?= $f['name'] ?>" id="e_<?= $f['name'] ?>">
                </div>
                <?php endforeach; ?>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" id="e_status">
                        <option value="tersedia">Tersedia</option>
                        <option value="tidak_tersedia">Tidak Tersedia</option>
                        <option value="perawatan">Perawatan</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Fasilitas Ruangan</label>
                    <textarea class="form-control" name="fasilitas" id="e_fasilitas" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-purple"><i class="bi bi-save"></i> Perbarui</button>
        </div>
    </form>
</div></div></div>

<script>
function editR(r) {
    ['kode_ruangan','nama_ruangan','lantai','kapasitas','fasilitas','status'].forEach(f => {
        const el = document.getElementById('e_' + f); if (el) el.value = r[f] ?? '';
    });
    document.getElementById('e_id').value = r.id;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
