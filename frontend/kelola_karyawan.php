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
    $id_user_input = trim($_POST['id_user'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $peran_input = $_POST['peran'] ?? 'karyawan';
    $uid = trim($_POST['uid'] ?? '');
    $pin = trim($_POST['pin'] ?? '');
    
    // Validasi input
    if (empty($id_user_input) || empty($username) || empty($password) || 
        empty($nama_lengkap) || empty($email) || empty($uid) || empty($pin)) {
        $error = "Semua field wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($pin) !== 4 || !ctype_digit($pin)) {
        $error = "PIN harus 4 digit angka!";
    } else {
        // Cek apakah ID User sudah ada
        $check_id = $pdo->prepare("SELECT id_user FROM pengguna WHERE id_user = ?");
        $check_id->execute([$id_user_input]);
        
        if ($check_id->rowCount() > 0) {
            $error = 'ID User sudah digunakan!';
        } else {
            // Cek apakah username sudah ada
            $check_username = $pdo->prepare("SELECT id_user FROM pengguna WHERE username = ?");
            $check_username->execute([$username]);
            
            if ($check_username->rowCount() > 0) {
                $error = 'Username sudah digunakan!';
            } else {
                // Cek apakah UID sudah ada
                $check_uid = $pdo->prepare("SELECT id_user FROM pengguna WHERE uid = ?");
                $check_uid->execute([$uid]);
                
                if ($check_uid->rowCount() > 0) {
                    $error = 'UID kartu sudah terdaftar!';
                } else {
                    // Default foto path
                    $foto_path_db = '../images/default-profile.jpg';
                    $data_wajah_path = null;
                    
                    // Handle file upload foto profil
                    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                        $target_dir = "../uploads/profiles/";
                        $file_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_ext;
                        $target_file = $target_dir . $new_filename;
                        
                        // Buat folder uploads jika belum ada
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        
                        // Validasi file
                        $allowed_types = ['jpg', 'jpeg', 'png'];
                        $max_size = 2 * 1024 * 1024; // 2MB
                        
                        if (in_array(strtolower($file_ext), $allowed_types) && 
                            $_FILES['foto']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                                $foto_path_db = $target_file;
                            } else {
                                $error = "Gagal mengunggah file foto.";
                            }
                        } else {
                            $error = "File tidak valid. Hanya JPG/JPEG/PNG maksimal 2MB yang diperbolehkan.";
                        }
                    }
                    
                    // Handle file upload data wajah (PKL)
                    if (empty($error) && isset($_FILES['data_wajah']) && $_FILES['data_wajah']['error'] === UPLOAD_ERR_OK) {
                        $target_dir = "../uploads/face_data/";
                        $file_ext = pathinfo($_FILES['data_wajah']['name'], PATHINFO_EXTENSION);
                        
                        // Validasi file PKL
                        if (strtolower($file_ext) !== 'pkl') {
                            $error = "File data wajah harus berformat .pkl";
                        } else {
                            $new_filename = 'face_' . $id_user_input . '_' . uniqid() . '.pkl';
                            $target_file = $target_dir . $new_filename;
                            
                            // Buat folder uploads jika belum ada
                            if (!file_exists($target_dir)) {
                                mkdir($target_dir, 0777, true);
                            }
                            
                            if (move_uploaded_file($_FILES['data_wajah']['tmp_name'], $target_file)) {
                                $data_wajah_path = $target_file;
                            } else {
                                $error = "Gagal mengunggah file data wajah.";
                            }
                        }
                    }
                    
                    if (empty($error)) {
                        try {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            $stmt = $pdo->prepare("INSERT INTO pengguna 
                                (id_user, username, password, nama_lengkap, email, peran, foto_path, uid, pin, status, data_wajah_path) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', ?)");
                            $stmt->execute([
                                $id_user_input, 
                                $username, 
                                $hashed_password, 
                                $nama_lengkap, 
                                $email, 
                                $peran_input, 
                                $foto_path_db, 
                                $uid, 
                                $pin,
                                $data_wajah_path
                            ]);
                            
                            // Catat log
                            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) 
                                VALUES (?, 'Menambahkan karyawan baru: $username')");
                            $stmt->execute([$_SESSION['id_user']]);
                            
                            $success = "Karyawan baru berhasil ditambahkan!";
                            
                            // Clear form values after successful submission
                            $_POST = array();
                        } catch (PDOException $e) {
                            $error = "Gagal menambahkan karyawan: " . $e->getMessage();
                            
                            // Delete uploaded files if database insertion failed
                            if ($foto_path_db != '../images/default-profile.jpg' && file_exists($foto_path_db)) {
                                unlink($foto_path_db);
                            }
                            if ($data_wajah_path && file_exists($data_wajah_path)) {
                                unlink($data_wajah_path);
                            }
                        }
                    }
                }
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
    <script>
        // Fungsi untuk mengambil UID dari server NFC
        function getUID() {
            const serverUrl = "http://localhost:5001";
            
            fetch(`${serverUrl}/api/last-uid`)
                .then(response => {
                    if (!response.ok) throw new Error('HTTP error');
                    return response.json();
                })
                .then(data => {
                    if (data.uid) {
                        document.getElementById("uid").value = data.uid;
                        showAlert(`UID berhasil dibaca: ${data.uid}`, 'success');
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

        // Fungsi untuk menampilkan alert
        function showAlert(message, type) {
            // Hapus alert sebelumnya jika ada
            const existingAlert = document.querySelector('.alert-nfc');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-nfc alert-dismissible fade show mt-2`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Tempatkan alert di atas form
            const uidField = document.getElementById('uid');
            uidField.parentNode.appendChild(alertDiv);
            
            // Hapus alert setelah 5 detik
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Panggil getUID saat halaman dimuat
        window.addEventListener('DOMContentLoaded', (event) => {
            getUID();
        });

        // Validasi file PKL sebelum upload
        function validatePKLFile(input) {
            const file = input.files[0];
            if (file) {
                const fileName = file.name;
                const fileExt = fileName.split('.').pop().toLowerCase();
                
                if (fileExt !== 'pkl') {
                    alert('Hanya file dengan format .pkl yang diizinkan!');
                    input.value = '';
                    return false;
                }
                
                // Tampilkan nama file yang dipilih
                const fileNameDisplay = document.getElementById('file-name-display');
                fileNameDisplay.textContent = `File terpilih: ${fileName}`;
                fileNameDisplay.style.display = 'block';
                
                return true;
            }
            return false;
        }
    </script>
    <style>
        .file-input-info {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
            display: none;
        }
        .file-requirements {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
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
                    <div class="col-md-10 mx-auto">
                        <div class="card mb-4">
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
                                                <input type="text" class="form-control" id="uid" name="uid" 
                                                    value="<?php echo htmlspecialchars($_POST['uid'] ?? ''); ?>" readonly required>
                                                <small class="text-muted">Tempelkan kartu NFC untuk membaca UID</small>
                                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="getUID()">
                                                    <i class="fas fa-sync-alt"></i> Baca Ulang UID
                                                </button>
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
                                                <label for="pin">PIN (4 digit angka)</label>
                                                <input type="password" class="form-control" id="pin" name="pin" 
                                                    value="<?php echo htmlspecialchars($_POST['pin'] ?? ''); ?>" 
                                                    maxlength="4" pattern="\d{4}" title="Harus 4 digit angka" required>
                                                <small class="text-muted">Masukkan 4 digit angka untuk PIN verifikasi</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="foto">Foto Profil</label>
                                                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png">
                                                <small class="text-muted">Format: JPG, JPEG, PNG (Maks. 2MB)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="data_wajah">File Data Wajah (.pkl)</label>
                                                <input type="file" class="form-control" id="data_wajah" name="data_wajah" 
                                                       accept=".pkl" onchange="validatePKLFile(this)">
                                                <div id="file-name-display" class="file-input-info"></div>
                                                <div class="file-requirements">
                                                    Format file: .pkl (Python Pickle)<br>
                                                    Berisi data embedding wajah untuk sistem presensi
                                                </div>
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

                        <!-- Daftar Karyawan -->
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Daftar Karyawan</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="modern-table">
                                        <thead>
                                            <tr>
                                                <th>ID User</th>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Peran</th>
                                                <th>Status</th>
                                                <th>UID</th>
                                                <th>Data Wajah</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($daftar_karyawan as $karyawan): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($karyawan['id_user']); ?></td>
                                                <td><?php echo htmlspecialchars($karyawan['nama_lengkap']); ?></td>
                                                <td><?php echo htmlspecialchars($karyawan['username']); ?></td>
                                                <td><?php echo htmlspecialchars($karyawan['email']); ?></td>
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
                                                <td><?php echo htmlspecialchars($karyawan['uid'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if (!empty($karyawan['data_wajah_path'])): ?>
                                                        <span class="badge badge-success">Ada</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Tidak Ada</span>
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
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>
</body>
</html>