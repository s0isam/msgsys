<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

/*
 * Demo message encryption/decryption.
 * IMPORTANT: keep this key outside the web root in a real deployment.
 * Generate a key with:
 * php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
 */
$key_b64 = "REPLACE_WITH_32_BYTE_BASE64_KEY";
$key = base64_decode($key_b64, true);

function enc_msg($plaintext, $key) {
    $iv = random_bytes(12);
    $tag = "";
    $ciphertext = openssl_encrypt(
        $plaintext, "aes-256-gcm", $key,
        OPENSSL_RAW_DATA, $iv, $tag
    );
    return base64_encode($iv . $tag . $ciphertext);
}

function dec_msg($encoded, $key) {
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 28) return false;
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    return openssl_decrypt(
        $ciphertext, "aes-256-gcm", $key,
        OPENSSL_RAW_DATA, $iv, $tag
    );
}

$result = null;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $input = $_POST["message"] ?? "";

    if ($action === "encrypt") {
        $result = enc_msg($input, $key);
    } elseif ($action === "decrypt") {
        $result = dec_msg($input, $key);
        if ($result === false) $result = "Decryption failed.";
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Encrypt / Decrypt</title></head>
<body>
<h2>Message Encryption / Decryption</h2>
<form method="post">
<textarea name="message" rows="8" cols="70" required><?= htmlspecialchars($_POST["message"] ?? "") ?></textarea><br><br>
<button name="action" value="encrypt">Encrypt</button>
<button name="action" value="decrypt">Decrypt</button>
</form>
<?php if ($result !== null): ?>
<h3>Result</h3>
<textarea rows="8" cols="70" readonly><?= htmlspecialchars($result) ?></textarea>
<?php endif; ?>
<p><a href="messages.php">← Messages</a></p>
</body>
</html>
