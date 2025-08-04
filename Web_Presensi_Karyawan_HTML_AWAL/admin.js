document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initCharts();
    
    // Initialize navigation
    initNavigation();
    
    // Initialize forms
    initForms();
    
    // Initialize modals
    initModals();
    
    // Initialize tables
    initTables();
    
    // Initialize report type switcher
    initReportTypeSwitcher();
});

// Chart Initialization
function initCharts() {
    // Attendance Trend Chart
    const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
    const attendanceTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: Array.from({length: 30}, (_, i) => `${i+1} Jun`),
            datasets: [
                {
                    label: 'Hadir',
                    data: Array.from({length: 30}, () => Math.floor(Math.random() * 100) + 50),
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.05)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Terlambat',
                    data: Array.from({length: 30}, () => Math.floor(Math.random() * 20) + 5),
                    borderColor: '#f6c23e',
                    backgroundColor: 'rgba(246, 194, 62, 0.05)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Tidak Hadir',
                    data: Array.from({length: 30}, () => Math.floor(Math.random() * 15) + 1),
                    borderColor: '#e74a3b',
                    backgroundColor: 'rgba(231, 74, 59, 0.05)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' orang';
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });

    // Attendance Pie Chart
    const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
    const attendancePieChart = new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Hadir', 'Terlambat', 'Tidak Hadir'],
            datasets: [{
                data: [78.5, 9.5, 12],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#dda20a', '#be2617'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            return `${label}: ${value}%`;
                        }
                    }
                }
            },
            cutout: '70%',
        },
    });

    // Chart export functionality
    document.getElementById('exportChart').addEventListener('click', function() {
        const link = document.createElement('a');
        link.download = 'chart-export.png';
        link.href = trendCtx.canvas.toDataURL('image/png');
        link.click();
    });

    document.getElementById('printChart').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print Chart</title></head><body>');
        printWindow.document.write('<img src="' + trendCtx.canvas.toDataURL('image/png') + '">');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    });
}

// Navigation Initialization
function initNavigation() {
    // Show dashboard by default
    document.querySelector('.dashboard-section').classList.remove('d-none');
    
    // Handle sidebar navigation clicks
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Hide all sections
            document.querySelectorAll('main section').forEach(section => {
                section.classList.add('d-none');
            });
            
            // Show selected section
            const target = this.getAttribute('href').substring(1);
            document.querySelector(`.${target}-section`).classList.remove('d-none');
            
            // For mobile, close sidebar after click
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });
    });
    
    // Toggle sidebar for mobile
    document.querySelector('.navbar-toggler').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });
}

// Modal Initialization
function initModals() {
    // Add employee modal
    document.getElementById('saveEmployeeBtn').addEventListener('click', function() {
        document.getElementById('addEmployeeForm').reset();
        bootstrap.Modal.getInstance(document.getElementById('tambahKaryawanModal')).hide();
    });
    
    // Import attendance modal
    document.getElementById('importAttendanceBtn').addEventListener('click', function() {
        const fileInput = document.getElementById('importFile');
        if (fileInput.files.length === 0) {
            return;
        }
        
        document.getElementById('importAttendanceForm').reset();
        bootstrap.Modal.getInstance(document.getElementById('importAttendanceModal')).hide();
    });
}

// Table Initialization
function initTables() {
    // Employee table search
    document.getElementById('searchEmployeeBtn').addEventListener('click', function() {
        const searchTerm = document.getElementById('searchEmployee').value.toLowerCase();
        const rows = document.querySelectorAll('#employeeTable tbody tr');
        
        rows.forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const nik = row.cells[0].textContent.toLowerCase();
            if (name.includes(searchTerm) || nik.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Employee table filters
    document.getElementById('filterDepartment').addEventListener('change', filterEmployeeTable);
    document.getElementById('filterStatus').addEventListener('change', filterEmployeeTable);
    
    // Reset filters
    document.getElementById('resetFilterBtn').addEventListener('click', function() {
        document.getElementById('searchEmployee').value = '';
        document.getElementById('filterDepartment').value = '';
        document.getElementById('filterStatus').value = '';
        
        document.querySelectorAll('#employeeTable tbody tr').forEach(row => {
            row.style.display = '';
        });
    });
    
    // Attendance table filter
    document.getElementById('applyFilterBtn').addEventListener('click', function() {
        const fromDate = document.getElementById('filterDateFrom').value;
        const toDate = document.getElementById('filterDateTo').value;
        const employee = document.getElementById('filterEmployee').value;
    });
    
    // Export attendance
    document.getElementById('exportAttendanceBtn').addEventListener('click', function() {
        // Export functionality would go here
    });
}

function filterEmployeeTable() {
    const department = document.getElementById('filterDepartment').value;
    const status = document.getElementById('filterStatus').value;
    
    document.querySelectorAll('#employeeTable tbody tr').forEach(row => {
        const rowDepartment = row.cells[2].textContent;
        const rowStatus = row.cells[4].textContent.includes('Aktif') ? 'Aktif' : 'Non-Aktif';
        
        const departmentMatch = department === '' || rowDepartment === department;
        const statusMatch = status === '' || rowStatus === status;
        
        if (departmentMatch && statusMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Report Type Switcher
function initReportTypeSwitcher() {
    const reportType = document.getElementById('reportType');
    const monthlyInput = document.getElementById('monthlyInput');
    const weeklyInput = document.getElementById('weeklyInput');
    const dailyInput = document.getElementById('dailyInput');
    const employeeInput = document.getElementById('employeeInput');
    const departmentInput = document.getElementById('departmentInput');
    
    function updateInputVisibility() {
        monthlyInput.classList.add('d-none');
        weeklyInput.classList.add('d-none');
        dailyInput.classList.add('d-none');
        employeeInput.classList.add('d-none');
        departmentInput.classList.add('d-none');
        
        switch(reportType.value) {
            case 'monthly':
                monthlyInput.classList.remove('d-none');
                break;
            case 'weekly':
                weeklyInput.classList.remove('d-none');
                break;
            case 'daily':
                dailyInput.classList.remove('d-none');
                break;
            case 'employee':
                employeeInput.classList.remove('d-none');
                break;
            case 'department':
                departmentInput.classList.remove('d-none');
                break;
        }
    }
    
    reportType.addEventListener('change', updateInputVisibility);
    updateInputVisibility(); // Initialize
}