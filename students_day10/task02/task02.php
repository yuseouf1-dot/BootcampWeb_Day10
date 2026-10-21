<?php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(["valid" => false, "message" => "Only POST requests are allowed"]);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    http_response_code(400);
    echo json_encode(["valid" => false, "message" => "Invalid email address"]);
    exit;
}

echo json_encode(["valid" => true, "message" => "Valid email address"]);
