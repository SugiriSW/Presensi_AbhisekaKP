<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Pastikan hanya superadmin yang bisa mengakses
if ($_SESSION['peran'] != 'superadmin') {
    header('Location: dashboard.php');
    exit();
}

$peran = $_SESSION['peran'];
$id_user = $_SESSION['id_user'];

// Ambil data user yang login
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';

// Ambil ID karyawan yang akan diedit dari parameter URL
if (!isset($_GET['id'])) {
    header('Location: kelola_karyawan.php');
    exit();
}

$id_karyawan = $_GET['id'];
$error = '';
$success = '';

// Ambil data karyawan yang akan diedit
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_karyawan]);
$karyawan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$karyawan) {
    header('Location: kelola_karyawan.php');
    exit();
}

// Proses form edit karyawan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_karyawan'])) {
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $peran = $_POST['peran'];
    $status = $_POST['status'];
    $verif = $_POST['verif'];
    
    // Validasi kode verifikasi
    if (strlen($verif) != 4 || !is_numeric($verif)) {
        $error = 'Kode verifikasi harus 4 digit angka';
    }
    
    // Handle password change (jika diisi)
    $password_changed = false;
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_changed = true;
    }
    
    // Handle file upload
    $new_foto_path = $karyawan['foto_path'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/profiles/";
        $file_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        // Validasi file
        $allowed_types = ['jpg', 'jpeg', 'png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array(strtolower($file_ext), $allowed_types) && 
            $_FILES['foto']['size'] <= $max_size) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                // Hapus foto lama jika bukan default dan ada
                if ($new_foto_path != '../images/default-profile.jpg' && file_exists($new_foto_path)) {
                    unlink($new_foto_path);
                }
                $new_foto_path = $target_file;
            } else {
                $error = "Gagal mengunggah file foto.";
            }
        } else {
            $error = "File tidak valid. Hanya JPG/JPEG/PNG maksimal 2MB yang diperbolehkan.";
        }
    }
    
    if (empty($error)) {
        try {
            if ($password_changed) {
                $stmt = $pdo->prepare("UPDATE pengguna SET 
                    username = ?, password = ?, nama_lengkap = ?, email = ?, 
                    peran = ?, status = ?, foto_path = ?, verif = ? WHERE id_user = ?");
                $stmt->execute([$username, $password, $nama_lengkap, $email, 
                    $peran, $status, $new_foto_path, $verif, $id_karyawan]);
            } else {
                $stmt = $pdo->prepare("UPDATE pengguna SET 
                    username = ?, nama_lengkap = ?, email = ?, 
                    peran = ?, status = ?, foto_path = ?, verif = ? WHERE id_user = ?");
                $stmt->execute([$username, $nama_lengkap, $email, 
                    $peran, $status, $new_foto_path, $verif, $id_karyawan]);
            }
            
            // Catat log
            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) 
                VALUES (?, 'Mengedit data karyawan: $username')");
            $stmt->execute([$_SESSION['id_user']]);
            
            $success = "Data karyawan berhasil diperbarui!";
            
            // Update data karyawan yang ditampilkan
            $karyawan['username'] = $username;
            $karyawan['nama_lengkap'] = $nama_lengkap;
            $karyawan['email'] = $email;
            $karyawan['peran'] = $peran;
            $karyawan['status'] = $status;
            $karyawan['foto_path'] = $new_foto_path;
            $karyawan['verif'] = $verif;
            
        } catch (PDOException $e) {
            $error = "Gagal memperbarui data karyawan: " . $e->getMessage();
            
            // Delete uploaded file if database update failed
            if (isset($target_file) && file_exists($target_file)) {
                unlink($target_file);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-fluid">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="sidebar-header">
                <img src="../images/Abhiseka.png" alt="Logo Perusahaan" class="heading-image">
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                
                <?php if ($peran == 'karyawan'): ?>
                    <a href="presensi.php" class="nav-link">
                        <i class="fas fa-fingerprint"></i>
                        <span>Riwayat Presensi</span>
                    </a>
                    <a href="izin.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span>Ajukan Izin</span>
                    </a>
                    <a href="dinas_luar.php" class="nav-link">
                        <i class="fas fa-briefcase"></i>
                        <span>Ajukan Dinas Luar</span>
                    </a>
                <?php endif; ?>
                
                <?php if ($peran == 'admin' || $peran == 'superadmin'): ?>
                    <a href="daftar_presensi.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Daftar Presensi</span>
                    </a>
                    <a href="kelola_izin.php" class="nav-link">
                        <i class="fas fa-tasks"></i>
                        <span>Kelola Izin</span>
                    </a>
                    <a href="kelola_dinas.php" class="nav-link">
                        <i class="fas fa-briefcase"></i>
                        <span>Kelola Dinas</span>
                    </a>                     
                <?php endif; ?>
                
                <?php if ($peran == 'superadmin'): ?>
                    <a href="kelola_karyawan.php" class="nav-link active">
                        <i class="fas fa-users-cog"></i>
                        <span>Kelola Karyawan</span>
                    </a>
                    <a href="kelola_event.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Kelola Event</span>
                    </a>
                <?php endif; ?>
                
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
                
                <a href="logout.php" class="nav-link logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
                <div class="navbar-user">
                    <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">
                    <span><?php echo $_SESSION['nama_lengkap']; ?></span>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">Form Edit Karyawan</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <?php if ($success): ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                <?php endif; ?>

                                <!-- Form Edit Karyawan -->
                                <form action="edit_karyawan.php?id=<?= $id_karyawan ?>" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username">Username</label>
                                                <input type="text" class="form-control" id="username" name="username" 
                                                    value="<?php echo htmlspecialchars($karyawan['username']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Password Baru (Kosongkan jika tidak diubah)</label>
                                                <input type="password" class="form-control" id="password" name="password">
                                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nama_lengkap">Nama Lengkap</label>
                                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                                    value="<?php echo htmlspecialchars($karyawan['nama_lengkap']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                    value="<?php echo htmlspecialchars($karyawan['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="peran">Peran</label>
                                                <select class="form-select" id="peran" name="peran" required>
                                                    <option value="karyawan" <?= $karyawan['peran'] == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
                                                    <option value="admin" <?= $karyawan['peran'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="superadmin" <?= $karyawan['peran'] == 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="aktif" <?= $karyawan['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="nonaktif" <?= $karyawan['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="verif">Kode Verifikasi (OTP)</label>
                                                <input type="text" class="form-control" id="verif" name="verif" 
                                                       value="<?php echo htmlspecialchars($karyawan['verif']); ?>" 
                                                       pattern="[0-9]{4}" maxlength="4" required>
                                                <small class="text-muted">4 digit angka untuk reset password</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="foto">Foto Profil</label>
                                                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png">
                                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Foto Saat Ini:</label><br>
                                            <img src="<?= $karyawan['foto_path'] ?>" alt="Foto Profil" class="img-thumbnail" style="max-width: 150px;">
                                        </div>
                                    </div>

                                    <div class="form-group text-center mt-4">
                                        <button type="submit" name="edit_karyawan" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                        <a href="kelola_karyawan.php" class="btn btn-secondary btn-lg">
                                            <i class="fas fa-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<footer class="flat-footer">
    <div class="footer-content">
        <div class="footer-section">
            <p><i class="fas fa-map-marker-alt"></i> Jl. Pinus Raya No.26, Ruko Komp. Pinus Regency
 </p>
        </div>
        <div class="footer-section">
            <p><i class="fas fa-phone"></i> +62 812-6789-1059</p>
        </div>
        <div class="footer-section">
            <p><i class="fas fa-code"></i> Developed by Sugiri</p>
        </div>
        <div class="footer-section">
            <p>&copy; 2023 PT Abhiseka</p>
        </div>
    </div>
</footer>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>
</body>
</html>