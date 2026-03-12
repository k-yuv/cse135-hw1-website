<?php
   session_start();
   if (isset($_SESSION['valid'])) {
        header("Location: graphs.php");
        exit;
    }

   $msg = '';
   $users = ['testuser'=>"Sanrio135Cse"];

   if (isset($_POST['login']) && !empty($_POST['username']) 
   && !empty($_POST['password'])) {
      $user=$_POST['username'];                  
      if (array_key_exists($user, $users)){
         if ($users[$_POST['username']]==$_POST['password']){
            $_SESSION['valid'] = true;
            $_SESSION['timeout'] = time();
            $_SESSION['username'] = $_POST['username'];
            header("Location: graphs.php");
            exit;
         } else {
            $msg = "Wrong password (｡•́︿•̀｡)";
         }
      } else {
         $msg = "Username not found (´･ω･`)";
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
    <link rel="stylesheet" href="analytics.css">
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