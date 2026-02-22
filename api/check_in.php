<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/db.php";

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

header("Content-Type: application/json; charset=utf-8");

$data = json_decode(file_get_contents("php://input"), true);

$reservation_id   = (int)($data["reservation_id"] ?? 0);
$user_id          = (int)($data["user_id"] ?? 0);
$document_type    = trim($data["document_type"] ?? "");
$document_number  = trim($data["document_number"] ?? "");
$document_country = trim($data["document_country"] ?? "");
$document_expiry  = $data["document_expiry"] ?? null;

if (
    !$reservation_id ||
    !$user_id ||
    !in_array($document_type, ["id_card", "passport"], true) ||
    !$document_number
) {
    echo json_encode([
        "success" => false,
        "message" => "Neplatné údaje"
    ]);
    exit;
}

try {
    $db = Database::connect();

    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.user_id,
            r.order_id,
            r.seat_number,
            f.departure_city,
            f.arrival_city,
            f.departure_time,
            f.arrival_time,
            o.payment_status,
            o.checked_in,
            p.firstname,
            p.lastname
        FROM reservations r
        LEFT JOIN orders o     ON r.order_id = o.id
        LEFT JOIN flights f    ON r.flight_id = f.id
        LEFT JOIN passengers p ON o.id = p.order_id AND p.is_main = 1
        WHERE r.id = ?
        LIMIT 1
    ");
    $stmt->execute([$reservation_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) throw new Exception("Rezervácia neexistuje");
    if ((int)$row["user_id"] !== $user_id) throw new Exception("Rezervácia nepatrí tomuto používateľovi");
    if (!$row["order_id"] || $row["payment_status"] !== "paid") throw new Exception("Rezervácia nie je uhradená");
    if ((int)$row["checked_in"] === 1) throw new Exception("Check-in už bol vykonaný");

    $order_id = (int)$row["order_id"];

    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO checkins
            (order_id, user_id, document_type, document_number, document_country, document_expiry)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_id,
        $user_id,
        $document_type,
        $document_number,
        $document_country,
        $document_expiry ?: null
    ]);

    $db->prepare("UPDATE orders SET checked_in = 1 WHERE id = ?")
       ->execute([$order_id]);

    $db->commit();

    $tempDir = __DIR__ . "/temp";
    if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

    $qr_data = "CHECKIN|{$order_id}|{$user_id}|{$row['seat_number']}|{$document_number}";
    $qr_file_rel = "temp/qr_{$order_id}.png";
    $qr_file_abs = $tempDir . "/qr_{$order_id}.png";

    $qrCode = new QrCode(data: $qr_data, size: 300, margin: 10);
    $writer = new PngWriter();
    $writer->write($qrCode)->saveToFile($qr_file_abs);

    $qr_url = "/api/" . $qr_file_rel;

    $pdf = new \FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    $enc = fn($t) => mb_convert_encoding($t, 'ISO-8859-1', 'UTF-8');

    $primary = [15,23,42];
    $accent  = [56,189,248];
    $bgLight = [249,250,251];

    $pdf->SetFillColor(...$bgLight);
    $pdf->Rect(5,10,200,110,'F');

    $pdf->SetFillColor(...$primary);
    $pdf->Rect(5,10,200,25,'F');

    $logoPath = __DIR__ . '/../frontend/public/logo-light.png';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 8, 12, 26);
    }

    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('Arial','B',18);
    $pdf->SetXY(38,14);
    $pdf->Cell(120,8,$enc('SKYPATH AIR'),0,1,'L');

    $pdf->SetFont('Arial','',11);
    $pdf->SetXY(38,22);
    $pdf->Cell(120,6,$enc('BOARDING PASS / PALUBNY LISTOK'),0,1,'L');

    $leftX=8; $topY=40; $width=194; $height=76;

    $pdf->SetDrawColor(226,232,240);
    $pdf->Rect($leftX,$topY,$width,$height);

    $tearX=$leftX+135;
    for($y=$topY+2;$y<$topY+$height-2;$y+=4){
        $pdf->Line($tearX,$y,$tearX,$y+2);
    }

    $pdf->SetTextColor(15,23,42);
    $pdf->SetFont('Arial','B',18);
    $pdf->SetXY($leftX+6,$topY+6);
    $pdf->Cell($tearX-$leftX-10,8,
        $enc("{$row['departure_city']}  ->  {$row['arrival_city']}"),
        0,1,'L'
    );

    $pdf->Line($leftX+6,$topY+18,$tearX-6,$topY+18);

    $col1X=$leftX+6;
    $col2X=$leftX+80;
    $infoY=$topY+22;

    $labelW=35;
    $valueW=55;

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY($col1X,$infoY);
    $pdf->Cell($labelW,6,$enc('PASAZIER'));
    $pdf->SetFont('Arial','',11);
    $pdf->Cell($valueW,6,$enc("{$row['firstname']} {$row['lastname']}"),0,1);

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY($col1X,$infoY+8);
    $pdf->Cell($labelW,6,$enc('DOKLAD'));
    $pdf->SetFont('Arial','',11);
    $pdf->Cell($valueW,6,strtoupper($document_type)." ".$document_number,0,1);

    if($document_country){
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY($col1X,$infoY+16);
        $pdf->Cell($labelW,6,$enc('STAT'));
        $pdf->SetFont('Arial','',11);
        $pdf->Cell($valueW,6,strtoupper($document_country),0,1);
    }

    if($document_expiry){
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY($col1X,$infoY+24);
        $pdf->Cell($labelW,6,$enc('PLATNOST DO'));
        $pdf->SetFont('Arial','',11);
        $pdf->Cell($valueW,6,date('d.m.Y',strtotime($document_expiry)),0,1);
    }

    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY($col2X,$infoY);
    $pdf->Cell(30,6,$enc('ODLET'));
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(40,6,date('d.m.Y H:i',strtotime($row['departure_time'])),0,1);

    $pdf->SetXY($col2X,$infoY+8);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(30,6,$enc('PRILET'));
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(40,6,date('d.m.Y H:i',strtotime($row['arrival_time'])),0,1);

    $pdf->SetXY($col2X,$infoY+20);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(30,6,$enc('SEDADLO'));

    $pdf->SetFillColor(...$accent);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('Arial','B',16);
    $pdf->SetXY($col2X,$infoY+26);
    $pdf->Cell(40,12,$row['seat_number'],0,1,'C',true);

    $pdf->SetTextColor(15,23,42);

    $qrBoxX=$tearX+4;
    $qrBoxY=$topY+8;
    $qrSize=55;

    $pdf->Rect($qrBoxX,$qrBoxY,$qrSize,$qrSize);
    $pdf->Image($qr_file_abs,$qrBoxX+5,$qrBoxY+5,$qrSize-10,$qrSize-10);

    $pdf->SetFont('Arial','',9);
    $pdf->SetXY($qrBoxX,$qrBoxY+$qrSize+3);
    $pdf->Cell($qrSize,5,$enc('Naskenujte pri nastupe do lietadla'),0,1,'C');

    $pdf->SetY($topY+$height+6);
    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(107,114,128);
    $pdf->Cell(0,4,$enc('Dostavte sa k odletovej brane najneskor 30 minut pred odletom.'),0,1,'C');
    $pdf->Cell(0,4,$enc('Tento palubny listok je neprenosny a plati len s platnym cestovnym dokladom.'),0,1,'C');

    $pdf_file_rel="temp/boarding_pass_{$order_id}.pdf";
    $pdf_file_abs=$tempDir."/boarding_pass_{$order_id}.pdf";
    $pdf->Output('F',$pdf_file_abs);

    echo json_encode([
        "success"=>true,
        "pdf_url"=>"/api/".$pdf_file_rel,
        "qr_url"=>$qr_url
    ]);

} catch(Throwable $e){
    if(isset($db)&&$db->inTransaction()) $db->rollBack();
    echo json_encode(["success"=>false,"message"=>$e->getMessage()]);
}
