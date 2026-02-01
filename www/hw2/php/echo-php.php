<?php
header('Cache-Control: no-cache');
header('Content-Type: text/html');
?>

<!DOCTYPE html>
<html><head><title>General Request Echo</title>
</head><body><h1 align="center">General Request Echo</h1>
<hr>


<p><b>HTTP Protocol:</b> $ENV{SERVER_PROTOCOL}</p>
<p><b>HTTP Method:</b> $ENV{REQUEST_METHOD}</p>
<p><b>Query String:</b> $ENV{QUERY_STRING}</p>

<?php
$form_data = file_get_contents('php://input');
$bytes_read = strlen($form_data);
?>

<p><b>Message Body:</b> $form_data</p>
</body></html>