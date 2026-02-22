<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

header("Content-Type: application/json");

$RECAPTCHA_SECRET = "6LdbCVcsAAAAAE0CyIaJp6OHtQIjGSSxEFLI4B19";

$data = json_decode(file_get_contents("php://input"), true);

$name       = trim($data["name"] ?? "");
$email      = trim($data["email"] ?? "");
$message    = trim($data["message"] ?? "");
$recaptcha  = $data["recaptcha"] ?? "";

if (!$name || !$email || !$message || !$recaptcha) {
  echo json_encode([
    "success" => false,
    "message" => "Neúplné údaje alebo chýbajúca ochrana."
  ]);
  exit;
}

$verifyResponse = file_get_contents(
  "https://www.google.com/recaptcha/api/siteverify?" .
  http_build_query([
    "secret"   => $RECAPTCHA_SECRET,
    "response" => $recaptcha
  ])
);

$captchaResult = json_decode($verifyResponse, true);

if (
  empty($captchaResult["success"]) ||
  ($captchaResult["score"] ?? 0) < 0.5
) {
  echo json_encode([
    "success" => false,
    "message" => "Overenie zlyhalo. Skúste to znova."
  ]);
  exit;
}

$mail = new PHPMailer(true);

$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';

try {
  $mail->isSMTP();
  $mail->Host       = "smtp.gmail.com";
  $mail->SMTPAuth   = true;
  $mail->Username   = "petertiza782@gmail.com";
  $mail->Password   = "waqtmbjoidwjfscb";
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  $mail->setFrom("no-reply@skypath.local", "SkyPath AIR");
  $mail->addReplyTo($email, $name);
  $mail->addAddress("petertiza782@gmail.com");

  $mail->isHTML(true);
  $mail->Subject = "Nová správa z kontaktu – SkyPath AIR";
  $mail->Body = "
    <h2>Kontaktný formulár</h2>
    <p><strong>Meno:</strong> {$name}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Správa:</strong><br>
      " . nl2br(htmlspecialchars($message)) . "
    </p>
    <hr>
    <small>reCAPTCHA score: {$captchaResult["score"]}</small>
  ";

  $mail->send();

  $mail->clearAddresses();
  $mail->clearReplyTos();

  $mail->addAddress($email, $name);
  $mail->setFrom("support@skypath.local", "SkyPath AIR – Zákaznícka podpora");

  $mail->Subject = "✈️ Ďakujeme za vašu správu – SkyPath AIR";

  $mail->Body = "
  <div style='max-width:600px;margin:auto;font-family:Segoe UI,Arial,sans-serif;
              background:#ffffff;border-radius:14px;overflow:hidden;
              box-shadow:0 12px 35px rgba(0,0,0,0.12)'>
    
    <div style='background:linear-gradient(135deg,#0078d7,#005bb5);
                padding:24px;color:white'>
      <h1 style='margin:0;font-size:22px'>SkyPath AIR ✈️</h1>
    </div>

    <div style='padding:28px;color:#0a2540'>
      <p>Dobrý deň <strong>{$name}</strong>,</p>

      <p>
        ďakujeme, že ste nás kontaktovali.  
        Vašu správu sme úspešne prijali a náš tím sa jej bude
        <strong>čo najskôr venovať</strong>.
      </p>

      <div style='background:#f1f5f9;padding:18px;border-radius:12px;margin:24px 0'>
        <p style='margin:0;font-size:14px;color:#334155'>
          <strong>Vaša správa:</strong><br>
          " . nl2br(htmlspecialchars($message)) . "
        </p>
      </div>

      <p>
        Ak ide o rezerváciu alebo let, odpovieme vám zvyčajne do
        <strong>24 hodín</strong>.
      </p>

      <p>
        Prajeme vám príjemný deň a bezpečný let ✈️
      </p>

      <p style='margin-top:30px'>
        <strong>SkyPath AIR – Zákaznícka podpora</strong>
      </p>
    </div>

    <div style='background:#f8fafc;padding:16px;text-align:center;
                font-size:12px;color:#64748b'>
      Tento email bol odoslaný automaticky. Prosím, neodpovedajte naň.
    </div>
  </div>
  ";

  $mail->send();

  echo json_encode(["success" => true]);

} catch (Exception $e) {
  echo json_encode([
    "success" => false,
    "message" => "Správu sa nepodarilo odoslať."
  ]);
}