<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

$db = Database::connect();

if (isset($_GET['all'])) {
  $stmt = $db->query("
    SELECT 
      f.id AS id,
      f.flight_number,
      f.departure_city,
      f.arrival_city,
      f.departure_time,
      f.arrival_time,
      f.price,
      f.aircraft_type,
      (a.seat_rows * a.seats_per_row - COUNT(r.id)) AS seats_available
    FROM flights f
    JOIN aircrafts a ON a.code = f.aircraft_type
    LEFT JOIN reservations r 
      ON r.flight_id = f.id AND r.status = 'reserved'
    GROUP BY 
      f.id,
      f.flight_number,
      f.departure_city,
      f.arrival_city,
      f.departure_time,
      f.arrival_time,
      f.price,
      f.aircraft_type
    ORDER BY f.price ASC
    LIMIT 20
  ");

  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}


$from = $_GET["from"] ?? "";
$to   = $_GET["to"] ?? "";
$date = $_GET["date"] ?? "";

$query = "
  SELECT 
    f.id AS id,
    f.flight_number,
    f.departure_city,
    f.arrival_city,
    f.departure_time,
    f.arrival_time,
    f.price,
    f.aircraft_type,
    (a.seat_rows * a.seats_per_row - COUNT(r.id)) AS seats_available
  FROM flights f
  JOIN aircrafts a ON a.code = f.aircraft_type
  LEFT JOIN reservations r 
    ON r.flight_id = f.id AND r.status = 'reserved'
  WHERE 1=1
";

$params = [];

if ($from !== "") {
  $query .= " AND f.departure_city LIKE ?";
  $params[] = "%{$from}%";
}

if ($to !== "") {
  $query .= " AND f.arrival_city LIKE ?";
  $params[] = "%{$to}%";
}

if ($date !== "") {
  $query .= " AND DATE(f.departure_time) = ?";
  $params[] = $date;
}

$query .= "
  GROUP BY 
    f.id,
    f.flight_number,
    f.departure_city,
    f.arrival_city,
    f.departure_time,
    f.arrival_time,
    f.price,
    f.aircraft_type
  ORDER BY f.departure_time ASC
";

$stmt = $db->prepare($query);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));