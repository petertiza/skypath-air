<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

header("Content-Type: application/json");
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);
$user_id = (int)($data["user_id"] ?? 0);

if (!$user_id) {
  echo json_encode([]);
  exit;
}

$db = Database::connect();

$query = "
SELECT 
  r.id AS reservation_id,
  r.order_id,
  r.flight_id,
  r.seat_number,

  f.flight_number,
  f.departure_city,
  f.arrival_city,
  f.departure_time,
  f.arrival_time,

  r.status,
  r.created_at,
  o.checked_in
FROM reservations r
JOIN flights f ON r.flight_id = f.id
LEFT JOIN orders o ON r.order_id = o.id
WHERE r.user_id = ?
ORDER BY r.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = time();

foreach ($reservations as &$r) {

  if (!$r['order_id'] || $r['checked_in'] == 1) {
    $r['can_checkin'] = 0;
    continue;
  }

  $departureTimestamp = strtotime($r['departure_time']);
  $diffHours = ($departureTimestamp - $now) / 3600;

  if ($diffHours <= 72 && $diffHours > 0) {
    $r['can_checkin'] = 1;
  } else {
    $r['can_checkin'] = 0;
  }
}

echo json_encode($reservations);