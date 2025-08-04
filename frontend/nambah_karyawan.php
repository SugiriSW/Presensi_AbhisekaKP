<?php
session_start();

// Load config dengan error handling
require_once __DIR__ . '/config.php';

$error = '';
$success = '';

// Hapus bagian yang mengambil data user dari session karena belum login
$peran = 'guest'; // Set default role untuk tamu
$id_user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi dan sanitasi input
    $id_user = trim($_POST['id_user'] ?? ''); // Tambahan input ID User
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $peran = $_POST['peran'] ?? 'karyawan';
    
    try {
        // Validasi input
        if (empty($id_user) || empty($username) || empty($password) || empty($nama_lengkap) || empty($email)) {
            throw new Exception('Semua field wajib diisi!');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid!');
        }
        
        // Cek apakah ID User sudah ada
        $check_id = $pdo->prepare("SELECT id_user FROM pengguna WHERE id_user = ?");
        $check_id->execute([$id_user]);
        
        if ($check_id->rowCount() > 0) {
            throw new Exception('ID User sudah digunakan!');
        }
        
        // Cek apakah username sudah ada
        $check_username = $pdo->prepare("SELECT id_user FROM pengguna WHERE username = ?");
        $check_username->execute([$username]);
        
        if ($check_username->rowCount() > 0) {
            throw new Exception('Username sudah digunakan!');
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Handle upload foto
        $foto_path = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $target_dir = __DIR__ . "/uploads/foto_profil/";
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    throw new Exception('Gagal membuat direktori upload');
                }
            }
            
            $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception('Hanya file JPG, JPEG, dan PNG yang diizinkan.');
            }
            
            // Validasi ukuran file (maks 2MB)
            if ($_FILES['foto']['size'] > 2097152) {
                throw new Exception('Ukuran file terlalu besar. Maksimal 2MB.');
            }
            
            $new_filename = uniqid('profile_', true) . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                throw new Exception('Gagal mengupload foto.');
            }
            
            $foto_path = "uploads/foto_profil/" . $new_filename;
        }
        
        // Insert data ke database
        $stmt = $pdo->prepare("INSERT INTO pengguna (id_user, username, password, nama_lengkap, email, peran, foto_path) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $id_user,
            $username,
            $hashed_password,
            $nama_lengkap,
            $email,
            $peran,
            $foto_path
        ]);
        
        $success = 'Karyawan berhasil ditambahkan!';
        $_POST = array(); // Reset form
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-fluid">
        <!-- Sidebar untuk tamu -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="sidebar-header">
                <img src="../images/Abhiseka.png" alt="Logo Perusahaan" class="heading-image">
            </div>
            
            <nav class="sidebar-nav">
                <a href="login.php" class="nav-link active">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                
                <a href="register.php" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            <nav class="navbar">
                <div class="navbar-title">Tambah Karyawan Baru</div>
                <div class="navbar-user">
                    <span>Guest User</span>
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

                                <form action="nambah_karyawan.php" method="POST" enctype="multipart/form-data">
                                    <!-- Tambahkan field ID User di sini -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="id_user">ID User</label>
                                                <input type="text" class="form-control" id="id_user" name="id_user" 
                                                       value="<?php echo htmlspecialchars($_POST['id_user'] ?? ''); ?>" required>
                                                <small class="text-muted">Masukkan ID unik untuk user</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username">Username</label>
                                                <input type="text" class="form-control" id="username" name="username" 
                                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nama_lengkap">Nama Lengkap</label>
                                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                                       value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
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

                                    <div class="form-group text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save"></i> Simpan Data
                                        </button>
                                        <a href="login.php" class="btn btn-secondary btn-lg">
                                            <i class="fas fa-arrow-left"></i> Kembali ke Login
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview foto sebelum upload
        document.getElementById('foto').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Anda bisa menambahkan preview gambar di sini jika diperlukan
                    console.log('File selected:', file.name);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>