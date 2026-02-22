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
$code = $data["code"] ?? null;

if (!$email || !$code) {
    echo json_encode(["success" => false, "message" => "Chýba e-mail alebo kód."]);
    exit;
}

$db = Database::connect();

$stmt = $db->prepare("
    SELECT id, fullname, code_expires_at 
    FROM users 
    WHERE email = ? AND verification_code = ? AND verified = 0
");
$stmt->execute([$email, $code]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Nesprávny overovací kód."]);
    exit;
}

if (strtotime($user['code_expires_at']) < time()) {
    echo json_encode(["success" => false, "message" => "Overovací kód vypršal, pošli si nový."]);
    exit;
}

$stmt2 = $db->prepare("UPDATE users SET verified = 1, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
$stmt2->execute([$user['id']]);

echo json_encode([
    "success" => true, 
    "message" => "Účet úspešne overený! Môžete sa prihlásiť.",
    "fullname" => $user['fullname']
]);
?>
