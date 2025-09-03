<?php
session_start();
require 'config.php';

$error = '';
$success = '';
$step = 1; // 1 = Input UID, 2 = Input Nama, 3 = Proses Rekam Wajah
$uid = '';
$nama = '';

// Proses form input UID
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_uid'])) {
    $uid = trim($_POST['uid'] ?? '');
    
    if (empty($uid)) {
        $error = 'UID harus diisi';
    } else {
        // Cek apakah UID sudah terdaftar
        $stmt = $pdo->prepare("SELECT id_user, nama_lengkap FROM pengguna WHERE uid = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        
        if ($user) {
            $error = 'UID sudah terdaftar atas nama: ' . htmlspecialchars($user['nama_lengkap']);
        } else {
            $_SESSION['rekam_uid'] = $uid;
            $step = 2; // Lanjut ke step input nama
        }
    }
}

// Proses form input nama
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_nama'])) {
    $uid = $_SESSION['rekam_uid'] ?? '';
    $nama = trim($_POST['nama'] ?? '');
    
    if (empty($nama)) {
        $error = 'Nama lengkap harus diisi';
    } else {
        $_SESSION['rekam_nama'] = $nama;
        $step = 3; // Lanjut ke step rekam wajah
    }
}

// Proses rekam wajah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_recording'])) {
    $uid = $_SESSION['rekam_uid'] ?? '';
    $nama = $_SESSION['rekam_nama'] ?? '';
    
    if (empty($uid) || empty($nama)) {
        $error = 'Data tidak lengkap';
        $step = 1;
    } else {
        // Jalankan script Python
        $python_script = "python rekam_wajah.py"; // Ganti dengan path script Python Anda
        $command = escapeshellcmd("$python_script --uid $uid --nama \"$nama\"");
        
        // Eksekusi command
        $output = shell_exec($command . " 2>&1");
        
        // Simpan output untuk ditampilkan
        $_SESSION['rekam_output'] = $output;
        
        // Cek hasil eksekusi
        if (strpos($output, '✅') !== false || strpos($output, 'disimpan') !== false) {
            $success = 'Proses perekaman wajah berhasil!';
            
            // Update database dengan UID
            $stmt = $pdo->prepare("UPDATE pengguna SET uid = ? WHERE nama_lengkap = ?");
            $stmt->execute([$uid, $nama]);
            
            // Hapus session setelah berhasil
            unset($_SESSION['rekam_uid']);
            unset($_SESSION['rekam_nama']);
            $step = 4; // Step sukses
        } else {
            $error = 'Proses perekaman wajah gagal. Silakan coba lagi.';
            $step = 3;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekam Wajah - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .output-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 500px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn-login:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Rekam Wajah</h1>
                <p class="step-indicator">
                    <?php if ($step < 4): ?>
                        Langkah <?php echo $step; ?> dari 3
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Step 1: Input UID -->
            <div class="step <?php echo $step == 1 ? 'active' : ''; ?>">
                <form method="POST">
                    <div class="form-group mb-3">
                        <label for="uid" class="form-label">UID Kartu NFC</label>
                        <input type="text" class="form-control" id="uid" name="uid" 
                            value="<?php echo htmlspecialchars($_POST['uid'] ?? ''); ?>" readonly required>
                        <small class="text-muted">Tempelkan kartu NFC untuk membaca UID</small>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="getUID()">
                            <i class="fas fa-sync-alt"></i> Baca Ulang UID
                        </button>
                    </div>

                    <button type="submit" name="submit_uid" class="btn btn-login">Lanjutkan</button>
                    
                    <div class="text-center mt-3">
                        <a href="kelola_karyawan.php">Kembali ke Kelola Karyawan</a>
                    </div>            
                </form>
            </div>

            <!-- Step 2: Input Nama -->
            <div class="step <?php echo $step == 2 ? 'active' : ''; ?>">
                <form method="POST">
                    <div class="form-group mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>" required>
                    </div>

                    <button type="submit" name="submit_nama" class="btn btn-login">Lanjutkan</button>
                    
                    <div class="text-center mt-3">
                        <a href="rekam_wajah.php">Kembali ke Awal</a>
                    </div>            
                </form>
            </div>

            <!-- Step 3: Rekam Wajah -->
            <div class="step <?php echo $step == 3 ? 'active' : ''; ?>">
                <form method="POST">
                    <p>Silakan siapkan diri untuk perekaman wajah. Pastikan pencahayaan cukup dan wajah terlihat jelas.</p>
                    
                    <div class="form-group mb-3">
                        <label>UID: <strong><?php echo htmlspecialchars($_SESSION['rekam_uid'] ?? ''); ?></strong></label>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Nama: <strong><?php echo htmlspecialchars($_SESSION['rekam_nama'] ?? ''); ?></strong></label>
                    </div>

                    <button type="submit" name="start_recording" class="btn btn-login">Mulai Rekam Wajah</button>
                    
                    <div class="text-center mt-3">
                        <a href="rekam_wajah.php">Batalkan</a>
                    </div>            
                </form>
            </div>

            <!-- Step 4: Hasil -->
            <div class="step <?php echo $step == 4 ? 'active' : ''; ?>">
                <div class="text-center">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: green;"></i>
                    <h3>Proses Selesai!</h3>
                    <p>Data wajah berhasil direkam untuk UID: <strong><?php echo htmlspecialchars($uid); ?></strong></p>
                    
                    <?php if (!empty($_SESSION['rekam_output'])): ?>
                        <h5>Output Proses:</h5>
                        <div class="output-container">
                            <?php echo htmlspecialchars($_SESSION['rekam_output']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="kelola_karyawan.php" class="btn btn-login">Kembali ke Kelola Karyawan</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk mengambil UID dari server NFC
        function getUID() {
            const serverUrl = "http://localhost:5001";
            
            fetch(${serverUrl}/api/last-uid)
                .then(response => {
                    if (!response.ok) throw new Error('HTTP error');
                    return response.json();
                })
            .then(data => {
                if (data.uid) {
                    document.getElementById("uid").value = data.uid;
                    showAlert(<span class="text-success">Berhasil</span> membaca UID: ${data.uid}, 'success');
                } else {
                    throw new Error('Data UID tidak valid');
                }
            })
            .catch(error => {
                console.error("Error:", error);
                showAlert(<span class="text-danger">Gagal</span> membaca UID. Cek koneksi atau server NFC., 'danger');
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
            alertDiv.className = alert alert-${type} alert-nfc alert-dismissible fade show mt-2;
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

        // Panggil getUID saat halaman dimuat jika di step 1
        window.addEventListener('DOMContentLoaded', (event) => {
            <?php if ($step == 1): ?>
                getUID();
            <?php endif; ?>
        });
    </script>
</body>
</html>