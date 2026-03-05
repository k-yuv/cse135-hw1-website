<?php
   session_start();
   unset($_SESSION["username"]);
   unset($_SESSION["password"]);
   unset($_SESSION["valid"]);
   
   echo '<h4>You have cleaned session</h4>';
   header('Refresh: 2; URL = login.php');
?>