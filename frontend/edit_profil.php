<?php
session_start();
require 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_SESSION['id_user']) || !in_array($_SESSION['peran'], ['karyawan', 'admin'])) {
    header('Location: login.php');
    exit();
}

// Ambil data dari session
$id_user = $_SESSION['id_user'];
$peran = $_SESSION['peran'] ?? ''; // <- Tambahkan ini untuk hilangkan error
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

// Proses form edit profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    
    // Validasi input
    if (empty($nama_lengkap) || empty($email)) {
        $_SESSION['error'] = "Nama lengkap dan email harus diisi";
        header('Location: edit_profile.php');
        exit();
    }
    
    // Validasi format email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid";
        header('Location: edit_profile.php');
        exit();
    }
    
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
        
        $_SESSION['success'] = "Profil berhasil diperbarui";
        
        // Catat log aktivitas
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Memperbarui profil')");
        $stmt->execute([$id_user]);
        
        header('Location: profil.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: edit_profile.php');
        exit();
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
            max-width: 800px;
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
            display: inline-block; /* Tambahkan ini */
        }

        .avatar-container:hover {
            transform: scale(1.03);
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
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
 <form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-4 text-center mb-4">
            <label for="foto_profil" style="cursor:pointer;">
                <div class="avatar-container">
                    <img src="<?= htmlspecialchars($user['foto_path'] ?? 'assets/default_profile.png') ?>" 
                        class="profile-picture" id="profilePreview">
                    <div class="avatar-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
            </label>
            <input type="file" id="foto_profil" name="foto_profil" accept="image/*" style="display:none;">
            
            <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                    onclick="document.getElementById('foto_profil').click()">
                <i class="fas fa-camera me-1"></i> Ganti Foto
            </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
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

        // Handle error dari upload
        <?php if (isset($_SESSION['upload_error'])): ?>
            alert("<?= $_SESSION['upload_error'] ?>");
            <?php unset($_SESSION['upload_error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>