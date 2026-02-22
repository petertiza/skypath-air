<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once "db.php";

$db = Database::connect();

$flights = (int)$db->query("
    SELECT COUNT(*) 
    FROM flights
")->fetchColumn();

$reservations = (int)$db->query("
    SELECT COUNT(*) 
    FROM reservations 
    WHERE status IN ('reserved', 'confirmed')
")->fetchColumn();

$capacity = (int)$db->query("
    SELECT COALESCE(SUM(a.seat_rows * a.seats_per_row), 0)
    FROM flights f
    JOIN aircrafts a ON a.code = f.aircraft_type
    WHERE a.active = 1
")->fetchColumn();

$occupied = (int)$db->query("
    SELECT COUNT(*) 
    FROM reservations r
    JOIN flights f ON f.id = r.flight_id
    JOIN aircrafts a ON a.code = f.aircraft_type
    WHERE r.status IN ('reserved', 'confirmed')
      AND a.active = 1
")->fetchColumn();

$occupancy = $capacity > 0
    ? round(($occupied / $capacity) * 100)
    : 0;

$revenue = (float)$db->query("
    SELECT IFNULL(SUM(f.price), 0)
    FROM reservations r
    JOIN flights f ON f.id = r.flight_id
    WHERE r.status IN ('reserved', 'confirmed')
")->fetchColumn();

echo json_encode([
    "flights" => $flights,
    "reservations" => $reservations,
    "occupancy" => $occupancy,
    "revenue" => round($revenue, 2),
]);
