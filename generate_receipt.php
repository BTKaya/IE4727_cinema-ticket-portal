<?php
require_once 'assets/libs/fpdf.php';

function generateReceiptPDF($bookings, $totalPrice, $outputPath) {
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont("Arial", "B", 18);
    $pdf->Cell(0, 10, "Your Lumina Cinema Booking Receipt", 0, 1, "C");
    $pdf->Ln(5);

    $pdf->SetFont("Arial", "", 12);

    foreach ($bookings as $i => $b) {
        $pdf->MultiCell(0, 7,
            ($i+1) . ". " . $b['title'] .
            "\n   Location: " . $b['location_name'] . 
            "\n   Seats: " . $b['seats'] .
            "\n   Date: " . $b['screening_date'] . "   Time: " . substr($b['screening_time'], 0, 5) .
            "\n   Price: $" . number_format($b['total_price'], 2),
            0, 1
        );
        $pdf->Ln(3);
    }

    $pdf->SetFont("Arial", "B", 14);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, "Total Paid: $" . number_format($totalPrice, 2), 0, 1, "C");

    $pdf->Output("F", $outputPath);
}
