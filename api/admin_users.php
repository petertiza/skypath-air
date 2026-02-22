<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require "db.php";

try {
    $db = Database::connect();

    $stmt = $db->query("
        SELECT id, fullname, email, role, is_active, points, created_at
        FROM users
        ORDER BY created_at DESC
    ");

    echo json_encode([
        "success" => true,
        "users" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
