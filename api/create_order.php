<?php
require "db.php";
session_start();

$user = $_SESSION['user'] ?? null;
if (!$user) exit;

$data = json_decode(file_get_contents("php://input"), true);

$db = Database::connect();
$db->beginTransaction();

try {
  $stmt = $db->prepare("
    INSERT INTO orders (user_id, flight_id, total_price, passenger_count, payment_status)
    VALUES (?, ?, ?, ?, 'paid')
  ");
  $stmt->execute([
    $user['id'],
    $data['flight_id'],
    $data['total'],
    count($data['seats'])
  ]);
  $orderId = $db->lastInsertId();

  $stmtP = $db->prepare("
    INSERT INTO passengers
    (order_id, firstname, lastname, birthdate, nationality, is_main)
    VALUES (?,?,?,?,?,?)
  ");

  foreach ($data['passengers'] as $i => $p) {
    $stmtP->execute([
      $orderId,
      $p['firstname'],
      $p['lastname'],
      $p['birthdate'],
      $p['nationality'],
      $i === 0 ? 1 : 0
    ]);
  }

  $stmt = $db->prepare("
    UPDATE reservations
    SET status='booked', payment_status='paid'
    WHERE user_id=? AND flight_id=? AND status='hold'
  ");
  $stmt->execute([$user['id'], $data['flight_id']]);

  $db->commit();

  echo json_encode(["success" => true, "order_id" => $orderId]);

} catch (Exception $e) {
  $db->rollBack();
  echo json_encode(["success" => false]);
}
