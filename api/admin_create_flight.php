<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$db = Database::connect();

$stmt = $db->prepare("
  INSERT INTO flights (
    flight_number,
    aircraft_type,
    departure_city,
    arrival_city,
    departure_time,
    arrival_time,
    price
  ) VALUES (?,?,?,?,?,?,?)
");

$stmt->execute([
  $data["flight_number"],
  $data["aircraft_type"],
  $data["departure_city"],
  $data["arrival_city"],
  $data["departure_time"],
  $data["arrival_time"],
  $data["price"]
]);

echo json_encode(["success" => true, "message" => "Let bol vytvorený"]);