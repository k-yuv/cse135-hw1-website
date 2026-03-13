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

        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, pdfWidth, pdfHeight);

        // Manually draw sessions table on a new page
        pdf.addPage();
        pdf.setFontSize(14);
        pdf.text('Sessions', 14, 16);

        const grid = document.getElementById('sessionsGrid');
        const sessionsData = grid ? grid.getData() : [];

        let y = 26;
        const rowHeight = 8;
        const startX = 14;
        const colWidths = [50, 50, 50, 20, 25, 50, 40];
        const headers = ['Session ID', 'First Page', 'Last Page', 'Pages', 'Duration (s)', 'Referrer', 'Start Time'];
        const keys    = ['session_id', 'first_page', 'last_page', 'page_count', 'duration_seconds', 'referrer', 'start_time'];

        // Header row
        pdf.setFontSize(9);
        pdf.setFont(undefined, 'bold');
        headers.forEach((h, i) => {
            const x = startX + colWidths.slice(0, i).reduce((a, b) => a + b, 0);
            pdf.text(h, x, y);
        });
        y += rowHeight;

        // Data rows
        pdf.setFont(undefined, 'normal');
        pdf.setFontSize(8);
        (sessionsData || []).forEach(row => {
            if (y > 195) { pdf.addPage(); y = 16; }
            keys.forEach((key, i) => {
                const x = startX + colWidths.slice(0, i).reduce((a, b) => a + b, 0);
                const val = String(row[key] ?? '');
                const truncated = val.length > 20 ? val.slice(0, 17) + '...' : val;
                pdf.text(truncated, x, y);
            });
            y += rowHeight;
        });

        pdf.save('behavior.pdf');
    });
}
    </script>
</head>
<body>
<nav class="navbar">
    <div class="left-navbar">
        <a href="dashboard.php" class="active">Dashboard</a>
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'owner'): ?>

            <?php if ($_SESSION['username'] != 'chiikawa'): ?>
                <a href="performance.php">Performance</a>
            <?php endif; ?>
              
            <?php if ($_SESSION['username'] != 'VashTheStampede'): ?>
                <a href="behavior.php">Behavior</a>
            <?php endif; ?>

            <?php if ($_SESSION['username'] != 'chiikawa'): ?>
                <a href="errors.php">Errors</a>
            <?php endif; ?>

        <?php endif; ?>

        <?php if ($_SESSION['username'] == 'super-admin'): ?>
            <a href="admin.php">Admin</a> 
        <?php endif; ?>

    </div>
    <div class="right-navbar d-flex align-items-center">
        <a href="logout.php">Logout</a>
        <p class='d-block text-center py-2 px-3 mb-0'> <?php echo $_SESSION['username'] ?> </p>
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
        <h4 class="card-title">Sessions</h4>
        <zing-grid id="sessionsGrid" sort pager page-size="15">
            <zg-colgroup>
                <zg-column index="session_id"       header="Session ID"></zg-column>
                <zg-column index="first_page"       header="First Page"></zg-column>
                <zg-column index="last_page"        header="Last Page"></zg-column>
                <zg-column index="page_count"       header="Pages"></zg-column>
                <zg-column index="duration_seconds" header="Duration (s)"></zg-column>
                <zg-column index="referrer"         header="Referrer"></zg-column>
                <zg-column index="start_time"       header="Start Time"></zg-column>
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
        document.getElementById('sessionsGrid').setData(data);
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