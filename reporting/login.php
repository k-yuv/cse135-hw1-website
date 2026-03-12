<?php
   session_start();
   if (isset($_SESSION['valid'])) {
        header("Location: graphs.php");
        exit;
    }
?>
<html lang = "en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="styles.css">
</head>
<body>
   <h2>Enter Display Name and Password:</h2> 
   <?php
      $msg = '';

      // --- Database configuration ---
$db_dsn  = "pgsql:host=127.0.0.1;port=5432;dbname=postgres;";
$db_user = "postgres";
$db_pass = "Sanrio135Cse";

if (isset($_POST['login']) && !empty($_POST['display_name']) && !empty($_POST['password'])) {
   $display_name    = $_POST['display_name'];
   $input_password  = $_POST['password'];

   try {
      $pdo = new PDO($db_dsn, $db_user, $db_pass, [
         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      ]);

      $stmt = $pdo->prepare("SELECT password_hash, username, role FROM users WHERE display_name = :display_name");
      $stmt->execute([':display_name' => $display_name]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($row) {
         if (password_verify($input_password, $row['password_hash'])){
                  $_SESSION['valid'] = true;
                  $_SESSION['timeout'] = time();
                  $_SESSION['username'] = $row['username'];
                  $_SESSION['display_name'] = $display_name;
                  $_SESSION['role'] = $row['role'];
                  header("Location: graphs.php");
                  exit;
               } else {
                  $msg = "You have entered the wrong password";
               }
            } else {
               $msg = "You have entered the wrong display name";
            }
         } catch (PDOException $e) {
            $msg = "Database connection failed. Please try again later.";
            // Uncomment the line below for debugging only — never in production:
            // $msg .= " Error: " . $e->getMessage();
         }
      }
   ?>

   <h4 style="color:red;"><?php echo $msg; ?></h4>
   <br/><br/>
   <form action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
      <div>
         <label for="display_name">Display Name:</label>
         <input type="text" name="display_name" id="display_name">
      </div>
      <div>
         <label for="password">Password:</label>
         <input type="password" name="password" id="password">
      </div>
      <button type="submit" name="login">Login</button>
   </form>
   </div> 
</body>
</html>