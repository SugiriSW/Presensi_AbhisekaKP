<?php
session_start();
require 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

// Ambil data dari session
$id_user = $_SESSION['id_user'];
$peran = $_SESSION['peran'] ?? '';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto_path = $_SESSION['foto_path'] ?? 'images/default-profile.jpg';

// Ambil data user dari database
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan";
    header('Location: profil.php');
    exit();
}

// Inisialisasi variabel
$error = '';
$success = '';

// Proses form edit profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek apakah form yang disubmit adalah ganti password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validasi input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "Semua field password harus diisi";
        } elseif ($new_password !== $confirm_password) {
            $error = "Password baru dan konfirmasi password tidak cocok";
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = "Password saat ini salah";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE pengguna SET password = ? WHERE id_user = ?");
            if ($stmt->execute([$hashed_password, $id_user])) {
                $success = "Password berhasil diubah";
                
                // Catat log aktivitas
                $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Mengubah password')");
                $stmt->execute([$id_user]);
            } else {
                $error = "Gagal mengubah password. Silakan coba lagi.";
            }
        }
    } else {
        // Proses form edit profil biasa
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        
        // Validasi input
        if (empty($nama_lengkap) || empty($email)) {
            $error = "Nama lengkap dan email harus diisi";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid";
        } else {
            try {
                // Proses upload foto jika ada
                if (!empty($_FILES['foto_profil']['name'])) {
                    $file = $_FILES['foto_profil'];
                    
                    // Validasi error
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Terjadi kesalahan saat mengunggah file");
                    }
                    
                    // Validasi tipe file
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($file['type'], $allowed_types)) {
                        throw new Exception("Hanya file JPG, PNG, atau GIF yang diizinkan");
                    }
                    
                    // Validasi ukuran file (max 2MB)
                    if ($file['size'] > 2097152) {
                        throw new Exception("Ukuran file maksimal 2MB");
                    }
                    
                    // Generate nama file unik
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $id_user . '_' . uniqid() . '.' . $ext;
                    $upload_path = '../uploads/profiles/' . $filename;
                    
                    // Buat folder uploads jika belum ada
                    if (!file_exists('../uploads/profiles')) {
                        mkdir('../uploads/profiles', 0777, true);
                    }
                    
                    // Pindahkan file
                    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                        throw new Exception("Gagal menyimpan file");
                    }
                    
                    // Update path foto di database
                    $stmt = $pdo->prepare("UPDATE pengguna SET foto_path = ? WHERE id_user = ?");
                    $stmt->execute([$upload_path, $id_user]);
                    
                    // Update session
                    $_SESSION['foto_path'] = $upload_path;
                }
                
                // Update data profil
                $stmt = $pdo->prepare("UPDATE pengguna SET nama_lengkap = ?, email = ? WHERE id_user = ?");
                $stmt->execute([$nama_lengkap, $email, $id_user]);
                
                // Update session
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['email'] = $email;
                
                $success = "Profil berhasil diperbarui";
                
                // Catat log aktivitas
                $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Memperbarui profil')");
                $stmt->execute([$id_user]);
                
                // Refresh data user
                $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
                $stmt->execute([$id_user]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Root Variables */
        :root {
            --primary-color: #a6cd29;
            --primary-dark: #165329;
            --secondary-color: #f8f9fc;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --border-color: #e3e6f0;
        }

        /* Profile Page Specific Styles */
        .profile-page {
            background-color: var(--secondary-color);
            min-height: 100vh;
        }

        .profile-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(5, 136, 7, 0.15);
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid var(--light-color);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid var(--light-color);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            font-size: 1.5rem;
        }

        .avatar-container:hover .avatar-overlay {
            opacity: 1;
            cursor: pointer;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(166, 205, 41, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #8fb722;
            border-color: #8fb722;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .heading-custom {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--primary-dark);
            margin-bottom: 20px;
            text-align: center;
        }

        .password-togglee {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .password-container {
            position: relative;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-pills .nav-link {
            color: var(--dark-color);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-container {
                padding: 20px;
                margin: 15px;
            }
        }
        
        a.d-block.position-relative {
            text-decoration: none;
            color: inherit;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
        }

        .avatar-container:hover {
            transform: scale(1.03);
        }
        
        .tab-content {
            padding: 20px 0;
        }
    </style>
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
                <?php endif; ?>
                
                <?php if ($peran == 'superadmin'): ?>
                    <a href="kelola_karyawan.php" class="nav-link">
                        <i class="fas fa-users-cog"></i>
                        <span>Kelola Karyawan</span>
                    </a>
                    <a href="kelola_event.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Kelola Event</span>
                    </a>
                <?php endif; ?>
                
                <a href="profil.php" class="nav-link active">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
                
                <a href="logout.php" class="nav-link logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </nav>
        </div>
    
    <div class="main-content">
        <div class="profile-container">
            <h2 class="heading-custom">EDIT PROFIL</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                        <i class="fas fa-user me-1"></i> Profil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                        <i class="fas fa-lock me-1"></i> Ganti Password
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="profileTabsContent">
                <!-- Tab Profil -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <label for="foto_profil" style="cursor:pointer;">
                                    <div class="avatar-container">
                                        <img src="<?= htmlspecialchars($user['foto_path'] ?? '../images/default-profile.jpg') ?>" 
                                            class="profile-picture" id="profilePreview">
                                        <div class="avatar-overlay">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                    </div>
                                </label>
                                <input type="file" id="foto_profil" name="foto_profil" accept="image/*" style="display:none;">                               
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                           value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Peran</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($user['peran'] ?? '')) ?>" disabled>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="profil.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tab Ganti Password -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <form method="POST">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="mb-3 password-container">
                                    <label for="current_password" class="form-label">Password Saat Ini</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <span class="password-togglee" onclick="togglePassword('current_password')">
                                    </span>
                                </div>
                                
                                <div class="mb-3 password-container">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <span class="password-togglee" onclick="togglePassword('new_password')">
                                    </span>
                                </div>
                                
                                <div class="mb-3 password-container">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <span class="password-togglee" onclick="togglePassword('confirm_password')">
                                    </span>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="profil.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali
                                    </a>
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-key me-1"></i> Ganti Password
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="flat-footer">
        <div class="footer-content">
            <div class="footer-section">
                <p><i class="fas fa-map-marker-alt"></i> Jl. Pinus Raya No.26, Ruko Komp. Pinus Regency</p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar sebelum upload
        document.getElementById('foto_profil').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Handle error dari upload
        <?php if (isset($_SESSION['upload_error'])): ?>
            alert("<?= $_SESSION['upload_error'] ?>");
            <?php unset($_SESSION['upload_error']); ?>
        <?php endif; ?>
        
        // Aktifkan tab berdasarkan URL hash
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash === '#password') {
                const triggerTab = document.querySelector('#password-tab');
                if (triggerTab) {
                    new bootstrap.Tab(triggerTab).show();
                }
            }
        });
    </script>
</body>
</html>