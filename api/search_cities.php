<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'db.php';

$q = $_GET["q"] ?? "";
$q = trim($q);

if ($q === "") {
    echo json_encode([]);
    exit;
}

$db = Database::connect();

$sql = "
SELECT DISTINCT departure_city AS city FROM flights WHERE departure_city LIKE ?
UNION
SELECT DISTINCT arrival_city AS city FROM flights WHERE arrival_city LIKE ?
ORDER BY city ASC
LIMIT 10
";

$like = "%{$q}%";
$stmt = $db->prepare($sql);
$stmt->execute([$like, $like]); 

$cities = [];
while ($row = $stmt->fetch()) { 
    $cities[] = $row["city"];
}

echo json_encode($cities);
?>
