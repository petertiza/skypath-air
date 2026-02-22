<?php
require "db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$user = $_SESSION['user'] ?? null;
if (!$user) exit;

$db = Database::connect();

$stmt = $db->prepare("
  UPDATE reservations
  SET status = 'cancelled'
  WHERE flight_id = ?
    AND seat_number = ?
    AND user_id = ?
    AND status = 'hold'
");

$stmt->execute([
  $data['flight_id'],
  $data['seat_number'],
  $user['id']
]);

echo json_encode(["success" => true]);