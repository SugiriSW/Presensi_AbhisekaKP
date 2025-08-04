<?php
session_start();
require 'config.php';

// Cek login dan role
if (!isset($_SESSION['id_user']) || $_SESSION['peran'] !== 'karyawan') {
    header('Location: login.php');
    exit();
}

// Ambil data dari session
$id_user = $_SESSION['id_user'];
$peran = $_SESSION['peran'] ?? '';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto_path = $_SESSION['foto_path'] ?? 'images/default-profile.jpg';

// Inisialisasi variabel pesan
$error = '';
$success = '';

// Handle form pengajuan dinas luar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lokasi = $_POST['lokasi'] ?? '';
    $keperluan = $_POST['keperluan'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';

    // Validasi tanggal
    if (strtotime($tanggal_selesai) < strtotime($tanggal_mulai)) {
        $error = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
    } else {
        try {
            // Mulai transaksi
            $pdo->beginTransaction();

            // Simpan data dinas luar ke database
            $stmt = $pdo->prepare("INSERT INTO dinas_luar 
                (id_user, tanggal_mulai, tanggal_selesai, lokasi, keperluan) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_user, $tanggal_mulai, $tanggal_selesai, $lokasi, $keperluan]);
            
            $id_dinas_luar = $pdo->lastInsertId();

            // Handle file upload jika ada
            if (!empty($_FILES['dokumen_pendukung']['name']) && $_FILES['dokumen_pendukung']['error'] === UPLOAD_ERR_OK) {
                $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo($_FILES['dokumen_pendukung']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowed_ext)) {
                    throw new Exception("Hanya file PDF, DOC, JPG, PNG yang diizinkan");
                }

                $target_dir = 'uploads/dinas_luar/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $nama_file = 'dinas_' . $id_dinas_luar . '_' . uniqid() . '.' . $ext;
                $path_file = $target_dir . $nama_file;
                
                if (!move_uploaded_file($_FILES['dokumen_pendukung']['tmp_name'], $path_file)) {
                    throw new Exception("Gagal mengupload dokumen");
                }

                // Simpan info dokumen ke tabel dokumen
                $stmt = $pdo->prepare("INSERT INTO dokumen 
                    (id_user, nama_file, path_file) 
                    VALUES (?, ?, ?)");
                $stmt->execute([$id_user, $_FILES['dokumen_pendukung']['name'], $path_file]);
            }

            // Catat log aktivitas
            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, 'Mengajukan dinas luar')");
            $stmt->execute([$id_user]);

            $pdo->commit();
            $success = "Pengajuan dinas luar berhasil dikirim.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Ambil daftar dinas luar pengguna ini
$stmt = $pdo->prepare("SELECT dl.*, d.path_file 
                      FROM dinas_luar dl
                      LEFT JOIN dokumen d ON dl.id_dinas_luar = d.id_dokumen
                      WHERE dl.id_user = ? 
                      ORDER BY dl.created_at DESC");
$stmt->execute([$id_user]);
$daftar_dinas = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

$foto_path = !empty($user['foto_path']) ? $user['foto_path'] : '../images/default-profile.jpg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Dinas Luar - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .file-upload-label {
            display: block;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-label:hover {
            background-color: #f8f9fa;
            border-color: #aaa;
        }
        .file-name {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 sidebar">
        <div class="sidebar-header text-center">
            <img src="../images/Abhiseka.png" alt="Presensi Abhiseka" class="heading-image">
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <?php if ($peran == 'karyawan'): ?>
                <a href="presensi.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'presensi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-fingerprint"></i>
                    <span>Riwayat Presensi</span>
                </a>
                <a href="izin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'izin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Ajukan Izin</span>
                </a>
                <a href="dinas_luar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dinas_luar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i>
                    <span>Ajukan Dinas Luar</span>
                </a>
            <?php endif; ?>
            
            <?php if (in_array($peran, ['admin', 'superadmin'])): ?>
                <a href="daftar_presensi.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'daftar_presensi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Daftar Presensi</span>
                </a>
                <a href="kelola_izin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_izin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Kelola Izin</span>
                </a>
                <a href="kelola_dinas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_dinas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Kelola Dinas</span>
                </a>
            <?php endif; ?>
            
            <?php if ($peran == 'superadmin'): ?>
                <a href="kelola_karyawan.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_karyawan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Kelola Karyawan</span>
                </a>
                <a href="kelola_event.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kelola_event.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Kelola Event</span>
                </a>
            <?php endif; ?>
            
            <a href="profil.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>">
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
            <div class="navbar-title">Pengajuan Dinas Luar</div>
            <div class="navbar-user">
                <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">
                <span><?php echo $_SESSION['nama_lengkap']; ?></span>
            </div>
        </nav>
        
        <!-- Content -->
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ajukan Dinas Luar</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="lokasi">Lokasi/Tujuan Dinas</label>
                                <input type="text" class="form-control" id="lokasi" name="lokasi" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="keperluan">Keperluan/Kegiatan</label>
                                <textarea class="form-control" id="keperluan" name="keperluan" rows="3" required></textarea>
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
                                <label>Dokumen Pendukung (Optional)</label>
                                <label for="dokumen_pendukung" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                                    <span>Klik untuk memilih file</span>
                                    <div class="file-name" id="file-name-display">Belum ada file dipilih</div>
                                </label>
                                <input type="file" class="d-none" id="dokumen_pendukung" name="dokumen_pendukung" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                            
                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="fas fa-paper-plane me-1"></i> Ajukan Dinas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Riwayat Dinas Luar</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover modern-table mb-0">
                                <thead class="thead-dark">
                                    <tr>
                                        <th scope="col">Tanggal</th>
                                        <th scope="col">Lokasi</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($daftar_dinas)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada pengajuan dinas luar.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($daftar_dinas as $dinas): ?>
                                            <tr>
                                                <td>
                                                    <?= date('d M Y', strtotime($dinas['tanggal_mulai'])); ?> - 
                                                    <?= date('d M Y', strtotime($dinas['tanggal_selesai'])); ?>
                                                </td>
                                                <td><?= htmlspecialchars($dinas['lokasi']) ?></td>
                                                <td>
                                                    <?php
                                                        $badgeClass = match ($dinas['status']) {
                                                            'Disetujui' => 'success',
                                                            'Ditolak' => 'danger',
                                                            default => 'warning'
                                                        };
                                                    ?>
                                                    <span class="badge badge-<?= $badgeClass ?>">
                                                        <?= $dinas['status'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Dinas -->
<div class="modal fade" id="modalDetailDinas" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Dinas Luar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detail-dinas-body">
                <!-- Konten akan diisi oleh JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
    // Tanggal mulai tidak boleh sebelum hari ini
    $('#tanggal_mulai').attr('min', new Date().toISOString().split('T')[0]);
    
    // Tanggal selesai tidak boleh sebelum tanggal mulai
    $('#tanggal_mulai').change(function() {
        $('#tanggal_selesai').attr('min', $(this).val());
    });
    
    // Tampilkan nama file yang dipilih
    $('#dokumen_pendukung').change(function() {
        var fileName = $(this).val().split('\\').pop();
        $('#file-name-display').text(fileName || 'Belum ada file dipilih');
    });
    
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
                alert('Gagal memuat detail dinas');
            }
        });
    });
});
</script>
</body>
</html>