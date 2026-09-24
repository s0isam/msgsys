<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$me = (int)$_SESSION["id"];
$selected = isset($_GET["user"]) ? (int)$_GET["user"] : 0;

/*
|--------------------------------------------------------------------------
| ASCII SHIFT ENCRYPTION
|--------------------------------------------------------------------------
| Printable ASCII characters: 32-126
| Random key: 1-94
|
| Example:
| a + 5 = f
| z + 5 = {
|
| Wrap-around is used so characters stay printable.
|--------------------------------------------------------------------------
*/

function encryptMessage($text, $key)
{
    $result = "";
    $min = 32;
    $range = 95;

    for ($i = 0; $i < strlen($text); $i++) {

        $ascii = ord($text[$i]);

        if ($ascii >= 32 && $ascii <= 126) {

            $newAscii =
                (($ascii - $min + $key) % $range) + $min;

            $result .= chr($newAscii);

        } else {

            $result .= $text[$i];

        }
    }

    return $result;
}

function decryptMessage($text, $key)
{
    $result = "";
    $min = 32;
    $range = 95;

    for ($i = 0; $i < strlen($text); $i++) {

        $ascii = ord($text[$i]);

        if ($ascii >= 32 && $ascii <= 126) {

            $newAscii =
                (($ascii - $min - $key + $range) % $range) + $min;

            $result .= chr($newAscii);

        } else {

            $result .= $text[$i];

        }
    }

    return $result;
}


/*
|--------------------------------------------------------------------------
| GET USERS
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, username
     FROM users
     WHERE id <> ?
     ORDER BY username"
);

mysqli_stmt_bind_param($stmt, "i", $me);
mysqli_stmt_execute($stmt);

$users = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| SELECTED USER
|--------------------------------------------------------------------------
*/

$selected_name = "";

if ($selected > 0) {

    $stmt2 = mysqli_prepare(
        $conn,
        "SELECT username
         FROM users
         WHERE id = ?
         AND id <> ?"
    );

    mysqli_stmt_bind_param(
        $stmt2,
        "ii",
        $selected,
        $me
    );

    mysqli_stmt_execute($stmt2);

    $result = mysqli_stmt_get_result($stmt2);

    if ($row = mysqli_fetch_assoc($result)) {
        $selected_name = $row["username"];
    }
}

?>
<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Private Messages</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f1f1;
}

.container {
    display: flex;
    height: 100vh;
}

/* USERS */

.users {
    width: 260px;
    background: #202020;
    color: white;
    padding: 20px;
}

.users h2 {
    margin-top: 0;
}

.user {
    display: block;
    color: white;
    text-decoration: none;
    padding: 12px;
    margin: 5px 0;
    border-radius: 7px;
}

.user:hover {
    background: #333;
}

.user.active {
    background: #444;
}

/* CHAT */

