<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id    = (int)($data["user_id"] ?? 0);
$flight_id  = (int)($data["flight_id"] ?? 0);
$seat       = trim($data["seat_number"] ?? "");

if (!$user_id || !$flight_id || !$seat) {
  echo json_encode(["success" => false]);
  exit;
}

$db = Database::connect();

$stmt = $db->prepare("
  INSERT INTO reservations (flight_id, seat_number, user_id, status)
  VALUES (?, ?, ?, 'hold')
");

$stmt->execute([$flight_id, $seat, $user_id]);

echo json_encode(["success" => true]);
