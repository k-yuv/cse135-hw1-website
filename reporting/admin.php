<?php
    session_start();

    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }

    $db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
    $db_user = "postgres";
    $db_pass = "Sanrio135Cse";

    $message      = '';
    $message_type = '';

    try {
        $pdo = new PDO($db_dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Handle remove user
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
            $id = (int) $_POST['user_id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $message      = 'User removed successfully.';
            $message_type = 'success';
        }

        // Handle add user
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
            $email        = trim($_POST['email']);
            $display_name = trim($_POST['display_name']);
            $role         = $_POST['role'];
            $password     = $_POST['password'];

            $allowed_roles = ['viewer', 'admin']; // adjust to match your user_role enum
            if (!in_array($role, $allowed_roles)) {
                $role = 'viewer';
            }

            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO users (email, display_name, role, password_hash)
                VALUES (:email, :display_name, :role, :password_hash)
            ");
            $stmt->execute([
                ':email'         => $email,
                ':display_name'  => $display_name,
                ':role'          => $role,
                ':password_hash' => $password_hash,
            ]);
            $message      = 'User added successfully.';
            $message_type = 'success';
        }

        // Fetch all users
        $stmt  = $pdo->query("SELECT id, email, display_name, role, last_login FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("DB error: " . $e->getMessage());
        $message      = 'A database error occurred.';
        $message_type = 'danger';
        $users        = [];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin ♡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
<nav class="navbar">
    <div class="left-navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="performance.php">Performance</a>
        <a href="behavior.php">Behavior</a>
        <a href="errors.php">Errors</a>
        <a href="admin.php" class="active">Admin</a>
    </div>
    <div class="right-navbar">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="main-content">
    <h1>Admin</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="card mt-4">
        <div class="card-body">
            <h4 class="card-title">Users</h4>
            <zing-grid id="usersGrid" sort pager page-size="10">
                <zg-colgroup>
                    <zg-column index="email"        header="Email"></zg-column>
                    <zg-column index="display_name" header="Display Name"></zg-column>
                    <zg-column index="role"         header="Role"></zg-column>
                    <zg-column index="last_login"   header="Last Login"></zg-column>
                    <zg-column index="id"           header="Actions" renderer="renderRemoveBtn"></zg-column>
                </zg-colgroup>
            </zing-grid>
        </div>
    </div>

    <!-- Add User Form -->
    <div class="card mt-4">
        <div class="card-body">
            <h4 class="card-title">Add User</h4>
            <form method="POST" action="admin.php">
                <input type="hidden" name="action" value="add">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="display_name" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="viewer">Viewer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden remove form, submitted via JS -->
    <form id="removeForm" method="POST" action="admin.php" style="display:none;">
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="user_id" id="removeUserId">
    </form>

    <footer>By Annejulia, Dishita, and Keyura ♡</footer>
</div>

<script>
    // ZingGrid renderer for the Remove button column
    function renderRemoveBtn(id) {
        return `<button
                    class="btn btn-danger btn-sm"
                    onclick="removeUser(${id})">
                    Remove
                </button>`;
    }

    function removeUser(id) {
        if (!confirm('Are you sure you want to remove this user?')) return;
        document.getElementById('removeUserId').value = id;
        document.getElementById('removeForm').submit();
    }

    // Feed PHP data into ZingGrid after it registers
    const usersData = <?= json_encode($users) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        const grid = document.getElementById('usersGrid');
        grid.setData(usersData);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>