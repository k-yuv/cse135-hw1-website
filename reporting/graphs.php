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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
</head>

<body>
    <a href="logout.php" title="Logout">Logout</a>

    <!-- Pageviews Bar Chart -->
    <div style="max-width: 700px; margin: 40px auto;">
        <canvas id="pageviewsChart"></canvas>
    </div>

    <!-- Performance Line Chart -->
    <div style="max-width: 700px; margin: 40px auto;">
        <canvas id="performanceChart"></canvas>
    </div>

    <!-- ZingGrid Data Grid -->
    <zing-grid caption="CSE 135 HW 4 Data Grid (Performance)"></zing-grid>

    <!-- Device Split Doughnut -->
    <div style="max-width: 700px; margin: 40px auto;">
        <canvas id="deviceChart"></canvas>
    </div>

    <!-- Session Duration Table -->
    <div style="max-width: 700px; margin: 40px auto;">
        <table id="sessionTable" border="1" cellpadding="6" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Session ID</th>
                    <th>Start time</th>
                    <th>Duration (s)</th>
                    <th>Pages</th>
                    <th>Device</th>
                </tr>
            </thead>
            <tbody id="sessionTableBody">
                <tr><td colspan="5">Loading…</td></tr>
            </tbody>
        </table>
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
                            backgroundColor: '#16a085'
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
                            borderColor: '#16a085',
                            backgroundColor: 'rgba(22, 160, 133, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#16a085'
                        }]
                    },
                    options: {
                        scales: { y: { beginAtZero: true } },
                        plugins: {
                            title: { display: true, text: 'Page Load Time' }
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
                // Device split doughnut
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
                            backgroundColor: ['#16a085', '#2980b9', '#e67e22', '#8e44ad', '#95a5a6']
                        }]
                    },
                    options: {
                        plugins: { title: { display: true, text: 'Device Split' } }
                    }
                });

                // Session table
                const tbody = document.getElementById('sessionTableBody');
                tbody.innerHTML = '';
                data.forEach(row => {
                    const start = row.start_time
                        ? new Date(row.start_time).toLocaleString()
                        : '—';
                    tbody.innerHTML += `
                        <tr>
                            <td style="font-family:monospace;font-size:11px;">${row.session_id}</td>
                            <td>${start}</td>
                            <td>${row.duration_seconds ?? '—'}</td>
                            <td>${row.page_count ?? '—'}</td>
                            <td>${getDevice(row.user_agent)}</td>
                        </tr>`;
                });
            });
    </script>
</body>
</html>