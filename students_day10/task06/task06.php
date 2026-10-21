<?php

header('Content-Type: application/json; charset=utf-8');

function fail(int $status, array $errors): void
{
    http_response_code($status);
    echo json_encode(["errors" => $errors]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(400, ["Error: only POST requests are allowed."]);
}

$type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : '';
$brand = isset($_POST['brand']) ? strtolower(trim($_POST['brand'])) : '';
$price = isset($_POST['price']) ? trim($_POST['price']) : '';
$number = isset($_POST['number']) ? trim($_POST['number']) : '';

$errors = [];

if (strlen($type) < 3) {
    $errors[] = "Error($type): this type does not have enough characters.";
} elseif (strlen($type) > 10) {
    $errors[] = "Error($type): this type has too many characters.";
} elseif (!preg_match('/^[a-z-]+$/', $type)) {
    $errors[] = "Error($type): this type has non-alphabetical characters (different from '-').";
}

if (strlen($brand) < 2) {
    $errors[] = "Error($brand): this brand does not have enough characters.";
} elseif (strlen($brand) > 20) {
    $errors[] = "Error($brand): this brand has too much characters.";
} elseif (!preg_match('/^[a-z0-9&-]+$/', $brand)) {
    $errors[] = "Error($brand): this brand has invalid characters.";
}

if (!preg_match('/^=?[0-9]{1,5}$/', $price)) {
    $errors[] = "Error($price): we cannot define a price - string invalid.";
}

if (!preg_match('/^[0-9]{1,5}$/', $number)) {
    $errors[] = "Error($number): the stock must be a number greater than or equal to 0.";
}

if (!empty($errors)) {
    fail(400, $errors);
}

$priceValue = (int)ltrim($price, '=');
$stockValue = (int)$number;

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../ajax_products.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = $db->prepare('SELECT id FROM products WHERE LOWER(type) = ? AND LOWER(brand) = ?');
    $query->execute([$type, $brand]);
    $id = $query->fetchColumn();

    if ($id === false) {
        $query = $db->prepare('INSERT INTO products (type, brand, price, stock) VALUES (?, ?, ?, ?)');
        $query->execute([$type, $brand, $priceValue, $stockValue]);
        $message = "$brand ($type) has been added to the database.";
    } else {
        $query = $db->prepare('UPDATE products SET price = ?, stock = ? WHERE id = ?');
        $query->execute([$priceValue, $stockValue, $id]);
        $message = "$brand ($type) has been updated: price and stock changed.";
    }
} catch (PDOException $e) {
    fail(500, ["Error: database error."]);
}

echo json_encode([
    "message" => $message,
    "product" => [
        "type" => $type,
        "brand" => $brand,
        "price" => $priceValue,
        "stock" => $stockValue
    ]
]);
