<?php
$page_title = 'Edit Profil & Ubah Password';
$active_menu = '';
require_once __DIR__ . '/partials/header.php';

$user = current_user();
$uid = (int)($user['id'] ?? 0);
$isAdmin = is_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_password'])):
    $old_pw   = trim($_POST['old_password'] ?? '');
    $new_pw   = trim($_POST['new_password'] ?? '');
    $conf_pw  = trim($_POST['confirm_password'] ?? '');

    $errors = [];
    $dbUser = db()->fetchOne("SELECT * FROM users WHERE id = ? LIMIT 1", [$uid]);

    if (!$dbUser) {
        $errors[] = 'Data pengguna tidak ditemukan.';
    } else {
        if ($old_pw === '') $errors[] = 'Password saat ini wajib diisi.';
        elseif (!password_verify($old_pw, $dbUser['password'] ?? '')) $errors[] = 'Password saat ini tidak sesuai.';

        if (strlen($new_pw) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        if ($new_pw !== $conf_pw) $errors[] = 'Konfirmasi password baru tidak sama.';
        if ($new_pw !== '' && $new_pw === $old_pw) $errors[] = 'Password baru tidak boleh sama dengan password lama.';
    }

    if (empty($errors)) {
        $hash = password_hash($new_pw, PASSWORD_BCRYPT, ['cost' => 10]);
        $ok = db()->update('users', ['password' => $hash], 'id = ?', [$uid]);
        if ($ok !== false) {
            set_flash('success', 'Password berhasil diperbarui. Silakan gunakan password baru Anda di login berikutnya.');
        } else {
            set_flash('error', 'Gagal memperbarui password, coba sesaat lagi.');
        }
        redirect(base_url('profile.php'));
    } else {
        set_flash('error', implode('<br>', $errors));
    }
endif;

$dbUser = db()->fetchOne("SELECT * FROM users WHERE id = ? LIMIT 1", [$uid]);
?>

<div class="container-fluid" style="padding:20px 26px 40px">
    <div style="margin-bottom:18px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
            <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#3B5FC7,#2563eb);box-shadow:0 6px 16px -5px rgba(37,99,235,0.65);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px">
                <i class="bi bi-person-fill-gear"></i>
            </div>
            <div>
                <h2 style="margin:0;font-size:20px;font-weight:800;color:#0f172a;letter-spacing:-0.2px">Pengaturan Profil</h2>
                <div style="font-size:11.5px;color:#64748b;margin-top:2px;font-weight:500">Kelola akun dan perbarui kata sandi Anda secara berkala</div>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Info Akun -->
        <div class="col-lg-4 col-md-5 col-12">
            <div style="border-radius:18px;overflow:hidden;background:#fff;border:1px solid #e2e8f0;box-shadow:0 14px 40px -18px rgba(15,23,42,0.15)">
                <div style="background:linear-gradient(135deg,#0B1C48 0%,#1F3A8B 55%,#2563eb 100%);padding:22px 20px 28px;position:relative;overflow:hidden">
                    <div style="position:absolute;top:-40px;right:-50px;width:220px;height:220px;background:radial-gradient(circle,rgba(147,197,253,0.35) 0%,transparent 65%);border-radius:50%"></div>
                    <div style="position:relative;display:flex;align-items:center;gap:14px">
                        <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#ffffff,#dbeafe);display:flex;align-items:center;justify-content:center;color:#0B1C48;font-size:26px;font-weight:900;box-shadow:0 10px 24px -10px rgba(0,0,0,0.4),inset 0 1px 0 rgba(255,255,255,0.9);border:2px solid rgba(255,255,255,0.8);position:relative">
                            <?= strtoupper(mb_substr($dbUser['nama_lengkap'] ?? $user['nama'] ?? 'U', 0, 1)) ?>
                            <div style="position:absolute;bottom:-2px;right:-2px;width:17px;height:17px;border-radius:50%;background:#10b981;border:3px solid rgba(11,28,72,0.85);box-shadow:0 0 0 1.5px rgba(16,185,129,0.5),0 0 10px rgba(16,185,129,0.5)"></div>
                        </div>
                        <div style="min-width:0">
                            <div style="font-size:15px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-shadow:0 1px 2px rgba(0,0,0,0.35)"><?= htmlspecialchars($dbUser['nama_lengkap'] ?? $user['nama'] ?? '-') ?></div>
                            <div style="margin-top:4px;display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,0.22);padding:3px 10px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:0.4px;color:#e0f2fe;text-transform:uppercase">
                                <?= $isAdmin ? 'Administrator' : 'Pegawai' ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding:6px 20px 20px">
                    <div style="padding:12px 0;border-bottom:1px dashed #e2e8f0;display:flex;gap:12px;align-items:flex-start">
                        <div style="width:32px;height:32px;border-radius:10px;background:rgba(59,95,199,0.08);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;margin-top:1px"><i class="bi bi-person-vcard-fill"></i></div>
                        <div style="min-width:0;flex:1">
                            <div style="font-size:9.5px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#94a3b8;margin-bottom:2px">NIP</div>
                            <div style="font-size:12.5px;font-weight:700;color:#0f172a"><?= htmlspecialchars($dbUser['nip'] ?? $user['nip'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div style="padding:12px 0;border-bottom:1px dashed #e2e8f0;display:flex;gap:12px;align-items:flex-start">
                        <div style="width:32px;height:32px;border-radius:10px;background:rgba(16,185,129,0.08);color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;margin-top:1px"><i class="bi bi-buildings-fill"></i></div>
                        <div style="min-width:0;flex:1">
                            <div style="font-size:9.5px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#94a3b8;margin-bottom:2px">Unit Kerja</div>
                            <div style="font-size:12.5px;font-weight:600;color:#1e293b"><?= htmlspecialchars($dbUser['unit_kerja'] ?? APP_BAGIAN) ?></div>
                        </div>
                    </div>
                    <div style="padding:12px 0;border-bottom:1px dashed #e2e8f0;display:flex;gap:12px;align-items:flex-start">
                        <div style="width:32px;height:32px;border-radius:10px;background:rgba(245,158,11,0.1);color:#d97706;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;margin-top:1px"><i class="bi bi-telephone-fill"></i></div>
                        <div style="min-width:0;flex:1">
                            <div style="font-size:9.5px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#94a3b8;margin-bottom:2px">Nomor HP</div>
                            <div style="font-size:12.5px;font-weight:600;color:#1e293b"><?= !empty($dbUser['no_hp']) ? htmlspecialchars($dbUser['no_hp']) : '<span style="color:#94a3b8;font-weight:500;font-size:11.5px">Belum diisi</span>' ?></div>
                        </div>
                    </div>
                    <div style="padding:12px 0 2px;display:flex;gap:12px;align-items:flex-start">
                        <div style="width:32px;height:32px;border-radius:10px;background:rgba(139,92,246,0.1);color:#7c3aed;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;margin-top:1px"><i class="bi bi-shield-lock-fill"></i></div>
                        <div style="min-width:0;flex:1">
                            <div style="font-size:9.5px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;color:#94a3b8;margin-bottom:2px">Instansi</div>
                            <div style="font-size:12px;font-weight:700;color:#233E90;line-height:1.3"><?= APP_INSTANSI ?></div>
                            <div style="font-size:10.5px;color:#64748b;margin-top:2px;font-weight:600"><?= APP_BAGIAN ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Ubah Password -->
        <div class="col-lg-6 col-md-7 col-12">
            <div style="border-radius:18px;overflow:hidden;background:#fff;border:1px solid #e2e8f0;box-shadow:0 14px 40px -18px rgba(15,23,42,0.15)">
                <div style="background:linear-gradient(90deg,rgba(59,95,199,0.06),rgba(11,28,72,0.02) 55%,transparent);padding:18px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px">
                    <div style="width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#3B5FC7,#2563eb);color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 6px 14px -5px rgba(37,99,235,0.65)"><i class="bi bi-key-fill"></i></div>
                    <div>
                        <div style="font-size:14px;font-weight:800;color:#0f172a">Ubah Kata Sandi</div>
                        <div style="font-size:10.5px;color:#64748b;margin-top:2px;font-weight:500">Gunakan kombinasi huruf, angka, dan simbol untuk keamanan maksimal</div>
                    </div>
                </div>
                <form method="POST" action="<?= base_url('profile.php') ?>" style="padding:22px" novalidate>
                    <div style="margin-bottom:16px">
                        <label style="font-size:11.5px;font-weight:700;color:#334155;margin-bottom:6px;display:flex;align-items:center;gap:5px"><i class="bi bi-lock-fill" style="color:#2563eb;font-size:11px"></i>Password Saat Ini</label>
                        <input type="password" name="old_password" required minlength="1" class="form-control" style="border-radius:11px;border:1.5px solid #cbd5e1;padding:10.5px 13px;font-size:12.5px;font-weight:500;color:#0f172a;box-shadow:none !important;transition:all .2s ease;background:#f8fafc" placeholder="Masukkan password yang sedang Anda gunakan" onfocus="this.style.borderColor='#3B5FC7';this.style.background='#ffffff'" onblur="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="font-size:11.5px;font-weight:700;color:#334155;margin-bottom:6px;display:flex;align-items:center;gap:5px"><i class="bi bi-shield-check" style="color:#059669;font-size:11px"></i>Password Baru</label>
                        <input type="password" name="new_password" required minlength="6" class="form-control" style="border-radius:11px;border:1.5px solid #cbd5e1;padding:10.5px 13px;font-size:12.5px;font-weight:500;color:#0f172a;box-shadow:none !important;transition:all .2s ease;background:#f8fafc" placeholder="Minimal 6 karakter, disarankan kombinasi huruf & angka" onfocus="this.style.borderColor='#3B5FC7';this.style.background='#ffffff'" onblur="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'">
                        <div style="margin-top:6px;font-size:10px;color:#64748b;display:flex;align-items:center;gap:5px;font-weight:500"><i class="bi bi-info-circle" style="color:#3B5FC7"></i>Minimal 6 karakter — disimpan terenkripsi BCrypt di server.</div>
                    </div>
                    <div style="margin-bottom:22px">
                        <label style="font-size:11.5px;font-weight:700;color:#334155;margin-bottom:6px;display:flex;align-items:center;gap:5px"><i class="bi bi-repeat" style="color:#d97706;font-size:11px"></i>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required minlength="6" class="form-control" style="border-radius:11px;border:1.5px solid #cbd5e1;padding:10.5px 13px;font-size:12.5px;font-weight:500;color:#0f172a;box-shadow:none !important;transition:all .2s ease;background:#f8fafc" placeholder="Ketik ulang password baru Anda" onfocus="this.style.borderColor='#3B5FC7';this.style.background='#ffffff'" onblur="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'">
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;justify-content:flex-end;flex-wrap:wrap;padding-top:12px;border-top:1px dashed #e2e8f0">
                        <a href="<?= base_url('dashboard.php') ?>" class="btn btn-secondary btn-sm" style="border-radius:10px;font-size:11.5px;font-weight:700;padding:9px 16px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;box-shadow:none">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>
                        <button type="submit" name="ubah_password" value="1" class="btn btn-primary btn-sm" style="border-radius:10px;font-size:11.5px;font-weight:800;padding:9.5px 20px;background:linear-gradient(135deg,#3B5FC7,#2563eb);border:none;box-shadow:0 8px 20px -8px rgba(37,99,235,0.75);letter-spacing:0.2px">
                            <i class="bi bi-check2-circle me-1"></i>Simpan Perubahan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php' ?>
