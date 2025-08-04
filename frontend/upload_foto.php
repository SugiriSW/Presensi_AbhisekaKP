<?php
session_start();
require 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

$id_user = $_SESSION['id_user'];

// Validasi file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_profil'])) {
    $file = $_FILES['foto_profil'];
    
    // Validasi error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Terjadi kesalahan saat mengunggah file";
        header('Location: profil.php');
        exit();
    }
    
    // Validasi tipe file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "Hanya file JPG, PNG, atau GIF yang diizinkan";
        header('Location: profil.php');
        exit();
    }
    
    // Validasi ukuran file (max 2MB)
    if ($file['size'] > 2097152) {
        $_SESSION['error'] = "Ukuran file maksimal 2MB";
        header('Location: profil.php');
        exit();
    }
    
    // Generate nama file unik
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $id_user . '_' . uniqid() . '.' . $ext;
    $upload_path = '../uploads/profiles/' . $filename;
    
    // Buat folder uploads jika belum ada
    if (!file_exists('../uploads/profiles')) {
        mkdir('../uploads/profiles', 0777, true);
    }
    
    // Pindahkan file ke folder uploads
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update path foto di database
        $stmt = $pdo->prepare("UPDATE pengguna SET foto_path = ? WHERE id_user = ?");
        $stmt->execute([$upload_path, $id_user]);
        
        // Update session
        $_SESSION['foto_path'] = $upload_path;
        $_SESSION['success'] = "Foto profil berhasil diubah";
        
        // Catat log aktivitas - SUDAH SESUAI DENGAN STRUKTUR TABEL ANDA
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Mengubah foto profil')");
        $stmt->execute([$id_user]);
    } else {
        $_SESSION['error'] = "Gagal menyimpan file";
    }
}

header('Location: profil.php');
exit();
?>