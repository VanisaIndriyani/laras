-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 30, 2026 at 08:40 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laras_db`
--

CREATE DATABASE IF NOT EXISTS `laras_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `laras_db`;

DROP TABLE IF EXISTS `reservasi_kendaraan`;
DROP TABLE IF EXISTS `reservasi_ruangan`;
DROP TABLE IF EXISTS `kendaraan`;
DROP TABLE IF EXISTS `ruangan`;
DROP TABLE IF EXISTS `driver`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------

--
-- Table structure for table `driver`
--

CREATE TABLE `driver` (
  `id` int NOT NULL,
  `nama_driver` varchar(120) NOT NULL,
  `no_wa` varchar(30) DEFAULT NULL,
  `status` enum('tersedia','bertugas','izin','sakit','libur') DEFAULT 'tersedia',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver`
--

INSERT INTO `driver` (`id`, `nama_driver`, `no_wa`, `status`, `created_at`) VALUES
(1, 'Idrus', '08157948036', 'tersedia', '2026-08-29 21:28:04'),
(2, 'Heri Susanto', '08564345558', 'bertugas', '2026-08-29 21:28:04'),
(3, 'Novianto', '085327387772', 'tersedia', '2026-08-29 21:28:04'),
(4, 'Deddy Yuliawan Suwondo', '081225550551', 'tersedia', '2026-08-29 21:28:04'),
(5, 'Maryanto', '085878778827', 'tersedia', '2026-08-29 21:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` int NOT NULL,
  `no_plat` varchar(20) NOT NULL,
  `merk` varchar(50) NOT NULL,
  `tipe` varchar(50) NOT NULL,
  `tahun` int DEFAULT NULL,
  `kapasitas` int DEFAULT '4',
  `status` enum('tersedia','digunakan','perawatan') NOT NULL DEFAULT 'tersedia',
  `driver` varchar(100) DEFAULT NULL,
  `no_hp_driver` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `kode_bmn` varchar(60) DEFAULT NULL,
  `unit_pengguna` varchar(120) DEFAULT NULL,
  `pajak_stnk_jatuh_tempo` date DEFAULT NULL,
  `pajak_tnkb_jatuh_tempo` date DEFAULT NULL,
  `terakhir_service` date DEFAULT NULL,
  `service_berikutnya` date DEFAULT NULL,
  `catatan_service` text,
  `driver_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `no_plat`, `merk`, `tipe`, `tahun`, `kapasitas`, `status`, `driver`, `no_hp_driver`, `foto`, `created_at`, `kode_bmn`, `unit_pengguna`, `pajak_stnk_jatuh_tempo`, `pajak_tnkb_jatuh_tempo`, `terakhir_service`, `service_berikutnya`, `catatan_service`, `driver_id`) VALUES
(1, 'AB 1325 UB', 'Toyota', 'Innova', 2023, 7, 'digunakan', 'Driver 1', '081227889901', NULL, '2026-08-26 07:14:23', 'BMN-0101-2023-001', 'Bagian Umum', '2026-10-25', '2030-08-25', '2026-07-27', '2026-11-24', NULL, 1),
(2, 'AB 1432 UB', 'Toyota', 'Innova', 2023, 7, 'tersedia', 'Driver 2', '081392114402', NULL, '2026-08-26 07:14:23', 'BMN-0101-2023-002', 'Bagian Umum', '2026-09-15', '2029-08-25', '2026-07-12', '2026-10-10', NULL, 2),
(3, 'AB 1449 UB', 'Toyota', 'Avanza', 2023, 7, 'tersedia', 'Driver 3', '081804556603', NULL, '2026-08-26 07:14:23', 'BMN-0101-2023-003', 'Bagian Umum', '2026-08-31', '2028-08-25', '2026-08-19', '2026-09-16', NULL, 3),
(4, 'AB 1769 UA', 'Toyota', 'Kijang', 2022, 7, 'tersedia', 'Driver 4', '085729334404', NULL, '2026-08-26 07:14:23', 'BMN-0101-2023-004', 'Bagian Umum', '2026-08-23', '2027-08-26', '2026-04-28', '2026-10-25', NULL, 4),
(5, 'AB 1180 UB', 'Toyota', 'Krista', 2022, 7, 'tersedia', 'Driver 1', '081227889901', NULL, '2026-08-26 07:14:23', 'BMN-0202-2022-011', 'Bidang Investigasi', '2026-12-24', '2028-08-15', '2026-06-27', '2026-12-24', NULL, 1),
(6, 'B 1247 TQO', 'Toyota', 'Innova Reborn', 2024, 7, 'tersedia', 'Driver 2', '081392114402', NULL, '2026-08-26 07:14:23', 'BMN-0303-2024-201', 'Bidang APD', '2027-02-22', '2029-08-25', '2026-08-16', '2026-11-14', NULL, 2),
(7, 'B 1248 TQO', 'Toyota', 'Innova Reborn', 2024, 7, 'tersedia', 'Driver 4', '085729334404', NULL, '2026-08-26 07:14:23', 'BMN-0303-2024-202', 'Bidang IPP', '2026-08-11', '2028-02-17', '2026-02-07', '2026-09-10', NULL, 4);

-- --------------------------------------------------------

--
-- Table structure for table `reservasi_kendaraan`
--

CREATE TABLE `reservasi_kendaraan` (
  `id` int NOT NULL,
  `kode_reservasi` varchar(30) NOT NULL,
  `user_id` int NOT NULL,
  `kendaraan_id` int NOT NULL,
  `keperluan` text NOT NULL,
  `tujuan` varchar(200) NOT NULL,
  `estimasi_peserta` int DEFAULT NULL,
  `tanggal_pinjam` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `jam_selesai` time NOT NULL,
  `status` enum('pending','disetujui','ditolak','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
  `catatan_approval` text,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `driver_id` int DEFAULT NULL,
  `fasilitas_tambahan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservasi_ruangan`
--

CREATE TABLE `reservasi_ruangan` (
  `id` int NOT NULL,
  `kode_reservasi` varchar(30) NOT NULL,
  `user_id` int NOT NULL,
  `ruangan_id` int NOT NULL,
  `nama_acara` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `unit_kerja` varchar(100) NOT NULL,
  `estimasi_peserta` int NOT NULL DEFAULT '0',
  `tanggal_mulai` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jam_selesai` time NOT NULL,
  `fasilitas_pendukung` text,
  `status` enum('pending','disetujui','ditolak','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
  `catatan_approval` text,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `catatan_fasilitas` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `id` int NOT NULL,
  `kode_ruangan` varchar(20) NOT NULL,
  `nama_ruangan` varchar(100) NOT NULL,
  `lantai` varchar(20) DEFAULT NULL,
  `kapasitas` int NOT NULL DEFAULT '10',
  `fasilitas` text,
  `status` enum('tersedia','tidak_tersedia','perawatan') NOT NULL DEFAULT 'tersedia',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ruangan`
--

INSERT INTO `ruangan` (`id`, `kode_ruangan`, `nama_ruangan`, `lantai`, `kapasitas`, `fasilitas`, `status`, `foto`, `created_at`) VALUES
(1, 'R001', 'Aula Bawana', '1', 200, 'Audio (Mic & Sound), Video (LCD & Proyektor)', 'tersedia', NULL, '2026-08-26 07:14:23'),
(2, 'R002', 'Pangarsa', '2', 25, 'LCD Proyektor, Whiteboard, Kabel Rol opsional', 'tersedia', NULL, '2026-08-26 07:14:23'),
(3, 'R003', 'Candala', '2', 15, 'Meja bundar 15 kursi + Proyektor mini', 'tersedia', NULL, '2026-08-26 07:14:23'),
(4, 'R004', 'Cakra', '2', 15, 'Smart TV, Standing TV, Kabel Rol', 'tersedia', NULL, '2026-08-26 07:14:23'),
(5, 'R005', 'R. Rapat Bagian Umum', '2', 10, 'Meja panjang 10 kursi + Proyektor', 'tersedia', NULL, '2026-08-26 07:14:24'),
(6, 'R006', 'R. Rapat Kepegawaian', '2', 8, '8 kursi + LCD Proyektor', 'tersedia', NULL, '2026-08-26 07:14:24'),
(7, 'R007', 'Sasmita', '2', 8, 'Sofa tamu, Standing TV opsional', 'tersedia', NULL, '2026-08-26 07:14:24'),
(8, 'R008', 'Cedekia', '3', 50, 'Rak buku, Sound system, 50 kursi', 'tersedia', NULL, '2026-08-26 07:14:24'),
(9, 'R009', 'Tepa', '3', 30, '30 meja kursi training + Proyektor', 'tersedia', NULL, '2026-08-26 07:14:24'),
(10, 'R010', 'Slira', '3', 30, '30 meja kursi training + Proyektor', 'tersedia', NULL, '2026-08-26 07:14:24'),
(11, 'R011', 'R. Fitnes', '3', 15, 'Alat fitnes lengkap + Cermin besar', 'tersedia', NULL, '2026-08-26 07:14:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nip` varchar(50) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `unit_kerja` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `role` enum('admin','pegawai') NOT NULL DEFAULT 'pegawai',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nip`, `nama_lengkap`, `password`, `unit_kerja`, `no_hp`, `role`, `created_at`, `updated_at`) VALUES
(3, '2001', 'Budi Santoso', '', 'Bidang IPP', '081234567890', 'pegawai', '2026-08-22 23:59:57', '2026-08-22 23:59:57'),
(4, '2002', 'Siti Rahayu', '', 'Bidang APD', '082345678901', 'pegawai', '2026-08-22 23:59:57', '2026-08-22 23:59:57'),
(5, '2003', 'Ahmad Fauzi', '', 'Bidang AN', '083456789012', 'pegawai', '2026-08-22 23:59:57', '2026-08-22 23:59:57'),
(6, '2004', 'Dewi Lestari', '', 'Bidang Investigasi', '084567890123', 'pegawai', '2026-08-22 23:59:57', '2026-08-22 23:59:57'),
(8, '19850210 200901 1 002', 'Luthfi Jauhari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:08:59', '2026-08-26 00:08:59'),
(9, '19861118 200901 1 001', 'Mulyadi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'admin', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(10, '19870125 200911 2 001', 'Rumbati Argo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(11, '19870305 200911 1 001', 'Arsa Nur Azhari Winarso', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(12, '19870412 200911 2 001', 'Dyah Qonitasari Estyamilla', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(13, '19870521 200911 2 001', 'Hani Zulniati', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(14, '19860128 201012 2 001', 'Danie Yanuar', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(15, '19880820 201012 2 001', 'Puspita Dewi Putri', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(16, '19890426 201012 1 001', 'Syah Mahardika', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(17, '19870816 201012 1 001', 'Agus Budi Laksono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(18, '19881017 201210 2 001', 'Riski Lukfiarini', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(19, '19881125 201210 1 001', 'Mandala Ulul Amri', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(20, '19890514 201210 1 001', 'Cahyo Dwi Sabdono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(21, '19900102 201210 2 001', 'Asri Primandari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(22, '19900204 201210 1 001', 'Fandy Prakasa Wardhana', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(23, '19900321 201210 1 001', 'Abu Achmad', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(24, '19900422 201210 1 001', 'Aditya Tri Rahmadi Putra', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(25, '19900906 201210 1 002', 'Sani Nurbani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(26, '19900621 201210 1 001', 'Enggar Nastanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(27, '19900706 201210 1 001', 'Juli Sarwanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(28, '19841121 201212 2 001', 'Anita Setianingtyas', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(29, '19890728 201212 2 002', 'Mareisca Yulistina Pratama', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(30, '19880102 201212 2 001', 'Asri Suwarsih', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(31, '19890607 201212 1 002', 'Usman Maulana', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(32, '19870821 201212 1 001', 'Fadlian Lazuardi Mulyono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(33, '19880313 201212 2 002', 'Hanifiar Bima Retnanti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(34, '19871106 201212 2 002', 'Lestariningsih', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(35, '19901025 201212 2 002', 'Oki Paramita', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(36, '19860104 201402 1 002', 'Wakhid Sulistio Adi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(37, '19860302 201402 2 001', 'Indria Putriasari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(38, '19860404 201402 2 004', 'Ngatini', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(39, '19870216 201402 2 003', 'Devita Febriani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(40, '19870815 201402 2 003', 'Lenni Agustina', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(41, '19870920 201402 2 002', 'Rini Risnawati', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(42, '19870922 201402 2 004', 'Anggra Dewi Sekarningrum', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(43, '19871201 201402 2 004', 'Monika Jayatri', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(44, '19871227 201402 1 001', 'Ananta Singgih Cahya Prasetya', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(45, '19880205 201402 2 003', 'Nur Hanifah Hayyuningtyas', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(46, '19880323 201402 2 003', 'Tri Ana Fauziah', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(47, '19880610 201402 2 004', 'Cholifatul Husna', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(48, '19880620 201402 2 001', 'Yunita Evi Kurniasari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(49, '19881115 201402 2 005', 'Nur Fita Sari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(50, '19881214 201402 2 003', 'Desi Susanti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(51, '19890219 201402 1 002', 'Widyawan Nugroho', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(52, '19890424 201402 2 009', 'Zulita Dyah Shintaningrum', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(53, '19890609 201402 1 004', 'Mega Yoga Prastika', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(54, '19890714 201402 2 004', 'Siti Muslikhah Kusuma Nurakhmadyati', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(55, '19891121 201402 2 005', 'Arum Ditha Safitri', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(56, '19900518 201402 2 008', 'Rizka Choirunnisa', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(57, '19900531 201402 1 002', 'Doni Kurniawan Subardo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(58, '19900914 201402 2 003', 'Dewi Asih Kurnia', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(59, '19900918 201402 2 009', 'Winda Dyah Kinasih', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(60, '19901028 201402 2 004', 'Asri Oktaviani Puitri', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(61, '19901107 201402 2 008', 'Irene Linda Widiastuti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(62, '19910528 201402 2 002', 'Sari Wahyuni', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(63, '19910817 201402 2 002', 'Puji Purnaweni', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(64, '19910827 201402 2 002', 'Raisha Pratidina', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(65, '19911029 201402 2 004', 'Fajar Cahyaning Sadarum', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(66, '19911223 201402 2 003', 'Noor Hanifah', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(67, '19930617 201402 2 001', 'Amaliyyah Raadhiyyata Mardhiyyah', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(68, '19871128 201402 1 003', 'Giri Firmansyah', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(69, '19870607 201402 2 004', 'Paramithasari R', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(70, '19910827 201402 2 003', 'Kurnia Yuspita', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(71, '19880229 201502 2 002', 'Galih Hapsari Kirana', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(72, '19880828 201502 2 001', 'Rizki Rusdhiani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(73, '19900325 201502 2 002', 'Rikha Aditya Wardhani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(74, '19900630 201502 2 002', 'Azizah Endrastaty', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(75, '19871118 201801 2 001', 'Dewi Kurniasari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(76, '19911110 201801 2 002', 'Riana Widiastuti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(77, '19931205 201801 2 002', 'Dini Susanti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(78, '19930910 201801 2 001', 'Vuji Suprihatin', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(79, '19940914 201801 1 002', 'Halim Prawiranata', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(80, '19951127 201801 1 001', 'Ismu Adi Pranawa', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(81, '19960305 201801 2 001', 'Siti Roh Chayatun', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(82, '19970322 201812 1 001', 'Akhmad Pandu Kurnia', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(83, '19951106 201812 1 001', 'Erik Darmawan', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(84, '19950613 201902 2 008', 'Arin Ambar Setiarani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(85, '19960520 201902 2 002', 'Chintria Tira Nadia', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(86, '19870717 201902 1 002', 'Dicky Ervyanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(87, '19940925 201902 2 003', 'Dinar Safir Fatikha', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(88, '19961108 201902 2 003', 'Naura Nadhifa', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(89, '19931017 201902 2 006', 'Nolita Ayu Puspitasari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(90, '19950525 201902 2 010', 'Ratni Dewi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(91, '19940803 201902 2 003', 'Rosa Rizki Agustina', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(92, '19931116 201902 2 001', 'Unik Novia Dara', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(93, '19960126 201902 1 002', 'Wahyu Nurwanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(94, '19941027 201902 2 006', 'Yenni K Nainggolan', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(95, '19821224 202521 1 027', 'Agus Cahyadi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(96, '19760928 202521 1 012', 'Amin Idrus', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(97, '19800819 202521 1 021', 'Heri Susanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(98, '19820318 202521 1 027', 'Rochmad Susanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(99, '19851109 202521 1 013', 'Pargiyono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(100, '19850118 202521 1 024', 'Sarwo Edi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(101, '19830904 202521 1 022', 'Andrianto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(102, '19850516 202521 1 037', 'Margiyanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(103, '19840124 202521 1 022', 'Marwadi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(104, '19740825 202521 1 014', 'Agus Suranto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(105, '19780603 202521 1 024', 'Kriswanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(106, '19840331 202521 1 024', 'Maryanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(107, '19781020 202521 1 022', 'Anang Hartaya', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(108, '19720410 202521 1 023', 'Parman', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(109, '19920707 202521 1 057', 'Fajar Pramono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(110, '19720215 202521 1 022', 'Sudaryanta', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(111, '19711128 202521 2 002', 'Ngatini', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(112, '19730102 202521 1 013', 'Paryanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(113, '19920605 202521 1 021', 'Yundaris Filiyanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(114, '19950912 202012 1 008', 'Faisal Ansari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(115, '19790723 202521 1 020', 'Risdiyanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(116, '19841106 202521 1 019', 'Endi Jayus', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(117, '19990326 202202 2 001', 'Upik Krismareta Nuratifa', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(118, '19870310 202521 1 048', 'Riska Heru Wibowo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(119, '20011202 202521 1 004', 'Hudalil Mustofa', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(120, '20010504 202521 1 005', 'Wisnu Saputra', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(121, '19890612 202521 2 067', 'Yunika Permata Sari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(122, '19971216 202421 2 024', 'Aninda Purba Cahyani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(123, '19741110 202421 2 005', 'Noor Latifah Dachlan', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(124, '19900305 202421 2 034', 'Raden Rara Kun Alfiah Nur Aerodynamicawati', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(125, '19670225 198703 2 001', 'Siti Solekah', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(126, '19661126 198703 1 001', 'Susetyo Gigih Trilaksono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(127, '19670705 198803 1 001', 'Edi Prasetyo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(128, '19681107 198903 1 001', 'Komaruz Zaman', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(129, '19681024 198903 1 001', 'Hary Eka Surjanta', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(130, '19681224 198903 1 001', 'Cukamnoto Hariyadi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(131, '19680208 198903 1 001', 'Achmad Fachri', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(132, '19680719 198903 1 001', 'Bambang Yuliyanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(133, '19690510 199003 1 001', 'Azis Hanafi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(134, '19680107 199103 2 001', 'Dessy Adin', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(135, '19701227 199103 1 001', 'Purnomo Aji', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(136, '19710313 199103 1 001', 'Eko Herman Budi Rahardjo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(137, '19690301 199103 1 001', 'Bagus Widodo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(138, '19720310 199202 2 001', 'Caecilia Hermawati', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(139, '19710511 199202 1 001', 'Suyatno', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(140, '19710203 199202 1 001', 'Franciscus Xaverius Sarwoko', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(141, '19721104 199203 2 001', 'Puji Estriningsih', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(142, '19720615 199303 1 001', 'Jun Suwarno', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(143, '19690820 199303 1 001', 'Ali Ihsan', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(144, '19670725 199303 1 001', 'Syahrizal Ali', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(145, '19690207 199303 2 001', 'Ni Made Duisthiti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(146, '19710703 199303 1 001', 'Rudy Tri Yulianto Widodo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(147, '19740607 199402 2 001', 'Purwaningsih Handayani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(148, '19730424 199402 2 001', 'Wiji Astuti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(149, '19740906 199502 1 001', 'Fahmi Atvidyan', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(150, '19750621 199502 1 002', 'Puji Yuwono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(151, '19760724 199601 1 001', 'Sulistyo Himawan', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(152, '19760727 199601 1 002', 'Agung Ragil Pujono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(153, '19740930 199603 2 001', 'Asri Damayanti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(154, '19710425 199603 1 001', 'Much. Bouxit Wibowo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(155, '19730113 199703 2 001', 'Niken Kusuma Wardhani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(156, '19740907 199703 2 001', 'Rosita Ariani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(157, '19701224 199703 2 001', 'Hartati', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(158, '19700817 199703 2 001', 'Maria Sinaga', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(159, '19690609 199803 1 001', 'Junaidi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(160, '19720903 199803 2 001', 'Nuraini Saptanti Dewi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(162, '19740509 199803 2 007', 'Rahayu Muji Lestari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(163, '19750608 199803 2 001', 'Asih Winarti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(164, '19750726 199803 1 001', 'Deddy Yuliawan Suwondo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(165, '19740505 199803 1 001', 'Paryono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(166, '19750929 199803 2 001', 'Evi Anggraini Soeryanti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(167, '19760625 199803 1 001', 'Muhammad Yasril Friandi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(168, '19700611 199803 1 007', 'Bima Gautama', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(169, '19730405 199803 2 001', 'Afriani Nurfajriyah', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(170, '19770209 199803 1 001', 'Nurhadi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(171, '19681117 199803 1 001', 'Novianto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(172, '19760515 199811 1 001', 'Iwanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(173, '19761203 199811 2 001', 'Ana Suprihatiningsih', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(174, '19761026 199811 1 001', 'Akhda Himmawan', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(175, '19761124 199811 2 001', 'Ninik Triani', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(176, '19770908 199811 2 001', 'Dyah Retno Palupi', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(177, '19770219 199811 2 001', 'Tri Anawati', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(178, '19760623 199811 2 001', 'Lea Triana', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(179, '19771020 199903 2 001', 'Titi Sari', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(180, '19720919 199903 2 011', 'Rosalia Kustyaningsih', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:00', '2026-08-26 00:09:00'),
(181, '19801129 200312 2 001', 'Maftukhah Nur Wijayanti', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:01', '2026-08-26 00:09:01'),
(182, '19840209 200701 1 002', 'Sulistyo Tri Cahyono', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:01', '2026-08-26 00:09:01'),
(183, '19851104 200701 1 003', 'Dedi Fafanto', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:01', '2026-08-26 00:09:01'),
(184, '19860706 200801 1 001', 'Rizky Shampitha Surya Wibowo', '$2y$10$68Wrc0E9MYH45NSxsJWgA.4QWZUbwZDd6MVPGR06fjmcWItB3s30i', NULL, NULL, 'pegawai', '2026-08-26 00:09:01', '2026-08-26 00:09:01'),
(185, '19730916 199803 2 001', 'Siti Akrojah', '', '', NULL, 'admin', '2026-08-26 04:56:47', '2026-08-30 03:48:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `driver`
--
ALTER TABLE `driver`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_plat` (`no_plat`);

--
-- Indexes for table `reservasi_kendaraan`
--
ALTER TABLE `reservasi_kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_reservasi` (`kode_reservasi`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kendaraan_id` (`kendaraan_id`);

--
-- Indexes for table `reservasi_ruangan`
--
ALTER TABLE `reservasi_ruangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_reservasi` (`kode_reservasi`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ruangan_id` (`ruangan_id`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_ruangan` (`kode_ruangan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `driver`
--
ALTER TABLE `driver`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reservasi_kendaraan`
--
ALTER TABLE `reservasi_kendaraan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservasi_ruangan`
--
ALTER TABLE `reservasi_ruangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=538;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservasi_kendaraan`
--
ALTER TABLE `reservasi_kendaraan`
  ADD CONSTRAINT `reservasi_kendaraan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservasi_kendaraan_ibfk_2` FOREIGN KEY (`kendaraan_id`) REFERENCES `kendaraan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservasi_ruangan`
--
ALTER TABLE `reservasi_ruangan`
  ADD CONSTRAINT `reservasi_ruangan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservasi_ruangan_ibfk_2` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
