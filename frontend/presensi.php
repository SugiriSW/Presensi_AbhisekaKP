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
$current_month = date('m');
$current_year = date('Y');

// Prepare and execute query to get attendance data
$stmt = $pdo->prepare("SELECT * FROM presensi 
                      WHERE id_user = ? 
                      AND MONTH(tanggal_presensi) = ? 
                      AND YEAR(tanggal_presensi) = ?
                      ORDER BY tanggal_presensi DESC");
$stmt->execute([$id_user, $current_month, $current_year]);
$presensi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .event-card {
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .event-date {
            font-size: 0.9rem;
            color: var(--primary-dark);
            font-weight: 600;
        }
        .event-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .event-type.libur {
            background-color: var(--success-color);
            color: white;
        }
        .event-type.acara {
            background-color: var(--info-color);
            color: white;
        }
        .event-type.pengumuman {
            background-color: var(--warning-color);
            color: white;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .badge-hadir {
            background-color: #28a745; /* hijau */
        }

        .badge-izin {
            background-color: #ffc107; /* kuning */
            color: #000;
        }

        .badge-sakit {
            background-color: #17a2b8; /* biru */
        }

        .badge-alpa {
            background-color: #dc3545; /* merah */
        }

        .badge-default {
            background-color: #6c757d; /* abu-abu */
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
                    <a href="presensi.php" class="nav-link active">
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
                <div class="navbar-user">
                    <img src="<?= htmlspecialchars($foto_path) ?>" alt="User" class="user-profile">
                    <span><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                </div>
            </nav>

            <!-- Content -->
            <div class="content-wrapper">
                    <div class="card-body p-0">
                        <?php if (count($presensi) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 modern-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($presensi as $row): ?>
                                        <tr>
                                            <td data-label="Tanggal"><?= date('d M Y', strtotime($row['tanggal_presensi'])) ?></td>
                                            <td data-label="Jam Masuk">
                                                <?php if (!empty($row['jam_masuk'])): ?>
                                                    <?= date('H:i', strtotime($row['jam_masuk'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Jam Pulang">
                                                <?php if (!empty($row['jam_pulang'])): ?>
                                                    <?= date('H:i', strtotime($row['jam_pulang'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="status_kehadiran">
<?php
$status = strtolower($row['status_kehadiran']);
switch ($status) {
    case 'hadir':
        $badge_class = 'badge-hadir';
        break;
    case 'izin':
        $badge_class = 'badge-izin';
        break;
    case 'sakit':
        $badge_class = 'badge-sakit';
        break;
    case 'alpa':
        $badge_class = 'badge-alpa';
        break;
    default:
        $badge_class = 'badge-default';
        break;
}
?>
<span class="status-badge <?= $badge_class ?>"><?= ucfirst($row['status_kehadiran']) ?></span>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="text-muted">Belum ada data presensi.</p>
                        <?php endif; ?>
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
<!-- Script Section -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi tooltip (jika diperlukan)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Pastikan modal tidak otomatis terbuka
    var modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(function(modal) {
        modal.style.display = 'none';
    });
});
</script>