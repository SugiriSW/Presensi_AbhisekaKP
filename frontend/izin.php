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
$peran = $_SESSION['peran'] ?? ''; // <- Tambahkan ini untuk hilangkan error
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto_path = $_SESSION['foto_path'] ?? 'images/default-profile.jpg';

// Inisialisasi variabel pesan
$error = '';
$success = '';

$id_izin = $pdo->lastInsertId();

// Handle form pengajuan izin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_izin = $_POST['jenis_izin'] ?? '';
    $alasan = $_POST['alasan'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';

    // Validasi tanggal
    if (strtotime($tanggal_selesai) < strtotime($tanggal_mulai)) {
        $error = "Tanggal selesai tidak boleh sebelum tanggal mulai.";
    } else {
        try {
            // Insert data izin terlebih dahulu
            $stmt = $pdo->prepare("INSERT INTO izin 
                (id_user, jenis_izin, alasan, tanggal_mulai, tanggal_selesai, status) 
                VALUES (?, ?, ?, ?, ?, 'menunggu')");
            $stmt->execute([$id_user, $jenis_izin, $alasan, $tanggal_mulai, $tanggal_selesai]);
            
            // Dapatkan ID izin yang baru saja diinsert
            $id_izin = $pdo->lastInsertId();
            
            // Upload file foto pendukung (jika ada)
            if (!empty($_FILES['dokumen_pendukung']['name'])) {
                if ($_FILES['dokumen_pendukung']['error'] === UPLOAD_ERR_OK) {
                    $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                    $ext = strtolower(pathinfo($_FILES['dokumen_pendukung']['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($ext, $allowed_ext)) {
                        throw new Exception("Hanya file PDF, DOC, JPG, PNG yang diizinkan");
                    }

                    // Batasi ukuran file (misal 5MB)
                    if ($_FILES['dokumen_pendukung']['size'] > 5242880) {
                        throw new Exception("Ukuran file maksimal 5MB");
                    }

                    $target_dir = 'uploads/izin/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $nama_file = 'izin_' . $id_izin . '_' . uniqid() . '.' . $ext;
                    $path_file = $target_dir . $nama_file;
                    
                    if (!move_uploaded_file($_FILES['dokumen_pendukung']['tmp_name'], $path_file)) {
                        throw new Exception("Gagal mengupload dokumen");
                    }

                    // Simpan info dokumen ke tabel dokumen
                    $stmt = $pdo->prepare("INSERT INTO dokumen 
                        (id_izin, id_user, nama_file, path_file, jenis_dokumen) 
                        VALUES (?, ?, ?, ?, 'izin')");
                    $stmt->execute([$id_izin, $id_user, $_FILES['dokumen_pendukung']['name'], $path_file]);
                } else {
                    throw new Exception("Error upload file: " . $_FILES['dokumen_pendukung']['error']);
                }
            }
            
            $success = "Pengajuan izin berhasil dikirim!";
            
            // Refresh daftar izin
            $stmt = $pdo->prepare("SELECT * FROM izin WHERE id_user = ? ORDER BY tanggal_mulai DESC");
            $stmt->execute([$id_user]);
            $daftar_izin = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Ambil daftar izin pengguna ini
$stmt = $pdo->prepare("SELECT * FROM izin WHERE id_user = ? ORDER BY tanggal_mulai DESC");
$stmt->execute([$id_user]);
$daftar_izin = $stmt->fetchAll();

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
    <title>Pengajuan Izin - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<style>
    /* Tambahkan di file CSS Anda */
.modal-content {
    border-radius: 10px;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    border-top: none;
    padding-top: 0;
}

.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: 500;
}

.badge-success {
    background-color: #28a745;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.badge-danger {
    background-color: #dc3545;
}

.img-fluid {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin-top: 10px;
    border: 1px solid #eee;
}
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
                    <a href="izin.php" class="nav-link active">
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
        <div class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4 py-3">

            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
                <div class="navbar-title">Pengajuan Izin</div>
                <div class="navbar-user">
                    <img src="<?= $foto_path ?>" alt="User" class="rounded-circle me-2" width="40" height="40">                    <span><?php echo $_SESSION['nama_lengkap']; ?></span>
                </div>
            </nav>
            <!-- Content -->
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Ajukan Izin</h5>
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
                                        <label for="jenis_izin">Jenis Izin</label>
                                        <select class="form-control" id="jenis_izin" name="jenis_izin" required>
                                            <option value="sakit">Sakit</option>
                                            <option value="cuti">Cuti</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="alasan">Alasan</label>
                                        <textarea class="form-control" id="alasan" name="alasan" rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_mulai">Tanggal Mulai</label>
                                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_selesai">Tanggal Selesai</label>
                                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
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
                                    
                                    <button type="submit" class="btn btn-primary">Ajukan Izin</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Riwayat Izin</h5>
                            </div>
                            <div class="card-body">
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th scope="col">Tanggal</th>
                                        <th scope="col">Jenis Izin</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($daftar_izin)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada pengajuan izin.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($daftar_izin as $izin): ?>
                                            <tr>
                                                <td>
                                                    <?= date('d M Y', strtotime($izin['tanggal_mulai'])); ?> - 
                                                    <?= date('d M Y', strtotime($izin['tanggal_selesai'])); ?>
                                                </td>
                                                <td><?= ucfirst(str_replace('_', ' ', $izin['jenis_izin'])); ?></td>
                                                <td>
                                                    <?php
                                                        $badgeClass = match ($izin['status']) {
                                                            'disetujui' => 'success',
                                                            'ditolak' => 'danger',
                                                            default => 'warning'
                                                        };
                                                    ?>
                                                    <span class="badge badge-<?= $badgeClass ?>">
                                                        <?= ucfirst($izin['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-info btn-detail-izin"
                                                            data-id="<?= $izin['id_izin'] ?>">
                                                        <i class="fas fa-eye me-1"></i> Detail
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
    
    <!-- Modal Detail Izin -->
<!-- Modal Detail Izin -->
<div class="modal fade" id="modalDetailIzin" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body" id="detail-izin-body">
                <!-- Konten akan diisi oleh JavaScript -->
            </div>
            <div class="modal-footer" id="modal-footer-izin" style="display: none;">
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
    // Fungsi untuk zoom gambar
function zoomImage(element) {
    var modal = document.getElementById("modalZoom");
    var modalImg = document.getElementById("imgZoom");
    
    modal.style.display = "block";
    modalImg.src = element.src;
    
    // Tambahkan event listener untuk close
    var span = document.getElementsByClassName("close-zoom")[0];
    span.onclick = function() {
        modal.style.display = "none";
    }
    
    // Close ketika klik di luar gambar
    modal.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
}

function closeZoom() {
    document.getElementById("modalZoom").style.display = "none";
}
$(document).ready(function() {
    // Validasi tanggal
    $('#tanggal_mulai').attr('min', new Date().toISOString().split('T')[0]);
    $('#tanggal_mulai').change(function() {
        $('#tanggal_selesai').attr('min', $(this).val());
    });
    
    // Handle detail izin dengan event delegation
    $(document).on('click', '.btn-detail-izin', function() {
        const izinId = $(this).data('id');
        const modal = $('#modalDetailIzin');
        const modalFooter = $('#modal-footer-izin');
        
        // Sembunyikan footer modal
        modalFooter.hide();
        
        // Tampilkan loading state
        modal.find('.modal-body').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Memuat data izin...</p>
            </div>
        `);
        modal.modal('show');
        
        // Ambil data izin via AJAX
        $.ajax({
            url: 'ajax_get_izin.php',
            type: 'GET',
            data: { id: izinId },
            success: function(response) {
                modal.find('.modal-body').html(response);
                // Tampilkan footer setelah data selesai dimuat
                modalFooter.show();
            },
            error: function(xhr, status, error) {
                modal.find('.modal-body').html(`
                    <div class="alert alert-danger">
                        Gagal memuat detail izin. Silakan coba lagi.
                    </div>
                `);
                // Tetap tampilkan footer meskipun error
                modalFooter.show();
                console.error("AJAX Error:", status, error);
            }
        });
    });
    
    // Tutup modal ketika klik di luar modal
    $(document).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            $('#modalDetailIzin').modal('hide');
        }
    });
});
// Menampilkan nama file yang dipilih
document.getElementById('dokumen_pendukung').addEventListener('change', function(e) {
    const fileNameDisplay = document.getElementById('file-name-display');
    if (this.files.length > 0) {
        fileNameDisplay.textContent = this.files[0].name;
    } else {
        fileNameDisplay.textContent = 'Belum ada file dipilih';
    }
});
</script>
</body>
</html>