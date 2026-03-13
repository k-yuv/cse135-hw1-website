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
    <title>Performance ♡</title>
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

<body>
    <h1>Performance</h1>
    <div style="display: flex; justify-content:center">
    <button onclick="exportToPDF()" class="btn btn-3d-lift">
        Export as PDF
    </button>
    </div>
    <footer>By Annejulia, Dishita, and Keyura ♡</footer>
</body>
</html>