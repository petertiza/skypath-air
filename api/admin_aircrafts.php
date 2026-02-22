<?php
require "db.php";
header("Content-Type: application/json");

$db = Database::connect();

$stmt = $db->query("
  SELECT *,
    (seat_rows * seats_per_row) AS capacity
  FROM aircrafts
  ORDER BY code
");

echo json_encode([
  "success" => true,
  "aircrafts" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
