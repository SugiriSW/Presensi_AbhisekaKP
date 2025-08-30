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

// Di bagian proses persetujuan/penolakan izin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $izin_id = $_POST['izin_id'];
    $action = $_POST['action'];
    $catatan = $_POST['catatan'] ?? '';
    
    if ($action == 'approve') {
        $status = 'disetujui';
        $aktivitas = 'Menyetujui izin ID: ' . $izin_id;
        
        // Jika kolom disetujui_pada ada
        $sql = "UPDATE izin SET 
            status = ?, disetujui_oleh = ?, catatan_admin = ?, disetujui_pada = NOW()
            WHERE id_izin = ?";
    } else {
        $status = 'ditolak';
        $aktivitas = 'Menolak izin ID: ' . $izin_id;
        
        // Jika kolom disetujui_pada tidak ada
        $sql = "UPDATE izin SET 
            status = ?, disetujui_oleh = ?, catatan_admin = ?
            WHERE id_izin = ?";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        
        // Sesuaikan parameter execute berdasarkan SQL yang digunakan
        if (strpos($sql, 'disetujui_pada') !== false) {
            $stmt->execute([$status, $user_id, $catatan, $izin_id]);
        } else {
            $stmt->execute([$status, $user_id, $catatan, $izin_id]);
        }
        
        // Catat log
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) 
            VALUES (?, ?)");
        $stmt->execute([$user_id, $aktivitas]);
        
        $success = "Izin berhasil diproses!";
    } catch (PDOException $e) {
        $error = "Gagal memproses izin: " . $e->getMessage();
    }
}

// Filter status
$status_filter = $_GET['status'] ?? 'menunggu';

// Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$sql_count = "SELECT COUNT(*) FROM izin WHERE status = ?";
$stmt = $pdo->prepare($sql_count);
$stmt->execute([$status_filter]);
$total_data = $stmt->fetchColumn();
$total_pages = ceil($total_data / $limit);

// Ambil daftar izin berdasarkan filter
$sql = "SELECT i.*, p.nama_lengkap 
    FROM izin i
    JOIN pengguna p ON i.id_user = p.id_user
    WHERE i.status = ?
    ORDER BY i.tanggal_mulai DESC
    LIMIT ? OFFSET ?";
    
$stmt = $pdo->prepare($sql);
$stmt->execute([$status_filter, $limit, $offset]);
$daftar_izin = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Izin - Sistem Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <a href="kelola_izin.php" class="nav-link active">
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
            <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
                <div class="navbar-user">
                    <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">                    <span><?php echo $_SESSION['nama_lengkap']; ?></span>
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
                            <a href="kelola_izin.php?status=Menunggu&page=1" 
                               class="btn <?= $status_filter == 'Menunggu' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-clock me-1"></i> Menunggu
                            </a>
                            <a href="kelola_izin.php?status=Disetujui&page=1" 
                               class="btn <?= $status_filter == 'Disetujui' ? 'btn-success' : 'btn-outline-success'; ?>">
                                <i class="fas fa-check me-1"></i> Disetujui
                            </a>
                            <a href="kelola_izin.php?status=Ditolak&page=1" 
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
                                        <th width="15%">Jenis Izin</th>
                                        <th width="15%">Status</th>
                                        <th width="15%">Disetujui Oleh</th>
                                        <th width="15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftar_izin as $izin): 
                                        // Ambil nama admin yang menyetujui
                                        $disetujui_oleh = '-';
                                        if ($izin['disetujui_oleh']) {
                                            $stmt = $pdo->prepare("SELECT nama_lengkap FROM pengguna WHERE id_user = ?");
                                            $stmt->execute([$izin['disetujui_oleh']]);
                                            $admin = $stmt->fetch();
                                            $disetujui_oleh = $admin ? $admin['nama_lengkap'] : '-';
                                        }
                                    ?>
                                    <tr>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center">
                                                <div class="ms-2">
                                                    <div class="fw-semibold"><?= htmlspecialchars($izin['nama_lengkap']); ?></div>
                                                    <small class="text-muted">ID: <?= $izin['id_izin']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex flex-column">
                                                <span><?= date('d M Y', strtotime($izin['tanggal_mulai'])); ?></span>
                                                <small class="text-muted">s/d <?= date('d M Y', strtotime($izin['tanggal_selesai'])); ?></small>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <?= ucfirst(str_replace('_', ' ', $izin['jenis_izin'])); ?>
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <?php if ($izin['status'] == 'menunggu'): ?>
                                                <span class="badge bg-warning bg-opacity-15 text-white">
                                                    <i class="fas fa-clock me-1"></i> Menunggu
                                                </span>
                                            <?php elseif ($izin['status'] == 'disetujui'): ?>
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
                                                <button class="btn btn-outline-info btn-detail-izin" 
                                                        data-id="<?= $izin['id_izin']; ?>" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($izin['status'] == 'menunggu'): ?>
                                                    <button class="btn btn-outline-success btn-approve-izin" 
                                                            data-id="<?= $izin['id_izin']; ?>" title="Setujui">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-reject-izin" 
                                                            data-id="<?= $izin['id_izin']; ?>" title="Tolak">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($daftar_izin)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox me-2"></i>Tidak ada data izin yang ditemukan
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($daftar_izin)): ?>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Menampilkan <?= count($daftar_izin); ?> dari <?= $total_data; ?> data izin
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="kelola_izin.php?status=<?= $status_filter; ?>&page=<?= $page - 1; ?>">
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
                                            <a class="page-link" href="kelola_izin.php?status=<?= $status_filter; ?>&page=<?= $i; ?>">
                                                <?= $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="kelola_izin.php?status=<?= $status_filter; ?>&page=<?= $page + 1; ?>">
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
    
    <!-- Modal Detail Izin -->
    <div class="modal fade" id="modalDetailIzin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Detail Izin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detail-izin-body">
                    <!-- Konten akan diisi oleh JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Approve/Reject Izin -->
    <div class="modal fade" id="modalActionIzin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" id="izin_id" name="izin_id">
                    <input type="hidden" id="action" name="action">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalActionTitle">Aksi Izin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3"></textarea>
                        </div>
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
        // Tampilkan detail izin
        $('.btn-detail-izin').click(function() {
            const izinId = $(this).data('id');
            
            $.ajax({
                url: 'ajax_get_izin.php',
                type: 'GET',
                data: { id: izinId },
                success: function(response) {
                    $('#detail-izin-body').html(response);
                    $('#modalDetailIzin').modal('show');
                },
                error: function() {
                    alert('Gagal memuat detail izin');
                }
            });
        });
        
        
        // Tombol approve izin
        $('.btn-approve-izin').click(function() {
            const izinId = $(this).data('id');
            
            $('#izin_id').val(izinId);
            $('#action').val('approve');
            $('#modalActionTitle').text('Setujui Izin');
            $('#modalActionBtn').removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-check me-1"></i> Setujui');
            
            $('#modalActionIzin').modal('show');
        });
        
        // Tombol reject izin
        $('.btn-reject-izin').click(function() {
            const izinId = $(this).data('id');
            
            $('#izin_id').val(izinId);
            $('#action').val('reject');
            $('#modalActionTitle').text('Tolak Izin');
            $('#modalActionBtn').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-times me-1"></i> Tolak');
            
            $('#modalActionIzin').modal('show');
        });
    });
    </script>
</body>
</html>