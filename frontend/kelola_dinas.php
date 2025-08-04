<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['peran'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit();
}
$peran = $_SESSION['peran'];
$user_id = $_SESSION['id_user'];

$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';

$error = '';
$success = '';

// Proses persetujuan/penolakan dinas luar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $id_dinas_luar = $_POST['id_dinas_luar'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $status = 'Disetujui';
        $aktivitas = 'Menyetujui dinas luar ID: ' . $id_dinas_luar;
    } else {
        $status = 'Ditolak';
        $aktivitas = 'Menolak dinas luar ID: ' . $id_dinas_luar;
    }
    
    try {
        $sql = "UPDATE dinas_luar SET 
            status = ?, disetujui_oleh = ?, updated_at = NOW()
            WHERE id_dinas_luar = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $user_id, $id_dinas_luar]);
        
        // Catat log
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $stmt->execute([$user_id, $aktivitas]);
        
        $success = "Dinas luar berhasil diproses!";
    } catch (PDOException $e) {
        $error = "Gagal memproses dinas luar: " . $e->getMessage();
    }
}

// Filter status
$status_filter = $_GET['status'] ?? 'Menunggu';

// Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$sql_count = "SELECT COUNT(*) FROM dinas_luar WHERE status = ?";
$stmt = $pdo->prepare($sql_count);
$stmt->execute([$status_filter]);
$total_data = $stmt->fetchColumn();
$total_pages = ceil($total_data / $limit);

// Ambil daftar dinas luar berdasarkan filter
$sql = "SELECT dl.*, p.nama_lengkap, d.path_file 
    FROM dinas_luar dl
    JOIN pengguna p ON dl.id_user = p.id_user
    LEFT JOIN dokumen d ON dl.id_dinas_luar = d.id_dokumen
    WHERE dl.status = ?
    ORDER BY dl.created_at DESC
    LIMIT ? OFFSET ?";
    
