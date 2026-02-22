<?php
require_once "db.php";
require_once "helpers/send_reservation_email.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id         = (int)($data["user_id"] ?? 0);
$flight_id       = (int)($data["flight_id"] ?? 0);
$seats           = $data["seats"] ?? [];
$passengers      = $data["passengers"] ?? [];
$passenger_count = (int)($data["passenger_count"] ?? 1);
$total_price     = (float)($data["total_price"] ?? 0);

$loyalty_points_used = (int)($data["loyalty_points_used"] ?? 0);
$loyalty_discount    = (float)($data["loyalty_discount"] ?? 0);

$email       = trim($data["email"] ?? "");
$phone       = trim($data["phone"] ?? "");
$firstname   = trim($data["firstname"] ?? "");
$lastname    = trim($data["lastname"] ?? "");
$birthdate   = $data["birthdate"] ?? null;
$nationality = $data["nationality"] ?? null;

if (
  !$user_id ||
  !$flight_id ||
  empty($seats) ||
  !$total_price ||
  !$email ||
  !$firstname ||
  !$lastname
) {
  echo json_encode([
    "success" => false,
    "message" => "Neplatné údaje"
  ]);
  exit;
}

$db = Database::connect();

$checkStmt = $db->prepare("
  SELECT COUNT(*)
  FROM reservations
  WHERE flight_id = ?
    AND seat_number = ?
    AND status = 'hold'
");

foreach ($seats as $seat) {
  $checkStmt->execute([$flight_id, $seat]);
  if ($checkStmt->fetchColumn() == 0) {
    echo json_encode([
      "success" => false,
      "message" => "Sedadlo už nie je rezervované"
    ]);
    exit;
  }
}

function getPointsMultiplier(int $points): float {
  if ($points >= 5000) return 2.0;
  if ($points >= 2500) return 1.5;
  if ($points >= 1000) return 1.25;
  return 1.0;
}

$db->beginTransaction();

try {
  $stmt = $db->prepare("
    INSERT INTO orders
      (user_id, flight_id, total_price, passenger_count, payment_status, email, phone)
    VALUES
      (?, ?, ?, ?, 'paid', ?, ?)
  ");
  $stmt->execute([
    $user_id,
    $flight_id,
    $total_price,
    $passenger_count,
    $email,
    $phone
  ]);

  $order_id = (int)$db->lastInsertId();

  if ($loyalty_points_used > 0) {
    $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $currentPoints = (int)$stmt->fetchColumn();
    
    if ($currentPoints < $loyalty_points_used) {
      $db->rollBack();
      echo json_encode([
        "success" => false, 
        "message" => "❌ Nedostatok bodov. Máte iba {$currentPoints}"
      ]);
      exit;
    }
    
    $stmt = $db->prepare("UPDATE users SET points = points - ? WHERE id = ?");
    $stmt->execute([$loyalty_points_used, $user_id]);
    
    $stmt = $db->prepare("
      INSERT INTO points_history (user_id, order_id, points, description) 
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
      $user_id, 
      $order_id, 
      -$loyalty_points_used, 
      "Použité na zľavu #{$order_id}"
    ]);
    
    $stmt = $db->prepare("UPDATE orders SET total_price = total_price - ? WHERE id = ?");
    $stmt->execute([$loyalty_discount, $order_id]);
  }

  $stmt = $db->prepare("
    INSERT INTO passengers
      (order_id, firstname, lastname, birthdate, nationality, is_main)
    VALUES
      (?, ?, ?, ?, ?, 1)
  ");
  $stmt->execute([
    $order_id,
    $firstname,
    $lastname,
    $birthdate,
    $nationality
  ]);

  foreach ($passengers as $p) {
    $stmt = $db->prepare("
      INSERT INTO passengers
        (order_id, firstname, lastname, birthdate, nationality, is_main)
      VALUES
        (?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([
      $order_id,
      $p["firstname"],
      $p["lastname"],
      $p["birthdate"] ?? null,
      $p["nationality"] ?? null
    ]);
  }

  $updateStmt = $db->prepare("
    UPDATE reservations
    SET
      status = 'confirmed',
      payment_status = 'paid',
      order_id = ?
    WHERE
      flight_id = ?
      AND seat_number = ?
      AND status = 'hold'
  ");

  $updated = 0;
  foreach ($seats as $seat) {
    $updateStmt->execute([
      $order_id,
      $flight_id,
      $seat
    ]);
    $updated += $updateStmt->rowCount();
  }

  if ($updated === 0) {
    throw new Exception("Rezervácia sa nepodarila potvrdiť");
  }

  $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $currentPoints = (int)$stmt->fetchColumn();

  $multiplier   = getPointsMultiplier($currentPoints);
  $pointsEarned = (int)floor(floor($total_price) * $multiplier);

  if ($pointsEarned > 0) {
    $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->execute([$pointsEarned, $user_id]);

    $stmt = $db->prepare("
      INSERT INTO points_history (user_id, order_id, points, description)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
      $user_id,
      $order_id,
      $pointsEarned,
      "Rezervácia letu #{$order_id} (×{$multiplier})"
    ]);
  }

  $db->commit();

  $stmt = $db->prepare("SELECT * FROM flights WHERE id = ?");
  $stmt->execute([$flight_id]);
  $flight = $stmt->fetch(PDO::FETCH_ASSOC);

  sendReservationEmail(
    $email,
    $order_id,
    $flight,
    $seats,
    array_merge(
      [[ "firstname" => $firstname, "lastname" => $lastname ]],
      $passengers
    )
  );

  echo json_encode([
    "success" => true,
    "order_id" => $order_id,
    "points_earned" => $pointsEarned
  ]);

} catch (Throwable $e) {
  $db->rollBack();
  echo json_encode([
    "success" => false,
    "message" => "Platba zlyhala",
    "debug" => $e->getMessage()
  ]);
}
?>
