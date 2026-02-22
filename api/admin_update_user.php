<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = (int)$data["id"];
$role = $data["role"];
$is_active = (int)$data["is_active"];

$db = Database::connect();

$stmt = $db->prepare("
  UPDATE users 
  SET role = ?, is_active = ?
  WHERE id = ?
");
$stmt->execute([$role, $is_active, $id]);

echo json_encode(["success" => true]);
