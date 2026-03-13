<?php
    session_start();

    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }

    $db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
    $db_user = "postgres";
    $db_pass = "Sanrio135Cse";

    $total_pageviews     = 0;
    $unique_sessions     = 0;
    $avg_load_time       = 0;
    $error_count         = 0;
    $pageviews_over_time = [];
    $top_pages           = [];

    try {
        $pdo = new PDO($db_dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Total page views
        $stmt = $pdo->query("SELECT COUNT(*) FROM pageviews");
        $total_pageviews = $stmt->fetchColumn();

        // Unique sessions
        $stmt = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM sessions");
        $unique_sessions = $stmt->fetchColumn();

        // Average load time (in seconds, rounded to 2 decimal places)
        $stmt = $pdo->query("SELECT AVG(load_time) FROM performance WHERE load_time IS NOT NULL");
        $avg_raw = $stmt->fetchColumn();
        $avg_load_time = $avg_raw !== null ? round($avg_raw / 1000, 2) . 's' : 'N/A';

        // Error count
        $stmt = $pdo->query("SELECT COUNT(*) FROM errors");
        $error_count = $stmt->fetchColumn();

        // Top pages by view count
        $stmt = $pdo->query("
            SELECT
                url,
                COUNT(*) AS views,
                COUNT(DISTINCT session_id) AS unique_sessions
            FROM pageviews
            GROUP BY url
            ORDER BY views DESC
            LIMIT 20
        ");
        $top_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pageviews over time — grouped by day for the last 30 days
        $stmt = $pdo->query("
            SELECT
                DATE(server_timestamp) AS day,
                COUNT(*) AS views
            FROM pageviews
            WHERE server_timestamp >= NOW() - INTERVAL '30 days'
            GROUP BY day
            ORDER BY day ASC
        ");
        $pageviews_over_time = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Dashboard ♡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('main-content');

            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL('image/png');

            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('report-' + new Date().toISOString().slice(0, 10) + '.pdf');
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
    <h1>Dashboard</h1>
    <div style="display: flex; justify-content:center">
    <button onclick="exportToPDF()" class="btn btn-3d-lift">
        Export as PDF
    </button>
    </div>
    <div class="row">
        <div class="card text-center" style="width: 20%">
            <div class="card-body p-5">
                <h4 class="card-title">Total Page Views</h4>
                <p class="text-muted"><?= htmlspecialchars($total_pageviews) ?></p>
            </div>
        </div>

        <div class="card text-center" style="width: 20%">
            <div class="card-body p-5">
                <h4 class="card-title">Unique Sessions</h4>
                <p class="text-muted"><?= htmlspecialchars($unique_sessions) ?></p>
            </div>
        </div>

        <div class="card text-center" style="width: 20%">
            <div class="card-body p-5">
                <h4 class="card-title">Average Load Time</h4>
                <p class="text-muted"><?= htmlspecialchars($avg_load_time) ?></p>
            </div>
        </div>

        <div class="card text-center" style="width: 20%">
            <div class="card-body p-5">
                <h4 class="card-title">Error Count</h4>
                <p class="text-muted"><?= htmlspecialchars($error_count) ?></p>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <h4 class="card-title">Page Views Over Time (Last 30 Days)</h4>
            <canvas id="pageviewsChart" height="100"></canvas>
        </div>
    </div>

    <script>
        const labels = <?= json_encode(array_column($pageviews_over_time, 'day')) ?>;
        const data   = <?= json_encode(array_map('intval', array_column($pageviews_over_time, 'views'))) ?>;

        new Chart(document.getElementById('pageviewsChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Page Views',
                    data: data,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: ctx => ctx[0].label,
                            label: ctx => ` ${ctx.parsed.y} views`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 10 }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    </script>

    <div class="card mt-4">
        <div class="card-body">
            <h4 class="card-title">Top Pages</h4>
            <zing-grid
                id="topPagesGrid"
                sort
                pager
                page-size="10"
                caption="Top Pages by Views">
                <zg-colgroup>
                    <zg-column index="url"             header="URL"></zg-column>
                    <zg-column index="views"           header="Views"           type="number"></zg-column>
                    <zg-column index="unique_sessions" header="Unique Sessions" type="number"></zg-column>
                </zg-colgroup>
            </zing-grid>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const topPagesData = <?= json_encode($top_pages) ?>;
            const grid = document.getElementById('topPagesGrid');
            grid.setData(topPagesData);
        });
    </script>

    <footer>By Annejulia, Dishita, and Keyura ♡</footer>
</div>
</body>
</html>