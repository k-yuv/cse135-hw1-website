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
    <title>Performance ♡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <nav class="navbar">
        <div classname="left-navbar">
            <a href="dashboard.php">Dashboard</a>
            <a href="performance.php" class="active">Performance</a>
            <a href="behavior.php">Behavior</a>
            <a href="errors.php">Errors</a>
            <a href="admin.php">Admin</a>
        </div>
        <div classname="right-navbar"> 
            <a href="logout.php">Logout</a>
        </div>
    </nav>
</body>