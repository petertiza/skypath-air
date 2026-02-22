<?php
header("Content-Type: application/json");
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (
  empty($data["email"]) ||
  empty($data["password"]) ||
  empty($data["fullname"])
) {
  echo json_encode([
    "success" => false,
    "message" => "Vyplň všetky povinné polia"
  ]);
  exit;
}

$email = trim($data["email"]);
$fullname = trim($data["fullname"]);
$password = password_hash($data["password"], PASSWORD_DEFAULT);
$role = $data["role"] ?? "user";

$db = Database::connect();

$check = $db->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->fetch()) {
  echo json_encode([
    "success" => false,
    "message" => "Používateľ s týmto emailom už existuje"
  ]);
  exit;
}

$stmt = $db->prepare("
  INSERT INTO users (email, password, fullname, role)
  VALUES (?, ?, ?, ?)
");

$stmt->execute([$email, $password, $fullname, $role]);

echo json_encode([
  "success" => true,
  "message" => "Používateľ bol úspešne vytvorený"
]);
