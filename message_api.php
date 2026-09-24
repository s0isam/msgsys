<?php
session_start();
header("Content-Type: application/json");
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(["ok"=>false,"error"=>"Not logged in"]);
    exit;
}

$me = (int)$_SESSION["id"];
$action = $_GET["action"] ?? "";

if ($action === "list") {
    $other = (int)($_GET["user"] ?? 0);

    $stmt = mysqli_prepare($conn,
        "SELECT id, sender_id, message, created_at
         FROM messages
         WHERE (sender_id=? AND receiver_id=?)
            OR (sender_id=? AND receiver_id=?)
         ORDER BY created_at ASC, id ASC");
    mysqli_stmt_bind_param($stmt, "iiii", $me, $other, $other, $me);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            "id" => (int)$row["id"],
            "message" => $row["message"],
            "created_at" => $row["created_at"],
            "mine" => (int)$row["sender_id"] === $me
        ];
    }

    echo json_encode(["ok"=>true,"messages"=>$messages]);
    exit;
}

if ($action === "send") {
    $receiver = (int)($_POST["receiver"] ?? 0);
    $message = trim($_POST["message"] ?? "");

    if ($receiver <= 0 || $receiver === $me || $message === "") {
        echo json_encode(["ok"=>false,"error"=>"Invalid message"]);
        exit;
    }

    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE id=?");
    mysqli_stmt_bind_param($check, "i", $receiver);
    mysqli_stmt_execute($check);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
        echo json_encode(["ok"=>false,"error"=>"User not found"]);
        exit;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iis", $me, $receiver, $message);

    echo json_encode(["ok"=>mysqli_stmt_execute($stmt)]);
    exit;
}

echo json_encode(["ok"=>false,"error"=>"Invalid action"]);
