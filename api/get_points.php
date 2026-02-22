<?php
require_once "db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$user_id = (int)($data["user_id"] ?? 0);

if (!$user_id) {
    echo json_encode(["points" => 0]);
    exit;
}

$db = Database::connect();
$stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$points = $result ? (int)($result["points"] ?? 0) : 0;

echo json_encode(["points" => $points]);
?>
