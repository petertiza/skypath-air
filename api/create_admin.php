<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require __DIR__ . '/db.php';

$db = Database::connect();

try {
  $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user' AFTER verified");
  echo "✅ Role stĺpec pridaný/pridaný už bol\n";
} catch (Exception $e) {
  echo "⚠️ Role stĺpec už existuje: " . $e->getMessage() . "\n";
}

$db->exec("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");

$db->exec("DELETE FROM users WHERE email = 'admin@skypath.sk'");
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$stmt = $db->prepare("
  INSERT INTO users (fullname, email, password, phone, points, role, verified) 
  VALUES (?, 'admin@skypath.sk', ?, NULL, 999, 'admin', 1)
");
$stmt->execute(['Admin SkyPath', $hash]);

$count = $db->query("SELECT COUNT(*) FROM users WHERE email = 'admin@skypath.sk'")->fetchColumn();
$role = $db->query("SELECT role FROM users WHERE email = 'admin@skypath.sk'")->fetchColumn();

echo json_encode([
  'success' => true,
  'message' => "✅ Admin vytvorený! Login: admin@skypath.sk / password",
  'admin_count' => $count,
  'admin_role' => $role
]);
?>
