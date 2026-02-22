<?php
require "db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$user = $_SESSION['user'] ?? null;

if (!$user) {
  http_response_code(401);
  exit;
}

$db = Database::connect();

$stmt = $db->prepare("
  SELECT id
  FROM reservations
  WHERE user_id = ?
    AND flight_id = ?
    AND seat_number = ?
    AND status = 'hold'
    AND hold_until > NOW()
  LIMIT 1
");

$stmt->execute([
  $user['id'],
  $data['flight_id'],
  $data['seat_number']
]);

$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
  echo json_encode([
    "success" => false,
    "message" => "Rezervácia vypršala"
  ]);
  exit;
}

$db->prepare("
  UPDATE reservations
  SET status = 'paid',
      payment_status = 'paid'
  WHERE id = ?
")->execute([$reservation['id']]);

echo json_encode(["success" => true]);
