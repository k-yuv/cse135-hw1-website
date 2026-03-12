<?php
    session_start();

    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graphs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <a href="logout.php" title="Logout">Logout</a>

    <button onclick="exportToPDF()" style="display:block; margin: 20px auto; padding: 10px 20px; cursor: pointer;">
        Export as PDF
    </button>
    <div id="report-Content">

        <!-- Pageviews Bar Chart -->
        <div style="max-width: 700px; margin: 40px auto;">
            <canvas id="pageviewsChart"></canvas>
        </div>

        <!-- Performance Line Chart -->
        <div style="max-width: 700px; margin: 40px auto; height: 400px;">
            <canvas id="performanceChart"></canvas>
        </div>

        <!-- ZingGrid Data Grid -->
        <zing-grid caption="CSE 135 HW 4 Data Grid"></zing-grid>

    <!-- Device Split Doughnut -->
    <div style="max-width: 700px; margin: 40px auto; height: 400px;">
    <canvas id="deviceChart"></canvas>
    </div>

     <!-- Session Duration Bar Chart -->
    <div style="max-width: 700px; margin: 40px auto; height: 400px;">
        <canvas id="sessionDurationChart"></canvas>
    </div>
    </div>

    <script>
        // Pageviews bar chart
        fetch('api.php/pageviews')
            .then(res => res.json())
            .then(data => {
                const counts = {};
                data.forEach(row => {
                    const key = row.page || row.url || row.path || Object.values(row)[1];
                    counts[key] = (counts[key] || 0) + 1;
                });

                new Chart(document.getElementById('pageviewsChart'), {
                    type: 'bar',
                    data: {
                        labels: Object.keys(counts),
                        datasets: [{
                            label: 'Pageviews',
                            data: Object.values(counts),
                            backgroundColor: '#d4a8e0'
                        }]
                    },
                    options: {
                        scales: { y: { beginAtZero: true } },
                        plugins: {
                            title: { display: true, text: 'Pageviews by Page' }
                        }
                    }
                });
            });

// Performance line chart
fetch('api.php/performance')
    .then(res => res.json())
    .then(data => {
        new Chart(document.getElementById('performanceChart'), {
            type: 'line',
            data: {
                labels: data.map(row => row.id),
                datasets: [{
                    label: 'Load Time (ms)',
                    data: data.map(row => row.load_time),
                    borderColor: '#e67e22',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Entry ID' } },
                    y: { beginAtZero: true, title: { display: true, text: 'Load Time (ms)' } }
                },
                plugins: {
                    title: { display: true, text: 'Page Load Time' },
                    legend: { display: false }
                }
            }
        });
    });        

        // ZingGrid data
        window.addEventListener('load', () => {
            const zgRef = document.querySelector('zing-grid');
            
            fetch('api.php/performance')
                .then(res => res.json())
                .then(data => {
                    zgRef.setData(data);
                });
    });

    async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('report-Content');

            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL('image/png');

            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('report-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }
    </script>
    
    <script>
    function getDevice(ua) {
        if (!ua) return 'Unknown';
        if (/iphone|ipad|ipod/i.test(ua)) return 'iOS';
        if (/windows/i.test(ua)) return 'Windows';
        if (/android/i.test(ua)) return 'Android';
        if (/mac/i.test(ua)) return 'Mac';
        return 'Other';
    }

    fetch('api.php/sessions')
        .then(res => res.json())
        .then(data => {
            const deviceCounts = {};
            data.forEach(row => {
                const d = getDevice(row.user_agent);
                deviceCounts[d] = (deviceCounts[d] || 0) + 1;
            });

            new Chart(document.getElementById('deviceChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(deviceCounts),
                    datasets: [{
                        data: Object.values(deviceCounts),
                        backgroundColor: ['#d4a8e0', '#f4a7c3', '#b08fd4', '#f9d0e3', '#e8c8f0']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: { display: true, text: 'Device Split' },
                        legend: { position: 'bottom' }
                    }
                }
            });

            new Chart(document.getElementById('sessionDurationChart'), {
                type: 'bar',
                data: {
                    labels: data.map(row => row.session_id.slice(0, 10) + '…'),
                    datasets: [{
                        label: 'Duration (s)',
                        data: data.map(row => row.duration_seconds ?? 0),
                        backgroundColor: '#d4a8e0'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { title: { display: true, text: 'Session' } },
                        y: { beginAtZero: true, title: { display: true, text: 'Duration (s)' } }
                    },
                    plugins: {
                        title: { display: true, text: 'Session Duration' }
                    }
                }
            });
        })
        .catch(err => console.error('sessions fetch error:', err));
</script>
</body>
</html>