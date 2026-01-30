<?php
header('Cache-Control: no-cache');
header('Content-Type: text/json');
?>
<?php $date =  date('Y-m-d H:i:s'); ?>

<?php
$address = $_SERVER['REMOTE_ADDR'];
?>

<?php
$message = array(
    'title' => 'Hello, PHP!',
    'heading' => 'Hello, PHP!',
    'message' => 'This page was generated with the PHP programming language',
    'time' => $date,
    'IP' => $address
);

$json = json_encode($message);

echo $json . "\n";
?>