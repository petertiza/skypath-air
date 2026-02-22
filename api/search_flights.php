<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$from = $data['from'] ?? '';
$to = $data['to'] ?? '';
$date = $data['date'] ?? '';

$query = "SELECT * FROM Flights WHERE 1=1";

if ($from !== '') {
    $query .= " AND departure_city LIKE ?";
}
if ($to !== '') {
    $query .= " AND arrival_city LIKE ?";
}
if ($date !== '') {
    $query .= " AND DATE(departure_time) = ?";
}

$db = Database::connect();
$stmt = $db->prepare($query);

$params = [];
if ($from !== '') $params[] = "%$from%";
if ($to !== '') $params[] = "%$to%";
if ($date !== '') $params[] = $date;

if ($from !== '') {
    $params[] = "%$from%";
    $types .= 's';
}
if ($to !== '') {
    $params[] = "%$to%";
    $types .= 's';
}
if ($date !== '') {
    $params[] = $date;
    $types .= 's';
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute($params); 
$result = $stmt->fetchAll();  
$flights = $result;

while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
}

echo json_encode(["success" => true, "flights" => $flights]);
?>
