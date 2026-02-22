<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? null;
$old_password = $data["old_password"] ?? null;
$new_password = $data["new_password"] ?? null;

if (!$email || !$old_password || !$new_password || strlen($new_password) < 6) {
    echo json_encode(["success" => false, "message" => "Neplatné dáta."]);
    exit;
}

$db = Database::connect();

$stmt = $db->prepare("SELECT password FROM users WHERE email = ? AND verified = 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($old_password, $user['password'])) {
    echo json_encode(["success" => false, "message" => "Nesprávne staré heslo."]);
    exit;
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt2 = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt2->execute([$hashed, $email]);

echo json_encode(["success" => true, "message" => "Heslo zmenené!"]);
?>
