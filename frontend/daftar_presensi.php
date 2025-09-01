<?php
session_start();
require 'config.php';

// Periksa apakah pengguna sudah login dan memiliki peran admin/superadmin
if (!isset($_SESSION['id_user']) || ($_SESSION['peran'] != 'admin' && $_SESSION['peran'] != 'superadmin')) {
    header('Location: login.php');
    exit();
}

$peran = $_SESSION['peran'];
$id_user = $_SESSION['id_user'];

// Inisialisasi variabel filter
$filter_nama = isset($_GET['filter_nama']) ? $_GET['filter_nama'] : '';
$filter_id = isset($_GET['filter_id']) ? $_GET['filter_id'] : '';
$filter_tanggal = isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Query dasar untuk mendapatkan data presensi
$query = "SELECT p.*, u.nama_lengkap, u.id_user
          FROM presensi p 
          JOIN pengguna u ON p.id_user = u.id_user 
          WHERE 1=1";

// Tambahkan kondisi filter jika ada
$params = [];
if (!empty($filter_nama)) {
    $query .= " AND u.nama_lengkap LIKE ?";
    $params[] = "%$filter_nama%";
}
if (!empty($filter_id)) {
    $query .= " AND u.id_user LIKE ?";
    $params[] = "%$filter_id%";
}
if (!empty($filter_tanggal)) {
    $query .= " AND p.tanggal_presensi = ?";
    $params[] = $filter_tanggal;
}
if (!empty($filter_status) && $filter_status != 'semua') {
    $query .= " AND p.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY p.tanggal_presensi DESC, p.id_presensi DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$presensi_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar nama untuk dropdown filter
$query_nama = "SELECT DISTINCT u.id_user, u.nama_lengkap, u.id_user 
               FROM presensi p 
               JOIN pengguna u ON p.id_user = u.id_user 
               ORDER BY u.nama_lengkap";
$stmt_nama = $pdo->query($query_nama);
$nama_options = $stmt_nama->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar status untuk dropdown
$status_options = ['hadir', 'terlambat', 'izin', 'cuti', 'libur', 'sakit'];

// Ambil data user untuk header
$stmt_user = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt_user->execute([$id_user]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);
$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';

// Set judul halaman
$page_title = "Daftar Presensi";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Presensi - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Root Variables */
        :root {
            --primary-color: #a6cd29;
            --primary-dark: #165329;
            --secondary-color: #f8f9fc;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --border-color: #e3e6f0;
        }

        /* Profile Page Specific Styles */
        .profile-page {
            background-color: var(--secondary-color);
            min-height: 100vh;
        }

        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(5, 136, 7, 0.15);
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid var(--light-color);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid var(--light-color);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            font-size: 1.5rem;
        }

        .avatar-container:hover .avatar-overlay {
            opacity: 1;
            cursor: pointer;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(166, 205, 41, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #8fb722;
            border-color: #8fb722;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .heading-custom {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--primary-dark);
            margin-bottom: 20px;
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-container {
                padding: 20px;
                margin: 15px;
            }
        }
        a.d-block.position-relative {
            text-decoration: none;
            color: inherit;
        }

        .avatar-container {
            position: relative;
            display: inline-block; /* Tambahkan ini */
        }

        .avatar-container:hover {
            transform: scale(1.03);
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
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                    <div class="tooltip-text">Dashboard</div>
                </a>
                
                <?php if ($peran == 'karyawan'): ?>
                    <a href="presensi.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'presensi.php' ? 'active' : ''; ?>">
                        <i class="fas fa-fingerprint"></i>
                        <span>Riwayat Presensi</span>
                        <div class="tooltip-text">Riwayat Presensi</div>
                    </a>
                    <a href="izin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'izin.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Ajukan Izin</span>
                        <div class="tooltip-text">Ajukan Izin</div>
                    </a>
                    <a href="dinas_luar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dinas_luar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-briefcase"></i>
                        <span>Ajukan Dinas Luar</span>
                        <div class="tooltip-text">Ajukan Dinas Luar</div>
                    </a>
                <?php endif; ?>
                
                <?php if ($peran == 'admin' || $peran == 'superadmin'): ?>
                    <a href="daftar_presensi.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'daftar_presensi.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Daftar Presensi</span>
                        <div class="tooltip-text">Daftar Presensi</div>
                    </a>
                    <a href="kelola_izin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_izin.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Kelola Izin</span>
                        <div class="tooltip-text">Kelola Izin</div>
                    </a>
                    <a href="kelola_dinas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_dinas.php' ? 'active' : ''; ?>">
                        <i class="fas fa-briefcase"></i>
                        <span>Kelola Dinas</span>
                        <div class="tooltip-text">Kelola Dinas</div>
                    </a>                     
                <?php endif; ?>
                
                <?php if ($peran == 'superadmin'): ?>
                    <a href="kelola_karyawan.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_karyawan.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i>
                        <span>Kelola Karyawan</span>
                        <div class="tooltip-text">Kelola Karyawan</div>
                    </a>
                    <a href="kelola_event.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_event.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Kelola Event</span>
                        <div class="tooltip-text">Kelola Event</div>
                    </a>
                <?php endif; ?>
                
                <a href="profil.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>">
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
            
            <div class="kelola-izin-container">
                <!-- Search and Filter Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Filter Data Presensi</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <!-- ID Karyawan Dropdown -->
                            <div class="col-md-3">
                                <label for="filter_id" class="form-label">ID Karyawan</label>
                                <select name="filter_id" id="filter_id" class="form-select">
                                    <option value="">Semua Karyawan</option>
                                    <?php foreach ($nama_options as $option): ?>
                                        <option value="<?= htmlspecialchars($option['id_user']) ?>" <?= $filter_id == $option['id_user'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($option['id_user']) ?> - <?= htmlspecialchars($option['nama_lengkap']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Nama Search -->
                            <div class="col-md-3">
                                <label for="filter_nama" class="form-label">Cari Nama</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="filter_nama" id="filter_nama" class="form-control" 
                                           placeholder="Nama karyawan..." value="<?= htmlspecialchars($filter_nama) ?>">
                                </div>
                            </div>
                            
                            <!-- Tanggal Filter -->
                            <div class="col-md-2">
                                <label for="filter_tanggal" class="form-label">Tanggal</label>
                                <input type="date" name="filter_tanggal" id="filter_tanggal" class="form-control" 
                                       value="<?= htmlspecialchars($filter_tanggal) ?>">
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <label for="filter_status" class="form-label">Status</label>
                                <select name="filter_status" id="filter_status" class="form-select">
                                    <option value="semua" <?= $filter_status == 'semua' ? 'selected' : '' ?>>Semua Status</option>
                                    <?php foreach ($status_options as $option): ?>
                                        <option value="<?= $option ?>" <?= $filter_status == $option ? 'selected' : '' ?>>
                                            <?= ucfirst($option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary me-md-2">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <a href="daftar_presensi.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sync-alt me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Table Section -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>ID Karyawan</th>
                                        <th>Nama Karyawan</th>
                                        <th>Tanggal</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($presensi_data) > 0): ?>
                                        <?php foreach ($presensi_data as $index => $row): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($row['id_user']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                                <td><?= date('d-m-Y', strtotime($row['tanggal_presensi'])) ?></td>
                                                <td><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-' ?></td>
                                                <td><?= $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-' ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = '';
                                                    switch ($row['status']) {
                                                        case 'hadir':
                                                            $badge_class = 'bg-success';
                                                            break;
                                                        case 'terlambat':
                                                            $badge_class = 'bg-warning text-dark';
                                                            break;
                                                        case 'izin':
                                                        case 'cuti':
                                                            $badge_class = 'bg-info';
                                                            break;
                                                        case 'libur':
                                                            $badge_class = 'bg-secondary';
                                                            break;
                                                        case 'sakit':
                                                            $badge_class = 'bg-danger';
                                                            break;
                                                        default:
                                                            $badge_class = 'bg-primary';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badge_class ?>"><?= ucfirst($row['status']) ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?= $row['id_presensi'] ?>">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal Detail -->
                                            <div class="modal fade" id="detailModal<?= $row['id_presensi'] ?>" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="detailModalLabel">Detail Presensi</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <strong>ID Karyawan:</strong>
                                                                    <p><?= htmlspecialchars($row['id_user']) ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <strong>Nama:</strong>
                                                                    <p><?= htmlspecialchars($row['nama_lengkap']) ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <strong>Tanggal:</strong>
                                                                    <p><?= date('d-m-Y', strtotime($row['tanggal_presensi'])) ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <strong>Status:</strong>
                                                                    <p><span class="badge <?= $badge_class ?>"><?= ucfirst($row['status']) ?></span></p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <strong>Jam Masuk:</strong>
                                                                    <p><?= $row['jam_masuk'] ? date('H:i:s', strtotime($row['jam_masuk'])) : '-' ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <strong>Jam Pulang:</strong>
                                                                    <p><?= $row['jam_pulang'] ? date('H:i:s', strtotime($row['jam_pulang'])) : '-' ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data presensi ditemukan</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
        
        // Inisialisasi tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
    </script>
</body>
</html>