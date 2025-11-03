<?php
session_start();
require_once 'db.php';
require_once 'assets/libs/fpdf.php';
require_once 'generate_receipt.php';
require_once 'email_template.php';

// must be logged in
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

// Get user email
$user = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$user->execute([$user_id]);
$userEmail = $user->fetchColumn();

// folder for PDF tickets
$pdfDir = __DIR__ . "/tickets/";
if (!file_exists($pdfDir)) {
    mkdir($pdfDir, 0777, true);
}

$bookingData = [];
$totalPriceAll = 0;
$ticketFiles = [];

foreach ($booking_ids as $bid) {

    // Fetch booking details
    $stmt = $pdo->prepare("
        SELECT b.*, m.title, l.name AS location_name
        FROM bookings b
        JOIN movies m ON b.movie_id = m.id
        JOIN locations l ON b.location_id = l.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bid, $user_id]);
    $b = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$b) continue;

    $seatArray = json_decode($b['seats'], true);
    $seatArray = is_array($seatArray) ? $seatArray : [$seatArray];

    // Break into lines of max 5 seats each
    $seatLines = array_chunk($seatArray, 5);
    $seatsWrapped = array_map(fn($chunk) => implode(", ", $chunk), $seatLines);


    $seatArray = json_decode($b['seats'], true);
    $seatArray = is_array($seatArray) ? $seatArray : [$seatArray];

    // Save original for receipt + email
    $b['seats'] = implode(", ", $seatArray);

    // Create wrapped version only for ticket PDF
    $seatLines = array_chunk($seatArray, 5);
    $seatsWrapped = array_map(fn($chunk) => implode(", ", $chunk), $seatLines);

    // Prepare table data for receipt & email
    $bookingData[] = [
        'title' => $b['title'],
        'location_name' => $b['location_name'],
        'seats' => $b['seats'], // already the readable version
        'screening_date' => $b['screening_date'],
        'screening_time' => substr($b['screening_time'], 0, 5),
        'total_price' => $b['total_price']
    ];

    $totalPriceAll += $b['total_price'];


    // Create PDF
    $pdf = new FPDF('L', 'mm', array(210, 110));
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    // Background
    $pdf->SetFillColor(0, 0, 0);
    $pdf->Rect(0, 0, 210, 110, 'F');
    $pdf->SetTextColor(255, 255, 255);

    // ===== Logo =====
    $logo = 'assets/images/luminaLogo.png';
    if (file_exists($logo)) {
        $pdf->Image($logo, (210 - 50) / 2, 6, 50);
    }

    // ===== Divider =====
    $pdf->SetDrawColor(255, 255, 255);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(30, 38, 180, 38);

    // ===== Movie Title =====
    $pdf->SetFont("Arial", "B", 18);
    $pdf->SetXY(0, 42);
    $pdf->Cell(210, 8, $b['title'], 0, 1, "C");

    // ===== Divider =====
    $pdf->Line(30, 54, 180, 54);

    // ===== Location =====
    $pdf->SetFont("Arial", "B", 14);
    $pdf->SetXY(0, 58);
    $pdf->Cell(210, 8, $b['location_name'], 0, 1, "C");

    // ===== Info Row (Aligned Columns) =====
    $pdf->SetFont("Arial", "", 12);

    // Column positions
    $xSeats = 35;     // seats label + values column
    $xDate  = 95;     // date column
    $xTime  = 155;    // time column
    $yStart = 70;     // top of the labels row
    $lineH  = 6;

    // ---- Labels row (one line) ----
    $pdf->SetXY($xSeats, $yStart);
    $pdf->Cell(40, $lineH, "Seats:", 0, 0, "L");

    $pdf->SetXY($xDate, $yStart);
    $pdf->Cell(40, $lineH, "Date:", 0, 0, "L");

    $pdf->SetXY($xTime, $yStart);
    $pdf->Cell(40, $lineH, "Time:", 0, 1, "L");

    // ---- Values row ----
    $valuesY = $yStart + 7; // start values just below labels

    // Seats (wrapped, under "Seats:")
    $pdf->SetFont("Arial", "B", 12);
    $yPos = $valuesY;
    foreach ($seatsWrapped as $line) {
        $pdf->SetXY($xSeats, $yPos);
        $pdf->Cell(60, $lineH, $line, 0, 1, "L");
        $yPos += $lineH;
    }

    // Date value (single line)
    $pdf->SetXY($xDate, $valuesY);
    $pdf->Cell(40, $lineH, $b['screening_date'], 0, 1, "L");

    // Time value (single line)
    $pdf->SetXY($xTime, $valuesY);
    $pdf->Cell(40, $lineH, substr($b['screening_time'], 0, 5), 0, 1, "L");

    // Compute the lower edge of this block (max of seats block vs date/time line)
    $blockBottomY = max($yPos, $valuesY + $lineH);


    // ===== Price (Spaced) =====
    $pdf->SetFont("Arial", "B", 12);
    $paidY = $blockBottomY + 6;          // push down a bit
    $pdf->SetXY($xTime, $paidY);
    $pdf->Cell(50, 8, "Paid: $" . number_format($b['total_price'], 2), 0, 1, "R");

    $pdf->Ln(3);

    // Save
    $ticketFile = $pdfDir . "ticket_" . $b['id'] . ".pdf";
    $pdf->Output("F", $ticketFile);

    $ticketFiles[] = $ticketFile;

    // Mark booking as completed
    $update = $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
    $update->execute([$bid]);
}

// ✅ Generate Receipt
$receiptFile = $pdfDir . "receipt_" . time() . "_" . $user_id . ".pdf";
generateReceiptPDF($bookingData, $totalPriceAll, $receiptFile);

// ✅ Send Email with attachments
$to = $userEmail;
$from = "lumina@localhost";
$subject = "Your Lumina Cinema Booking Confirmation";

$htmlMessage = buildEmailHTML($bookingData, $totalPriceAll);

// ✅ Build email
$boundary = "==Multipart_Boundary_x" . md5(time()) . "x";

$headers = "From: $from\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

// ✅ Email Body (HTML)
$body = "--$boundary\r\n";
$body .= "Content-Type: text/html; charset=\"UTF-8\"\r\n\r\n";
$body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$body .= $htmlMessage . "\r\n\r\n";

// ✅ Attach Receipt & Tickets
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

// ✅ Send Mail
mail($to, $subject, $body, $headers);


echo "<script>alert('✅ Checkout successful! Tickets emailed.'); window.location='index.php';</script>";
