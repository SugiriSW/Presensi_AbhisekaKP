<?php
session_start();

// Load config with error handling
require_once __DIR__ . '/config.php';

// Check if user is logged in and has superadmin role
if (!isset($_SESSION['id_user']) || $_SESSION['peran'] !== 'superadmin') {
    header('Location: login.php');
    exit();
}

$peran = $_SESSION['peran'];
$user_id = $_SESSION['id_user'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi dan sanitasi input
    $id_user = trim($_POST['id_user'] ?? ''); 
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $peran = $_POST['peran'] ?? 'karyawan';
    $uid = trim($_POST['uid'] ?? ''); 
    $pin = trim($_POST['pin'] ?? '');
    
    try {
        // Validasi input
        if (empty($id_user) || empty($username) || empty($password) || 
            empty($nama_lengkap) || empty($email) || empty($uid) || empty($pin)) {
            throw new Exception('Semua field wajib diisi!');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid!');
        }
        
        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            throw new Exception('PIN harus 4 digit angka!');
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
        
        // Cek apakah UID sudah ada
        $check_uid = $pdo->prepare("SELECT id_user FROM pengguna WHERE uid = ?");
        $check_uid->execute([$uid]);
        
        if ($check_uid->rowCount() > 0) {
            throw new Exception('UID kartu sudah terdaftar!');
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
        $stmt = $pdo->prepare("INSERT INTO pengguna (id_user, username, password, nama_lengkap, email, peran, foto_path, uid, pin) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $id_user,
            $username,
            $hashed_password,
            $nama_lengkap,
            $email,
            $peran,
            $foto_path,
            $uid,
            $pin
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
    <script>
        // Fungsi untuk mengambil UID dari server NFC
        function getUID() {
            fetch("http://127.0.0.1:5000/get_uid")
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById("uid").value = data.uID;
                    // Beri notifikasi sukses
                    showAlert('UID berhasil dibaca: ' + data.uID, 'success');
                })
                .catch(error => {
                    console.error("Gagal ambil UID:", error);
                    showAlert('Gagal membaca UID. Pastikan server NFC berjalan.', 'danger');
                });
        }

        // Fungsi untuk menampilkan alert
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Tempatkan alert di atas form
            const form = document.querySelector('form');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Hapus alert setelah 5 detik
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Panggil getUID saat halaman dimuat
        window.addEventListener('DOMContentLoaded', (event) => {
            getUID();
            
            // Tambahkan tombol untuk membaca ulang UID
            const uidField = document.getElementById('uid');
            const refreshButton = document.createElement('button');
            refreshButton.type = 'button';
            refreshButton.className = 'btn btn-sm btn-outline-secondary mt-2';
            refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i> Baca Ulang UID';
            refreshButton.onclick = getUID;
            uidField.parentNode.appendChild(refreshButton);
        });
    </script>
</head>
<body>
    <div class="dashboard-fluid">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="sidebar-header">
                <img src="../images/Abhiseka.png" alt="Logo Perusahaan" class="heading-image">
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link active">
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
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="uid">UID Kartu NFC</label>
                                                <input type="text" class="form-control" id="uid" name="uid" readonly>
                                                <small class="text-muted">Tempelkan kartu NFC untuk membaca UID</small>
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
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="foto">Foto Profil</label>
                                                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png">
                                                <small class="text-muted">Format: JPG, JPEG, PNG (Maks. 2MB)</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="pin">PIN (4 digit angka)</label>
                                                <input type="password" class="form-control" id="pin" name="pin" 
                                                    maxlength="4" pattern="\d{4}" title="Harus 4 digit angka">
                                                <small class="text-muted">Masukkan 4 digit angka untuk PIN verifikasi</small>
                                            </div>
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
        // Ganti URL dengan dinamis
        function getUID() {
            const serverUrl = window.location.origin.replace(/:\d+$/, ':5000');
            
            fetch(`${serverUrl}/get_uid`)
                .then(response => {
                    if (!response.ok) throw new Error('HTTP error');
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success' && data.uID) {
                        document.getElementById("uid").value = data.uID;
                        showAlert(`UID berhasil dibaca: ${data.uID}`, 'success');
                    } else {
                        throw new Error('Data UID tidak valid');
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    showAlert('Gagal membaca UID. Cek koneksi atau server NFC.', 'danger');
                    // Fallback: Input manual
                    document.getElementById("uid").readOnly = false;
                });
        }
    </script>
</body>
</html>