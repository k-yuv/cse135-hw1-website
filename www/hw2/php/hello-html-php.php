<?php
header('Cache-Control: no-cache');
header('Content-Type: text/html');
?>

<!DOCTYPE html>
<html>
<head>
<title>Hello CGI World</title>
</head>
<body>

<h1 align=center>Hello HTML World</h1><hr/>
<p>Hello World</p>
<p>This page was generated with the PHP programming langauge</p>

<p>This program was generated at: <?php $date =  date('Y-m-d H:i:s');
                                        echo $date; 
?></p>

<p><?php
$address = $_SERVER['REMOTE_ADDR'];
echo "Your current IP address is: $address";
?></p>

</body>
</html>