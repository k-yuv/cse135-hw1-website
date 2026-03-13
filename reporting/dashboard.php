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
    <title>Dashboard ♡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>


    <nav class="navbar">
        <div classname="left-navbar">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="performance.php">Performance</a>
            <a href="behavior.php">Behavior</a>
            <a href="errors.php">Errors</a>
            <a href="admin.php">Admin</a>
        </div>
        <div classname="right-navbar"> 
            <a href="logout.php">Logout</a>
        </div>
    </nav>

<body>
    <div class="row">
        <div class="card text-center">
            <div class="card-body p-5">
                <h4 class="card-title">Total Page Views</h4>
                <p class="text-muted">5</p>
            </div>
        </div>

        <div class="card text-center">
            <div class="card-body p-5">
                <h4 class="card-title">Unique Sessions</h4>
                <p class="text-muted">5</p>
            </div>
        </div>

        <div class="card text-center">
            <div class="card-body p-5">
                <h4 class="card-title">Average Load Time</h4>
                <p class="text-muted">5</p>
            </div>
        </div>

        <div class="card text-center">
            <div class="card-body p-5">
                <h4 class="card-title">Error Count</h4>
                <p class="text-muted">5</p>
            </div>
        </div>
    </div>

    <p>line graph of page views over time</p>
            
    <p>table of top pages</p>

    <footer>hi!!</footer>
</body>