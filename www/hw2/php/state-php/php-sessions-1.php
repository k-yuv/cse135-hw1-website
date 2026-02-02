<?php
// Start or resume session
session_start();

// Store Data in Session
if (isset($_POST['username'])) {
    $_SESSION['username'] = $_POST['username'];
}

$name = isset($_SESSION['username']) ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Sessions</title>
</head>
<body>

<h1>PHP Sessions Page 1</h1>

<?php if ($name): ?>
    <p><b>Name:</b> <?php echo htmlspecialchars($name); ?></p>
<?php else: ?>
    <p><b>Name:</b> You do not have a name set</p>
<?php endif; ?>

<br/><br/>
<a href="/cgi-bin/php-sessions-2.php">Session Page 2</a><br/>
<a href="/php-cgiform.html">PHP CGI Form</a><br />
<form style="margin-top:30px" action="/cgi-bin/php-destroy-session.php" method="get">
    <button type="submit">Destroy Session</button>
</form>

</body>
</html>