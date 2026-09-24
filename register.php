<?php
require_once "config.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    // Hash the password for security before saving
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // Use prepared statements to prevent SQL injection
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
      if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Registration successful! Please login.'); window.location='login.php';</script>";
        } else {
            echo "Something went wrong. Username might already be taken.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html>
<head><title>Register</title></head>
<body>
    <h2>Sign Up</h2>
    <form action="register.php" method="post">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <input type="submit" value="Register">
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</body>
</html>
