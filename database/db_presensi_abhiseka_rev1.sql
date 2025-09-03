-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 08:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_presensi_abhiseka_rev1`
--

-- --------------------------------------------------------

--
-- Table structure for table `dinas_luar`
--

CREATE TABLE `dinas_luar` (
  `id_dinas_luar` int(10) NOT NULL,
  `id_user` varchar(20) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `keperluan` text DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tujuan` varchar(100) NOT NULL,
  `disetujui_oleh` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dinas_luar`
--

INSERT INTO `dinas_luar` (`id_dinas_luar`, `id_user`, `tanggal_mulai`, `tanggal_selesai`, `lokasi`, `keperluan`, `status`, `created_at`, `tujuan`, `disetujui_oleh`, `updated_at`) VALUES
(1, 'FR00001', '2025-07-22', '2025-07-25', 'Jakarta', 'Nemenin Bu Bos Karouke', 'Disetujui', '2025-07-21 07:50:11', '', 'DR00001', '2025-07-21 07:59:23'),
(2, 'FR00001', '2025-07-22', '2025-07-30', 'Jakarta', 'eqweqeq', 'Disetujui', '2025-07-21 08:54:50', '', 'DR00001', '2025-07-21 08:56:59');

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id_dokumen` int(20) NOT NULL,
  `id_user` varchar(20) DEFAULT NULL,
  `nama_file` varchar(100) DEFAULT NULL,
  `path_file` varchar(100) DEFAULT NULL,
  `tanggal_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id_dokumen`, `id_user`, `nama_file`, `path_file`, `tanggal_upload`) VALUES
