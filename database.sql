-- Database LARAS - Layanan Aplikasi Reservasi Aset & Sarana
CREATE DATABASE IF NOT EXISTS laras_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laras_db;

-- Tabel Pengguna (Admin & Pegawai)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(50) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    unit_kerja VARCHAR(100) DEFAULT NULL,
    no_hp VARCHAR(20) DEFAULT NULL,
    role ENUM('admin','pegawai') NOT NULL DEFAULT 'pegawai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Kendaraan
CREATE TABLE IF NOT EXISTS kendaraan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_plat VARCHAR(20) UNIQUE NOT NULL,
    merk VARCHAR(50) NOT NULL,
    tipe VARCHAR(50) NOT NULL,
    tahun INT DEFAULT NULL,
    kapasitas INT DEFAULT 4,
    driver_id INT DEFAULT NULL,
    kode_bmn VARCHAR(60) DEFAULT NULL,
    unit_pengguna VARCHAR(120) DEFAULT NULL,
    pajak_stnk_jatuh_tempo DATE DEFAULT NULL,
    pajak_tnkb_jatuh_tempo DATE DEFAULT NULL,
    terakhir_service DATE DEFAULT NULL,
    service_berikutnya DATE DEFAULT NULL,
    catatan_service TEXT DEFAULT NULL,
    status ENUM('tersedia','digunakan','perawatan') NOT NULL DEFAULT 'tersedia',
    driver VARCHAR(100) DEFAULT NULL,
    no_hp_driver VARCHAR(20) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Driver (Master Supir / Operator)
CREATE TABLE IF NOT EXISTS driver (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_driver VARCHAR(120) NOT NULL,
    no_wa VARCHAR(20) DEFAULT NULL,
    status ENUM('tersedia','bertugas','libur') NOT NULL DEFAULT 'tersedia',
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Ruangan
CREATE TABLE IF NOT EXISTS ruangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_ruangan VARCHAR(20) UNIQUE NOT NULL,
    nama_ruangan VARCHAR(100) NOT NULL,
    lantai VARCHAR(20) DEFAULT NULL,
    kapasitas INT NOT NULL DEFAULT 10,
    fasilitas TEXT DEFAULT NULL,
    status ENUM('tersedia','tidak_tersedia','perawatan') NOT NULL DEFAULT 'tersedia',
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Reservasi Kendaraan
CREATE TABLE IF NOT EXISTS reservasi_kendaraan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_reservasi VARCHAR(30) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    kendaraan_id INT NOT NULL,
    driver_id INT DEFAULT NULL,
    keperluan TEXT NOT NULL,
    tujuan VARCHAR(200) NOT NULL,
    estimasi_peserta INT DEFAULT NULL,
    fasilitas_tambahan VARCHAR(255) DEFAULT NULL,
    tanggal_pinjam DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    tanggal_kembali DATE NOT NULL,
    jam_selesai TIME NOT NULL,
    status ENUM('pending','disetujui','ditolak','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
    catatan_approval TEXT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES driver(id) ON DELETE SET NULL
);

-- Tabel Reservasi Ruangan
CREATE TABLE IF NOT EXISTS reservasi_ruangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_reservasi VARCHAR(30) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    ruangan_id INT NOT NULL,
    nama_acara VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    unit_kerja VARCHAR(100) NOT NULL,
    estimasi_peserta INT NOT NULL DEFAULT 0,
    tanggal_mulai DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    tanggal_selesai DATE NOT NULL,
    jam_selesai TIME NOT NULL,
    fasilitas_pendukung TEXT DEFAULT NULL,
    status ENUM('pending','disetujui','ditolak','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
    catatan_approval TEXT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ruangan_id) REFERENCES ruangan(id) ON DELETE CASCADE
);

