<?php

header('Content-Type: application/json; charset=utf-8');

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../ajax_products.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    $error = ["valid" => false, "message" => "Database connection failed"];
    echo json_encode(["type" => $error, "brand" => $error]);
    exit;
}

function checkType(PDO $db, string $type): array
{
    if ($type === '') {
        return [false, "No type sent yet!"];
    }
    if (strlen($type) < 3) {
        return [false, "$type: this type does not have enough characters."];
    }
    if (strlen($type) > 10) {
        return [false, "$type: this type has too many characters."];
    }
    if (!preg_match('/^[a-z-]+$/i', $type)) {
        return [false, "$type: this type has non-alphabetical characters (different from '-')."];
    }

    $query = $db->prepare('SELECT COUNT(*) FROM products WHERE LOWER(type) = LOWER(?)');
    $query->execute([$type]);
    if ($query->fetchColumn() == 0) {
        return [false, "$type: this type doesn't exist in our shop."];
    }
    return [true, "$type: this type exists in databases."];
}

function checkBrand(PDO $db, string $brand, string $type): array
{
    if ($brand === '') {
        return [false, "No brand sent yet!"];
    }
    if (strlen($brand) < 2) {
        return [false, "$brand: this brand does not have enough characters."];
    }
    if (strlen($brand) > 20) {
        return [false, "$brand: this brand has too many characters."];
    }
    if (!preg_match('/^[a-z0-9&-]+$/i', $brand)) {
        return [false, "$brand: this brand has invalid characters."];
    }

    $query = $db->prepare('SELECT COUNT(*) FROM products WHERE LOWER(type) = LOWER(?) AND LOWER(brand) = LOWER(?)');
    $query->execute([$type, $brand]);
    if ($query->fetchColumn() > 0) {
        return [false, "$brand: this brand already exists in databases."];
    }
    return [true, "$brand: this brand is valid for the type $type."];
}

[$typeValid, $typeMessage] = checkType($db, $type);
[$brandValid, $brandMessage] = checkBrand($db, $brand, $type);

echo json_encode([
    "type" => ["valid" => $typeValid, "message" => $typeMessage],
    "brand" => ["valid" => $brandValid, "message" => $brandMessage]
]);
