<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require __DIR__ . "/db.php";

require_once "helpers/cleanup_holds.php";

$pdo = Database::connect();

$flight_id = isset($_GET["flight_id"]) ? (int)$_GET["flight_id"] : 0;

if ($flight_id <= 0) {
  echo json_encode([]);
  exit;
}

$pdo->prepare("
  UPDATE reservations
  SET status = 'cancelled'
  WHERE status = 'hold'
    AND hold_until < NOW()
")->execute();

$stmt = $pdo->prepare("
  SELECT seat_number
  FROM reservations
  WHERE flight_id = :flight_id
    AND (
      status = 'confirmed'
      OR (status = 'hold' AND hold_until > NOW())
    )
");

$stmt->execute([
  ":flight_id" => $flight_id
]);

$taken = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($taken);
