<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$me = (int)$_SESSION["id"];
$selected = isset($_GET["user"]) ? (int)$_GET["user"] : 0;

$stmt = mysqli_prepare($conn, "SELECT id, username FROM users WHERE id <> ? ORDER BY username");
mysqli_stmt_bind_param($stmt, "i", $me);
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);

$selected_name = "";
if ($selected > 0) {
    $stmt2 = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ? AND id <> ?");
    mysqli_stmt_bind_param($stmt2, "ii", $selected, $me);
    mysqli_stmt_execute($stmt2);
    $r = mysqli_stmt_get_result($stmt2);
    if ($row = mysqli_fetch_assoc($r)) $selected_name = $row["username"];
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Messages</title>
<style>
body{font-family:Arial;margin:0;background:#f4f4f4}
.wrap{display:flex;height:100vh}
.users{width:260px;background:#222;color:#fff;padding:20px;box-sizing:border-box}
.users a{display:block;color:#fff;text-decoration:none;padding:12px;border-radius:6px;margin:5px 0}
.users a:hover,.users a.active{background:#444}
.chat{flex:1;display:flex;flex-direction:column}
.top{background:#fff;padding:18px;border-bottom:1px solid #ddd}
.box{flex:1;padding:20px;overflow:auto}
.msg{max-width:65%;padding:10px 14px;margin:8px 0;border-radius:12px;white-space:pre-wrap}
.mine{margin-left:auto;background:#d8f8d8}
.theirs{background:#fff}
form{display:flex;gap:8px;padding:15px;background:#fff;border-top:1px solid #ddd}
textarea{flex:1;resize:none;padding:10px}
button{padding:10px 15px;cursor:pointer}
.note{padding:20px;color:#666}
</style>
</head>
<body>
<div class="wrap">
  <aside class="users">
    <h3>Users</h3>
    <?php while ($u = mysqli_fetch_assoc($users)): ?>
      <a class="<?= $selected === (int)$u["id"] ? "active" : "" ?>"
         href="messages.php?user=<?= (int)$u["id"] ?>">
        <?= htmlspecialchars($u["username"]) ?>
      </a>
    <?php endwhile; ?>
    <br>
    <a href="dashboard.php">← Dashboard</a>
  </aside>

  <main class="chat">
    <div class="top">
      <strong><?= $selected_name ? "Chat with " . htmlspecialchars($selected_name) : "Messages" ?></strong>
    </div>

    <div id="messages" class="box">
      <?php if (!$selected_name): ?>
        <div class="note">Select a user to start messaging.</div>
      <?php endif; ?>
    </div>

    <?php if ($selected_name): ?>
    <form id="sendForm">
      <textarea id="message" rows="2" placeholder="Type a message..." required></textarea>
      <button type="submit">Send</button>
    </form>
    <?php endif; ?>
  </main>
</div>

<?php if ($selected_name): ?>
<script>
const receiver = <?= $selected ?>;
const box = document.getElementById("messages");

function escapeHtml(s) {
  const d = document.createElement("div");
  d.textContent = s;
  return d.innerHTML;
}

async function loadMessages() {
  const r = await fetch("message_api.php?action=list&user=" + receiver);
  const data = await r.json();
  if (!data.ok) return;

  box.innerHTML = "";
  for (const m of data.messages) {
    const div = document.createElement("div");
    div.className = "msg " + (m.mine ? "mine" : "theirs");
    div.innerHTML = escapeHtml(m.message);
    box.appendChild(div);
  }
  box.scrollTop = box.scrollHeight;
}

document.getElementById("sendForm").addEventListener("submit", async e => {
  e.preventDefault();
  const input = document.getElementById("message");
  const message = input.value.trim();
  if (!message) return;

  const body = new URLSearchParams();
  body.set("receiver", receiver);
  body.set("message", message);

  const r = await fetch("message_api.php?action=send", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body
  });
  const data = await r.json();

  if (data.ok) {
    input.value = "";
    loadMessages();
  } else {
    alert(data.error || "Could not send message.");
  }
});

loadMessages();
setInterval(loadMessages, 2000);
</script>
<?php endif; ?>
</body>
</html>
<?php mysqli_close($conn); ?>
