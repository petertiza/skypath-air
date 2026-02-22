<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST only"]);
    exit;
}

require __DIR__ . '/db.php';

$user_id = $_POST['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Chýba user_id"]);
    exit;
}

if (!isset($_FILES['picture']) || $_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Žiadny obrázok"]);
    exit;
}

$file = $_FILES['picture'];
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed) || $file['size'] > 2 * 1024 * 1024) {
    echo json_encode(["success" => false, "message" => "Neplatný súbor (max 2MB JPG/PNG)"]);
    exit;
}

$filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
$upload_dir = __DIR__ . '/../frontend/public/uploads/profiles/';
$upload_path = $upload_dir . $filename;

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    $db = Database::connect();
    
    $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->execute(["/uploads/profiles/$filename", $user_id]);
    
    echo json_encode([
        "success" => true, 
        "message" => "Obrázok uložený!",
        "picture_url" => "/uploads/profiles/$filename"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Chyba ukladania"]);
}
?>
