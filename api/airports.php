<?php
require "db.php";

header("Content-Type: application/json");

$db = Database::connect();

$q = strtolower($_GET["q"] ?? "");

$stmt = $db->prepare("
  SELECT city, country, iata
  FROM airports
  WHERE LOWER(city) LIKE ?
     OR LOWER(country) LIKE ?
  ORDER BY country, city
  LIMIT 20
");

$like = "%$q%";
$stmt->execute([$like, $like]);

echo json_encode($stmt->fetchAll());
