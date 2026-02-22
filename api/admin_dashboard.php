<?php
require "db.php";
header("Content-Type: application/json");
ob_clean();

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

$revenue = (float)$db->query("
    SELECT IFNULL(SUM(f.price), 0)
    FROM reservations r
    JOIN flights f ON f.id = r.flight_id
    WHERE r.status IN ('reserved', 'confirmed')
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


$reservations_chart = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM reservations 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
      AND status IN ('reserved', 'confirmed')
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll(PDO::FETCH_ASSOC);

$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $dateLabel = date('M j', strtotime("-$i days"));
    $found = false;

    foreach ($reservations_chart as $r) {
        if (date('M j', strtotime($r['date'])) === $dateLabel) {
            $chart_data[] = [
                'd' => $dateLabel,
                'c' => (int)$r['count'],
            ];
            $found = true;
            break;
        }
    }

    if (!$found) {
        $chart_data[] = [
            'd' => $dateLabel,
            'c' => 0,
        ];
    }
}


$aircraft_chart = $db->query("
    SELECT 
        a.code,
        COUNT(r.id) AS reservations_count
    FROM aircrafts a
    LEFT JOIN flights f ON f.aircraft_type = a.code
    LEFT JOIN reservations r 
        ON r.flight_id = f.id
       AND r.status IN ('reserved', 'confirmed')
    WHERE a.active = 1
    GROUP BY a.id, a.code
    ORDER BY a.code
")->fetchAll(PDO::FETCH_ASSOC);


echo json_encode([
    "stats" => [
        "flights" => $flights,
        "reservations" => $reservations,
        "revenue" => round($revenue, 2),
        "occupancy" => $occupancy,
    ],
    "reservations_chart" => array_reverse($chart_data),
    "aircraft_chart" => $aircraft_chart,
]);
