<?php
session_start();
require_once 'db.php';
require_once 'assets/libs/fpdf.php';
require_once 'generate_receipt.php';
require_once 'email_template.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$booking_ids = $_POST['booking_ids'] ?? [];

if (empty($booking_ids)) {
    echo "<script>alert('No bookings selected.'); window.location='cart.php';</script>";
    exit;
}

$user = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$user->execute([$user_id]);
$userEmail = $user->fetchColumn();

$pdfDir = __DIR__ . "/tickets/";
if (!file_exists($pdfDir))
    mkdir($pdfDir, 0777, true);

$bookingData = [];
$totalPriceAll = 0;
$ticketFiles = [];

foreach ($booking_ids as $bid) {
    $stmt = $pdo->prepare("
        SELECT b.*, m.title, l.name AS location_name, s.id AS session_id
        FROM bookings b
        JOIN movies m ON b.movie_id = m.id
        JOIN locations l ON b.location_id = l.id
        JOIN sessions s ON b.session_id = s.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bid, $user_id]);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$b)
        continue;

    $seats = trim($b['seats'], '[]"');
    $seats = str_replace('","', ', ', $seats);
    $seatArray = explode(', ', $seats);
    $seatsWrapped = array_map(fn($c) => implode(", ", $c), array_chunk($seatArray, 5));

    try {
        $pdo->beginTransaction();
        $sel = $pdo->prepare("SELECT booked_seats FROM sessions WHERE id = ? FOR UPDATE");
        $sel->execute([$b['session_id']]);
        $current = $sel->fetchColumn() ?: '';
        $merged = trim($current . ',' . implode(',', $seatArray), ',');
        $upd = $pdo->prepare("UPDATE sessions SET booked_seats = ? WHERE id = ?");
        $upd->execute([$merged, $b['session_id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        echo "<script>alert('Failed to update session booked seats'); window.location='cart.php';</script>";
        exit;
    }

    $bookingData[] = [
        'title' => $b['title'],
        'location_name' => $b['location_name'],
        'seats' => $seats,
        'screening_date' => $b['screening_date'],
        'screening_time' => substr($b['screening_time'], 0, 5),
        'total_price' => $b['total_price']
    ];
    $totalPriceAll += $b['total_price'];

    $pdf = new FPDF('L', 'mm', array(210, 110));
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    $pdf->SetFillColor(0, 0, 0);
    $pdf->Rect(0, 0, 210, 110, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $logo = 'assets/images/luminaLogo.png';
    if (file_exists($logo))
        $pdf->Image($logo, (210 - 50) / 2, 6, 50);
    $pdf->SetDrawColor(255, 255, 255);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(30, 38, 180, 38);
    $pdf->SetFont("Arial", "B", 18);
    $pdf->SetXY(0, 42);
    $pdf->Cell(210, 8, $b['title'], 0, 1, "C");
    $pdf->Line(30, 54, 180, 54);
    $pdf->SetFont("Arial", "B", 14);
    $pdf->SetXY(0, 58);
    $pdf->Cell(210, 8, $b['location_name'], 0, 1, "C");
    $pdf->SetFont("Arial", "", 12);
    $xSeats = 35;
    $xDate = 95;
    $xTime = 155;
    $yStart = 70;
    $lineH = 6;
    $pdf->SetXY($xSeats, $yStart);
    $pdf->Cell(40, $lineH, "Seats:", 0, 0, "L");
    $pdf->SetXY($xDate, $yStart);
    $pdf->Cell(40, $lineH, "Date:", 0, 0, "L");
    $pdf->SetXY($xTime, $yStart);
    $pdf->Cell(40, $lineH, "Time:", 0, 1, "L");
    $valuesY = $yStart + 7;
    $pdf->SetFont("Arial", "B", 12);
    $yPos = $valuesY;
    foreach ($seatsWrapped as $line) {
        $pdf->SetXY($xSeats, $yPos);
        $pdf->Cell(60, $lineH, $line, 0, 1, "L");
        $yPos += $lineH;
    }
    $pdf->SetXY($xDate, $valuesY);
    $pdf->Cell(40, $lineH, $b['screening_date'], 0, 1, "L");
    $pdf->SetXY($xTime, $valuesY);
    $pdf->Cell(40, $lineH, substr($b['screening_time'], 0, 5), 0, 1, "L");
    $blockBottomY = max($yPos, $valuesY + $lineH);
    $pdf->SetFont("Arial", "B", 12);
    $paidY = $blockBottomY + 6;
    $pdf->SetXY($xTime, $paidY);
    $pdf->Cell(50, 8, "Paid: $" . number_format($b['total_price'], 2), 0, 1, "R");
    $pdf->Ln(3);
    $ticketFile = $pdfDir . "ticket_" . $b['id'] . ".pdf";
    $pdf->Output("F", $ticketFile);
    $ticketFiles[] = $ticketFile;
    $update = $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
    $update->execute([$bid]);
}

$receiptFile = $pdfDir . "receipt_" . time() . "_" . $user_id . ".pdf";
generateReceiptPDF($bookingData, $totalPriceAll, $receiptFile);

$to = $userEmail;
$from = "lumina@localhost";
$subject = "Your Lumina Cinema Booking Confirmation";
$htmlMessage = buildEmailHTML($bookingData, $totalPriceAll);
$boundary = "==Multipart_Boundary_x" . md5(time()) . "x";
$headers = "From: $from\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
$body = "--$boundary\r\n";
$body .= "Content-Type: text/html; charset=\"UTF-8\"\r\n\r\n";
$body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$body .= $htmlMessage . "\r\n\r\n";
$attachments = array_merge([$receiptFile], $ticketFiles);
foreach ($attachments as $file) {
    $fileName = basename($file);
    $fileData = chunk_split(base64_encode(file_get_contents($file)));
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/pdf; name=\"$fileName\"\r\n";
    $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= $fileData . "\r\n\r\n";
}
$body .= "--$boundary--";
mail($to, $subject, $body, $headers);
echo "<script>alert('Checkout successful! Tickets emailed.'); window.location='index.php';</script>";
