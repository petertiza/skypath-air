<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once "db.php";

$id = $_GET["id"] ?? null;
if (!$id) {
  echo json_encode(["success" => false, "message" => "Chýba ID"]);
  exit;
}

$db = Database::connect();

$check = $db->prepare("
  SELECT COUNT(*) 
  FROM reservations 
  WHERE flight_id = ? AND status = 'reserved'
");
$check->execute([$id]);

if ($check->fetchColumn() > 0) {
  echo json_encode([
    "success" => false,
    "message" => "Let má aktívne rezervácie"
  ]);
  exit;
}

$stmt = $db->prepare("DELETE FROM flights WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["success" => true, "message" => "Let bol zmazaný"]);