<?php

header('Content-Type: application/json; charset=utf-8');

function fail(int $status, array $errors): void
{
    http_response_code($status);
    echo json_encode(["errors" => $errors]);
    exit;
}

function textLength(string $text): int
{
    return preg_match_all('/./su', $text);
}

try {
    $db = new PDO('sqlite:' . __DIR__ . '/chat.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        message TEXT NOT NULL,
        created_at INTEGER NOT NULL
    )');
} catch (PDOException $e) {
    fail(500, ["Error: database connection failed."]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    $errors = [];
    if ($name === '') {
        $errors[] = "Error: please enter your name.";
    } elseif (textLength($name) > 20) {
        $errors[] = "Error: the name is too long (20 characters max).";
    }
    if ($message === '') {
        $errors[] = "Error: please enter a message.";
    } elseif (textLength($message) > 500) {
        $errors[] = "Error: the message is too long (500 characters max).";
    }
    if (!empty($errors)) {
        fail(400, $errors);
    }

    $query = $db->prepare('INSERT INTO messages (name, message, created_at) VALUES (?, ?, ?)');
    $query->execute([$name, $message, time()]);

    echo json_encode(["id" => (int)$db->lastInsertId()]);
    exit;
}

$after = isset($_GET['after']) ? (int)$_GET['after'] : 0;

if ($after === 0) {
    $query = $db->query('SELECT * FROM (SELECT id, name, message, created_at FROM messages ORDER BY id DESC LIMIT 50) ORDER BY id ASC');
} else {
    $query = $db->prepare('SELECT id, name, message, created_at FROM messages WHERE id > ? ORDER BY id ASC');
    $query->execute([$after]);
}

$messages = [];
foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $messages[] = [
        "id" => (int)$row['id'],
        "name" => $row['name'],
        "message" => $row['message'],
        "created_at" => (int)$row['created_at']
    ];
}

echo json_encode(["messages" => $messages]);
