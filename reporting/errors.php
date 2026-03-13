<?php
    session_start();

    if (!isset($_SESSION['valid']) || $_SESSION['username'] == 'chiikawa' || $_SESSION['role'] == 'viewer') {
        header("Location: login.php");
        exit;
    }

    $db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
    $db_user = "postgres";
    $db_pass = "Sanrio135Cse";

    $error_msg_counts    = [];
    $errors_by_page      = [];
    $errors_over_time    = [];
    $error_type_counts   = [];
    $null_error_pages    = [];
    $all_errors          = [];

    try {
        $pdo = new PDO($db_dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Top error messages by count
        $stmt = $pdo->query("
            SELECT error_message, COUNT(*) AS count
            FROM errors
            WHERE error_message IS NOT NULL
            GROUP BY error_message
            ORDER BY count DESC
            LIMIT 10
        ");
        $error_msg_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Errors by page/URL
        $stmt = $pdo->query("
            SELECT url, COUNT(*) AS count
            FROM errors
            WHERE error_message IS NOT NULL AND url IS NOT NULL
            GROUP BY url
            ORDER BY count DESC
            LIMIT 10
        ");
        $errors_by_page = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Error frequency over time (last 30 days, only real errors)
        $stmt = $pdo->query("
            SELECT DATE(server_timestamp) AS day, COUNT(*) AS count
            FROM errors
            WHERE error_message IS NOT NULL
              AND server_timestamp >= NOW() - INTERVAL '30 days'
            GROUP BY day
            ORDER BY day ASC
        ");
        $errors_over_time = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Error types (pie chart — extract error type prefix e.g. ReferenceError, TypeError)
        $stmt = $pdo->query("
            SELECT
                CASE
                    WHEN error_message LIKE 'ReferenceError%' THEN 'ReferenceError'
                    WHEN error_message LIKE 'TypeError%'      THEN 'TypeError'
                    WHEN error_message LIKE 'SyntaxError%'    THEN 'SyntaxError'
                    WHEN error_message LIKE 'RangeError%'     THEN 'RangeError'
                    WHEN error_message LIKE 'Uncaught%'       THEN 'Uncaught (other)'
                    ELSE 'Other'
                END AS error_type,
                COUNT(*) AS count
            FROM errors
            WHERE error_message IS NOT NULL
            GROUP BY error_type
            ORDER BY count DESC
        ");
        $error_type_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pages with null error messages
        $stmt = $pdo->query("
            SELECT url, COUNT(*) AS count, MAX(server_timestamp) AS last_seen
            FROM errors
            WHERE error_message IS NULL AND url IS NOT NULL
            GROUP BY url
            ORDER BY count DESC
        ");
        $null_error_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // All errors
        $stmt = $pdo->query("
            SELECT id, session_id, error_message, error_source, error_line,
                   error_column, url, server_timestamp
            FROM errors
            ORDER BY server_timestamp DESC
            LIMIT 500
        ");
        $all_errors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errors ♡</title>
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

                // Helper to draw a simple table
                function drawTable(title, headers, keys, data, colWidths) {
                    pdf.addPage();
                    pdf.setFontSize(14);
                    pdf.text(title, 14, 16);
                    let y = 26;
                    pdf.setFontSize(9);
                    pdf.setFont(undefined, 'bold');
                    headers.forEach((h, i) => {
                        pdf.text(h, 14 + colWidths.slice(0, i).reduce((a, b) => a + b, 0), y);
                    });
                    y += 8;
                    pdf.setFont(undefined, 'normal');
                    pdf.setFontSize(8);
                    data.forEach(row => {
                        if (y > 195) { pdf.addPage(); y = 16; }
                        keys.forEach((k, i) => {
                            const val = String(row[k] ?? '');
                            const trunc = val.length > 45 ? val.slice(0, 42) + '...' : val;
                            pdf.text(trunc, 14 + colWidths.slice(0, i).reduce((a, b) => a + b, 0), y);
                        });
                        y += 8;
                    });
                }

                const nullPages  = <?= json_encode($null_error_pages) ?>;
                const allErrors  = document.getElementById('allErrorsGrid')?.getData() || [];

                drawTable(
                    'Pages with No Error Message',
                    ['URL', 'Count', 'Last Seen'],
                    ['url', 'count', 'last_seen'],
                    nullPages,
                    [160, 30, 60]
                );

                drawTable(
                    'All Errors',
                    ['ID', 'Error Message', 'URL', 'Line', 'Timestamp'],
                    ['id', 'error_message', 'url', 'error_line', 'server_timestamp'],
                    allErrors,
                    [15, 90, 70, 15, 55]
                );

                pdf.save('errors.pdf');
            });
        }
    </script>
</head>
<body>
<nav class="navbar">
    <div class="left-navbar">
        <a href="dashboard.php">Dashboard</a>
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'owner'): ?>
            <?php if ($_SESSION['username'] != 'chiikawa'): ?>
                <a href="performance.php">Performance</a>
            <?php endif; ?>
            <?php if ($_SESSION['username'] != 'VashTheStampede'): ?>
                <a href="behavior.php">Behavior</a>
            <?php endif; ?>
            <?php if ($_SESSION['username'] != 'chiikawa'): ?>
                <a href="errors.php" class="active">Errors</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($_SESSION['username'] == 'super-admin'): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
    </div>
    <div class="right-navbar d-flex align-items-center">
        <a href="logout.php">Logout</a>
        <p class='d-block text-center py-2 px-3 mb-0'><?php echo htmlspecialchars($_SESSION['username']) ?></p>
    </div>
</nav>

<div class="main-content">
    <h1>Errors</h1>
    <div style="display: flex; justify-content: center;">
        <button onclick="exportToPDF()" class="btn btn-3d-lift">Export as PDF</button>
    </div>

    <!-- Row 1: Top error messages + Errors by page (side by side) -->
    <div class="mt-4" style="display: flex; gap: 20px; margin:auto; margin-bottom:0px; max-width: 1400px;">
        <div class="card mt-4 pb-3" style="flex: 1;">
            <div class="card-body" style="height: 380px;">
                <h4 class="card-title">Top Error Messages</h4>
                <canvas id="errorMsgChart"></canvas>
            </div>
        </div>
        <div class="card mt-4 pb-3" style="flex: 1;">
            <div class="card-body" style="height: 380px;">
                <h4 class="card-title">Errors by Page</h4>
                <canvas id="errorsByPageChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Row 2: Error frequency over time + Pie chart (side by side) -->
    <div style="display: flex; gap: 20px; margin: 10px auto; max-width: 1400px;">
        <div class="card mt-4 pb-3" style="flex: 2;">
            <div class="card-body" style="height: 350px;">
                <h4 class="card-title">Error Frequency Over Time</h4>
                <canvas id="errorTimeChart"></canvas>
            </div>
        </div>
        <div class="card mt-4 pb-3" style="flex: 1;">
            <div class="card-body" style="height: 350px;">
                <h4 class="card-title">Error Types</h4>
                <canvas id="errorTypeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Null error pages table -->
    <div class="card mt-4" style="max-width: 1400px; margin: 20px auto;">
        <div class="card-body">
            <h4 class="card-title">Pages with No Error Message</h4>
            <p class="text-muted" style="font-size: 13px;">Entries where an error event was recorded but no message was captured — may indicate silent failures or misconfigured error handlers.</p>
            <zing-grid id="nullErrorsGrid" sort pager page-size="10">
                <zg-colgroup>
                    <zg-column index="url"       header="URL"></zg-column>
                    <zg-column index="count"     header="Count"     type="number"></zg-column>
                    <zg-column index="last_seen" header="Last Seen"></zg-column>
                </zg-colgroup>
            </zing-grid>
        </div>
    </div>

    <!-- All errors table -->
    <div class="card mt-4" style="max-width: 1400px; margin: 20px auto;">
        <div class="card-body">
            <h4 class="card-title">All Errors</h4>
            <zing-grid id="allErrorsGrid" sort pager page-size="15">
                <zg-colgroup>
                    <zg-column index="id"               header="ID"           type="number"></zg-column>
                    <zg-column index="error_message"    header="Error Message"></zg-column>
                    <zg-column index="error_source"     header="Source"></zg-column>
                    <zg-column index="error_line"       header="Line"         type="number"></zg-column>
                    <zg-column index="error_column"     header="Col"          type="number"></zg-column>
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
    const palette = ['#b08fd4', '#f4a7c3', '#d4a8e0', '#f9d0e3', '#e8c8f0', '#c9a0dc', '#f7c5d5', '#dbb8ed'];

    // --- Top Error Messages (horizontal bar) ---
    const msgLabels = <?= json_encode(array_column($error_msg_counts, 'error_message')) ?>;
    const msgCounts = <?= json_encode(array_map('intval', array_column($error_msg_counts, 'count'))) ?>;

    new Chart(document.getElementById('errorMsgChart'), {
        type: 'bar',
        data: {
            labels: msgLabels.map(l => l.length > 50 ? l.slice(0, 47) + '...' : l),
            datasets: [{ data: msgCounts, backgroundColor: palette }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // --- Errors by Page (horizontal bar) ---
    const pageLabels = <?= json_encode(array_column($errors_by_page, 'url')) ?>;
    const pageCounts = <?= json_encode(array_map('intval', array_column($errors_by_page, 'count'))) ?>;
    const domain = 'https://test.cse135hw1.online';

    new Chart(document.getElementById('errorsByPageChart'), {
        type: 'bar',
        data: {
            labels: pageLabels.map(l => l.replace(domain, '') || '/'),
            datasets: [{ data: pageCounts, backgroundColor: palette }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // --- Error Frequency Over Time (line) ---
    const timeLabels = <?= json_encode(array_column($errors_over_time, 'day')) ?>;
    const timeCounts = <?= json_encode(array_map('intval', array_column($errors_over_time, 'count'))) ?>;

    new Chart(document.getElementById('errorTimeChart'), {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Errors',
                data: timeCounts,
                borderColor: '#b08fd4',
                backgroundColor: 'rgba(176, 143, 212, 0.1)',
                borderWidth: 2, pointRadius: 3, fill: true, tension: 0.3
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    // --- Error Types (pie) ---
    const typeLabels = <?= json_encode(array_column($error_type_counts, 'error_type')) ?>;
    const typeCounts = <?= json_encode(array_map('intval', array_column($error_type_counts, 'count'))) ?>;

    new Chart(document.getElementById('errorTypeChart'), {
        type: 'pie',
        data: {
            labels: typeLabels,
            datasets: [{ data: typeCounts, backgroundColor: palette }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
        }
    });

    // --- ZingGrids ---
    document.addEventListener('DOMContentLoaded', () => {
        const nullPages = <?= json_encode($null_error_pages) ?>;
        const allErrors = <?= json_encode($all_errors) ?>;

        document.getElementById('nullErrorsGrid').setData(nullPages);
        document.getElementById('allErrorsGrid').setData(allErrors);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>