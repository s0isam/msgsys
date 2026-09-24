<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

function shiftEncrypt($text, $shift) {
    $result = "";

    // Printable ASCII: 32 through 126 = 95 characters
    $min = 32;
    $range = 95;

    for ($i = 0; $i < strlen($text); $i++) {
        $ascii = ord($text[$i]);

        if ($ascii >= $min && $ascii <= 126) {
            $newAscii = (($ascii - $min + $shift) % $range) + $min;
            $result .= chr($newAscii);
        } else {
            // Keep non-printable/non-ASCII characters unchanged
            $result .= $text[$i];
        }
    }

    return $result;
}

function shiftDecrypt($text, $shift) {
    $result = "";

    $min = 32;
    $range = 95;

    for ($i = 0; $i < strlen($text); $i++) {
        $ascii = ord($text[$i]);

        if ($ascii >= $min && $ascii <= 126) {
            $newAscii = (($ascii - $min - $shift + $range) % $range) + $min;
            $result .= chr($newAscii);
        } else {
            $result .= $text[$i];
        }
    }

    return $result;
}

$result = "";
$shift = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $text = $_POST["message"] ?? "";
    $shift = (int)($_POST["shift"] ?? 0);

    if ($action === "encrypt") {

        // Generate a new random number from 1-94
        $shift = random_int(1, 94);

        $result = shiftEncrypt($text, $shift);

    } elseif ($action === "decrypt") {

        if ($shift < 1 || $shift > 94) {
            $result = "Invalid key. Enter a number between 1 and 94.";
        } else {
            $result = shiftDecrypt($text, $shift);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Privacy Mode</title>

    <style>
        body {
            font-family: Arial;
            background: #f2f2f2;
            padding: 30px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin-bottom: 15px;
        }

        input {
            padding: 10px;
            width: 100px;
        }

        button {
            padding: 10px 18px;
            margin: 5px;
            cursor: pointer;
        }

        .result {
            background: #eee;
            padding: 15px;
            word-break: break-all;
        }

        .key {
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Privacy Mode</h2>

    <form method="POST">

        <label>Message</label><br>

        <textarea
            name="message"
            rows="7"
            placeholder="Enter your message..."
            required><?= htmlspecialchars($_POST["message"] ?? "") ?></textarea>

        <br>

        <label>Key / Random Number</label><br>

        <input
            type="number"
            name="shift"
            min="1"
            max="94"
            value="<?= htmlspecialchars($shift) ?>"
            placeholder="Key">

        <br><br>

        <button type="submit" name="action" value="encrypt">
            Encrypt
        </button>

        <button type="submit" name="action" value="decrypt">
            Decrypt
        </button>

    </form>

    <?php if ($result !== ""): ?>

        <h3>Result</h3>

        <div class="result">
            <?= htmlspecialchars($result) ?>
        </div>

        <?php if (isset($action) && $action === "encrypt"): ?>

            <p>
                <strong>Your random key:</strong>
                <span class="key"><?= $shift ?></span>
            </p>

            <p>
                Give this key to the recipient separately.
            </p>

        <?php endif; ?>

    <?php endif; ?>

    <br>

    <a href="messages.php">← Back to Messages</a>

</div>

</body>
</html>