<?php
require "db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$db = Database::connect();
$stmt = $db->prepare("
  INSERT INTO aircrafts
  (code, manufacturer, model, seat_rows, seats_per_row, active)
  VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
  strtoupper($data["code"]),
  $data["manufacturer"],
  $data["model"],
  (int)$data["seat_rows"],
  (int)$data["seats_per_row"],
  (int)$data["active"]
]);

echo json_encode(["success" => true]);