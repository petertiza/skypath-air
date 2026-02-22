<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require "db.php";

$id = (int)($_GET["id"] ?? 0);
$db = Database::connect();

$stmt = $db->prepare("
  UPDATE reservations
  SET status = 'cancelled'
  WHERE id = ?
");
$stmt->execute([$id]);

echo json_encode(["success" => true]);
