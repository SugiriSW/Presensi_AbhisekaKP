<?php
session_start();
require 'config.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['peran'] !== 'superadmin') {
    header('Location: login.php');
    exit();
}
$peran = $_SESSION['peran'];
$id_user = $_SESSION['id_user'];

$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC); // ✅ ini yang penting!
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';

// Inisialisasi variabel
$error = '';
$success = '';
$events = [];

// Handle form tambah event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_event'])) {
    $judul = $_POST['judul'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $jenis_event = $_POST['jenis_event'] ?? 'pengumuman';

    // Validasi
    if (empty($judul)) {
        $error = 'Judul event harus diisi';
    } elseif (strtotime($tanggal_selesai) < strtotime($tanggal_mulai)) {
        $error = 'Tanggal selesai tidak boleh sebelum tanggal mulai';
    } else {
        try {
            // Generate ID event unik
            $id_event = 'EV-' . date('Ymd') . '-' . substr(uniqid(), -5);
            
            $stmt = $pdo->prepare("INSERT INTO event 
                (id_event, judul, deskripsi, tanggal_mulai, tanggal_selesai, jenis_event, dibuat_oleh) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id_event, 
                $judul, 
                $deskripsi, 
                $tanggal_mulai, 
                $tanggal_selesai, 
                $jenis_event, 
                $id_user
            ]);
            
            $success = 'Event berhasil ditambahkan!';
            
            // Catat log
            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Menambahkan event')");
            $stmt->execute([$id_user]);
            
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan event: ' . $e->getMessage();
        }
    }
}

// Handle hapus event
if (isset($_GET['hapus'])) {
    $id_event = $_GET['hapus'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM event WHERE id_event = ?");
        $stmt->execute([$id_event]);
        
        if ($stmt->rowCount() > 0) {
            $success = 'Event berhasil dihapus';
            
            // Catat log
            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Menghapus event')");
            $stmt->execute([$id_user]);
        } else {
            $error = 'Event tidak ditemukan';
        }
    } catch (PDOException $e) {
        $error = 'Gagal menghapus event: ' . $e->getMessage();
    }
}

// Ambil semua event
$stmt = $pdo->prepare("SELECT e.*, p.nama_lengkap 
                      FROM event e
                      JOIN pengguna p ON e.dibuat_oleh = p.id_user
                      ORDER BY e.tanggal_mulai DESC");
$stmt->execute();
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Event - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .event-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .event-card.libur {
            border-left-color: #28a745;
        }
        .event-card.acara {
            border-left-color: #ffc107;
        }
        .event-card.pengumuman {
            border-left-color: #17a2b8;
        }
        .badge-event {
            font-size: 0.8em;
            padding: 5px 8px;
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
                    <a href="kelola_event.php" class="nav-link active">
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
                <div class="navbar-user">
                    <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">
                    <span><?php echo $_SESSION['nama_lengkap']; ?></span>
                </div>
            </nav>
        
        <!-- Content -->
        <div class="content-wrapper">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Tambah Event Baru</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="judul">Judul Event</label>
                                    <input type="text" class="form-control" id="judul" name="judul" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="deskripsi">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tanggal_mulai">Tanggal Mulai</label>
                                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tanggal_selesai">Tanggal Selesai</label>
                                            <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="jenis_event">Jenis Event</label>
                                    <select class="form-control" id="jenis_event" name="jenis_event" required>
                                        <option value="pengumuman">Pengumuman</option>
                                        <option value="acara">Acara</option>
                                        <option value="libur">Hari Libur</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="tambah_event" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Tambah Event
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Daftar Event</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($events)): ?>
                                <div class="alert alert-info">Belum ada event yang ditambahkan.</div>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <div class="card event-card <?= $event['jenis_event'] ?> mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        <?= htmlspecialchars($event['judul']) ?>
                                                        <span class="badge badge-event badge-<?= 
                                                            $event['jenis_event'] == 'libur' ? 'success' : 
                                                            ($event['jenis_event'] == 'acara' ? 'warning' : 'info') 
                                                        ?>">
                                                            <?= ucfirst($event['jenis_event']) ?>
                                                        </span>
                                                    </h5>
                                                    <p class="text-muted small mb-2">
                                                        <i class="far fa-calendar-alt"></i> 
                                                        <?= date('d M Y', strtotime($event['tanggal_mulai'])) ?>
                                                        <?php if ($event['tanggal_mulai'] != $event['tanggal_selesai']): ?>
                                                            - <?= date('d M Y', strtotime($event['tanggal_selesai'])) ?>
                                                        <?php endif; ?>
                                                        | Dibuat oleh: <?= $event['nama_lengkap'] ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <a href="?hapus=<?= $event['id_event'] ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Yakin ingin menghapus event ini?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($event['deskripsi'])): ?>
                                                <div class="card-text mt-2">
                                                    <?= nl2br(htmlspecialchars($event['deskripsi'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Set tanggal minimal hari ini
    const today = new Date().toISOString().split('T')[0];
    $('#tanggal_mulai, #tanggal_selesai').attr('min', today);
    
    // Validasi tanggal selesai tidak boleh sebelum tanggal mulai
    $('#tanggal_mulai').change(function() {
        $('#tanggal_selesai').attr('min', $(this).val());
    });
});
</script>
</body>
</html>