<?php
require 'config.php';
session_start();

if (!isset($_SESSION['id_user'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'ID tidak valid']));
}

$id_dinas = $_GET['id'];

// Ambil data dinas
$stmt = $pdo->prepare("SELECT dl.*, p.nama_lengkap, a.nama_lengkap AS admin_approver, d.path_file 
                      FROM dinas_luar dl
                      JOIN pengguna p ON dl.id_user = p.id_user
                      LEFT JOIN pengguna a ON dl.disetujui_oleh = a.id_user
                      LEFT JOIN dokumen d ON dl.id_dinas_luar = d.id_dokumen
                      WHERE dl.id_dinas_luar = ?");
$stmt->execute([$id_dinas]);
$dinas = $stmt->fetch();

if (!$dinas) {
    die(json_encode(['error' => 'Data tidak ditemukan']));
}

// Format output HTML
$output = '
<div class="detail-dinas">
    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Nama Karyawan:</strong>
            <p>'.htmlspecialchars($dinas['nama_lengkap']).'</p>
        </div>
        <div class="col-md-6">
            <strong>Tanggal:</strong>
            <p>'.date('d M Y', strtotime($dinas['tanggal_mulai'])).' - '.date('d M Y', strtotime($dinas['tanggal_selesai'])).'</p>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <strong>Lokasi/Tujuan:</strong>
            <p>'.htmlspecialchars($dinas['lokasi']).'</p>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <strong>Keperluan/Kegiatan:</strong>
            <p>'.nl2br(htmlspecialchars($dinas['keperluan'])).'</p>
        </div>
    </div>';
    
if (!empty($dinas['path_file'])) {
    $output .= '
    <div class="row mb-3">
        <div class="col-md-12">
            <strong>Dokumen Pendukung:</strong>
            <p><a href="'.$dinas['path_file'].'" target="_blank" class="btn btn-sm btn-primary">
                <i class="fas fa-download"></i> Download Dokumen
            </a></p>
        </div>
    </div>';
}

$output .= '
    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Status:</strong>
            <p>';

$badgeClass = match ($dinas['status']) {
    'Disetujui' => 'success',
    'Ditolak' => 'danger',
    default => 'warning'
};

$output .= '<span class="badge bg-'.$badgeClass.'">'.$dinas['status'].'</span>';

$output .= '
            </p>
        </div>
        <div class="col-md-6">
            <strong>Disetujui Oleh:</strong>
            <p>'.($dinas['admin_approver'] ? htmlspecialchars($dinas['admin_approver']) : '-').'</p>
        </div>
    </div>';

if (!empty($dinas['catatan_admin'])) {
    $output .= '
    <div class="row">
        <div class="col-md-12">
            <strong>Catatan Admin:</strong>
            <p>'.nl2br(htmlspecialchars($dinas['catatan_admin'])).'</p>
        </div>
    </div>';
}

$output .= '</div>';

echo $output;
?>