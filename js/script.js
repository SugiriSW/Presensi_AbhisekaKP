// Fungsi untuk menampilkan tanggal dan waktu
function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    document.getElementById('currentDateTime').textContent = now.toLocaleDateString('id-ID', options);
}

// Update setiap detik
setInterval(updateDateTime, 1000);
updateDateTime(); // Panggil pertama kali

// Fungsi untuk toggle sidebar pada mobile
document.getElementById('toggleSidebar').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
});

// Fungsi untuk menampilkan konfirmasi sebelum menghapus
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus?')) {
            e.preventDefault();
        }
    });
});

// Fungsi untuk menampilkan loading
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

// Fungsi untuk menyembunyikan loading
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Event listener untuk semua form submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        showLoading();
    });
});

// Inisialisasi tooltip Bootstrap
$(function () {
    $('[data-toggle="tooltip"]').tooltip()
});

// Fungsi untuk menangani error AJAX
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    hideLoading();
    alert('Terjadi kesalahan: ' + thrownError);
});