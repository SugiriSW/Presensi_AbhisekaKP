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
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $peran_input = $_POST['peran'];
    $status = $_POST['status'];
    $uid = trim($_POST['uid']);
    $verif = trim($_POST['pin']); // PIN disimpan di kolom 'verif'
    
    // Validasi input
    if (empty($username) || empty($nama_lengkap) || empty($email) || empty($uid) || empty($verif)) {
        $error = "Semua field wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($verif) !== 4 || !ctype_digit($verif)) {
        $error = "PIN harus 4 digit angka!";
    } else {
        // Cek apakah username sudah ada (kecuali untuk user ini)
        $check_username = $pdo->prepare("SELECT id_user FROM pengguna WHERE username = ? AND id_user != ?");
        $check_username->execute([$username, $id_karyawan]);
        
        if ($check_username->rowCount() > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Cek apakah UID sudah ada (kecuali untuk user ini)
            $check_uid = $pdo->prepare("SELECT id_user FROM pengguna WHERE uid = ? AND id_user != ?");
            $check_uid->execute([$uid, $id_karyawan]);
            
            if ($check_uid->rowCount() > 0) {
                $error = 'UID kartu sudah terdaftar!';
            } else {
                // Handle password change (jika diisi)
                $password_changed = false;
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $password_changed = true;
                }
                
                // Handle file upload foto profil
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
                
                // Handle file upload data wajah (PKL)
                $new_data_wajah = $karyawan['data_wajah'];
                if (empty($error) && isset($_FILES['data_wajah']) && $_FILES['data_wajah']['error'] === UPLOAD_ERR_OK) {
                    $target_dir = "../uploads/face_data/";
                    $file_ext = pathinfo($_FILES['data_wajah']['name'], PATHINFO_EXTENSION);
                    
                    // Validasi file PKL
                    if (strtolower($file_ext) !== 'pkl') {
                        $error = "File data wajah harus berformat .pkl";
                    } else {
                        $new_filename = 'face_' . $id_karyawan . '_' . uniqid() . '.pkl';
                        $target_file = $target_dir . $new_filename;
                        
                        // Buat folder uploads jika belum ada
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        
                        if (move_uploaded_file($_FILES['data_wajah']['tmp_name'], $target_file)) {
                            // Hapus file data wajah lama jika ada
                            if (!empty($new_data_wajah) && file_exists($new_data_wajah)) {
                                unlink($new_data_wajah);
                            }
                            $new_data_wajah = $target_file;
                        } else {
                            $error = "Gagal mengunggah file data wajah.";
                        }
                    }
                }
                
                if (empty($error)) {
                    try {
                        if ($password_changed) {
                            $stmt = $pdo->prepare("UPDATE pengguna SET 
                                username = ?, password = ?, nama_lengkap = ?, email = ?, 
                                peran = ?, status = ?, foto_path = ?, uid = ?, verif = ?, data_wajah = ? WHERE id_user = ?");
                            $stmt->execute([$username, $password, $nama_lengkap, $email, 
                                $peran_input, $status, $new_foto_path, $uid, $verif, $new_data_wajah, $id_karyawan]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE pengguna SET 
                                username = ?, nama_lengkap = ?, email = ?, 
                                peran = ?, status = ?, foto_path = ?, uid = ?, verif = ?, data_wajah = ? WHERE id_user = ?");
                            $stmt->execute([$username, $nama_lengkap, $email, 
                                $peran_input, $status, $new_foto_path, $uid, $verif, $new_data_wajah, $id_karyawan]);
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
                        $karyawan['peran'] = $peran_input;
                        $karyawan['status'] = $status;
                        $karyawan['foto_path'] = $new_foto_path;
                        $karyawan['uid'] = $uid;
                        $karyawan['verif'] = $verif;
                        $karyawan['data_wajah'] = $new_data_wajah;
                        
                    } catch (PDOException $e) {
                        $error = "Gagal memperbarui data karyawan: " . $e->getMessage();
                        
                        // Delete uploaded files if database update failed
                        if (isset($target_file) && file_exists($target_file)) {
                            unlink($target_file);
                        }
                    }
                }
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
                    showAlert(`<span class="text-success">Berhasil</span> membaca UID: ${data.uid}`, 'success');
                } else {
                    throw new Error('Data UID tidak valid');
                }
            })
            .catch(error => {
                console.error("Error:", error);
                showAlert(`<span class="text-danger">Gagal</span> membaca UID. Cek koneksi atau server NFC.`, 'danger');
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
                                                <label for="id_user">ID User</label>
                                                <input type="text" class="form-control" id="id_user" name="id_user" 
                                                    value="<?php echo htmlspecialchars($karyawan['id_user']); ?>" readonly>
                                                <small class="text-muted">ID User tidak dapat diubah</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username">Username</label>
                                                <input type="text" class="form-control" id="username" name="username" 
                                                    value="<?php echo htmlspecialchars($karyawan['username']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Password Baru (Kosongkan jika tidak diubah)</label>
                                                <input type="password" class="form-control" id="password" name="password">
                                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nama_lengkap">Nama Lengkap</label>
                                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                                    value="<?php echo htmlspecialchars($karyawan['nama_lengkap']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                    value="<?php echo htmlspecialchars($karyawan['email']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="uid">UID Kartu NFC</label>
                                                <input type="text" class="form-control" id="uid" name="uid" 
                                                    value="<?php echo htmlspecialchars($karyawan['uid'] ?? ''); ?>" required>
                                                <small class="text-muted">Tempelkan kartu NFC untuk membaca UID</small>
                                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="getUID()">
                                                    <i class="fas fa-sync-alt"></i> Baca Ulang UID
                                                </button>
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
                                                <label for="pin">PIN (4 digit angka)</label>
                                                <input type="password" class="form-control" id="pin" name="pin" 
                                                    value="<?php echo htmlspecialchars($karyawan['verif']); ?>" 
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
                                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto. Format: JPG, JPEG, PNG (Maks. 2MB)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Foto Saat Ini:</label><br>
                                            <img src="<?= $karyawan['foto_path'] ?>" alt="Foto Profil" class="img-thumbnail" style="max-width: 150px;">
                                        </div>
                                    </div>

                                    <div class="row mt-3">
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
                                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah file data wajah</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Status Data Wajah Saat Ini:</label><br>
                                            <?php if (!empty($karyawan['data_wajah'])): ?>
                                                <span class="badge badge-success">File data wajah tersedia</span>
                                                <p class="small text-muted mt-1">File: <?php echo basename($karyawan['data_wajah']); ?></p>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Tidak ada file data wajah</span>
                                            <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Panggil getUID saat halaman dimuat
        window.addEventListener('DOMContentLoaded', (event) => {
            getUID();
        });
    </script>
</body>
</html>