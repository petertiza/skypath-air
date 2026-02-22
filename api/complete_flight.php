<?php
require_once "db.php";

header("Content-Type: application/json");

$flight_id = (int)($_POST["flight_id"] ?? 0);

if (!$flight_id) {
  echo json_encode([
    "success" => false,
    "message" => "Chýba flight_id"
  ]);
  exit;
}

$db = Database::connect();

$POINTS_PER_EURO = 2; 

try {
  $db->beginTransaction();

  $stmt = $db->prepare("
    SELECT id, user_id, total_price
    FROM orders
    WHERE flight_id = ?
      AND payment_status = 'paid'
  ");
  $stmt->execute([$flight_id]);
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!$orders) {
    $db->commit();
    echo json_encode([
      "success" => true,
      "message" => "Žiadne objednávky na spracovanie"
    ]);
    exit;
  }

  foreach ($orders as $order) {
    $points = (int) round($order["total_price"] * $POINTS_PER_EURO);

    $stmt = $db->prepare("
      UPDATE users
      SET points = points + ?
      WHERE id = ?
    ");
    $stmt->execute([
      $points,
      $order["user_id"]
    ]);

    $stmt = $db->prepare("
      INSERT INTO points_history
        (user_id, order_id, points, reason)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
      $order["user_id"],
      $order["id"],
      $points,
      'Let absolvovaný'
    ]);
  }

  $db->commit();

  echo json_encode([
    "success" => true,
    "orders_processed" => count($orders)
  ]);

} catch (Exception $e) {
  $db->rollBack();
  echo json_encode([
    "success" => false,
    "message" => "Chyba pri pripisovaní bodov"
  ]);
}
