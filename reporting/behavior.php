<?php
    session_start();

    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }

    $db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
    $db_user = "postgres";
    $db_pass = "Sanrio135Cse";

    try {
        $pdo = new PDO($db_dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);


    } catch (PDOException $e) {
        // Silently fall back to defaults; log in production
        error_log("DB error: " . $e->getMessage());
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Behavior ♡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function exportToPDF() {
            const { jsPDF } = window.jspdf;

            html2canvas(document.body, {
                ignoreElements: el => el.tagName === 'ZING-GRID'
            }).then(canvas => {
                const pdf = new jsPDF('l', 'mm', 'a4');
                const pdfWidth  = pdf.internal.pageSize.getWidth();
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

                // Add the screenshot (everything except the ZingGrid)
                pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, pdfWidth, pdfHeight);

                // Draw the Top Pages table manually on a new page
                pdf.addPage();
                pdf.setFontSize(14);
                pdf.text('Top Pages', 14, 16);

                const topPagesData = <?= json_encode($top_pages) ?>;

                const tableHead = [['URL', 'Views', 'Unique Sessions']];
                const tableBody = topPagesData.map(row => [
                    row.url,
                    String(row.views),
                    String(row.unique_sessions)
                ]);

                // jsPDF's built-in autoTable isn't available by default — use a simple manual draw
                let y = 26;
                const colWidths = [180, 30, 40];
                const rowHeight = 8;
                const startX = 14;

                // Header row
                pdf.setFontSize(10);
                pdf.setFont(undefined, 'bold');
                tableHead[0].forEach((cell, i) => {
                    const x = startX + colWidths.slice(0, i).reduce((a, b) => a + b, 0);
                    pdf.text(cell, x, y);
                });
                y += rowHeight;

                // Data rows
                pdf.setFont(undefined, 'normal');
                pdf.setFontSize(9);
                tableBody.forEach(row => {
                    if (y > 195) { // new page if running out of room
                        pdf.addPage();
                        y = 16;
                    }
                    row.forEach((cell, i) => {
                        const x = startX + colWidths.slice(0, i).reduce((a, b) => a + b, 0);
                        const truncated = cell.length > 60 ? cell.slice(0, 57) + '...' : cell;
                        pdf.text(truncated, x, y);
                    });
                    y += rowHeight;
                });

                pdf.save('dashboard.pdf');
            });
        }
    </script>
</head>
<body>
<nav class="navbar">
    <div class="left-navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="performance.php">Performance</a>
        <a href="behavior.php">Behavior</a>
        <a href="errors.php">Errors</a>
        <a href="admin.php">Admin</a>
    </div>
    <div class="right-navbar">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="main-content">
    <h1>Behavior</h1>
    <div style="display: flex; justify-content:center">
    <button onclick="exportToPDF()" class="btn btn-3d-lift">
        Export as PDF
    </button>
    </div>
    <div style="display: flex; gap: 20px; margin: 40px auto; max-width: 1400px;">
    <div class="card mt-4" style="flex: 1;">
        <div style="flex: 1; height: 350px;">
            <canvas id="deviceChart"></canvas>
        </div>
    </div>
    <div class="card mt-4" style="flex: 1;">
        <div style="flex: 1; height: 350px;">
            <canvas id="firstLastPageChart"></canvas>
        </div>
    </div>

    </div>
    <div style="background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;">
    <strong style="color:#7a4fa3;">Analyst comment:</strong> Fig 3: iOS devices make up the largest share of sessions, suggesting the audience skews mobile. Responsive design and mobile performance should be prioritized.
    </div>
    <div style="background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;">
    <strong style="color:#7a4fa3;">Analyst comment:</strong> Fig 4: Most sessions start and end on the same page. 8 out of 11 sessions bounced, a 73% bounce rate, indicating the landing page is not driving further engagement.
    </div>
    <div class="card mt-4" style="max-width: 1400px; margin: 20px auto;">
    <div class="card-body">
        <h4 class="card-title">Events</h4>
        <zing-grid id="eventsGrid" sort pager page-size="15">
            <zg-colgroup>
                <zg-column index="event_name"       header="Event Name"></zg-column>
                <zg-column index="event_category"   header="Category"></zg-column>
                <zg-column index="url"              header="URL"></zg-column>
                <zg-column index="session_id"       header="Session ID"></zg-column>
                <zg-column index="server_timestamp" header="Timestamp"></zg-column>
            </zg-colgroup>
        </zing-grid>
    </div>
</div>
    <footer class="mt-4">By Annejulia, Dishita, and Keyura ♡</footer>
</div>
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
                    title: { display: true, text: 'Fig 3: Device Split' },
                    legend: { position: 'bottom' }
                }
            }
        });
        fetch('api.php/events')
            .then(res => res.json())
            .then(data => {
                document.getElementById('eventsGrid').setData(data);
            })
            .catch(err => console.error('events fetch error:', err));

        // First vs last page
        const pagePairs = {};
        data.forEach(row => {
            const domain = 'https://test.cse135hw1.online';
            const shorten = url => {
                const path = (url || '').replace(domain, '') || '/';
                return path === '/' ? 'root' : path;
            };
            const first = shorten(row.first_page);
            const last = shorten(row.last_page);
            const key = first === last ? 'Bounced (same page)' : `${first} → ${last}`;
            pagePairs[key] = (pagePairs[key] || 0) + 1;
        });

        new Chart(document.getElementById('firstLastPageChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(pagePairs),
                datasets: [{
                    label: 'Sessions',
                    data: Object.values(pagePairs),
                    backgroundColor: Object.keys(pagePairs).map(k =>
                        k.startsWith('Bounced') ? '#f4a7c3' : '#d4a8e0'
                    )
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Sessions' } },
                    y: { ticks: { font: { size: 10 } } }
                },
                plugins: {
                    title: { display: true, text: 'Fig 4: First Page vs Last Page' },
                    legend: { display: false }
                }
            }
        });
    })
    .catch(err => console.error('sessions fetch error:', err));
</script>
</body>
</html>