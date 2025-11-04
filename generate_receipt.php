<?php
require_once 'assets/libs/fpdf.php';

function generateReceiptPDF($bookingData, $totalPriceAll, $outputFile)
{
    $baseHeight = 140;          
    $perBookingHeight = 60;       
    $height = $baseHeight + (count($bookingData) * $perBookingHeight);

    if ($height < 250) $height = 250;

    $pdf = new FPDF('P', 'mm', array(210, $height));
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(20, 10, 20);
    $pdf->AddPage();

    $pdf->SetFillColor(0, 0, 0);
    $pdf->Rect(0, 0, 210, $height, 'F');
    $pdf->SetTextColor(255, 255, 255);

    $logo = 'assets/images/luminaLogo.png';
    if (file_exists($logo)) {
        $pdf->Image($logo, 75, 15, 60);
    }

    $pdf->Ln(55);

    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 12, 'Booking Receipt', 0, 1, 'C');

    $pdf->SetLineWidth(0.6);
    $pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
    $pdf->Ln(12);

    foreach ($bookingData as $b) {

        $pdf->SetFont('Arial', 'B', 15);
        $pdf->MultiCell(0, 8, $b['title'], 0, 'L');

        $pdf->SetFont('Arial', '', 13);
        $pdf->MultiCell(0, 7, $b['location_name'], 0, 'L');
        $pdf->Ln(1);

        $seatArray = array_map('trim', explode(',', $b['seats']));
        $seatChunks = array_chunk($seatArray, 4);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(22, 7, "Seats:", 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 12);

        $pdf->Cell(0, 7, implode(", ", $seatChunks[0]), 0, 1, 'L');

        foreach (array_slice($seatChunks, 1) as $chunk) {
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(22, 7, "", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 7, implode(", ", $chunk), 0, 1, 'L');
        }


        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(22, 7, "Date:", 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, $b['screening_date'], 0, 1, 'L');

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(22, 7, "Time:", 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, substr($b['screening_time'], 0, 5), 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, "Price: $" . number_format($b['total_price'], 2), 0, 1, 'R');

        $pdf->Ln(4);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
        $pdf->Ln(8);
    }

    $pdf->SetFont('Arial', 'B', 15);
    $pdf->Cell(0, 12, "Total Paid: $" . number_format($totalPriceAll, 2), 0, 1, 'R');

    $pdf->SetY($height - 25);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(200, 200, 200);
    $pdf->Cell(0, 6, "Thank you for choosing Lumina Cinema.", 0, 1, 'C');
    $pdf->Cell(0, 6, "Enjoy your movie experience.", 0, 1, 'C');

    $pdf->Output('F', $outputFile);
}