$stmt = $pdo->prepare($sql);
$stmt->execute([$status_filter, $limit, $offset]);
$daftar_dinas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dinas Luar - Sistem Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
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
                    <a href="kelola_dinas.php" class="nav-link active">
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
            <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
                <div class="navbar-title">Kelola Dinas Luar</div>
                <div class="navbar-user">
                    <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">                    
                    <span><?php echo $_SESSION['nama_lengkap']; ?></span>
                </div>
            </nav>

            <!-- Content -->
            <div class="content-wrapper">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="btn-group">
                            <a href="kelola_dinas.php?status=Menunggu&page=1" 
                               class="btn <?= $status_filter == 'Menunggu' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-clock me-1"></i> Menunggu
                            </a>
                            <a href="kelola_dinas.php?status=Disetujui&page=1" 
                               class="btn <?= $status_filter == 'Disetujui' ? 'btn-success' : 'btn-outline-success'; ?>">
                                <i class="fas fa-check me-1"></i> Disetujui
                            </a>
                            <a href="kelola_dinas.php?status=Ditolak&page=1" 
                               class="btn <?= $status_filter == 'Ditolak' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                <i class="fas fa-times me-1"></i> Ditolak
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Nama Karyawan</th>
                                        <th width="20%">Tanggal</th>
                                        <th width="20%">Lokasi</th>
                                        <th width="15%">Status</th>
                                        <th width="15%">Disetujui Oleh</th>
                                        <th width="15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftar_dinas as $dinas): 
                                        // Ambil nama admin yang menyetujui
                                        $disetujui_oleh = '-';
                                        if ($dinas['disetujui_oleh']) {
                                            $stmt = $pdo->prepare("SELECT nama_lengkap FROM pengguna WHERE id_user = ?");
                                            $stmt->execute([$dinas['disetujui_oleh']]);
                                            $admin = $stmt->fetch();
                                            $disetujui_oleh = $admin ? $admin['nama_lengkap'] : '-';
                                        }
                                    ?>
                                    <tr>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center">
                                                <div class="ms-2">
                                                    <div class="fw-semibold"><?= htmlspecialchars($dinas['nama_lengkap']); ?></div>
                                                    <small class="text-muted">ID: <?= $dinas['id_dinas_luar']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex flex-column">
                                                <span><?= date('d M Y', strtotime($dinas['tanggal_mulai'])); ?></span>
                                                <small class="text-muted">s/d <?= date('d M Y', strtotime($dinas['tanggal_selesai'])); ?></small>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <?= htmlspecialchars($dinas['lokasi']); ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php if ($dinas['status'] == 'Menunggu'): ?>
                                                <span class="badge bg-warning bg-opacity-15 text-white">
                                                    <i class="fas fa-clock me-1"></i> Menunggu
                                                </span>
                                            <?php elseif ($dinas['status'] == 'Disetujui'): ?>
                                                <span class="badge bg-success bg-opacity-15 text-white">
                                                    <i class="fas fa-check-circle me-1"></i> Disetujui
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-15 text-white">
                                                    <i class="fas fa-times-circle me-1"></i> Ditolak
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <small><?= $disetujui_oleh ?></small>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-info btn-detail-dinas" 
                                                        data-id="<?= $dinas['id_dinas_luar']; ?>" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($dinas['status'] == 'Menunggu'): ?>
                                                    <button class="btn btn-outline-success btn-approve-dinas" 
                                                            data-id="<?= $dinas['id_dinas_luar']; ?>" title="Setujui">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-reject-dinas" 
                                                            data-id="<?= $dinas['id_dinas_luar']; ?>" title="Tolak">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($daftar_dinas)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox me-2"></i>Tidak ada data dinas luar yang ditemukan
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($daftar_dinas)): ?>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Menampilkan <?= count($daftar_dinas); ?> dari <?= $total_data; ?> data dinas luar
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="kelola_dinas.php?status=<?= $status_filter; ?>&page=<?= $page - 1; ?>">
                                                Sebelumnya
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#" tabindex="-1">Sebelumnya</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="kelola_dinas.php?status=<?= $status_filter; ?>&page=<?= $i; ?>">
                                                <?= $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="kelola_dinas.php?status=<?= $status_filter; ?>&page=<?= $page + 1; ?>">
                                                Selanjutnya
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <a class="page-link" href="#">Selanjutnya</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail Dinas -->
    <div class="modal fade" id="modalDetailDinas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Detail Dinas Luar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detail-dinas-body">
                    <!-- Konten akan diisi oleh JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Approve/Reject Dinas -->
    <div class="modal fade" id="modalActionDinas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" id="id_dinas_luar" name="id_dinas_luar">
                    <input type="hidden" id="action" name="action">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalActionTitle">Aksi Dinas Luar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn" id="modalActionBtn">
                            <!-- Tombol akan diisi oleh JavaScript -->
                        </button>
                    </div>
                </form>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Tampilkan detail dinas
        $('.btn-detail-dinas').click(function() {
            const dinasId = $(this).data('id');
            
            $.ajax({
                url: 'ajax_get_dinas.php',
                type: 'GET',
                data: { id: dinasId },
                success: function(response) {
                    $('#detail-dinas-body').html(response);
                    $('#modalDetailDinas').modal('show');
                },
                error: function() {
                    alert('Gagal memuat detail dinas luar');
                }
            });
        });
        
        // Tombol approve dinas
        $('.btn-approve-dinas').click(function() {
            const dinasId = $(this).data('id');
            
            $('#id_dinas_luar').val(dinasId);
            $('#action').val('approve');
            $('#modalActionTitle').text('Setujui Dinas Luar');
            $('#modalActionBtn').removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check me-1"></i> Setujui');
            
            $('#modalActionDinas').modal('show');
        });
        
        // Tombol reject dinas
        $('.btn-reject-dinas').click(function() {
            const dinasId = $(this).data('id');
            
            $('#id_dinas_luar').val(dinasId);
            $('#action').val('reject');
            $('#modalActionTitle').text('Tolak Dinas Luar');
            $('#modalActionBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-times me-1"></i> Tolak');
            
            $('#modalActionDinas').modal('show');
        });
    });
    </script>
</body>
</html>