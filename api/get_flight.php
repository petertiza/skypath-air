<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "db.php";
require_once "helpers/cleanup_holds.php";

if (!isset($_GET['id'])) {
  echo json_encode(["success" => false, "message" => "ID letu chýba"]);
  exit;
}

$id = intval($_GET['id']);
$db = Database::connect();

try {
  $stmt = $db->prepare("
    SELECT 
      f.id,
      f.flight_number,
      f.departure_city,
      f.arrival_city,
      f.departure_time,
      f.arrival_time,
      f.price,
      f.seats_available,
      f.aircraft_type,
      COALESCE(a.seat_rows, 30) as seat_rows,
      COALESCE(a.seats_per_row, 6) as seats_per_row
    FROM flights f
    LEFT JOIN aircrafts a ON a.code = f.aircraft_type
    LEFT JOIN reservations r ON r.flight_id = f.id AND r.status = 'reserved'
    WHERE f.id = ?
    GROUP BY f.id
  ");

  $stmt->execute([$id]);
  $flight = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$flight) {
    echo json_encode(["success" => false, "message" => "Let ID $id nebol nájdený"]);
    exit;
  }

  echo json_encode([
    "success" => true,
    "flight" => $flight
  ]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false, 
    "message" => "DB Error: " . $e->getMessage()
  ]);
}
?>
