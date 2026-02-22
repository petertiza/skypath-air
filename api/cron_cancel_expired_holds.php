<?php
require_once "db.php";

$db = Database::connect();

$stmt = $db->prepare("
  UPDATE reservations
  SET status = 'cancelled'
  WHERE status = 'hold'
    AND hold_until < NOW()
");

$stmt->execute();

echo "Expired holds cancelled: " . $stmt->rowCount();