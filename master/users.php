<?php
$page_title = 'Data Pengguna';
$active_menu = 'master-users';
require_once __DIR__ . '/../partials/header.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    try {
        if ($act === 'tambah') {
            $nip = sanitize($_POST['nip']);
            $nama = sanitize($_POST['nama_lengkap']);
            $role = sanitize($_POST['role']);
            $unit = sanitize($_POST['unit_kerja']);
           
         $pass = $role === 'admin' ? password_hash('password', PASSWORD_DEFAULT) : '';

if (!$nip || !$nama) throw new Exception('Lengkapi data wajib.');

$cek = db()->count('users', 'nip = ?', [$nip]);
if ($cek > 0) throw new Exception('NIP sudah ada.');

$nama_lengkap = $nama;
$password = $pass;
$unit_kerja = $unit;

db()->insert('users', compact(
    'nip',
    'nama_lengkap',
    'password',
    'role',
    'unit_kerja'
));
           
            set_flash('success', 'Pengguna berhasil ditambahkan.');
        } elseif ($act === 'edit') {
            $id = (int)$_POST['id'];
            $nama = sanitize($_POST['nama_lengkap']);
            $role = sanitize($_POST['role']);
            $unit = sanitize($_POST['unit_kerja']);
         
            $upd = ['nama_lengkap' => $nama, 'role' => $role, 'unit_kerja' => $unit,];
            if ($role === 'admin' && !empty($_POST['reset_password'])) {
                $upd['password'] = password_hash('password', PASSWORD_DEFAULT);
            }
            db()->update('users', $upd, 'id = ?', [$id]);
            set_flash('success', 'Data pengguna diperbarui.');
        } elseif ($act === 'hapus') {
            $id = (int)$_POST['id'];
            db()->delete('users', 'id = ?', [$id]);
            set_flash('success', 'Data pengguna dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
    }
    redirect(base_url('master/users.php'));
}

$search = sanitize($_GET['search'] ?? '');
$where = '1=1'; $p = [];
if ($search) {
    $where .= " AND (nama_lengkap LIKE ? OR nip LIKE ? OR unit_kerja LIKE ?)";
    $s = "%{$search}%";
    array_push($p, $s, $s, $s);
}
$users = db()->fetchAll("SELECT * FROM users WHERE {$where} ORDER BY role, nama_lengkap", $p);
$jmlAdmin = db()->count('users', "role = 'admin'");
$jmlPegawai = db()->count('users', "role = 'pegawai'");
?>

<div class="page-header d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
    <div>
        <h4><i class="bi bi-people-fill me-2" style="color:#2563eb"></i>Master Data Pengguna</h4>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="<?= base_url('dashboard.php') ?>">Home</a>
            <a class="breadcrumb-item" href="#">Master Data</a>
            <span class="breadcrumb-item active">Data Pengguna</span>
        </nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
        <i class="bi bi-plus-lg"></i> Tambah Pengguna
    </button>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon blue"><i class="bi bi-people-fill"></i></div><div class="stat-label">Total</div><div class="stat-value"><?= count($users) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon amber"><i class="bi bi-shield-lock"></i></div><div class="stat-label">Admin</div><div class="stat-value"><?= $jmlAdmin ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-icon green"><i class="bi bi-person-vcard"></i></div><div class="stat-label">Pegawai</div><div class="stat-value"><?= $jmlPegawai ?></div></div></div>
</div>

<div class="card">
    <div class="card-header flex-wrap gap-2">
        <h6 class="card-title">Daftar Pengguna Sistem</h6>
        <form method="GET" action="<?= base_url('master/users.php') ?>" class="search-wrapper" style="width:280px">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control search-input" name="search" placeholder="Cari nama / NIP / unit..." value="<?= $search ?>">
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>NIP</th><th>Nama Lengkap</th><th>Role</th><th>Unit Kerja</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="font-weight:700;color:#1e40af;font-size:11px"><?= $u['nip'] ?></td>
                        <td style="font-weight:600;font-size:11px"><?= $u['nama_lengkap'] ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-danger rounded-pill px-3 py-1" style="font-size:9.5px"><i class="bi bi-shield-lock me-1"></i> ADMIN</span>
                            <?php else: ?>
                                <span class="badge bg-primary rounded-pill px-3 py-1" style="font-size:9.5px"><i class="bi bi-person me-1"></i> PEGAWAI</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:11px"><?= $u['unit_kerja'] ?: '-' ?></td>
                   
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-secondary" onclick="editUser(<?= $u['id'] ?>, '<?= sanitize($u['nip']) ?>', '<?= sanitize($u['nama_lengkap']) ?>', '<?= sanitize($u['role']) ?>', '<?= sanitize($u['unit_kerja']) ?>')"><i class="bi bi-pencil"></i></button>
                                <?php if ($u['id'] != $user['id']): ?>
                                <form method="POST" action="<?= base_url('master/users.php') ?>" onsubmit="return confirm('Yakin hapus pengguna ini?')" style="display:inline">
                                    <input type="hidden" name="act" value="hapus">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Pengguna Baru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="<?= base_url('master/users.php') ?>">
            <input type="hidden" name="act" value="tambah">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NIP *</label>
                        <input type="text" class="form-control" name="nip" required placeholder="Contoh: 2006">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="pegawai">Pegawai</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Unit Kerja</label>
                        <select class="form-select" name="unit_kerja">
                            <?php foreach (get_unit_kerja_list() as $u): ?>
                                <option value="<?= $u ?>"><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                   
                    <div class="col-md-12">
                        <small style="color:#f59e0b"><i class="bi bi-info-circle me-1"></i> Admin akan mendapatkan password default: <code style="background:#fef3c7;padding:2px 6px;border-radius:4px">password</code>. Pegawai login hanya dengan NIP.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            </div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Pengguna</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="<?= base_url('master/users.php') ?>">
            <input type="hidden" name="act" value="edit">
            <input type="hidden" name="id" id="e_id">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NIP</label>
                        <input type="text" class="form-control" id="e_nip" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" id="e_role" required>
                            <option value="pegawai">Pegawai</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="e_nama" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Unit Kerja</label>
                        <select class="form-select" name="unit_kerja" id="e_unit">
                            <?php foreach (get_unit_kerja_list() as $u): ?>
                                <option value="<?= $u ?>"><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                   
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="reset_password" id="e_reset">
                            <label class="form-check-label" for="e_reset">Reset password Admin ke default (<code>password</code>) ΓÇö hanya jika role Admin</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Perbarui</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function editUser(id, nip, nama, role, unit) {
    document.getElementById('e_id').value = id;
    document.getElementById('e_nip').value = nip;
    document.getElementById('e_nama').value = nama;
    document.getElementById('e_role').value = role;
    document.getElementById('e_unit').value = unit;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php' ?>
