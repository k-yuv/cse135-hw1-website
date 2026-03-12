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
      $db_host = 'localhost';
      $db_port = '5432';
      $db_name = 'postgres';
      $db_user = 'postgres';
      $db_pass = '';

      if (isset($_POST['login']) && !empty($_POST['display_name']) 
      && !empty($_POST['password'])) {
         $display_name = $_POST['display_name'];
         $password = $_POST['password'];

         // Connect to PostgreSQL
         $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
         try {
            $pdo = new PDO($dsn, $db_user, $db_pass, [
               PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Fetch the user by display_name
            $stmt = $pdo->prepare("SELECT password_hash, username, role FROM users WHERE display_name = :display_name");
            $stmt->execute([':display_name' => $display_name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
               // Verify the password against the stored hash
               if (password_verify($password, $row['password_hash'])) {
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