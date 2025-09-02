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
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data event/pengumuman terbaru
$stmt_events = $pdo->prepare("SELECT * FROM event 
    WHERE tanggal_selesai >= CURDATE() 
    ORDER BY tanggal_mulai DESC LIMIT 3");
$stmt_events->execute();
$events = $stmt_events->fetchAll();

$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';
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
        
        /* Sidebar dalam keadaan kecil */
        .sidebar.collapsed {
            width: 70px !important;
            min-width: 70px;
        }

        .sidebar.collapsed .sidebar-header {
            padding: 15px 5px;
            justify-content: center;
            position: relative;
        }

        .sidebar.collapsed .heading-image {
            width: 40px;
            height: 40px;
        }

        .sidebar.collapsed .sidebar-nav span {
            display: none;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px 5px;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.2rem;
        }

        .sidebar.collapsed .sidebar-toggle {
            right: -15px;
            transform: translateY(-50%) rotate(180deg);
        }

        /* Tombol toggle sidebar */
        .sidebar-toggle {
            position: absolute;
            top: 50%;
            right: -15px;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: var(--primary-dark);
        }

        /* Animasi transisi */
        .sidebar {
            transition: all 0.3s ease;
            position: relative;
        }

        .main-content {
            transition: margin-left 0.3s ease;
        }

        /* Tooltip untuk menu yang disembunyikan */
        .nav-link {
            position: relative;
        }

        .tooltip-text {
            display: none;
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            white-space: nowrap;
            z-index: 1000;
            margin-left: 10px;
            font-size: 0.9rem;
        }

        .sidebar.collapsed .nav-link:hover .tooltip-text {
            display: block;
        }
        
        /* Perbaikan layout untuk sidebar yang mengecil */
        .dashboard-fluid {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            min-width: 250px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-fluid">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/Abhiseka.png" alt="Logo Perusahaan" class="heading-image">
                <!-- Tombol toggle sidebar -->
                <button id="sidebarToggle" class="sidebar-toggle">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                    <div class="tooltip-text">Dashboard</div>
                </a>
                
                <?php if ($peran == 'karyawan'): ?>
                    <a href="presensi.php" class="nav-link">
                        <i class="fas fa-fingerprint"></i>
                        <span>Riwayat Presensi</span>
                        <div class="tooltip-text">Riwayat Presensi</div>
                    </a>
                    <a href="izin.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span>Ajukan Izin</span>
                        <div class="tooltip-text">Ajukan Izin</div>
                    </a>
                    <a href="dinas_luar.php" class="nav-link">
                        <i class="fas fa-briefcase"></i>
                        <span>Ajukan Dinas Luar</span>
                        <div class="tooltip-text">Ajukan Dinas Luar</div>
                    </a>
                <?php endif; ?>
                
                <?php if ($peran == 'admin' || $peran == 'superadmin'): ?>
                    <a href="daftar_presensi.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Daftar Presensi</span>
                        <div class="tooltip-text">Daftar Presensi</div>
                    </a>
                    <a href="kelola_izin.php" class="nav-link">
                        <i class="fas fa-tasks"></i>
                        <span>Kelola Izin</span>
                        <div class="tooltip-text">Kelola Izin</div>
                    </a>
                    <a href="kelola_dinas.php" class="nav-link">
                        <i class="fas fa-briefcase"></i>
                        <span>Kelola Dinas</span>
                        <div class="tooltip-text">Kelola Dinas</div>
                    </a>                     
                <?php endif; ?>
                
                <?php if ($peran == 'superadmin'): ?>
                    <a href="kelola_karyawan.php" class="nav-link">
                        <i class="fas fa-users-cog"></i>
                        <span>Kelola Karyawan</span>
                        <div class="tooltip-text">Kelola Karyawan</div>
                    </a>
                    <a href="kelola_event.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Kelola Event</span>
                        <div class="tooltip-text">Kelola Event</div>
                    </a>
                <?php endif; ?>
                
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                    <div class="tooltip-text">Profil</div>
                </a>
                
                <a href="logout.php" class="nav-link logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                    <div class="tooltip-text">Keluar</div>
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
                <div class="row">
                    <!-- Stat Cards -->
                    <?php if ($peran == 'karyawan'): ?>
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h5 class="stat-value">
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM presensi 
                                            WHERE id_user = ? AND tanggal_presensi = CURDATE()");
                                        $stmt->execute([$id_user]);
                                        echo $stmt->fetchColumn() > 0 ? 'Sudah' : 'Belum';
                                        ?>
                                    </h5>
                                    <p class="stat-label">Presensi Hari Ini</p>
                                    <i class="fas fa-fingerprint icon"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h5 class="stat-value">
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM izin 
                                            WHERE id_user = ? AND status = 'disetujui'");
                                        $stmt->execute([$id_user]);
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </h5>
                                    <p class="stat-label">Izin Disetujui</p>
                                    <i class="fas fa-calendar-check icon"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($peran == 'admin' || $peran == 'superadmin'): ?>
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h5 class="stat-value">
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE peran = 'karyawan'");
                                        $stmt->execute();
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </h5>
                                    <p class="stat-label">Total Karyawan</p>
                                    <i class="fas fa-users icon"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h5 class="stat-value">
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM presensi 
                                            WHERE tanggal_presensi = CURDATE()");
                                        $stmt->execute();
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </h5>
                                    <p class="stat-label">Presensi Hari Ini</p>
                                    <i class="fas fa-clipboard-list icon"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h5 class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM izin WHERE status = 'menunggu'");
                                    $stmt->execute();
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h5>
                                <p class="stat-label">Izin Menunggu</p>
                                <i class="fas fa-clock icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pengumuman dan Event -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Pengumuman & Event Terkini</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($events) > 0): ?>
                                    <div class="row">
                                        <?php foreach ($events as $event): ?>
                                            <div class="col-md-12 mb-3">
                                                <div class="card event-card">
                                                    <div class="card-body">
                                                        <span class="event-type <?php echo $event['jenis_event']; ?>">
                                                            <?php echo ucfirst($event['jenis_event']); ?>
                                                        </span>
                                                        <h5><?php echo htmlspecialchars($event['judul']); ?></h5>
                                                        <p class="event-date">
                                                            <?php 
                                                            echo date('d M Y', strtotime($event['tanggal_mulai'])); 
                                                            if ($event['tanggal_mulai'] != $event['tanggal_selesai']) {
                                                                echo ' - ' . date('d M Y', strtotime($event['tanggal_selesai']));
                                                            }
                                                            ?>
                                                        </p>
                                                        <p><?php echo nl2br(htmlspecialchars(substr($event['deskripsi'], 0, 100))); ?>...</p>
                                                        <button class="btn btn-link text-primary p-0" data-bs-toggle="modal" data-bs-target="#eventModal<?php echo $event['id_event']; ?>">
                                                            Baca selengkapnya
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal untuk detail event -->
                                            <div class="modal fade" id="eventModal<?php echo $event['id_event']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><?php echo htmlspecialchars($event['judul']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Jenis:</strong> 
                                                                <span class="event-type <?php echo $event['jenis_event']; ?>">
                                                                    <?php echo ucfirst($event['jenis_event']); ?>
                                                                </span>
                                                            </p>
                                                            <p><strong>Tanggal:</strong> 
                                                                <?php 
                                                                echo date('d M Y', strtotime($event['tanggal_mulai'])); 
                                                                if ($event['tanggal_mulai'] != $event['tanggal_selesai']) {
                                                                    echo ' - ' . date('d M Y', strtotime($event['tanggal_selesai']));
                                                                }
                                                                ?>
                                                            </p>
                                                            <p><strong>Deskripsi:</strong></p>
                                                            <p><?php echo nl2br(htmlspecialchars($event['deskripsi'])); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada pengumuman atau event terkini.</p>
                                <?php endif; ?>
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
        </div>
    </div>

    <!-- Script Section -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        
        // Cek status sidebar dari localStorage
        const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        if (isSidebarCollapsed) {
            sidebar.classList.add('collapsed');
        }
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                
                const isCollapsed = sidebar.classList.contains('collapsed');
                
                // Simpan status di localStorage
                localStorage.setItem('sidebarCollapsed', isCollapsed);
                
                // Ganti ikon tombol
                const icon = this.querySelector('i');
                if (isCollapsed) {
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                }
            });
        }
        
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
</body>
</html>