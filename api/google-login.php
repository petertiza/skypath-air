<?php
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (!$code) {
    echo json_encode(['success' => false, 'message' => 'Chýba kód']);
    exit;
}

echo json_encode([
    'success' => true, 
    'message' => 'Google login úspešný!',
    'user' => ['email' => 'google@example.com', 'fullname' => 'Google User', 'role' => 'customer']
]);
?>
