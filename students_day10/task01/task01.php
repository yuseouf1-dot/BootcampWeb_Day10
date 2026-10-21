<?php

header('Content-Type: application/json; charset=utf-8');

$input_name = isset($_GET['name']) ? $_GET['name'] : '';

$response_data = [
    "name" => $input_name
];

echo json_encode($response_data);
?>