.chat {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.header {
    background: white;
    padding: 18px;
    border-bottom: 1px solid #ddd;
    font-size: 18px;
    font-weight: bold;
}

.messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.message {
    max-width: 65%;
    padding: 12px 15px;
    margin: 10px 0;
    border-radius: 12px;
    white-space: pre-wrap;
    word-break: break-word;
}

.mine {
    margin-left: auto;
    background: #d8f8d8;
}

.theirs {
    background: white;
}

.message-key {
    font-size: 11px;
    color: #777;
    margin-top: 5px;
}

/* SEND */

.send-area {
    background: white;
    border-top: 1px solid #ddd;
    padding: 15px;
}

textarea {
    width: 100%;
    resize: none;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

.buttons {
    margin-top: 10px;
}

button {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.send {
    background: #222;
    color: white;
}

.privacy {
    background: #555;
    color: white;
}

.notice {
    background: #fff3cd;
    padding: 10px;
    margin-top: 10px;
    border-radius: 6px;
    font-size: 13px;
}

.empty {
    color: #777;
    text-align: center;
    margin-top: 100px;
}

</style>

</head>

<body>

<div class="container">

<!-- USER LIST -->

<div class="users">

<h2>Users</h2>

<?php while ($user = mysqli_fetch_assoc($users)): ?>

<a
class="user <?= $selected === (int)$user["id"] ? "active" : "" ?>"
href="messages.php?user=<?= (int)$user["id"] ?>"
>

<?= htmlspecialchars($user["username"]) ?>

</a>

<?php endwhile; ?>

<br>

<a class="user" href="dashboard.php">
← Dashboard
</a>

</div>


<!-- CHAT -->

<div class="chat">

<div class="header">

<?php if ($selected_name): ?>

Chat with <?= htmlspecialchars($selected_name) ?>

<?php else: ?>

Private Messages

<?php endif; ?>

</div>


<div id="messages" class="messages">

<?php if (!$selected_name): ?>

<div class="empty">

Select a user to start messaging.

</div>

<?php endif; ?>

</div>


<?php if ($selected_name): ?>

<div class="send-area">

<textarea
id="message"
rows="3"
placeholder="Write your message..."
></textarea>


<div class="buttons">

<button
class="send"
onclick="sendMessage()"
>
Send
</button>


<button
class="privacy"
onclick="encryptAndSend()"
>
🔐 Encrypt & Send
</button>


<button
class="privacy"
onclick="decryptSelected()"
>
🔓 Decrypt
</button>

</div>


<div class="notice">

<b>Privacy Mode:</b>

Encrypt & Send generates a random ASCII shift key for the message.

Keep the key if you need to decrypt the message later.

</div>

</div>

<?php endif; ?>

</div>

</div>


<script>

const receiver = <?php echo (int)$selected; ?>;

function encryptMessage(text, key) {
    let result = "";
    const min = 32;
    const range = 95;

    for (let i = 0; i < text.length; i++) {
        const ascii = text.charCodeAt(i);
        if (ascii >= 32 && ascii <= 126) {
            result += String.fromCharCode(((ascii - min + key) % range) + min);
        } else {
            result += text[i];
        }
    }
    return result;
}

function decryptMessage(text, key) {
    let result = "";
    const min = 32;
    const range = 95;

    for (let i = 0; i < text.length; i++) {
        const ascii = text.charCodeAt(i);
        if (ascii >= 32 && ascii <= 126) {
            result += String.fromCharCode(((ascii - min - key + range) % range) + min);
        } else {
            result += text[i];
        }
    }
    return result;
}

async function sendMessage() {
    const input = document.getElementById("message");
    if (!input) return;

    const message = input.value.trim();
    if (!message) {
        alert("Please enter a message.");
        return;
    }

    try {
        const body = new URLSearchParams();
        body.set("receiver", receiver);
        body.set("message", message);

        const response = await fetch("message_api.php?action=send", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body.toString()
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("message_api.php returned:", text);
            alert("Server error. Check message_api.php.");
            return;
        }

        if (!data.ok) {
            alert(data.error || "Message could not be sent.");
            return;
        }

        input.value = "";
        await loadMessages();
    } catch (e) {
        console.error(e);
        alert("Could not connect to message server.");
    }
}

async function encryptAndSend() {
    const input = document.getElementById("message");
    if (!input) return;

    const plainText = input.value.trim();
    if (!plainText) {
        alert("Please enter a message.");
        return;
    }

    const key = Math.floor(Math.random() * 94) + 1;
    const encrypted = encryptMessage(plainText, key);
    const storedMessage = "[[PRIVATE:" + key + "]]" + encrypted;

    try {
        const body = new URLSearchParams();
        body.set("receiver", receiver);
        body.set("message", storedMessage);

        const response = await fetch("message_api.php?action=send", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body.toString()
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("message_api.php returned:", text);
            alert("Server error. Check message_api.php.");
            return;
        }

        if (!data.ok) {
            alert(data.error || "Encrypted message could not be sent.");
            return;
        }

        input.value = "";
        alert("Message encrypted.\n\nEncryption key: " + key + "\n\nKeep this key to decrypt the message.");
        await loadMessages();
    } catch (e) {
        console.error(e);
        alert("Could not connect to message server.");
    }
}

async function loadMessages() {
    if (!receiver) return;

    try {
        const response = await fetch("message_api.php?action=list&user=" + encodeURIComponent(receiver));
        const text = await response.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("message_api.php returned:", text);
            return;
        }

        if (!data.ok) {
            console.error(data.error || "Could not load messages.");
            return;
        }

        const box = document.getElementById("messages");
        if (!box) return;
        box.innerHTML = "";

        data.messages.forEach(function(message) {
            const div = document.createElement("div");
            div.className = "message " + (message.mine ? "mine" : "theirs");

            if (typeof message.message === "string" && message.message.indexOf("[[PRIVATE:") === 0) {
                const end = message.message.indexOf("]]" );

                if (end !== -1) {
                    const key = parseInt(message.message.substring(10, end), 10);
                    const encrypted = message.message.substring(end + 2);

                    div.dataset.encrypted = encrypted;
                    div.dataset.key = key;

                    div.innerHTML =
                        "<b>🔐 Encrypted message</b>" +
                        "<br><br>" +
                        escapeHtml(encrypted) +
                        "<div class='message-key'>Key required to decrypt</div>";
                } else {
                    div.textContent = message.message;
                }
            } else {
                div.textContent = message.message;
            }

            box.appendChild(div);
        });

        box.scrollTop = box.scrollHeight;
    } catch (e) {
        console.error("Could not load messages:", e);
    }
}

function decryptSelected() {
    const encryptedMessages = document.querySelectorAll(".message[data-encrypted]");

    if (encryptedMessages.length === 0) {
        alert("There are no encrypted messages.");
        return;
    }

    const div = encryptedMessages[encryptedMessages.length - 1];
    const encrypted = div.dataset.encrypted;
    const actualKey = parseInt(div.dataset.key, 10);
    const enteredKey = prompt("Enter the encryption key:");

    if (enteredKey === null) return;

    const key = parseInt(enteredKey, 10);

    if (key !== actualKey) {
        alert("Wrong encryption key.");
        return;
    }

    const decrypted = decryptMessage(encrypted, key);

    div.innerHTML =
        "<b>🔓 Decrypted message</b>" +
        "<br><br>" +
        escapeHtml(decrypted);
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

loadMessages();
setInterval(loadMessages, 2000);

</script>

</body>

</html>

<?php

mysqli_close($conn);

?>