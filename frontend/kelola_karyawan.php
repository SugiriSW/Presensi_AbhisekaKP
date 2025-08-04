<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['peran'] != 'superadmin') {
    header('Location: login.php');
    exit();
}
$peran = $_SESSION['peran'];
$id_user = $_SESSION['id_user'];

$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';

$error = '';
$success = '';

// Tambah karyawan baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_karyawan'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $peran = $_POST['peran'];
    
    // Default foto path
    $foto_path = '../images/default-profile.jpg';
    
    // Handle file upload
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
                $foto_path = $target_file;
            } else {
                $error = "Gagal mengunggah file foto.";
            }
        } else {
            $error = "File tidak valid. Hanya JPG/JPEG/PNG maksimal 2MB yang diperbolehkan.";
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pengguna 
                (username, password, nama_lengkap, email, peran, foto_path) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $nama_lengkap, $email, $peran, $foto_path]);
            
            // Catat log
            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_pengguna, aktivitas) 
                VALUES (?, 'Menambahkan karyawan baru: $username')");
            $stmt->execute([$_SESSION['id_user']]);
            
            $success = "Karyawan baru berhasil ditambahkan!";
            
            // Clear form values after successful submission
            $_POST = array();
        } catch (PDOException $e) {
            $error = "Gagal menambahkan karyawan: " . $e->getMessage();
            
            // Delete uploaded file if database insertion failed
            if ($foto_path != '../images/default-profile.jpg' && file_exists($foto_path)) {
                unlink($foto_path);
            }
        }
    }
}

// Ambil daftar semua karyawan
$stmt = $pdo->query("SELECT * FROM pengguna ORDER BY peran, nama_lengkap");
$daftar_karyawan = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan - Sistem Presensi</title>
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
                <div class="navbar-title">Kelola Karyawan</div>
                <div class="navbar-user">
                    <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">                    <span><?php echo $_SESSION['nama_lengkap']; ?></span>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">Form Tambah Karyawan</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <?php if ($success): ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                <?php endif; ?>

                                <!-- Form Tambah Karyawan -->
                                <form action="kelola_karyawan.php" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username">Username</label>
                                                <input type="text" class="form-control" id="username" name="username" 
                                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nama_lengkap">Nama Lengkap</label>
                                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                                    value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="peran">Peran</label>
                                                <select class="form-select" id="peran" name="peran" required>
                                                    <option value="karyawan" <?php echo (isset($_POST['peran']) && $_POST['peran'] === 'karyawan') ? 'selected' : ''; ?>>Karyawan</option>
                                                    <option value="admin" <?php echo (isset($_POST['peran']) && $_POST['peran'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                    <option value="superadmin" <?php echo (isset($_POST['peran']) && $_POST['peran'] === 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>                                               
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="foto">Foto Profil</label>
                                                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png">
                                                <small class="text-muted">Format: JPG, JPEG, PNG (Maks. 2MB)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group text-center mt-4">
                                        <button type="submit" name="tambah_karyawan" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save"></i> Simpan Data
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Daftar Karyawan</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="modern-table">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Peran</th>
                                                <th>Status</th>                                                
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($daftar_karyawan as $karyawan): ?>
                                            <tr>
                                                <td><?php echo $karyawan['nama_lengkap']; ?></td>
                                                <td><?php echo $karyawan['username']; ?></td>
                                                <td><?php echo $karyawan['email']; ?></td>
                                                <td>
                                                    <?php if ($karyawan['peran'] == 'superadmin'): ?>
                                                        <span class="badge badge-danger">Superadmin</span>
                                                    <?php elseif ($karyawan['peran'] == 'admin'): ?>
                                                        <span class="badge badge-warning">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-primary">Karyawan</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                <?php if ($karyawan['status'] == 'aktif'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_karyawan.php?id=<?= $karyawan['id_user']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($karyawan['id_user'] != $_SESSION['id_user']): ?>
                                    <?php if ($karyawan['status'] == 'aktif'): ?>
                                        <a href="nonaktifkan.php?id=<?= $karyawan['id_user'] ?>" 
                                        class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Yakin ingin menonaktifkan karyawan ini?')">
                                            <i class="fas fa-user-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="aktifkan.php?id=<?= $karyawan['id_user'] ?>" 
                                        class="btn btn-sm btn-success" 
                                        onclick="return confirm('Yakin ingin mengaktifkan kembali karyawan ini?')">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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