<?php
   //ob_start();
   session_start();
?>
<html lang = "en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login</title>
</head>
<body>
   <h2 style="margin-left:10rem; margin-top:5rem;">Enter Username and Password</h2> 
   <?php
      $msg = '';
      $users = ['user'=>"test"];

      if (isset($_POST['login']) && !empty($_POST['username']) 
      && !empty($_POST['password'])) {
         $user=$_POST['username'];                  
         if (array_key_exists($user, $users)){
            if ($users[$_POST['username']]==$_POST['password']){
               $_SESSION['valid'] = true;
               $_SESSION['timeout'] = time();
               $_SESSION['username'] = $_POST['username'];
            }
            else {
               $msg = "You have entered the wrong password";
            }
         }
         else {
            $msg = "You have entered the wrong username";
         }
      }
   ?>

   <h4 style="margin-left:10rem; color:red;"><?php echo $msg; ?></h4>
   <br/><br/>
   <form action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
      <div>
         <label for="username">Username:</label>
         <input type="text" name="username" id="name">
      </div>
      <div>
         <label for="password">Password:</label>
         <input type="password" name="password" id="password">
      </div>
      <section style="margin-left:2rem;">
         <a href='graphs.php'>
            <button type="submit" name="login" >Login</button>
        </a>
      </section>
   </form>
   </div> 
</body>
</html>