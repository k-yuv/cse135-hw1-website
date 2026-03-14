<?php
    session_start();

    if (!isset($_SESSION['valid']) || $_SESSION['username'] == 'chiikawa' || $_SESSION['role'] == 'viewer') {
        header("Location: login");
        exit;
    }

    $db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
    $db_user = "postgres";
    $db_pass = "Sanrio135Cse";

    $load_time_over_time = [];
    $avg_per_page        = [];
    $slowest_pages       = [];
    $performance_data    = [];

    try {
        $pdo = new PDO($db_dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Load time over time — grouped by day for last 30 days
        $stmt = $pdo->query("
            SELECT
                DATE(server_timestamp) AS day,
                ROUND(AVG(load_time)::numeric, 2) AS avg_load_time
            FROM performance
            WHERE load_time IS NOT NULL
              AND server_timestamp >= NOW() - INTERVAL '30 days'
            GROUP BY day
            ORDER BY day ASC
        ");
        $load_time_over_time = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Average load time per page (for bar chart)
        $stmt = $pdo->query("
            SELECT
                url,
                ROUND(AVG(load_time)::numeric, 2) AS avg_load_time,
                ROUND(AVG(ttfb)::numeric, 2)      AS avg_ttfb,
                ROUND(AVG(lcp)::numeric, 2)       AS avg_lcp
            FROM performance
            WHERE url IS NOT NULL AND load_time IS NOT NULL
            GROUP BY url
            ORDER BY avg_load_time DESC
            LIMIT 10
        ");
        $avg_per_page = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top 5 slowest pages
        $stmt = $pdo->query("
            SELECT
                url,
                ROUND(AVG(load_time)::numeric, 2) AS avg_load_time,
                ROUND(AVG(ttfb)::numeric, 2)      AS avg_ttfb,
                ROUND(AVG(lcp)::numeric, 2)       AS avg_lcp,
                COUNT(*)                          AS samples
            FROM performance
            WHERE url IS NOT NULL AND load_time IS NOT NULL
            GROUP BY url
            ORDER BY avg_load_time DESC
            LIMIT 5
        ");
        $slowest_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Full performance table
        $stmt = $pdo->query("
            SELECT
                url, ttfb, dom_content_loaded, dom_complete,
                load_time, lcp, cls, inp, fcp,
                transfer_size, resource_count, server_timestamp
            FROM performance
            ORDER BY server_timestamp DESC
            LIMIT 500
        ");
        $performance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance ♡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function addComment() {
            const input = document.getElementById('analystInput');
            const text  = input.value.trim();
            if (!text) return;

            const div = document.createElement('div');
            div.style.cssText = 'background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;';
            div.innerHTML = `<strong style="color:#7a4fa3;">Analyst comment:</strong> ${text}`;

            document.getElementById('analystComments').appendChild(div);
            input.value = '';
        }
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
        <a href="dashboard">Dashboard</a>
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'owner'): ?>
            <?php if ($_SESSION['username'] != 'chiikawa'): ?>
                <a href="performance" class="active">Performance</a>
            <?php endif; ?>
            <?php if ($_SESSION['username'] != 'VashTheStampede'): ?>
                <a href="behavior">Behavior</a>
            <?php endif; ?>
            <?php if ($_SESSION['username'] != 'chiikawa'): ?>
                <a href="errors">Errors</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($_SESSION['username'] == 'super-admin'): ?>
            <a href="admin">Admin</a>
        <?php endif; ?>
    </div>
    <div class="right-navbar d-flex align-items-center">
        <a href="logout">Logout</a>
        <p class='d-block text-center py-2 px-3 mb-0'><?php echo htmlspecialchars($_SESSION['username']) ?></p>
    </div>
</nav>

<div class="main-content" id="main-content">
    <h1>Performance</h1>
    <div style="display: flex; justify-content: center;">
        <button onclick="exportToPDF()" class="btn btn-3d-lift">Export as PDF</button>
    </div>

    <!-- Analyst comment input -->
    <div style="display: flex; justify-content: center; margin: 16px auto; max-width: 1400px;">
        <div style="display: flex; gap: 10px; width: 100%;">
            <input type="text" id="analystInput" class="form-control"
                placeholder="Add an analyst comment..."
                onkeydown="if(event.key === 'Enter') addComment()">
            <button onclick="addComment()" class="btn btn-3d-lift">Add Comment</button>
        </div>
    </div>
    <div id="main-content">
    <!-- Comments appear here -->
    <div id="analystComments"></div>

    <!-- Load Time Over Time -->
    <div class="card mt-4 pb-3">
        <div class="card-body" style="height: 350px;">
            <h4 class="card-title">Page Load Time Over Time (Last 30 Days)</h4>
            <canvas id="loadTimeChart"></canvas>
        </div>
    </div>

    <!-- Avg Performance Per Page -->
    <div class="card mt-4 pb-3">
        <div class="card-body" style="height: 400px;">
            <h4 class="card-title">Average Load Time Per Page</h4>
            <canvas id="perPageChart"></canvas>
        </div>
    </div>

    <!-- Top 5 Slowest Pages -->
    <div class="card mt-4 pb-3">
        <div class="card-body">
            <h4 class="card-title">Top 5 Slowest Pages</h4>
            <zing-grid id="slowestGrid" sort>
                <zg-colgroup>
                    <zg-column index="url"           header="URL"></zg-column>
                    <zg-column index="avg_load_time" header="Avg Load Time (ms)" type="number"></zg-column>
                    <zg-column index="avg_ttfb"      header="Avg TTFB (ms)"      type="number"></zg-column>
                    <zg-column index="avg_lcp"       header="Avg LCP (ms)"       type="number"></zg-column>
                    <zg-column index="samples"       header="Samples"            type="number"></zg-column>
                </zg-colgroup>
            </zing-grid>
        </div>
    </div>

    <div style="background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;">
    <strong style="color:#7a4fa3;">Analyst comment:</strong> The table above suggests that work needs to done to improve the performance of the Accessories tab, perhaps due to infinite scroll.
    </div>

    <!-- Full Performance Table -->
    <div class="card mt-4 ">
        <div class="card-body">
            <h4 class="card-title">Performance Log</h4>
            <zing-grid id="performanceGrid" sort pager page-size="15">
                <zg-colgroup>
                    <zg-column index="url"                header="URL"></zg-column>
                    <zg-column index="load_time"          header="Load Time (ms)"   type="number"></zg-column>
                    <zg-column index="ttfb"               header="TTFB (ms)"        type="number"></zg-column>
                    <zg-column index="lcp"                header="LCP (ms)"         type="number"></zg-column>
                    <zg-column index="fcp"                header="FCP (ms)"         type="number"></zg-column>
                    <zg-column index="cls"                header="CLS"              type="number"></zg-column>
                    <zg-column index="inp"                header="INP (ms)"         type="number"></zg-column>
                    <zg-column index="dom_content_loaded" header="DOM Loaded (ms)"  type="number"></zg-column>
                    <zg-column index="dom_complete"       header="DOM Complete (ms)" type="number"></zg-column>
                    <zg-column index="transfer_size"      header="Transfer Size (b)" type="number"></zg-column>
                    <zg-column index="resource_count"     header="Resources"        type="number"></zg-column>
                    <zg-column index="server_timestamp"   header="Timestamp"></zg-column>
                </zg-colgroup>
            </zing-grid>
        </div>
    </div>

    <footer class="mt-4">By Annejulia, Dishita, and Keyura ♡</footer>
</div>
</div>

<script>
    // --- Load Time Over Time (line chart) ---
    const ltLabels = <?= json_encode(array_column($load_time_over_time, 'day')) ?>;
    const ltData   = <?= json_encode(array_map('floatval', array_column($load_time_over_time, 'avg_load_time'))) ?>;

    new Chart(document.getElementById('loadTimeChart'), {
        type: 'line',
        data: {
            labels: ltLabels,
            datasets: [{
                label: 'Avg Load Time (ms)',
                data: ltData,
                borderColor: 'rgb(176, 143, 212)',
                backgroundColor: 'rgba(176, 143, 212, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                y: { beginAtZero: true, title: { display: true, text: 'ms' } }
            }
        }
    });

    // --- Avg Load Time Per Page (horizontal bar chart) ---
    const ppLabels   = <?= json_encode(array_column($avg_per_page, 'url')) ?>;
    const ppLoad     = <?= json_encode(array_map('floatval', array_column($avg_per_page, 'avg_load_time'))) ?>;
    const ppTTFB     = <?= json_encode(array_map('floatval', array_column($avg_per_page, 'avg_ttfb'))) ?>;
    const ppLCP      = <?= json_encode(array_map('floatval', array_column($avg_per_page, 'avg_lcp'))) ?>;

    new Chart(document.getElementById('perPageChart'), {
        type: 'bar',
        data: {
            labels: ppLabels,
            datasets: [
                {
                    label: 'Avg Load Time (ms)',
                    data: ppLoad,
                    backgroundColor: 'rgba(176, 143, 212, 0.8)'
                },
                {
                    label: 'Avg TTFB (ms)',
                    data: ppTTFB,
                    backgroundColor: 'rgba(244, 167, 195, 0.8)'
                },
                {
                    label: 'Avg LCP (ms)',
                    data: ppLCP,
                    backgroundColor: 'rgba(212, 168, 224, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { beginAtZero: true, title: { display: true, text: 'ms' } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // --- ZingGrids ---
    document.addEventListener('DOMContentLoaded', () => {
        const slowestData     = <?= json_encode($slowest_pages) ?>;
        const performanceData = <?= json_encode($performance_data) ?>;

        document.getElementById('slowestGrid').setData(slowestData);
        document.getElementById('performanceGrid').setData(performanceData);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>