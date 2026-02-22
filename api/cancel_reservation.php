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
$reservation_id = $data["reservation_id"] ?? null;
$user_id = $data["user_id"] ?? null;

$response = [];

if (!$reservation_id || !$user_id) {
    $response["success"] = false;
    $response["message"] = "Chýbajú údaje.";
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("UPDATE Reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $reservation_id, $user_id);

if ($stmt->execute()) {
    $pointsToSubtract = 10;
    $desc = "Storno rezervácie ID " . $reservation_id;

    $stmt2 = $conn->prepare("INSERT INTO LoyaltyPoints (user_id, points_change, description) VALUES (?, ?, ?)");
    $pointsToSubtractNegative = -$pointsToSubtract;
    $stmt2->bind_param("iis", $user_id, $pointsToSubtractNegative, $desc);
    $stmt2->execute();

    $stmt3 = $conn->prepare("UPDATE Users SET points = points - ? WHERE id = ?");
    $stmt3->bind_param("ii", $pointsToSubtract, $user_id);
    $stmt3->execute();

    $response["success"] = true;
    $response["message"] = "Rezervácia zrušená, body odpočítané.";
} else {
    $response["success"] = false;
    $response["message"] = "Chyba pri rušení rezervácie.";
}

echo json_encode($response);
?>