(1, 'FR00001', 'profile_4_686b52b00e02f.png', 'uploads/dinas_luar/dinas_0_687df1334158e.png', '2025-07-21 07:50:11'),
(2, 'FR00001', '152022129_Sugiri Satrio Wicaksono_LogBook KP.pdf', 'uploads/izin/izin_0_687dffec67e9c.pdf', '2025-07-21 08:53:00'),
(3, 'FR00001', '152022129_Sugiri Satrio Wicaksono_LogBook KP.pdf', 'uploads/izin/izin_0_687e00027a87c.pdf', '2025-07-21 08:53:22'),
(4, 'FR00001', '152022129_Sugiri Satrio Wicaksono_LogBook KP.pdf', 'uploads/izin/izin_0_687e000b118f8.pdf', '2025-07-21 08:53:31'),
(5, 'FR00001', '152022129_Sugiri Satrio Wicaksono_LogBook KP.pdf', 'uploads/dinas_luar/dinas_2_687e005ad8a61.pdf', '2025-07-21 08:54:50');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `id_event` varchar(20) NOT NULL,
  `judul` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jenis_event` enum('libur','acara','pengumuman') NOT NULL,
  `dibuat_oleh` varchar(20) NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`id_event`, `judul`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `jenis_event`, `dibuat_oleh`, `dibuat_pada`) VALUES
('EV-20250721-a6ddc', 'Proyek Presensi', 'Proyek Presensi Karyawan mohon cepat diberesin', '2025-07-21', '2025-12-31', 'pengumuman', 'DR00001', '2025-07-21 10:23:51'),
('EV-20250723-6c396', 'Olahraga Setiap Jum\'at Jam 9-10', 'Mari hidup sehat dengan olahraga pagi hari dan juga sempatkan untuk olahraga sejenak', '2025-07-23', '2025-07-25', 'pengumuman', 'DR00001', '2025-07-23 04:09:45');

-- --------------------------------------------------------

--
-- Table structure for table `izin`
--

CREATE TABLE `izin` (
  `id_izin` int(20) NOT NULL,
  `id_user` varchar(20) NOT NULL,
  `jenis_izin` enum('sakit','cuti') NOT NULL,
  `alasan` text NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `foto_pendukung` varchar(100) DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
  `disetujui_oleh` varchar(20) DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `disetujui_pada` timestamp NULL DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `izin`
--

INSERT INTO `izin` (`id_izin`, `id_user`, `jenis_izin`, `alasan`, `tanggal_mulai`, `tanggal_selesai`, `foto_pendukung`, `status`, `disetujui_oleh`, `catatan_admin`, `disetujui_pada`, `dibuat_pada`) VALUES
(1, 'FR00001', 'sakit', 'Demam', '2025-07-21', '2025-07-23', 'uploads/izin/687deb918cc7a.png', 'disetujui', 'DR00001', '', '2025-07-21 07:38:26', '2025-07-21 07:26:09'),
(2, 'FR00001', 'sakit', 'Demam', '2025-07-21', '2025-07-23', 'uploads/izin/687ded9b10698.png', 'disetujui', 'DR00001', '', '2025-07-21 07:38:29', '2025-07-21 07:34:51'),
(3, 'FR00001', 'cuti', 'Acara Keluarga', '2025-07-21', '2025-07-21', 'uploads/izin/687def68f1bae.jpg', 'ditolak', 'JR00002', 'KERJA DLU BOS', NULL, '2025-07-21 07:42:32'),
(4, 'FR00001', 'sakit', 'Batuk', '2025-07-22', '2025-07-23', NULL, 'menunggu', NULL, NULL, NULL, '2025-07-21 09:01:59'),
(5, 'FR00001', 'sakit', 'Bwhehee', '2025-07-22', '2025-07-23', NULL, 'menunggu', NULL, NULL, NULL, '2025-07-22 03:50:21');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_aktivitas` int(10) NOT NULL,
  `id_user` varchar(20) NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `detail` text DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_aktivitas`, `id_user`, `aktivitas`, `detail`, `waktu`) VALUES
(1, '', 'Memperbarui profil', NULL, '2025-07-21 06:19:09'),
(2, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 07:25:41'),
(3, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 07:25:43'),
(4, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 07:25:48'),
(5, 'FR00001', 'Mengajukan izin', NULL, '2025-07-21 07:34:51'),
(6, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 07:35:10'),
(7, 'JR00002', 'Memperbarui profil', NULL, '2025-07-21 07:36:42'),
(8, 'JR00002', 'Logout dari sistem', NULL, '2025-07-21 07:37:04'),
(9, 'JR00002', 'Logout dari sistem', NULL, '2025-07-21 07:37:09'),
(10, 'DR00001', 'Menyetujui izin ID: 1', NULL, '2025-07-21 07:38:26'),
(11, 'DR00001', 'Menyetujui izin ID: 2', NULL, '2025-07-21 07:38:29'),
(12, 'DR00001', 'Logout dari sistem', NULL, '2025-07-21 07:39:43'),
(13, '', 'Logout dari sistem', NULL, '2025-07-21 07:39:50'),
(14, 'FR00001', 'Memperbarui profil', NULL, '2025-07-21 07:40:01'),
(15, 'FR00001', 'Mengajukan izin', NULL, '2025-07-21 07:42:32'),
(16, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 07:42:38'),
(17, 'JR00002', 'Menolak izin ID: 3', NULL, '2025-07-21 07:42:52'),
(18, 'JR00002', 'Logout dari sistem', NULL, '2025-07-21 07:42:56'),
(19, 'FR00001', 'Mengajukan dinas luar', NULL, '2025-07-21 07:50:11'),
(20, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 07:50:13'),
(21, 'DR00001', 'Menyetujui dinas luar ID: ', NULL, '2025-07-21 07:59:17'),
(22, 'DR00001', 'Menyetujui dinas luar ID: 1', NULL, '2025-07-21 07:59:23'),
(23, 'DR00001', 'Logout dari sistem', NULL, '2025-07-21 08:01:59'),
(24, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 08:27:32'),
(25, 'DR00001', 'Logout dari sistem', NULL, '2025-07-21 08:33:16'),
(26, 'JR00002', 'Logout dari sistem', NULL, '2025-07-21 08:33:25'),
(27, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 08:53:35'),
(28, 'JR00002', 'Logout dari sistem', NULL, '2025-07-21 08:54:26'),
(29, 'FR00001', 'Mengajukan dinas luar', NULL, '2025-07-21 08:54:50'),
(30, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 08:54:58'),
(31, 'DR00001', 'Menyetujui dinas luar ID: 2', NULL, '2025-07-21 08:56:59'),
(32, 'DR00001', 'Logout dari sistem', NULL, '2025-07-21 09:01:36'),
(33, 'DR00001', 'Logout dari sistem', NULL, '2025-07-21 09:01:41'),
(34, 'FR00001', 'Logout dari sistem', NULL, '2025-07-21 09:02:12'),
(35, 'DR00001', 'Menambahkan event', NULL, '2025-07-21 10:23:51'),
(36, 'DR00001', 'Logout dari sistem', NULL, '2025-07-21 10:23:56'),
(37, 'FR00001', 'Logout dari sistem', NULL, '2025-07-22 03:15:35'),
(38, 'DR00001', 'Logout dari sistem', NULL, '2025-07-22 03:16:13'),
(39, 'FR00001', 'Logout dari sistem', NULL, '2025-07-22 03:33:34'),
(40, 'DR00001', 'Logout dari sistem', NULL, '2025-07-22 03:46:57'),
(41, 'FR00001', 'Logout dari sistem', NULL, '2025-07-22 03:47:46'),
(42, 'DR00001', 'Logout dari sistem', NULL, '2025-07-22 03:50:04'),
(43, 'FR00001', 'Logout dari sistem', NULL, '2025-07-22 03:50:30'),
(44, 'DR00001', 'Logout dari sistem', NULL, '2025-07-22 05:00:30'),
(45, 'FR00001', 'Logout dari sistem', NULL, '2025-07-22 05:34:22'),
(46, 'JR00002', 'Logout dari sistem', NULL, '2025-07-22 05:44:04'),
(47, 'DR00001', 'Logout dari sistem', NULL, '2025-07-22 05:50:45'),
(48, 'FR00001', 'Logout dari sistem', NULL, '2025-07-23 03:45:04'),
(49, 'DR00001', 'Logout dari sistem', NULL, '2025-07-23 03:45:14'),
(50, 'FR00001', 'Logout dari sistem', NULL, '2025-07-23 03:45:26'),
(51, 'DR00001', 'Mengedit data karyawan: Kasmal Ras', NULL, '2025-07-23 04:07:13'),
(52, 'DR00001', 'Menambahkan event', NULL, '2025-07-23 04:09:45'),
(53, 'DR00001', 'Logout dari sistem', NULL, '2025-07-23 05:56:27'),
(54, 'DR00001', 'Logout dari sistem', NULL, '2025-07-23 05:57:16'),
(55, 'FR00001', 'Logout dari sistem', NULL, '2025-07-23 05:57:52'),
(56, 'JR00002', 'Logout dari sistem', NULL, '2025-07-23 05:57:58'),
(57, 'JR00002', 'Logout dari sistem', NULL, '2025-07-24 03:52:48'),
(58, 'JR00002', 'Logout dari sistem', NULL, '2025-07-24 05:47:20'),
(59, 'FR00001', 'Logout dari sistem', NULL, '2025-07-29 02:39:02'),
(60, 'DR00001', 'Logout dari sistem', NULL, '2025-07-31 05:43:38'),
(61, 'DR00001', 'Logout dari sistem', NULL, '2025-09-03 04:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_user` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(60) NOT NULL,
  `email` varchar(50) NOT NULL,
  `peran` enum('superadmin','admin','karyawan') NOT NULL DEFAULT 'karyawan',
  `uid` varchar(255) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `data_wajah` text DEFAULT NULL COMMENT 'Data wajah untuk face recognition',
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `verif` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_user`, `username`, `password`, `nama_lengkap`, `email`, `peran`, `uid`, `foto_path`, `data_wajah`, `dibuat_pada`, `diperbarui_pada`, `status`, `verif`) VALUES
('BE00001', 'Kasmal Ras', '$2y$10$ogqH5n6gggNvhvI2FtdXiO8xXhtUQyzYJ6kZUvecs1UHDXXE6RJvW', 'Kasmal Rasis', 'Kas@gmail.com', 'karyawan', NULL, 'uploads/foto_profil/profile_687f0e47cf26d1.35721437.jpg', NULL, '2025-07-22 04:06:31', '2025-07-29 02:38:43', 'aktif', 2580),
('DR00001', 'Suge', '$2y$10$jD1dTC1dENOrkHNgxXnF/eQHs/oC/gqkNQHiEkSFMRXsGygkwPANW', 'Sugiri Satrio Wicaksono', 'sugirisatriowi@gmail.com', 'superadmin', NULL, 'uploads/foto_profil/profile_687dee4fa35bf0.72987060.jpg', NULL, '2025-07-21 07:37:51', '2025-07-21 07:37:51', 'aktif', 0),
('FR00001', 'SugiriS', '$2y$10$ZY/RXuxOayd.jG53ngmCL.xGpDeAGyx4QE8r3rgzwjosYh3YYLybG', 'Sugiri Satrio Wicaksono', 'Sugey@gmail.com', 'karyawan', '450AD905', '../uploads/profiles/profile_FR00001_687deed171df3.jpg', NULL, '2025-07-21 07:23:53', '2025-09-01 04:37:04', 'aktif', 0),
('JR00002', 'Wika', '$2y$10$nCTsF62vCxWPtslJKrlj4uyfs6xh4Ph9lEuaZGr.FoNdbiADr84gi', 'Wika Aditya', 'Sugiriakun11@gmail.com', 'admin', NULL, '../uploads/profiles/profile_JR00002_687dee0aa73be.png', NULL, '2025-07-21 07:35:53', '2025-07-21 07:36:42', 'aktif', 0);

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id_presensi` int(11) NOT NULL,
  `id_user` varchar(20) NOT NULL,
  `uid` varchar(255) NOT NULL,
  `tanggal_presensi` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status_kehadiran` enum('Hadir','Terlambat','Cuti','Sakit','Tidak Hadir','Libur','Izin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id_presensi`, `id_user`, `uid`, `tanggal_presensi`, `jam_masuk`, `jam_pulang`, `status_kehadiran`) VALUES
