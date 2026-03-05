<?php
    session_start();

    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }
?>

<html lang = "en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Graphs</title>
</head>

<body>
     <a href = "logout.php" tite = "Logout">Logout</a>
    <h1>this is the page where the graphs and tables go</h1>
</body>