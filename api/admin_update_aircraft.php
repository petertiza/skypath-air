<?php
require "db.php";
header("Content-Type: application/json");

$id = (int)$_GET["id"];
$data = json_decode(file_get_contents("php://input"), true);

$db = Database::connect();
$stmt = $db->prepare("
  UPDATE aircrafts SET
    code = ?,
    manufacturer = ?,
    model = ?,
    seat_rows = ?,
    seats_per_row = ?,
    active = ?
  WHERE id = ?
");

$stmt->execute([
  strtoupper($data["code"]),
  $data["manufacturer"],
  $data["model"],
  (int)$data["seat_rows"],
  (int)$data["seats_per_row"],
  (int)$data["active"],
  $id
]);

echo json_encode(["success" => true]);