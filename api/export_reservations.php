<?php
require_once "db.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = (int)($data["user_id"] ?? 0);

if (!$user_id) {
  http_response_code(403);
  exit("Neautorizované");
}

$db = Database::connect();

$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
  http_response_code(403);
  exit("Prístup zamietnutý");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reservations.csv');

$query = "
SELECT 
  r.id,
  u.fullname,
  u.email,
  f.flight_number,
  f.departure_city,
  f.arrival_city,
  f.departure_time,
  r.seat_number,
  r.status,
  r.created_at
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN flights f ON r.flight_id = f.id
ORDER BY r.created_at DESC
";

$stmt = $db->query($query);

echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');

fputcsv($output, [
  'ID rezervácie',
  'Meno',
  'Email',
  'Číslo letu',
  'Odlet',
  'Prílet',
  'Dátum odletu',
  'Sedadlo',
  'Stav',
  'Vytvorená'
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($output, $row);
}

fclose($output);
exit;