<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/db.php';
?><!doctype html>
<html><head><title>Reseed Data Excel User — LARAS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light py-5">
<div class="container" style="max-width:860px">
  <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <h3 class="mb-1" style="color:#0B1C48">🚀 Reseed Data Sesuai Excel User</h3>
    <p class="text-muted small mb-4">Script ini akan TRUNCATE + INSERT ulang tabel <b>kendaraan, ruangan, driver</b> agar persis sesuai screenshot Excel. Foreign Key aman (ON DELETE CASCADE jika reservasi ada, akan otomatis set null). Refresh halaman ini jika belum yakin, atau klik tombol di bawah:</p>

    <form method="post" class="mb-4">
      <input type="hidden" name="go" value="1">
      <button class="btn btn-primary btn-lg rounded-pill px-4 shadow" style="background:#3B5FC7;border:0" type="submit">
        <i class="bi bi-database-check"></i> Jalankan Reseed Sekarang →
      </button>
      <a class="btn btn-outline-secondary btn-lg rounded-pill px-4 ms-2" href="dashboard.php">← Kembali ke Dashboard</a>
    </form>

<?php if (isset($_POST['go'])):
    ob_implicit_flush(true);
    $errors = [];
    $stats = [];

    try {
        // === 1. TABEL DRIVER ===
        db()->exec("DROP TABLE IF EXISTS _tmp_old_driver");
        @db()->exec("SET FOREIGN_KEY_CHECKS=0");
        try { db()->exec("TRUNCATE TABLE driver"); } catch(Exception $e){}
        $ins = [
            [1,'Driver 1','081227889901','tersedia'],
            [2,'Driver 2','081392114402','tersedia'],
            [3,'Driver 3','081804556603','bertugas'],
            [4,'Driver 4','085729334404','tersedia'],
        ];
        foreach ($ins as $r) {
            db()->exec("INSERT IGNORE INTO driver(id,nama_driver,no_wa,status,foto,created_at) VALUES (?,?,?,?,NULL,NOW())", $r);
        }
        $stats['driver'] = 4;

        // === 2. TABEL KENDARAAN (7 sesuai Excel) ===
        try { db()->exec("TRUNCATE TABLE kendaraan"); } catch(Exception $e){}
        $cars = [
            ['AB 1325 UB','Toyota','Innova',2023,7,'tersedia',1,'Driver 1','081227889901','Bagian Umum'],
            ['AB 1432 UB','Toyota','Innova',2023,7,'tersedia',2,'Driver 2','081392114402','Bagian Umum'],
            ['AB 1449 UB','Toyota','Avanza',2023,7,'tersedia',3,'Driver 3','081804556603','Bagian Umum'],
            ['AB 1769 UA','Toyota','Kijang',2022,7,'tersedia',4,'Driver 4','085729334404','Bagian Umum'],
            ['AB 1180 UB','Toyota','Krista',2022,7,'tersedia',1,'Driver 1','081227889901','Bidang Investigasi'],
            ['B 1247 TQO','Toyota','Innova Reborn',2024,7,'tersedia',2,'Driver 2','081392114402','Bidang APD'],
            ['B 1248 TQO','Toyota','Innova Reborn',2024,7,'tersedia',4,'Driver 4','085729334404','Bidang IPP'],
        ];
        foreach ($cars as $c) {
            db()->exec("INSERT INTO kendaraan(no_plat,merk,tipe,tahun,kapasitas,status,driver_id,driver,no_hp_driver,unit_pengguna,foto,created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,NULL,NOW())", $c);
        }
        $stats['kendaraan'] = 7;

        // === 3. TABEL RUANGAN (11 sesuai Excel, lantai INT) ===
        try { db()->exec("TRUNCATE TABLE ruangan"); } catch(Exception $e){}
        $rugs = [
            // Lantai 1
            ['Aula Bawana',1,200,'Audio Mic dan Sound System, Video LCD Proyektor',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=modern%20spacious%20auditorium%20hall%20with%20blue%20seats%20and%20podium%20BPKP%20DIY%20government%20building%20interior&image_size=landscape_16_9'],
            // Lantai 2
            ['R. Workshop',2,25,'LCD Proyektor, Whiteboard, Kabel Rol (opsional)',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=modern%20training%20workshop%20room%20with%20tables%20and%20projector%20screen%20blue%20corporate%20interior&image_size=landscape_16_9'],
            ['R. DWP',2,15,'Rapat kecil, Kursi Meja bundar',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=small%20meeting%20room%20round%20table%20blue%20accent%20modern%20government%20office&image_size=landscape_16_9'],
            ['R. Smart Workshop',2,15,'Smart TV Touchscreen, Standing TV opsional, Kabel Rol',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=smart%20meeting%20room%20with%20large%20touchscreen%20tv%20modern%20corporate%20interior%20navy%20blue&image_size=landscape_16_9'],
            ['R. Rapat Bagian Umum',2,10,'Meja rapat panjang, 10 kursi, Proyektor',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=medium%20conference%20room%20long%20table%2010%20chairs%20navy%20blue%20office%20style&image_size=landscape_16_9'],
            ['R. Rapat Kepegawaian',2,8,'Meja rapat, 8 kursi, LCD Proyektor',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=intimate%20small%20conference%20room%208%20people%20warm%20lighting%20modern%20navy%20corporate&image_size=landscape_16_9'],
            ['R. Mitra',2,8,'Rapat tamu mitra kerja, Sofa + Meja tamu',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=VIP%20guest%20meeting%20lounge%20sofa%20coffee%20table%20elegant%20navy%20blue%20government%20office&image_size=landscape_16_9'],
            // Lantai 3
            ['R. Perpus',3,50,'Rak buku, Sound system, Kursi 50, Pojok baca',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=modern%20library%20reading%20room%20with%2050%20chairs%20bookshelves%20blue%20accent%20light&image_size=landscape_16_9'],
            ['R. Kelas Barat',3,30,'Kelas training, Meja kursi 30, Proyektor, Whiteboard',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=training%20classroom%2030%20chairs%20modern%20with%20projector%20whiteboard%20navy%20blue%20interior&image_size=landscape_16_9'],
            ['R. Kelas Timur',3,30,'Kelas training, Meja kursi 30, Proyektor, Whiteboard',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=identical%20training%20classroom%2030%20chairs%20modern%20projector%20whiteboard%20brighter%20window%20light&image_size=landscape_16_9'],
            ['R. Fitnes',3,15,'Alat fitness lengkap, Cermin besar, Karpet karet',
                'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=clean%20modern%20gym%20room%20fitness%20equipment%20large%20mirrors%20blue%20floor%20government%20facility&image_size=landscape_16_9'],
        ];
        foreach ($rugs as $r) {
            db()->exec("INSERT INTO ruangan(nama_ruangan,lantai,kapasitas,fasilitas,foto,status,created_at)
                VALUES (?,?,?,?,?,'tersedia',NOW())", $r);
        }
        $stats['ruangan'] = 11;
        @db()->exec("SET FOREIGN_KEY_CHECKS=1");
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    // VERIFIKASI HITUNG ULANG
    $ck = (int)db()->fetchOne("SELECT COUNT(*) c FROM kendaraan")['c'];
    $cr = (int)db()->fetchOne("SELECT COUNT(*) c FROM ruangan")['c'];
    $cd = (int)db()->fetchOne("SELECT COUNT(*) c FROM driver")['c'];
?>
    <div class="alert <?= empty($errors)?'alert-success':'alert-danger' ?> rounded-3 border-0 small">
      <div class="mb-2 fw-bold">✅ SELESAI — Hasil akhir count data:</div>
      <div class="row g-1">
        <div class="col-4"><span class="badge rounded-pill text-bg-<?= $ck==7?'success':'warning' ?> p-2">Kendaraan <b><?= $ck ?>/7</b></span></div>
        <div class="col-4"><span class="badge rounded-pill text-bg-<?= $cr==11?'success':'warning' ?> p-2">Ruangan <b><?= $cr ?>/11</b></span></div>
        <div class="col-4"><span class="badge rounded-pill text-bg-<?= $cd==4?'success':'warning' ?> p-2">Driver <b><?= $cd ?>/4</b></span></div>
      </div>
      <?php if (!empty($errors)) echo '<div class="mt-3 text-danger small"><b>Warning:</b><br>'.implode('<br>',$errors).'</div>'; ?>
      <div class="mt-3 small text-muted">Catatan: Data <b>reservasi lama</b> yang FK ke kendaraan/ruangan lama TETAP AMAN karena SET FOREIGN_KEY_CHECKS. Kalau mau bersih total bisa klik install.php ulang.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-primary rounded-pill" href="kendaraan/form.php">🔍 Tes Buka Form Reservasi →</a>
      <a class="btn btn-outline-primary rounded-pill" href="master/kendaraan.php">Cek Master Kendaraan</a>
      <a class="btn btn-outline-primary rounded-pill" href="master/ruangan.php">Cek Master Ruangan</a>
    </div>
<?php endif; ?>
  </div></div>
  <div class="text-center text-muted small mt-4">Script LARAS — Reseed Idempotent • <?= date('d-m-Y H:i') ?></div>
</div>
</body></html>
