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
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? null;

if (!$email) {
    echo json_encode(["success" => false, "message" => "Chýba e-mail."]);
    exit;
}

$db = Database::connect();

$stmt = $db->prepare("SELECT id, fullname FROM users WHERE email = ? AND verified = 0");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Účet s týmto e-mailom neexistuje alebo už overený."]);
    exit;
}

$fullname = $user['fullname'];
$code = sprintf("%06d", rand(0, 999999));  
$expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

$stmt2 = $db->prepare("UPDATE users SET verification_code = ?, code_expires_at = ? WHERE id = ?");
$stmt2->execute([$code, $expires, $user['id']]);

$mail = new PHPMailer(true);
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'petertiza782@gmail.com'; 
    $mail->Password = 'waqtmbjoidwjfscb';  
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('petertiza782@gmail.com', 'SkyPath AIR');  
    $mail->addAddress($email, $fullname);                   
    $mail->addReplyTo('petertiza782@gmail.com', 'SkyPath AIR'); 

    $mail->isHTML(true);
$mail->Subject = '✈️ Overenie účtu - SkyPath AIR';

$mail->Body = "
<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<style>
  body {
    background-color: #F3F4F6;
    font-family: Space Grotesk, Helvetica, sans-serif;
    color: #333333;
    margin: 0;
    padding: 0;
  }
  .container {
    background: #ffffff;
    max-width: 480px;
    margin: 40px auto;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    overflow: hidden;
  }
  .header {
    background-color: #0078D7;
    color: #ffffff;
    padding: 20px;
    text-align: center;
    font-size: 20px;
    letter-spacing: 1px;
  }
  .content {
    padding: 30px;
    text-align: center;
  }
  .code {
    background-color: #F3F4F6;
    display: inline-block;
    padding: 12px 24px;
    font-size: 28px;
    letter-spacing: 6px;
    font-weight: bold;
    color: #0078D7;
    border-radius: 8px;
    margin: 15px 0;
  }
  .footer {
    background-color: #F9FAFB;
    color: #777777;
    font-size: 12px;
    text-align: center;
    padding: 12px;
  }
</style>
</head>
<body>
  <div class='container'>
    <div class='header'>FlightApp Overenie účtu</div>
    <div class='content'>
      <p>Ahoj <strong>$fullname</strong>,</p>
      <p>ďakujeme, že ste sa zaregistrovali vo <strong>SkyPath AIR</strong>! ✈️</p>
      <p>Váš nový overovací kód je:</p>
      <div class='code'>$code</div>
      <p>Tento kód je platný <strong>5 minút</strong>.</p>
      <p>Ak ste túto žiadosť neposlali vy, môžete tento e-mail ignorovať.</p>
    </div>
    <div class='footer'>
      &copy; " . date('Y') . " SkyPath AIR • každý let je nový príbeh
    </div>
  </div>
</body>
</html>
";

    $mail->send();

    echo json_encode(["success" => true, "message" => "Overovací e-mail bol znova odoslaný."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Chyba pri odosielaní e-mailu: {$mail->ErrorInfo}"]);
}
?>
