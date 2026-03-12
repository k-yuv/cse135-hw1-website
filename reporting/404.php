<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card text-center" style="max-width: 30rem;">
            <div class="card-body p-5">
                <h1 style="font-size: 5rem; color: #f4a7c3;">404</h1>
                <h4 class="card-title">404 Page Not Found</h4>
                <p class="text-muted">૮꒰ ˶• ༝ •˶꒱ა ♡ Oops! The page you're looking for doesn't exist.</p>
                <a href="<?= isset($_SESSION['valid']) ? 'graphs.php' : 'login.php' ?>" class="btn btn-primary">
                    Take me home
                </a>
            </div>
        </div>
    </div>
</body>
</html>