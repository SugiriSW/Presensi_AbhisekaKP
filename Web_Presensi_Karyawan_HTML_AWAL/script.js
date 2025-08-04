document.addEventListener('DOMContentLoaded', function() {
    // Line Chart
    var ctx = document.getElementById('attendanceChart').getContext('2d');
    var attendanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['1 Jun', '2 Jun', '5 Jun', '6 Jun', '7 Jun', '8 Jun', '9 Jun', '12 Jun', '13 Jun', '14 Jun', '15 Jun', '16 Jun', '19 Jun', '20 Jun', '21 Jun', '22 Jun', '23 Jun', '26 Jun', '27 Jun', '28 Jun', '29 Jun', '30 Jun'],
            datasets: [{
                label: 'Status Presensi',
                data: [1, 0.8, 1, 0.8, 0, 1, 1, 0.8, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: function(context) {
                    var value = context.dataset.data[context.dataIndex];
                    if (value === 1) return 'rgba(28, 200, 138, 1)';
                    if (value === 0.8) return 'rgba(246, 194, 62, 1)';
                    return 'rgba(231, 74, 59, 1)';
                },
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 5,
                pointHoverRadius: 7,
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: 0,
                    max: 1,
                    ticks: {
                        callback: function(value) {
                            if (value === 1) return 'Hadir';
                            if (value === 0.8) return 'Terlambat';
                            if (value === 0) return 'Tidak Hadir';
                            return '';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var value = context.raw;
                            if (value === 1) return 'Status: Hadir Tepat Waktu';
                            if (value === 0.8) return 'Status: Terlambat';
                            return 'Status: Tidak Hadir';
                        }
                    }
                }
            }
        }
    });

    // Pie Chart
    var ctx2 = document.getElementById('attendancePieChart').getContext('2d');
    var attendancePieChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Tepat Waktu', 'Terlambat', 'Tidak Hadir'],
            datasets: [{
                data: [18, 4, 2],
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
                            var label = context.label || '';
                            var value = context.raw || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((value / total) * 100);
                            return label + ': ' + value + ' hari (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '70%',
        },
    });
});