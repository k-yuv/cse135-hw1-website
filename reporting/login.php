<?php
   //ob_start();
   session_start();
   if (isset($_SESSION['valid'])) {
        header("Location: graphs.php");
        exit;
    }

   $msg = '';

   // --- Database configuration ---
   $db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
   $db_user = "postgres";
   $db_pass = "Sanrio135Cse";

   if (isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
      $display_name   = $_POST['username'];
      $input_password = $_POST['password'];

      try {
         $pdo = new PDO($db_dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
         ]);

         $stmt = $pdo->prepare("SELECT password_hash, display_name, role FROM users WHERE display_name = :display_name");
         $stmt->execute([':display_name' => $display_name]);
         $row = $stmt->fetch(PDO::FETCH_ASSOC);

         if ($row) {
            if (password_verify($input_password, $row['password_hash'])) {
               $_SESSION['valid']        = true;
               $_SESSION['timeout']      = time();
               $_SESSION['username']     = $row['display_name'];
               $_SESSION['display_name'] = $display_name;
               $_SESSION['role']         = $row['role'];
               header("Location: graphs.php");
               exit;
            } else {
               $msg = "Wrong password (｡•́︿•̀｡)";
            }
         } else {
            $msg = "Username not found (´･ω･`)";
         }
      } catch (PDOException $e) {
         $msg = "Database connection failed. Please try again later.";
         // Uncomment for debugging only — never in production:
         // $msg .= " Error: " . $e->getMessage();
      }
   }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login ♡</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card text-center" style="max-width: 30rem;">
            <div class="card-body p-5">
                <div style="font-size: 3.5rem; margin-bottom: 0.5rem;">🌸</div>
                <h4 class="card-title">Welcome back ♡</h4>
                <p class="text-muted">૮꒰ ˶• ༝ •˶꒱ა CSE 135 Analytics</p>

                <?php if ($msg): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="text-start">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">Username</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Enter username ✨">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter password 🔒">
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100 mt-2">
                        Login ♡
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>