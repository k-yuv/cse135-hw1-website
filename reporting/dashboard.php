<?php
    session_start();
 
    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }
 
    $db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
    $db_user = "postgres";
    $db_pass = "Sanrio135Cse";
 
    $total_pageviews  = 0;
    $unique_sessions  = 0;
    $avg_load_time    = 0;
    $error_count      = 0;
 
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
</head>
 
<nav class="navbar">
    <div class="left-navbar">
        <a href="dashboard.php" class="active">Dashboard</a>
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
    <h1>Dashboard</h1>
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
 
    <p>line graph of page views over time</p>
 
    <p>table of top pages</p>
 
    <footer>hi!!</footer>
</body>
</html>