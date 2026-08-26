<?php
// === CLi / Browser SAFE Reseed: TANPA session logic ===
header('Content-Type: text/plain; charset=utf-8');
$root = __DIR__;
require_once $root . '/db.php';

echo "=== LARAS RESEED DATA EXCEL USER (via db() wrapper LARAS) ===\n\n";

try {
    // 0) FK disable & TRUNCATE
    @db()->exec("SET FOREIGN_KEY_CHECKS=0");
    echo "[ok] SET FOREIGN_KEY_CHECKS=0\n";
    foreach (['reservasi_kendaraan','reservasi_ruangan'] as $t) {
        try { db()->exec("TRUNCATE TABLE $t"); echo "[ok] TRUNCATE $t\n"; } catch(Exception $e){ echo "[skip] $t: " . $e->getMessage()."\n"; }
    }
    foreach (['kendaraan','ruangan','driver'] as $t) {
        try { db()->exec("TRUNCATE TABLE $t"); echo "[ok] TRUNCATE $t\n"; } catch(Exception $e){ echo "[skip] $t: " . $e->getMessage()."\n"; }
    }
    @db()->exec("ALTER TABLE kendaraan AUTO_INCREMENT = 1");
    @db()->exec("ALTER TABLE ruangan AUTO_INCREMENT = 1");
    @db()->exec("ALTER TABLE driver AUTO_INCREMENT = 1");

    // 1) DRIVER 4 (pakai insert ignore dengan id)
    $dr = [
        ['id'=>1,'nama_driver'=>'Driver 1','no_wa'=>'081227889901','status'=>'tersedia'],
        ['id'=>2,'nama_driver'=>'Driver 2','no_wa'=>'081392114402','status'=>'tersedia'],
        ['id'=>3,'nama_driver'=>'Driver 3','no_wa'=>'081804556603','status'=>'bertugas'],
        ['id'=>4,'nama_driver'=>'Driver 4','no_wa'=>'085729334404','status'=>'tersedia'],
    ];
    foreach ($dr as $d){
        @db()->exec("INSERT IGNORE INTO driver(id,nama_driver,no_wa,status,created_at) VALUES (?,?,?,?,NOW())",
            [$d['id'],$d['nama_driver'],$d['no_wa'],$d['status']]);
    }
    echo "[ok] INSERT 4 DRIVER (id 1-4)\n";

    // 2) 7 KENDARAAN EXCEL
    $cars = [
        ['no_plat'=>'AB 1325 UB','merk'=>'Toyota','tipe'=>'Innova','tahun'=>2023,'kapasitas'=>7,'status'=>'tersedia','driver_id'=>1,'driver'=>'Driver 1','no_hp_driver'=>'081227889901','unit_pengguna'=>'Bagian Umum'],
        ['no_plat'=>'AB 1432 UB','merk'=>'Toyota','tipe'=>'Innova','tahun'=>2023,'kapasitas'=>7,'status'=>'tersedia','driver_id'=>2,'driver'=>'Driver 2','no_hp_driver'=>'081392114402','unit_pengguna'=>'Bagian Umum'],
        ['no_plat'=>'AB 1449 UB','merk'=>'Toyota','tipe'=>'Avanza','tahun'=>2023,'kapasitas'=>7,'status'=>'tersedia','driver_id'=>3,'driver'=>'Driver 3','no_hp_driver'=>'081804556603','unit_pengguna'=>'Bagian Umum'],
        ['no_plat'=>'AB 1769 UA','merk'=>'Toyota','tipe'=>'Kijang','tahun'=>2022,'kapasitas'=>7,'status'=>'tersedia','driver_id'=>4,'driver'=>'Driver 4','no_hp_driver'=>'085729334404','unit_pengguna'=>'Bagian Umum'],
        ['no_plat'=>'AB 1180 UB','merk'=>'Toyota','tipe'=>'Krista','tahun'=>2022,'kapasitas'=>7,'status'=>'tersedia','driver_id'=>1,'driver'=>'Driver 1','no_hp_driver'=>'081227889901','unit_pengguna'=>'Bidang Investigasi'],
        ['no_plat'=>'B 1247 TQO','merk'=>'Toyota','tipe'=>'Innova Reborn','tahun'=>2024,'kapasitas'=>7,'status'=>'tersedia','driver_id'=>2,'driver'=>'Driver 2','no_hp_driver'=>'081392114402','unit_pengguna'=>'Bidang APD'],
        ['no_plat'=>'B 1248 TQO','merk'=>'Toyota','tipe'=>'Innova Reborn','tahun'=>2024,'kapasitas'=>7,'status'=>'tersedia','driver_id'=>4,'driver'=>'Driver 4','no_hp_driver'=>'085729334404','unit_pengguna'=>'Bidang IPP'],
    ];
    foreach ($cars as $c){
        db()->insert('kendaraan',[
            'no_plat'=>$c['no_plat'],'merk'=>$c['merk'],'tipe'=>$c['tipe'],
            'tahun'=>$c['tahun'],'kapasitas'=>$c['kapasitas'],'status'=>$c['status'],
            'driver_id'=>$c['driver_id'],'driver'=>$c['driver'],'no_hp_driver'=>$c['no_hp_driver'],
            'unit_pengguna'=>$c['unit_pengguna'],
        ]);
    }
    echo "[ok] INSERT 7 KENDARAAN EXCEL\n";

    // 3) 11 RUANGAN EXCEL (tambah kode_ruangan NOT NULL required)
    $rugs = [
        ['kode_ruangan'=>'R001','nama_ruangan'=>'Aula Bawana','lantai'=>1,'kapasitas'=>200,'fasilitas'=>'Audio (Mic & Sound), Video (LCD & Proyektor)'],
        ['kode_ruangan'=>'R002','nama_ruangan'=>'R. Workshop','lantai'=>2,'kapasitas'=>25,'fasilitas'=>'LCD Proyektor, Whiteboard, Kabel Rol opsional'],
        ['kode_ruangan'=>'R003','nama_ruangan'=>'R. DWP','lantai'=>2,'kapasitas'=>15,'fasilitas'=>'Meja bundar 15 kursi + Proyektor mini'],
        ['kode_ruangan'=>'R004','nama_ruangan'=>'R. Smart Workshop','lantai'=>2,'kapasitas'=>15,'fasilitas'=>'Smart TV, Standing TV, Kabel Rol'],
        ['kode_ruangan'=>'R005','nama_ruangan'=>'R. Rapat Bagian Umum','lantai'=>2,'kapasitas'=>10,'fasilitas'=>'Meja panjang 10 kursi + Proyektor'],
        ['kode_ruangan'=>'R006','nama_ruangan'=>'R. Rapat Kepegawaian','lantai'=>2,'kapasitas'=>8,'fasilitas'=>'8 kursi + LCD Proyektor'],
        ['kode_ruangan'=>'R007','nama_ruangan'=>'R. Mitra','lantai'=>2,'kapasitas'=>8,'fasilitas'=>'Sofa tamu, Standing TV opsional'],
        ['kode_ruangan'=>'R008','nama_ruangan'=>'R. Perpus','lantai'=>3,'kapasitas'=>50,'fasilitas'=>'Rak buku, Sound system, 50 kursi'],
        ['kode_ruangan'=>'R009','nama_ruangan'=>'R. Kelas Barat','lantai'=>3,'kapasitas'=>30,'fasilitas'=>'30 meja kursi training + Proyektor'],
        ['kode_ruangan'=>'R010','nama_ruangan'=>'R. Kelas Timur','lantai'=>3,'kapasitas'=>30,'fasilitas'=>'30 meja kursi training + Proyektor'],
        ['kode_ruangan'=>'R011','nama_ruangan'=>'R. Fitnes','lantai'=>3,'kapasitas'=>15,'fasilitas'=>'Alat fitnes lengkap + Cermin besar'],
    ];
    foreach ($rugs as $r){
        db()->insert('ruangan',[
            'kode_ruangan'=>$r['kode_ruangan'],'nama_ruangan'=>$r['nama_ruangan'],
            'lantai'=>$r['lantai'],'kapasitas'=>$r['kapasitas'],
            'fasilitas'=>$r['fasilitas'],'status'=>'tersedia',
        ]);
    }
    echo "[ok] INSERT 11 RUANGAN (L1 1, L2 6, L3 4) sesuai Excel\n";

    @db()->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "[ok] SET FOREIGN_KEY_CHECKS=1\n\n";

    // VERIFIKASI
    echo "--- VERIFIKASI COUNT ---";
    $ck = (int)db()->fetchOne("SELECT COUNT(*) c FROM kendaraan")['c'];
    $cr = (int)db()->fetchOne("SELECT COUNT(*) c FROM ruangan")['c'];
    $cd = (int)db()->fetchOne("SELECT COUNT(*) c FROM driver")['c'];
    $pass = ($ck==7 && $cr==11 && $cd==4);
    echo "\n  Kendaraan: $ck / 7  " . ($ck==7?"✅":"⚠️");
    echo "\n  Ruangan  : $cr / 11 " . ($cr==11?"✅":"⚠️");
    echo "\n  Driver   : $cd / 4  " . ($cd==4?"✅":"⚠️");
    echo "\n\n=== " . ($pass?"🎉 SELESAI. SEMUA DATA SESUAI EXCEL USER":"⚠️ MASIH ADA YANG KURANG") . " ===\n";

    if ($pass) {
        echo "\nLIST 7 KENDARAAN:\n";
        $all = db()->fetchAll("SELECT no_plat, merk, tipe, kapasitas, driver, unit_pengguna FROM kendaraan ORDER BY id");
        foreach ($all as $k) echo "  # {$k['no_plat']} {$k['merk']} {$k['tipe']} ({$k['kapasitas']} org) - {$k['driver']} ({$k['unit_pengguna']})\n";
    }
} catch (Exception $e) {
    echo "\n[FATAL] " . $e->getMessage() . "\n" . $e->getTraceAsString();
    exit(1);
}
