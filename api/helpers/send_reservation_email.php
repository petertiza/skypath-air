<?php
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . "/../../vendor/autoload.php";

function sendReservationEmail($to, $orderId, $flight, $seats, $passengers)
{
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

        $mail->setFrom("no-reply@skypath-air.com", "SkyPath AIR");
        $mail->addAddress($to);

        $seatList = implode(", ", $seats);

        $passengerItems = "";
        foreach ($passengers as $p) {
            $passengerItems .= "
              <tr>
                <td style='padding:6px 0;color:#0a2540;'>
                  {$p['firstname']} {$p['lastname']}
                </td>
              </tr>
            ";
        }

        $departureTime = date("d.m.Y H:i", strtotime($flight['departure_time']));

        $mail->isHTML(true);
        $mail->Subject = "SkyPath AIR – Rezervácia potvrdená (#{$orderId})";

        $mail->Body = "
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin:0;padding:0;background:#f2f6fb;font-family:Space Grotesk,Arial,sans-serif;'>

  <table width='100%' cellpadding='0' cellspacing='0'>
    <tr>
      <td align='center' style='padding:40px 20px;'>

        <!-- CARD -->
        <table width='100%' cellpadding='0' cellspacing='0' style='max-width:620px;background:#ffffff;border-radius:20px;box-shadow:0 18px 45px rgba(0,0,0,0.12);overflow:hidden;'>

          <!-- HEADER -->
          <tr>
            <td style='padding:28px 32px;background:linear-gradient(135deg,#0078d7,#005bb5);color:white;'>
              <h1 style='margin:0;font-size:26px;'>✈️ SkyPath AIR</h1>
              <p style='margin:6px 0 0;opacity:.9;'>Rezervácia potvrdená</p>
            </td>
          </tr>

          <!-- BODY -->
          <tr>
            <td style='padding:32px;color:#0a2540;'>

              <h2 style='margin-top:0;'>Ďakujeme za Vašu objednávku</h2>

              <p>
                Vaša rezervácia bola úspešne potvrdená.
                Nižšie nájdete prehľad Vašej cesty.
              </p>

              <!-- ORDER -->
              <table width='100%' style='margin:24px 0;border-collapse:collapse;'>
                <tr>
                  <td style='padding:10px 0;font-weight:600;'>Číslo rezervácie</td>
                  <td style='padding:10px 0;text-align:right;font-weight:700;color:#0078d7;'>#{$orderId}</td>
                </tr>
              </table>

              <hr style='border:none;border-top:1px solid #e5e7eb;margin:24px 0;'>

              <!-- FLIGHT -->
              <h3 style='margin-bottom:12px;'>✈️ Let</h3>
              <p style='margin:0 0 6px;'>
                <b>{$flight['departure_city']} → {$flight['arrival_city']}</b>
              </p>
              <p style='margin:0;color:#555;'>
                Odlet: {$departureTime}
              </p>

              <!-- SEATS -->
              <h3 style='margin:24px 0 8px;'>💺 Sedadlá</h3>
              <p style='margin:0;font-weight:600;'>{$seatList}</p>

              <!-- PASSENGERS -->
              <h3 style='margin:24px 0 8px;'>👤 Cestujúci</h3>
              <table width='100%' cellpadding='0' cellspacing='0'>
                {$passengerItems}
              </table>

              <hr style='border:none;border-top:1px solid #e5e7eb;margin:28px 0;'>

              <!-- INFO -->
              <p style='font-size:14px;color:#555;line-height:1.6;'>
                Online check-in bude dostupný niekoľko dní pred odletom.
                Po jeho dokončení si budete môcť stiahnuť palubnú vstupenku.
              </p>

              <p style='margin-top:24px;font-weight:600;'>
                Prajeme príjemný let ✈️
              </p>

            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td style='padding:20px 32px;background:#f9fafb;font-size:12px;color:#6b7280;text-align:center;'>
              © " . date("Y") . " SkyPath AIR • Tento email bol odoslaný automaticky
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
        ";

        $mail->send();

    } catch (Exception $e) {
    }
}
