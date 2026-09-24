<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Default XAMPP MySQL password is empty
define('DB_NAME', 'auth_demo');
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conn) {
    die("ERROR: Connection failed. " . mysqli_connect_error());
}
?>
