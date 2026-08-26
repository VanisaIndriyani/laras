<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/db.php';

$default_password = password_hash('password', PASSWORD_BCRYPT);

$pegawai = [
    ['19850210 200901 1 002', 'Luthfi Jauhari', 'pegawai'],
    ['19861118 200901 1 001', 'Mulyadi', 'admin'],
    ['19870125 200911 2 001', 'Rumbati Argo', 'pegawai'],
    ['19870305 200911 1 001', 'Arsa Nur Azhari Winarso', 'pegawai'],
    ['19870412 200911 2 001', 'Dyah Qonitasari Estyamilla', 'pegawai'],
    ['19870521 200911 2 001', 'Hani Zulniati', 'pegawai'],
    ['19860128 201012 2 001', 'Danie Yanuar', 'pegawai'],
    ['19880820 201012 2 001', 'Puspita Dewi Putri', 'pegawai'],
    ['19890426 201012 1 001', 'Syah Mahardika', 'pegawai'],
    ['19870816 201012 1 001', 'Agus Budi Laksono', 'pegawai'],
    ['19881017 201210 2 001', 'Riski Lukfiarini', 'pegawai'],
    ['19881125 201210 1 001', 'Mandala Ulul Amri', 'pegawai'],
    ['19890514 201210 1 001', 'Cahyo Dwi Sabdono', 'pegawai'],
    ['19900102 201210 2 001', 'Asri Primandari', 'pegawai'],
    ['19900204 201210 1 001', 'Fandy Prakasa Wardhana', 'pegawai'],
    ['19900321 201210 1 001', 'Abu Achmad', 'pegawai'],
    ['19900422 201210 1 001', 'Aditya Tri Rahmadi Putra', 'pegawai'],
    ['19900906 201210 1 002', 'Sani Nurbani', 'pegawai'],
    ['19900621 201210 1 001', 'Enggar Nastanto', 'pegawai'],
    ['19900706 201210 1 001', 'Juli Sarwanto', 'pegawai'],
    ['19841121 201212 2 001', 'Anita Setianingtyas', 'pegawai'],
    ['19890728 201212 2 002', 'Mareisca Yulistina Pratama', 'pegawai'],
    ['19880102 201212 2 001', 'Asri Suwarsih', 'pegawai'],
    ['19890607 201212 1 002', 'Usman Maulana', 'pegawai'],
    ['19870821 201212 1 001', 'Fadlian Lazuardi Mulyono', 'pegawai'],
    ['19880313 201212 2 002', 'Hanifiar Bima Retnanti', 'pegawai'],
    ['19871106 201212 2 002', 'Lestariningsih', 'pegawai'],
    ['19901025 201212 2 002', 'Oki Paramita', 'pegawai'],
    ['19860104 201402 1 002', 'Wakhid Sulistio Adi', 'pegawai'],
    ['19860302 201402 2 001', 'Indria Putriasari', 'pegawai'],
    ['19860404 201402 2 004', 'Ngatini', 'pegawai'],
    ['19870216 201402 2 003', 'Devita Febriani', 'pegawai'],
    ['19870815 201402 2 003', 'Lenni Agustina', 'pegawai'],
    ['19870920 201402 2 002', 'Rini Risnawati', 'pegawai'],
    ['19870922 201402 2 004', 'Anggra Dewi Sekarningrum', 'pegawai'],
    ['19871201 201402 2 004', 'Monika Jayatri', 'pegawai'],
    ['19871227 201402 1 001', 'Ananta Singgih Cahya Prasetya', 'pegawai'],
    ['19880205 201402 2 003', 'Nur Hanifah Hayyuningtyas', 'pegawai'],
    ['19880323 201402 2 003', 'Tri Ana Fauziah', 'pegawai'],
    ['19880610 201402 2 004', 'Cholifatul Husna', 'pegawai'],
    ['19880620 201402 2 001', 'Yunita Evi Kurniasari', 'pegawai'],
    ['19881115 201402 2 005', 'Nur Fita Sari', 'pegawai'],
    ['19881214 201402 2 003', 'Desi Susanti', 'pegawai'],
    ['19890219 201402 1 002', 'Widyawan Nugroho', 'pegawai'],
    ['19890424 201402 2 009', 'Zulita Dyah Shintaningrum', 'pegawai'],
    ['19890609 201402 1 004', 'Mega Yoga Prastika', 'pegawai'],
    ['19890714 201402 2 004', 'Siti Muslikhah Kusuma Nurakhmadyati', 'pegawai'],
    ['19891121 201402 2 005', 'Arum Ditha Safitri', 'pegawai'],
    ['19900518 201402 2 008', 'Rizka Choirunnisa', 'pegawai'],
    ['19900531 201402 1 002', 'Doni Kurniawan Subardo', 'pegawai'],
    ['19900914 201402 2 003', 'Dewi Asih Kurnia', 'pegawai'],
    ['19900918 201402 2 009', 'Winda Dyah Kinasih', 'pegawai'],
    ['19901028 201402 2 004', 'Asri Oktaviani Puitri', 'pegawai'],
    ['19901107 201402 2 008', 'Irene Linda Widiastuti', 'pegawai'],
    ['19910528 201402 2 002', 'Sari Wahyuni', 'pegawai'],
    ['19910817 201402 2 002', 'Puji Purnaweni', 'pegawai'],
    ['19910827 201402 2 002', 'Raisha Pratidina', 'pegawai'],
    ['19911029 201402 2 004', 'Fajar Cahyaning Sadarum', 'pegawai'],
    ['19911223 201402 2 003', 'Noor Hanifah', 'pegawai'],
    ['19930617 201402 2 001', 'Amaliyyah Raadhiyyata Mardhiyyah', 'pegawai'],
    ['19871128 201402 1 003', 'Giri Firmansyah', 'pegawai'],
    ['19870607 201402 2 004', 'Paramithasari R', 'pegawai'],
    ['19910827 201402 2 003', 'Kurnia Yuspita', 'pegawai'],
    ['19880229 201502 2 002', 'Galih Hapsari Kirana', 'pegawai'],
    ['19880828 201502 2 001', 'Rizki Rusdhiani', 'pegawai'],
    ['19900325 201502 2 002', 'Rikha Aditya Wardhani', 'pegawai'],
    ['19900630 201502 2 002', 'Azizah Endrastaty', 'pegawai'],
    ['19871118 201801 2 001', 'Dewi Kurniasari', 'pegawai'],
    ['19911110 201801 2 002', 'Riana Widiastuti', 'pegawai'],
    ['19931205 201801 2 002', 'Dini Susanti', 'pegawai'],
    ['19930910 201801 2 001', 'Vuji Suprihatin', 'pegawai'],
    ['19940914 201801 1 002', 'Halim Prawiranata', 'pegawai'],
    ['19951127 201801 1 001', 'Ismu Adi Pranawa', 'pegawai'],
    ['19960305 201801 2 001', 'Siti Roh Chayatun', 'pegawai'],
    ['19970322 201812 1 001', 'Akhmad Pandu Kurnia', 'pegawai'],
    ['19951106 201812 1 001', 'Erik Darmawan', 'pegawai'],
    ['19950613 201902 2 008', 'Arin Ambar Setiarani', 'pegawai'],
    ['19960520 201902 2 002', 'Chintria Tira Nadia', 'pegawai'],
    ['19870717 201902 1 002', 'Dicky Ervyanto', 'pegawai'],
    ['19940925 201902 2 003', 'Dinar Safir Fatikha', 'pegawai'],
    ['19961108 201902 2 003', 'Naura Nadhifa', 'pegawai'],
    ['19931017 201902 2 006', 'Nolita Ayu Puspitasari', 'pegawai'],
    ['19950525 201902 2 010', 'Ratni Dewi', 'pegawai'],
    ['19940803 201902 2 003', 'Rosa Rizki Agustina', 'pegawai'],
    ['19931116 201902 2 001', 'Unik Novia Dara', 'pegawai'],
    ['19960126 201902 1 002', 'Wahyu Nurwanto', 'pegawai'],
    ['19941027 201902 2 006', 'Yenni K Nainggolan', 'pegawai'],
    ['19821224 202521 1 027', 'Agus Cahyadi', 'pegawai'],
    ['19760928 202521 1 012', 'Amin Idrus', 'pegawai'],
    ['19800819 202521 1 021', 'Heri Susanto', 'pegawai'],
    ['19820318 202521 1 027', 'Rochmad Susanto', 'pegawai'],
    ['19851109 202521 1 013', 'Pargiyono', 'pegawai'],
    ['19850118 202521 1 024', 'Sarwo Edi', 'pegawai'],
    ['19830904 202521 1 022', 'Andrianto', 'pegawai'],
    ['19850516 202521 1 037', 'Margiyanto', 'pegawai'],
    ['19840124 202521 1 022', 'Marwadi', 'pegawai'],
    ['19740825 202521 1 014', 'Agus Suranto', 'pegawai'],
    ['19780603 202521 1 024', 'Kriswanto', 'pegawai'],
    ['19840331 202521 1 024', 'Maryanto', 'pegawai'],
    ['19781020 202521 1 022', 'Anang Hartaya', 'pegawai'],
    ['19720410 202521 1 023', 'Parman', 'pegawai'],
    ['19920707 202521 1 057', 'Fajar Pramono', 'pegawai'],
    ['19720215 202521 1 022', 'Sudaryanta', 'pegawai'],
    ['19711128 202521 2 002', 'Ngatini', 'pegawai'],
    ['19730102 202521 1 013', 'Paryanto', 'pegawai'],
    ['19920605 202521 1 021', 'Yundaris Filiyanto', 'pegawai'],
    ['19950912 202012 1 008', 'Faisal Ansari', 'pegawai'],
    ['19790723 202521 1 020', 'Risdiyanto', 'pegawai'],
    ['19841106 202521 1 019', 'Endi Jayus', 'pegawai'],
    ['19990326 202202 2 001', 'Upik Krismareta Nuratifa', 'pegawai'],
    ['19870310 202521 1 048', 'Riska Heru Wibowo', 'pegawai'],
    ['20011202 202521 1 004', 'Hudalil Mustofa', 'pegawai'],
    ['20010504 202521 1 005', 'Wisnu Saputra', 'pegawai'],
    ['19890612 202521 2 067', 'Yunika Permata Sari', 'pegawai'],
    ['19971216 202421 2 024', 'Aninda Purba Cahyani', 'pegawai'],
    ['19741110 202421 2 005', 'Noor Latifah Dachlan', 'pegawai'],
    ['19900305 202421 2 034', 'Raden Rara Kun Alfiah Nur Aerodynamicawati', 'pegawai'],
    ['19670225 198703 2 001', 'Siti Solekah', 'pegawai'],
    ['19661126 198703 1 001', 'Susetyo Gigih Trilaksono', 'pegawai'],
    ['19670705 198803 1 001', 'Edi Prasetyo', 'pegawai'],
    ['19681107 198903 1 001', 'Komaruz Zaman', 'pegawai'],
    ['19681024 198903 1 001', 'Hary Eka Surjanta', 'pegawai'],
    ['19681224 198903 1 001', 'Cukamnoto Hariyadi', 'pegawai'],
    ['19680208 198903 1 001', 'Achmad Fachri', 'pegawai'],
    ['19680719 198903 1 001', 'Bambang Yuliyanto', 'pegawai'],
    ['19690510 199003 1 001', 'Azis Hanafi', 'pegawai'],
    ['19680107 199103 2 001', 'Dessy Adin', 'pegawai'],
    ['19701227 199103 1 001', 'Purnomo Aji', 'pegawai'],
    ['19710313 199103 1 001', 'Eko Herman Budi Rahardjo', 'pegawai'],
    ['19690301 199103 1 001', 'Bagus Widodo', 'pegawai'],
    ['19720310 199202 2 001', 'Caecilia Hermawati', 'pegawai'],
    ['19710511 199202 1 001', 'Suyatno', 'pegawai'],
    ['19710203 199202 1 001', 'Franciscus Xaverius Sarwoko', 'pegawai'],
    ['19721104 199203 2 001', 'Puji Estriningsih', 'pegawai'],
    ['19720615 199303 1 001', 'Jun Suwarno', 'pegawai'],
    ['19690820 199303 1 001', 'Ali Ihsan', 'pegawai'],
    ['19670725 199303 1 001', 'Syahrizal Ali', 'pegawai'],
    ['19690207 199303 2 001', 'Ni Made Duisthiti', 'pegawai'],
    ['19710703 199303 1 001', 'Rudy Tri Yulianto Widodo', 'pegawai'],
    ['19740607 199402 2 001', 'Purwaningsih Handayani', 'pegawai'],
    ['19730424 199402 2 001', 'Wiji Astuti', 'pegawai'],
    ['19740906 199502 1 001', 'Fahmi Atvidyan', 'pegawai'],
    ['19750621 199502 1 002', 'Puji Yuwono', 'pegawai'],
    ['19760724 199601 1 001', 'Sulistyo Himawan', 'pegawai'],
    ['19760727 199601 1 002', 'Agung Ragil Pujono', 'pegawai'],
    ['19740930 199603 2 001', 'Asri Damayanti', 'pegawai'],
    ['19710425 199603 1 001', 'Much. Bouxit Wibowo', 'pegawai'],
    ['19730113 199703 2 001', 'Niken Kusuma Wardhani', 'pegawai'],
    ['19740907 199703 2 001', 'Rosita Ariani', 'pegawai'],
    ['19701224 199703 2 001', 'Hartati', 'pegawai'],
    ['19700817 199703 2 001', 'Maria Sinaga', 'pegawai'],
    ['19690609 199803 1 001', 'Junaidi', 'pegawai'],
    ['19720903 199803 2 001', 'Nuraini Saptanti Dewi', 'pegawai'],
    ['19730916 199803 2 001', 'Siti Akrojah', 'admin'],
    ['19740509 199803 2 007', 'Rahayu Muji Lestari', 'pegawai'],
    ['19750608 199803 2 001', 'Asih Winarti', 'pegawai'],
    ['19750726 199803 1 001', 'Deddy Yuliawan Suwondo', 'pegawai'],
    ['19740505 199803 1 001', 'Paryono', 'pegawai'],
    ['19750929 199803 2 001', 'Evi Anggraini Soeryanti', 'pegawai'],
    ['19760625 199803 1 001', 'Muhammad Yasril Friandi', 'pegawai'],
    ['19700611 199803 1 007', 'Bima Gautama', 'pegawai'],
    ['19730405 199803 2 001', 'Afriani Nurfajriyah', 'pegawai'],
    ['19770209 199803 1 001', 'Nurhadi', 'pegawai'],
    ['19681117 199803 1 001', 'Novianto', 'pegawai'],
    ['19760515 199811 1 001', 'Iwanto', 'pegawai'],
    ['19761203 199811 2 001', 'Ana Suprihatiningsih', 'pegawai'],
    ['19761026 199811 1 001', 'Akhda Himmawan', 'pegawai'],
    ['19761124 199811 2 001', 'Ninik Triani', 'pegawai'],
    ['19770908 199811 2 001', 'Dyah Retno Palupi', 'pegawai'],
    ['19770219 199811 2 001', 'Tri Anawati', 'pegawai'],
    ['19760623 199811 2 001', 'Lea Triana', 'pegawai'],
    ['19771020 199903 2 001', 'Titi Sari', 'pegawai'],
    ['19720919 199903 2 011', 'Rosalia Kustyaningsih', 'pegawai'],
    ['19801129 200312 2 001', 'Maftukhah Nur Wijayanti', 'pegawai'],
    ['19840209 200701 1 002', 'Sulistyo Tri Cahyono', 'pegawai'],
    ['19851104 200701 1 003', 'Dedi Fafanto', 'pegawai'],
    ['19860706 200801 1 001', 'Rizky Shampitha Surya Wibowo', 'pegawai'],
];

