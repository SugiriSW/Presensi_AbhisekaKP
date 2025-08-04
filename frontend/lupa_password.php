<?php
session_start();
require 'config.php';

$error = '';
$success = '';
$step = 1; // 1 = Input email, 2 = Input OTP, 3 = Input password baru
$email = '';

// Proses form input email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_otp'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email harus diisi';
    } else {
        // Cek apakah email terdaftar
        $stmt = $pdo->prepare("SELECT id_user, verif FROM pengguna WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_verif_code'] = $user['verif']; // Kode verifikasi dari database
            $step = 2; // Lanjut ke step input OTP
        } else {
            $error = 'Email tidak ditemukan';
        }
    }
}

// Proses form verifikasi OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp'] ?? '');
    $email = $_SESSION['reset_email'] ?? '';
    
    if (empty($otp)) {
        $error = 'Kode OTP harus diisi';
    } elseif (strlen($otp) != 4 || !is_numeric($otp)) {
        $error = 'Kode OTP harus 4 digit angka';
    } elseif ($otp != ($_SESSION['reset_verif_code'] ?? '')) {
        $error = 'Kode OTP tidak valid';
    } else {
        $step = 3; // Lanjut ke step input password baru
        $_SESSION['otp_verified'] = true;
    }
}

// Proses form ubah password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        $error = 'Silakan verifikasi OTP terlebih dahulu';
        $step = 1;
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Password baru dan konfirmasi password harus diisi';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Password baru dan konfirmasi password tidak sama';
        } else {
            // Update password di database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $email = $_SESSION['reset_email'];
            
            $stmt = $pdo->prepare("UPDATE pengguna SET password = ? WHERE email = ?");
            if ($stmt->execute([$hashed_password, $email])) {
                $success = 'Password berhasil diubah. Anda akan diarahkan ke halaman login dalam <span id="countdown">5</span> detik...';
                session_destroy();
                $step = 4; // Step sukses
            } else {
                $error = 'Gagal mengubah password. Silakan coba lagi.';
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
    <title>Lupa Password - Sistem Presensi</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .otp-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .resend-otp {
            margin-top: 15px;
            text-align: center;
        }
        .resend-otp a {
            color: #007bff;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Reset Password</h1>
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

            <!-- Step 1: Input Email -->
            <div class="step <?php echo $step == 1 ? 'active' : ''; ?>">
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Terdaftar</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <button type="submit" name="request_otp" class="btn btn-login">Kirim Kode OTP</button>
                    
                    <div class="text-center" style="margin-top: 15px;">
                        <a href="login.php">Kembali ke Login</a>
                    </div>            
                </form>
            </div>

            <!-- Step 2: Input OTP -->
            <div class="step <?php echo $step == 2 ? 'active' : ''; ?>">
                <form method="POST" id="otpForm">
                    <p>Kode verifikasi telah dikirim ke <strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></strong></p>
                    
                    <div class="otp-container">
                        <input type="text" class="otp-input" maxlength="1" data-index="1" autofocus>
                        <input type="text" class="otp-input" maxlength="1" data-index="2">
                        <input type="text" class="otp-input" maxlength="1" data-index="3">
                        <input type="text" class="otp-input" maxlength="1" data-index="4">
                    </div>
                    <input type="hidden" name="otp" id="otp">

                    <button type="submit" name="verify_otp" class="btn btn-login">Verifikasi</button>
                    
                    <div class="resend-otp">
                        <span>Tidak menerima kode? </span>
                        <a onclick="resendOtp()">Kirim ulang</a>
                    </div>
                    
                    <div class="text-center" style="margin-top: 15px;">
                        <a href="login.php">Kembali ke Login</a>
                    </div>            
                </form>
            </div>

            <!-- Step 3: Input Password Baru -->
            <div class="step <?php echo $step == 3 ? 'active' : ''; ?>">
                <form method="POST">
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-login">Ubah Password</button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($step == 4): ?>
    <script>
        // Hitung mundur otomatis
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
    <?php endif; ?>

    <script>
        // Fungsi untuk input OTP
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHiddenInput = document.getElementById('otp');
        
        otpInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                const index = parseInt(e.target.dataset.index);
                
                // Jika input diisi, pindah ke input berikutnya
                if (value.length === 1 && index < 4) {
                    otpInputs[index].focus();
                }
                
                updateOtpValue();
            });
            
            input.addEventListener('keydown', (e) => {
                // Jika tombol backspace ditekan dan input kosong, pindah ke input sebelumnya
                if (e.key === 'Backspace' && e.target.value === '' && index > 1) {
                    otpInputs[index - 2].focus();
                }
                
                updateOtpValue();
            });
        });
        
        function updateOtpValue() {
            let otpValue = '';
            otpInputs.forEach(input => {
                otpValue += input.value;
            });
            otpHiddenInput.value = otpValue;
        }
        
        function resendOtp() {
            // Diimplementasikan sesuai kebutuhan (misalnya kirim ulang OTP via email)
            alert('Kode OTP telah dikirim ulang ke email Anda');
        }
    </script>
</body>
</html>