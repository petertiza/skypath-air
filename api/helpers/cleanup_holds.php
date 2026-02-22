<?php
require_once __DIR__ . '/../db.php';

$db = Database::connect();

$stmt = $db->prepare("
  UPDATE reservations
  SET status = 'cancelled'
  WHERE status = 'hold'
    AND created_at < NOW() - INTERVAL 15 MINUTE
");

$stmt->execute();
