<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$user_id = isset($body['user_id']) ? intval($body['user_id']) : 0;
$flight_id = isset($body['flight_id']) ? intval($body['flight_id']) : 0;
$seat_number = isset($body['seat_number']) ? $conn->real_escape_string($body['seat_number']) : '';

if (!$user_id || !$flight_id || !$seat_number) {
    echo json_encode(["success" => false, "message" => "Neplatné dáta."]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM Reservations WHERE flight_id = ? AND seat_number = ? AND status = 'confirmed' LIMIT 1");
$stmt->bind_param("is", $flight_id, $seat_number);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Sedadlo už je obsadené."]);
    exit;
}

$stmt2 = $conn->prepare("INSERT INTO Reservations (user_id, flight_id, seat_number, status) VALUES (?, ?, ?, 'confirmed')");
$stmt2->bind_param("iis", $user_id, $flight_id, $seat_number);

if (!$stmt2->execute()) {
    echo json_encode(["success" => false, "message" => "Nepodarilo sa vytvoriť rezerváciu."]);
    exit;
}

$reservation_id = $stmt2->insert_id;

$stmt3 = $conn->prepare("UPDATE Flights SET seats_available = GREATEST(0, seats_available - 1) WHERE id = ?");
$stmt3->bind_param("i", $flight_id);
$stmt3->execute();

$stmt4 = $conn->prepare("SELECT price FROM Flights WHERE id = ?");
$stmt4->bind_param("i", $flight_id);
$stmt4->execute();
$r4 = $stmt4->get_result()->fetch_assoc();
$price = isset($r4['price']) ? floatval($r4['price']) : 0;
$pointsEarned = intval(floor($price));

if ($pointsEarned > 0) {
    $stmt5 = $conn->prepare("UPDATE Users SET points = points + ? WHERE id = ?");
    $stmt5->bind_param("ii", $pointsEarned, $user_id);
    $stmt5->execute();

    $stmt6 = $conn->prepare("INSERT INTO LoyaltyPoints (user_id, points_change, description) VALUES (?, ?, ?)");
    $desc = "Bod(y) za nákup letenky (rezervácia #$reservation_id)";
    $stmt6->bind_param("iis", $user_id, $pointsEarned, $desc);
    $stmt6->execute();
}

echo json_encode(["success" => true, "message" => "Rezervácia vytvorená.", "reservation_id" => $reservation_id]);
