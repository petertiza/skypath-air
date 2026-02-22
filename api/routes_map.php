<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require "db.php";

try {
    $db = Database::connect();

    $sql = "
        SELECT DISTINCT
            a1.city   AS from_city,
            a1.iata   AS from_iata,
            a1.country AS from_country,
            a1.lat    AS from_lat,
            a1.lng    AS from_lng,

            a2.city   AS to_city,
            a2.iata   AS to_iata,
            a2.country AS to_country,
            a2.lat    AS to_lat,
            a2.lng    AS to_lng
        FROM flights f
        JOIN airports a1 
          ON f.departure_city = CONCAT(a1.city, ' (', a1.iata, ')')
        JOIN airports a2 
          ON f.arrival_city   = CONCAT(a2.city, ' (', a2.iata, ')')
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $routes = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $routes[] = [
            "from" => [
                "city"    => $row["from_city"],
                "iata"    => $row["from_iata"],
                "country" => $row["from_country"],
                "lat"     => (float)$row["from_lat"],
                "lng"     => (float)$row["from_lng"]
            ],
            "to" => [
                "city"    => $row["to_city"],
                "iata"    => $row["to_iata"],
                "country" => $row["to_country"],
                "lat"     => (float)$row["to_lat"],
                "lng"     => (float)$row["to_lng"]
            ]
        ];
    }

    echo json_encode($routes);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Chyba pri načítaní trás",
        "details" => $e->getMessage()
    ]);
}
