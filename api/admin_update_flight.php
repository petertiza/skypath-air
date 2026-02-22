<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once "db.php";

$id = $_GET["id"] ?? null;
$data = json_decode(file_get_contents("php://input"), true);

if (!$id) {
  echo json_encode(["success" => false, "message" => "Chýba ID letu"]);
  exit;
}

$db = Database::connect();

$stmt = $db->prepare("
  UPDATE flights SET
    flight_number = ?,
    aircraft_type = ?,
    departure_city = ?,
    arrival_city = ?,
    departure_time = ?,
    arrival_time = ?,
    price = ?
  WHERE id = ?
");

$stmt->execute([
  $data["flight_number"],
  $data["aircraft_type"],
  $data["departure_city"],
  $data["arrival_city"],
  $data["departure_time"],
  $data["arrival_time"],
  $data["price"],
  $id
]);

echo json_encode(["success" => true, "message" => "Let bol upravený"]);