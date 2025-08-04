<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}


$peran = $_SESSION['peran'];
$id_user = $_SESSION['id_user'];

$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC); // ✅ ini yang penting!
$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .profile-card:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .profile-detail {
            padding: 20px;
        }
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: var(--primary-dark);
        }
        .edit-btn {
            transition: all 0.3s ease;
        }
        .edit-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .profile-img {
                width: 120px;
                height: 120px;
            }
            .profile-detail {
                padding: 15px;
            }
        }
        @media (max-width: 576px) {
            .profile-img {
                width: 100px;
                height: 100px;
            }
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
        
        <!-- Main Content -->
        <div class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4 py-3">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
                <div class="navbar-title">Profil Pengguna</div>
                <div class="navbar-user">
                    <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">
                    <span><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="content-wrapper">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card profile-card mb-4">
                            <div class="card-body text-center">
                                <div class="mb-4">
                                    <img src="<?= $foto_path ?>" alt="Foto Profil" class="profile-img rounded-circle">
                                    <h3 class="mt-3 mb-0"><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                                    <p class="text-muted"><?= ucfirst($user['peran']) ?></p>
                                    <a href="edit_profil.php" class="btn btn-primary edit-btn">
                                        <i class="fas fa-edit"></i> Edit Profil
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5 class="mb-0">Detail Profil</h5>
                            </div>
                            <div class="card-body profile-detail">
                                <div class="detail-item">
                                    <span class="detail-label">Nama Lengkap:</span>
                                    <p><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <p><?= htmlspecialchars($user['email']) ?></p>
                                </div>                            
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="detail-item">
    <span class="detail-label">PIN:</span>
    <p><?= !empty($user['pin']) ? '****' : 'Belum diatur' ?></p>
    <button class="btn btn-sm btn-outline-primary mt-2" onclick="showEditPinModal()">
        <i class="fas fa-edit"></i> Edit PIN
    </button>
</div>

<!-- Modal Edit PIN -->
<div class="modal fade" id="editPinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPinForm" method="POST" action="update_pin.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password_pin" class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control" id="current_password_pin" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_pin" class="form-label">PIN Baru (4 digit)</label>
                        <input type="password" class="form-control" id="new_pin" name="new_pin" maxlength="4" pattern="\d{4}" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_pin" class="form-label">Konfirmasi PIN Baru</label>
                        <input type="password" class="form-control" id="confirm_pin" name="confirm_pin" maxlength="4" pattern="\d{4}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ganti Password -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ganti Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm" method="POST" action="change_password.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sama seperti di dashboard.php
        document.addEventListener('DOMContentLoaded', function() {
            // Pastikan konten selalu mengambil lebar yang tersedia
            function adjustContentWidth() {
                const sidebarWidth = document.querySelector('.sidebar').offsetWidth;
                document.querySelector('.main-content').style.marginLeft = sidebarWidth + 'px';
            }
            
            adjustContentWidth();
            window.addEventListener('resize', adjustContentWidth);
        });
    </script>
</body>
</html>