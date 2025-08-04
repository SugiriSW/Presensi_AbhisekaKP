<?php
session_start();
require 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validasi input
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = "Semua field harus diisi";
    header('Location: profil.php');
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "Password baru dan konfirmasi password tidak sama";
    header('Location: profil.php');
    exit();
}

if (strlen($new_password) < 8) {
    $_SESSION['error'] = "Password minimal 8 karakter";
    header('Location: profil.php');
    exit();
}

// Ambil data user dari database
$stmt = $pdo->prepare("SELECT password FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan";
    header('Location: profil.php');
    exit();
}

// Verifikasi password saat ini
if (!password_verify($current_password, $user['password'])) {
    $_SESSION['error'] = "Password saat ini salah";
    header('Location: profil.php');
    exit();
}

// Hash password baru
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Update password di database
$stmt = $pdo->prepare("UPDATE pengguna SET password = ? WHERE id_user = ?");
$stmt->execute([$hashed_password, $id_user]);

// Catat log aktivitas - SUDAH SESUAI DENGAN STRUKTUR TABEL ANDA
$stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Mengubah password')");
$stmt->execute([$id_user]);

$_SESSION['success'] = "Password berhasil diubah";
header('Location: profil.php');
exit();
?>