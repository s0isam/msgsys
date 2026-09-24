<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
    <p>This is a secure page accessible only to logged-in users.</p>
    <a href="messages.php">Messages</a>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>

    <p>This is a secure page accessible only to logged-in users.</p>
    <a href="messages.php">Messages</a><br><br>
    <a href="logout.php">Log Out</a>
</body>
</html>
logout.php (Clearing data)
<?php
session_start();
$_SESSION = array();
session_destroy();
header("location: login.php");
exit;
?>