$success = 0;
$skipped = 0;
$errors = [];
$admin_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['go'])) {
    foreach ($pegawai as $p) {
        list($nip, $nama, $role) = $p;
        try {
            $exists = db()->fetchOne("SELECT id FROM users WHERE nip = ?", [$nip]);
            if ($exists) {
                db()->exec("UPDATE users SET nama_lengkap = ?, role = ? WHERE nip = ?", [$nama, $role, $nip]);
                $skipped++;
            } else {
                db()->exec("INSERT INTO users (nip, nama_lengkap, password, unit_kerja, no_hp, role, created_at) VALUES (?, ?, ?, NULL, NULL, ?, NOW())",
                    [$nip, $nama, $default_password, $role]);
                $success++;
            }
            if ($role === 'admin') $admin_count++;
        } catch (Exception $e) {
            $errors[] = "NIP $nip $nama: " . $e->getMessage();
        }
    }
}

$total_users = (int)db()->fetchOne("SELECT COUNT(*) c FROM users")['c'];
$total_admin = (int)db()->fetchOne("SELECT COUNT(*) c FROM users WHERE role = 'admin'")['c'];
$total_pegawai = (int)db()->fetchOne("SELECT COUNT(*) c FROM users WHERE role = 'pegawai'")['c'];
?><!doctype html>
<html><head><title>Seed Data Pegawai — LARAS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light py-5">
<div class="container" style="max-width:960px">
  <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <h3 class="mb-1" style="color:#0B1C48">👥 Seed Data Pegawai (177 Orang)</h3>
    <p class="text-muted small mb-4">Script ini akan memasukkan data <b>177 pegawai</b> ke tabel <code>users</code>. Password default: <code>password</code>. <b>Mulyadi</b> dan <b>Siti Akrojah</b> diset sebagai <span class="badge bg-danger">Admin</span>.</p>

    <div class="mb-3 p-3 rounded-3 bg-light border">
      <div class="row text-center small g-2">
        <div class="col-3"><div class="fw-bold text-muted">Total Data Pegawai</div><div class="fs-4 fw-bold" style="color:#0B1C48"><?= count($pegawai) ?></div></div>
        <div class="col-3"><div class="fw-bold text-muted">Total Users di DB</div><div class="fs-4 fw-bold" style="color:#3B5FC7"><?= $total_users ?></div></div>
        <div class="col-3"><div class="fw-bold text-muted">Role Admin</div><div class="fs-4 fw-bold text-danger"><?= $total_admin ?></div></div>
        <div class="col-3"><div class="fw-bold text-muted">Role Pegawai</div><div class="fs-4 fw-bold text-success"><?= $total_pegawai ?></div></div>
      </div>
    </div>

    <form method="post" class="mb-4">
      <input type="hidden" name="go" value="1">
      <button class="btn btn-primary btn-lg rounded-pill px-4 shadow" style="background:#3B5FC7;border:0" type="submit">
        <i class="bi bi-database-add"></i> Insert / Update Data Pegawai Sekarang →
      </button>
      <a class="btn btn-outline-secondary btn-lg rounded-pill px-4 ms-2" href="dashboard.php">← Kembali ke Dashboard</a>
    </form>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['go'])): ?>
    <div class="alert <?= empty($errors)?'alert-success':'alert-warning' ?> rounded-3 border-0 small">
      <div class="mb-2 fw-bold">✅ SELESAI — Hasil eksekusi:</div>
      <div class="row g-1 mb-2">
        <div class="col-auto"><span class="badge rounded-pill text-bg-success p-2">Baru Insert: <b><?= $success ?></b></span></div>
        <div class="col-auto"><span class="badge rounded-pill text-bg-info p-2">Update: <b><?= $skipped ?></b></span></div>
        <div class="col-auto"><span class="badge rounded-pill text-bg-danger p-2">Jadi Admin: <b><?= $admin_count ?></b></span></div>
        <div class="col-auto"><span class="badge rounded-pill text-bg-<?= empty($errors)?'secondary':'warning' ?> p-2">Error: <b><?= count($errors) ?></b></span></div>
      </div>
      <?php if (!empty($errors)): ?>
        <div class="mt-2 text-danger small"><b>Error Details:</b><br><?= implode('<br>', $errors) ?></div>
      <?php endif; ?>
    </div>
<?php endif; ?>

    <hr>
    <div class="small">
      <div class="fw-bold mb-2">📋 Daftar Admin yang diset:</div>
      <ol class="mb-3">
        <li><b>Mulyadi</b> — NIP 19861118 200901 1 001</li>
        <li><b>Siti Akrojah</b> — NIP 19730916 199803 2 001</li>
      </ol>
      <div class="text-muted">Catatan: Data yang NIP-nya sudah ada akan di-UPDATE (nama & role), tidak dobel.</div>
    </div>

  </div></div>
  <div class="text-center text-muted small mt-4">Script LARAS — Seed Pegawai 177 • <?= date('d-m-Y H:i') ?></div>
</div>
</body></html>
