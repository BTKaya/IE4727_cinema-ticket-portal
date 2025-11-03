<?php

function buildEmailHTML($bookingData, $totalPriceAll) {

    $logoUrl = "http://localhost/IE4727%20Final%20Project/IE4727-Project/assets/images/luminaLogo.png";

    $rowsHTML = "";
    foreach ($bookingData as $b) {
        // Convert seats string -> array -> wrap 5 per line
        $seatArray = explode(", ", $b['seats']);
        $seatLines = array_chunk($seatArray, 5);
        $seatsWrapped = implode("<br>", array_map(fn($chunk) => implode(", ", $chunk), $seatLines));

        // Ensure time is trimmed to HH:MM format
        $time = substr($b['screening_time'], 0, 5);

        $rowsHTML .= "
            <tr>
                <td style='padding:8px; border-bottom:1px solid #eee;'>{$b['title']}</td>
                <td style='padding:8px; border-bottom:1px solid #eee;'>{$b['location_name']}</td>
                <td style='padding:8px; border-bottom:1px solid #eee;'>$seatsWrapped</td>
                <td style='padding:8px; border-bottom:1px solid #eee;'>{$b['screening_date']}</td>
                <td style='padding:8px; border-bottom:1px solid #eee;'>$time</td>
                <td style='padding:8px; border-bottom:1px solid #eee;'>$" . number_format($b['total_price'], 2) . "</td>
            </tr>
        ";
    }

    return "
    <div style='background-color:#000; color:#fff; padding:30px; font-family:Arial, sans-serif;'>
        <div style='text-align:center; margin-bottom:20px;'>
            <img src='{$logoUrl}' alt='Lumina Logo' style='width:130px; opacity:0.95;'/>
        </div>

        <h2 style='text-align:center; font-weight:normal; letter-spacing:1px;'>Your Booking Confirmation</h2>

        <br>

        <table style='width:100%; border-collapse:collapse; font-size:14px;'>
            <thead>
                <tr style='background:#111;'>
                    <th style='padding:10px; text-align:left;'>Movie</th>
                    <th style='padding:10px; text-align:left;'>Location</th>
                    <th style='padding:10px; text-align:left;'>Seats</th>
                    <th style='padding:10px; text-align:left;'>Date</th>
                    <th style='padding:10px; text-align:left;'>Time</th>
                    <th style='padding:10px; text-align:left;'>Price</th>
                </tr>
            </thead>
            <tbody>
                $rowsHTML
            </tbody>
        </table>

        <h3 style='text-align:right; margin-top:20px; font-weight:normal;'>
            Total Paid: <strong>$" . number_format($totalPriceAll, 2) . "</strong>
        </h3>

        <p style='margin-top:35px; text-align:center; opacity:0.85;'>
            Your ticket PDFs are attached to this email. <br>
            Please show them at the cinema entrance during admission.
        </p>
        <br>
        <p style='text-align:center; opacity:0.9;'>Thank you for booking with Lumina Cinema.</p>
    </div>
    ";
}
