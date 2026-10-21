<?php

header('Content-Type: application/json; charset=utf-8');

function fail(int $status, array $errors): void
{
    http_response_code($status);
    echo json_encode(["errors" => $errors]);
    exit;
}

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$price = isset($_GET['price']) ? trim($_GET['price']) : '';
$number = isset($_GET['number']) ? trim($_GET['number']) : '';

$errors = [];

if (strlen($type) < 3) {
    $errors[] = "Error($type): this type does not have enough characters.";
} elseif (strlen($type) > 10) {
    $errors[] = "Error($type): this type has too many characters.";
} elseif (!preg_match('/^[a-z-]+$/i', $type)) {
    $errors[] = "Error($type): this type has non-alphabetical characters (different from '-').";
}

if (strlen($brand) < 2) {
    $errors[] = "Error($brand): this brand does not have enough characters.";
} elseif (strlen($brand) > 20) {
    $errors[] = "Error($brand): this brand has too much characters.";
} elseif (!preg_match('/^[a-z0-9&-]+$/i', $brand)) {
    $errors[] = "Error($brand): this brand has invalid characters.";
}

if (strlen($price) < 2) {
    $errors[] = "Error($price): this price does not have enough characters.";
} elseif (strlen($price) > 5) {
    $errors[] = "Error($price): this price has too many characters.";
} elseif (!preg_match('/^[<>=][0-9]+$/', $price)) {
    $errors[] = "Error($price): we cannot define a price - string invalid.";
}

if (!preg_match('/^[0-9]+$/', $number) || (int)$number <= 0) {
    $errors[] = "Error($number): not a positive number.";
}

if (!empty($errors)) {
    fail(400, $errors);
}

// 2. DB 검사
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../ajax_products.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    fail(500, ["Error: database connection failed."]);
}

$query = $db->prepare('SELECT COUNT(*) FROM products WHERE LOWER(type) = LOWER(?)');
$query->execute([$type]);
if ($query->fetchColumn() == 0) {
    $errors[] = "Error($type): this type doesn't exist in our shop.";
}

$query = $db->prepare('SELECT COUNT(*) FROM products WHERE LOWER(type) = LOWER(?) AND LOWER(brand) = LOWER(?)');
$query->execute([$type, $brand]);
if ($query->fetchColumn() == 0) {
    $errors[] = "Error($brand): this brand doesn't exist in our database.";
}

if (!empty($errors)) {
    fail(404, $errors);
}

$operators = ['<' => '<', '>' => '>', '=' => '='];
$operator = $operators[$price[0]];
$priceValue = (int)substr($price, 1);

$query = $db->prepare("SELECT type, brand, price, stock FROM products
    WHERE LOWER(type) = LOWER(?) AND LOWER(brand) = LOWER(?) AND price $operator ?");
$query->execute([$type, $brand, $priceValue]);
$rows = $query->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    fail(404, ["Error($price): no products found at this price."]);
}

$products = [];
foreach ($rows as $row) {
    if ((int)$number > (int)$row['stock']) {
        $errors[] = "Error($number): sorry, we don't have enough stock, we only have {$row['stock']} in stock.";
        continue;
    }
    $products[] = [
        "type" => $row['type'],
        "brand" => $row['brand'],
        "price" => (int)$row['price'],
        "number" => (int)$number,
        "stock" => (int)$row['stock']
    ];
}

if (empty($products)) {
    fail(409, $errors);
}

echo json_encode(["products" => $products]);