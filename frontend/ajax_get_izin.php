<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_user'])) {
    die(json_encode(['error' => 'Akses ditolak']));
}

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'ID izin tidak valid']));
}

$izin_id = $_GET['id'];
$user_id = $_SESSION['id_user'];

// Ambil data izin dan nama karyawan
$stmt = $pdo->prepare("
    SELECT i.*, 
           p.nama_lengkap,
           a.nama_lengkap AS admin_approver
    FROM izin i
    JOIN pengguna p ON i.id_user = p.id_user
    LEFT JOIN pengguna a ON i.disetujui_oleh = a.id_user
    WHERE i.id_izin = ?
      AND (i.id_user = ? OR ? IN (SELECT id_user FROM pengguna WHERE peran IN ('admin', 'superadmin')))
");
$stmt->execute([$izin_id, $user_id, $user_id]);
$izin = $stmt->fetch();

if (!$izin) {
    die(json_encode(['error' => 'Izin tidak ditemukan atau Anda tidak memiliki akses']));
}

// Format tanggal
$tanggal_mulai = date('d M Y', strtotime($izin['tanggal_mulai']));
$tanggal_selesai = date('d M Y', strtotime($izin['tanggal_selesai']));
$dibuat_pada = date('d M Y H:i', strtotime($izin['dibuat_pada']));
$jenis_izin = ucfirst(str_replace('_', ' ', $izin['jenis_izin']));

// Status badge
$badgeClass = match ($izin['status']) {
    'disetujui' => 'success',
    'ditolak' => 'danger',
    default => 'warning'
};

$status_badge = '<span class="badge bg-'.$badgeClass.'">'.ucfirst($izin['status']).'</span>';

// Mulai output HTML
$output = '
<div class="detail-izin">
    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Nama Karyawan:</strong>
            <p>'.htmlspecialchars($izin['nama_lengkap']).'</p>
        </div>
        <div class="col-md-6">
            <strong>Jenis Izin:</strong>
            <p>'.$jenis_izin.'</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Tanggal:</strong>
            <p>'.$tanggal_mulai.' - '.$tanggal_selesai.'</p>
        </div>
        <div class="col-md-6">
            <strong>Diajukan Pada:</strong>
            <p>'.$dibuat_pada.'</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <strong>Alasan:</strong>
            <p>'.nl2br(htmlspecialchars($izin['alasan'])).'</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Status:</strong>
            <p>'.$status_badge.'</p>
        </div>';

if ($izin['status'] != 'menunggu' && $izin['admin_approver']) {
    $disetujui_pada = date('d M Y H:i', strtotime($izin['disetujui_pada']));
    $output .= '
        <div class="col-md-6">
            <strong>Disetujui Oleh:</strong>
            <p>'.htmlspecialchars($izin['admin_approver']).'</p>
            <strong>Pada:</strong>
            <p>'.$disetujui_pada.'</p>
        </div>';
}
$output .= '</div>';

// Dokumen pendukung (jika ada)
if (!empty($izin['path_file'])) {
    $output .= '
    <div class="row mb-3">
        <div class="col-md-12">
            <strong>Dokumen Pendukung:</strong>
            <p>
                <a href="'.htmlspecialchars($izin['path_file']).'" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fas fa-download"></i> Download Dokumen
                </a>
            </p>
        </div>
    </div>';
}

// Catatan admin
if (!empty($izin['catatan_admin'])) {
    $output .= '
    <div class="row mb-3">
        <div class="col-md-12">
            <strong>Catatan Admin:</strong>
            <p>'.nl2br(htmlspecialchars($izin['catatan_admin'])).'</p>
        </div>
    </div>';
}

$output .= '</div>';

echo $output;
?>
