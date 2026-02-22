<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

require __DIR__ . '/db.php'; 

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? null;
$password = $data["password"] ?? null;

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Zadaj e-mail a heslo."]);
    exit;
}

$db = Database::connect();

$stmt = $db->prepare("
  SELECT id, fullname, email, password, phone, points, verified, role, profile_picture 
  FROM users WHERE email = ?
"); 
$stmt->execute([$email]); 
$user = $stmt->fetch();  

if (!$user) {
    echo json_encode(["success" => false, "message" => "Účet s týmto e-mailom neexistuje."]);
    exit;
}

if ($user["verified"] == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Tvoj účet ešte nebol overený. Skontroluj svoj e-mail."
    ]);
    exit;
}

if (!password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Nesprávne heslo."]);
    exit;
}

unset($user["password"]);
echo json_encode(["success" => true, "message" => "Prihlásenie úspešné!", "user" => $user]);
?>
