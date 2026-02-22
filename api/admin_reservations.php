<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

ob_clean();
flush();

require "db.php";

try {
    $db = Database::connect();
    
    $stmt = $db->query("
        SELECT 
            r.id,
            r.seat_number,
            CASE 
                WHEN r.status = 'confirmed' THEN 'reserved'
                WHEN r.status = 'hold' THEN 'hold'  
                WHEN r.status = 'cancelled' THEN 'cancelled'
                ELSE r.status
            END as status,
            r.created_at,
            u.fullname,
            u.email,
            f.flight_number,
            f.departure_city,
            f.arrival_city
        FROM reservations r
        JOIN users u ON u.id = r.user_id
        JOIN flights f ON f.id = r.flight_id
        ORDER BY r.created_at DESC
    ");
    
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "reservations" => $reservations,
        "count" => count($reservations)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>
