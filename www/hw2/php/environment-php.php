<?php
header('Cache-Control: no-cache');
header('Content-Type: text/html');
?>

<!DOCTYPE html>
<html><head><title>Environment Variables</title>
</head><body><h1 align="center">Environment Variables</h1>
<hr>

<?php

foreach ($_SERVER as $variable => $value) {
    print "<b>$variable:</b> $value<br />\n";
}

?>

</body></html>