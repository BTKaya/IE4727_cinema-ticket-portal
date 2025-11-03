<?php
require_once 'assets/libs/fpdf.php';

function generateReceiptPDF($bookingData, $totalPriceAll, $outputFile)
{
    // ===== Dynamic height calculation =====
    $baseHeight = 140;              // header + footer
    $perBookingHeight = 60;         // height per booking block
    $height = $baseHeight + (count($bookingData) * $perBookingHeight);

    // Guarantee minimum height so small orders still fill page
    if ($height < 250) $height = 250;

    // Create dynamic-height PDF
    $pdf = new FPDF('P', 'mm', array(210, $height));
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(20, 10, 20);
    $pdf->AddPage(); // <-- correct, uses dynamic height now

    // ===== Full background after AddPage() =====
    $pdf->SetFillColor(0, 0, 0);
    $pdf->Rect(0, 0, 210, $height, 'F');
    $pdf->SetTextColor(255, 255, 255);

    // ===== Centered Logo =====
    $logo = 'assets/images/luminaLogo.png';
    if (file_exists($logo)) {
        $pdf->Image($logo, 75, 15, 60);
    }

    $pdf->Ln(55);

    // ===== Title =====
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 12, 'Booking Receipt', 0, 1, 'C');

    // Divider under title
    $pdf->SetLineWidth(0.6);
    $pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
    $pdf->Ln(12);

    // ===== Booking Blocks =====
    foreach ($bookingData as $b) {

        // Movie
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->MultiCell(0, 8, $b['title'], 0, 'L');

        // Location
        $pdf->SetFont('Arial', '', 13);
        $pdf->MultiCell(0, 7, $b['location_name'], 0, 'L');
        $pdf->Ln(1);

        // Seats (wrap every 5)
        $seatArray = explode(", ", $b['seats']);
        $chunks = array_chunk($seatArray, 5);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(22, 7, "Seats:", 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, implode(", ", $chunks[0]), 0, 1, 'L');

        for ($i = 1; $i < count($chunks); $i++) {
            $pdf->Cell(22, 7, "", 0, 0, 'L');
            $pdf->Cell(0, 7, implode(", ", $chunks[$i]), 0, 1, 'L');
        }

        // Date
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(22, 7, "Date:", 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, $b['screening_date'], 0, 1, 'L');

        // Time
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(22, 7, "Time:", 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, substr($b['screening_time'], 0, 5), 0, 1, 'L');

        // Price right aligned
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, "Price: $" . number_format($b['total_price'], 2), 0, 1, 'R');

        // Divider
        $pdf->Ln(4);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
        $pdf->Ln(8);
    }

    // ===== Total Paid =====
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->Cell(0, 12, "Total Paid: $" . number_format($totalPriceAll, 2), 0, 1, 'R');

    // ===== Footer (Keep safely above bottom edge) =====
    $pdf->SetY($height - 25);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(200, 200, 200);
    $pdf->Cell(0, 6, "Thank you for choosing Lumina Cinema.", 0, 1, 'C');
    $pdf->Cell(0, 6, "Enjoy your movie experience.", 0, 1, 'C');

    $pdf->Output('F', $outputFile);
}
