<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

ob_clean();
flush();

require_once "db.php";

try {
    $db = Database::connect();

    $stmt = $db->query("
        SELECT 
            f.id,
            f.flight_number,
            f.aircraft_type,
            f.departure_city,
            f.arrival_city,
            f.departure_time,
            f.arrival_time,
            f.price,
            -- celková kapacita podľa typu lietadla
            (a.seat_rows * a.seats_per_row) AS total_capacity,
            -- počet obsadených miest (reserved + confirmed)
            COALESCE(SUM(
                CASE 
                    WHEN r.status IN ('reserved', 'confirmed') THEN 1 
                    ELSE 0 
                END
            ), 0) AS used_seats
        FROM flights f
        JOIN aircrafts a 
            ON a.code = f.aircraft_type
        LEFT JOIN reservations r 
            ON r.flight_id = f.id
        GROUP BY 
            f.id,
            f.flight_number,
            f.aircraft_type,
            f.departure_city,
            f.arrival_city,
            f.departure_time,
            f.arrival_time,
            f.price,
            a.seat_rows,
            a.seats_per_row
        ORDER BY f.departure_time DESC
    ");

    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "flights" => $flights,
        "count" => count($flights),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "count" => 0,
    ], JSON_UNESCAPED_UNICODE);
}