-- Insert data default Admin
INSERT INTO users (nip, nama_lengkap, password, unit_kerja, role) VALUES
('1001', 'Mulyadi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bagian Umum', 'admin'),
('1002', 'Admin LARAS', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bagian Umum', 'admin');

-- Insert data default Pegawai
INSERT INTO users (nip, nama_lengkap, password, unit_kerja, no_hp, role) VALUES
('2001', 'Budi Santoso', '', 'Bidang IPP', '081234567890', 'pegawai'),
('2002', 'Siti Rahayu', '', 'Bidang APD', '082345678901', 'pegawai'),
('2003', 'Ahmad Fauzi', '', 'Bidang AN', '083456789012', 'pegawai'),
('2004', 'Dewi Lestari', '', 'Bidang Investigasi', '084567890123', 'pegawai'),
('2005', 'Rendi Pratama', '', 'Subbag Kepegawaian & Tata Usaha', '085678901234', 'pegawai');

-- Insert data default Driver (sesuai master data BPKP DIY)
INSERT INTO driver (nama_driver, no_wa, status) VALUES
('Driver 1', '081227889901', 'tersedia'),
('Driver 2', '081392114402', 'tersedia'),
('Driver 3', '081804556603', 'bertugas'),
('Driver 4', '085729334404', 'tersedia');

-- Insert data Kendaraan (SESUAI EXCEL USER) - 7 unit dengan assign driver_id 1-4
INSERT INTO kendaraan (no_plat, merk, tipe, tahun, kapasitas, status, driver_id, driver, no_hp_driver, unit_pengguna) VALUES
('AB 1325 UB', 'Toyota', 'Innova',          2023, 7, 'tersedia', 1, 'Driver 1', '081227889901', 'Bagian Umum'),
('AB 1432 UB', 'Toyota', 'Innova',          2023, 7, 'tersedia', 2, 'Driver 2', '081392114402', 'Bagian Umum'),
('AB 1449 UB', 'Toyota', 'Avanza',          2023, 7, 'tersedia', 3, 'Driver 3', '081804556603', 'Bagian Umum'),
('AB 1769 UA', 'Toyota', 'Kijang',          2022, 7, 'tersedia', 4, 'Driver 4', '085729334404', 'Bagian Umum'),
('AB 1180 UB', 'Toyota', 'Krista',          2022, 7, 'tersedia', 1, 'Driver 1', '081227889901', 'Bidang Investigasi'),
('B 1247 TQO', 'Toyota', 'Innova Reborn',   2024, 7, 'tersedia', 2, 'Driver 2', '081392114402', 'Bidang APD'),
('B 1248 TQO', 'Toyota', 'Innova Reborn',   2024, 7, 'tersedia', 4, 'Driver 4', '085729334404', 'Bidang IPP');

-- Insert data Ruangan (SESUAI EXCEL USER) - 11 ruangan Lantai 1, 2, 3
INSERT INTO ruangan (kode_ruangan, nama_ruangan, lantai, kapasitas, fasilitas, status) VALUES
('AULA-01',      'Aula Bawana',              'Lantai 1', 200, 'Audio (Mic dan sound system), Video (LCD dan Screen Proyektor)', 'tersedia'),
('RWS-01',       'R. Workshop',              'Lantai 2',  25, 'Proyektor, Whiteboard, AC Central, Kabel Rol opsional', 'tersedia'),
('RDWP-01',      'R. DWP',                   'Lantai 2',  15, 'Proyektor, Whiteboard, AC Central', 'tersedia'),
('RSW-01',       'R. Smart Workshop',        'Lantai 2',  15, 'Smart TV, Proyektor, AC Central, Standing TV opsional', 'tersedia'),
('RBU-01',       'R. Rapat Bagian Umum',     'Lantai 2',  10, 'Proyektor, Whiteboard, AC Central', 'tersedia'),
('RKEP-01',      'R. Rapat Kepegawaian',     'Lantai 2',   8, 'Proyektor, Whiteboard, AC Central', 'tersedia'),
('RM-01',        'R. Mitra',                 'Lantai 2',   8, 'Proyektor, Whiteboard, AC Central', 'tersedia'),
('RP-01',        'R. Perpus',                'Lantai 3',  50, 'Proyektor, Sound System, AC Central', 'tersedia'),
('RKB-01',       'R. Kelas Barat',           'Lantai 3',  30, 'Proyektor, Whiteboard, AC Central', 'tersedia'),
('RKT-01',       'R. Kelas Timur',           'Lantai 3',  30, 'Proyektor, Whiteboard, AC Central', 'tersedia'),
('RF-01',        'R. Fitnes',                'Lantai 3',  15, 'Alat Fitnes lengkap, AC Central, Kaca Spion', 'tersedia');
