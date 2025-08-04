<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username COLLATE utf8mb4_general_ci = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['peran'] = $user['peran'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

        header('Location: ../frontend/dashboard.php');
        exit();
    } else {
        $error = "Username atau password salah.";
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Presensi</title>
    <!-- Perhatikan path ini -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Sistem Presensi Karyawan</h1>
                <p>Silakan masuk dengan akun Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-login">Masuk</button>
                
                <div class="text-center" style="margin-top: 15px;">
                    <a href="lupa_password.php" onclick="openModal('forgotModal')">Lupa Password?</a>
                </div>            
            </form>
            
        </div>
    </div>
</body>
</html>