(1, 'FR00001', '450AD905', '2025-09-01', '15:35:33', NULL, 'Terlambat');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dinas_luar`
--
ALTER TABLE `dinas_luar`
  ADD PRIMARY KEY (`id_dinas_luar`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `fk_disetujui_oleh` (`disetujui_oleh`);

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`id_event`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`);

--
-- Indexes for table `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id_izin`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `disetujui_oleh` (`disetujui_oleh`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_aktivitas`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD UNIQUE KEY `uid_2` (`uid`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id_presensi`),
  ADD KEY `idx_id_user` (`id_user`),
  ADD KEY `fk_presensi_pengguna_uid` (`uid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dinas_luar`
--
ALTER TABLE `dinas_luar`
  MODIFY `id_dinas_luar` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id_dokumen` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id_izin` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_aktivitas` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dinas_luar`
--
ALTER TABLE `dinas_luar`
  ADD CONSTRAINT `dinas_luar_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disetujui_oleh` FOREIGN KEY (`disetujui_oleh`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `pengguna` (`id_user`);

--
-- Constraints for table `izin`
--
ALTER TABLE `izin`
  ADD CONSTRAINT `izin_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`),
  ADD CONSTRAINT `izin_ibfk_2` FOREIGN KEY (`disetujui_oleh`) REFERENCES `pengguna` (`id_user`);

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `fk_presensi_pengguna_uid` FOREIGN KEY (`uid`) REFERENCES `pengguna` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
