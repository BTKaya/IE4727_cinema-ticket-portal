<?php
require_once __DIR__ . '/db.php';

// get all movies
$movies = $pdo->query("SELECT id FROM movies")->fetchAll(PDO::FETCH_COLUMN);

// get all locations
$locations = $pdo->query("SELECT id FROM locations")->fetchAll(PDO::FETCH_COLUMN);

// fixed session times
$times = ["09:00:00", "12:00:00", "15:00:00", "18:00:00", "21:00:00"];

// number of halls per location (adjust if needed)
$hallsPerLocation = 12;

// generate sessions for next 5 days
for ($i = 0; $i <= 4; $i++) {

    $date = date("Y-m-d", strtotime("+$i day"));

    foreach ($locations as $location_id) {

        foreach ($times as $t) {

            // generate a unique random hall order
            $availableHalls = range(1, $hallsPerLocation);
            shuffle($availableHalls);

            // loop through movies
            foreach ($movies as $movie_id) {

                // assign hall
                $hallNumber = array_pop($availableHalls);
                if (!$hallNumber) break; // if no halls left, stop assigning

                $hallName = "Hall " . $hallNumber;

                // insert only if not exists
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO sessions 
                    (movie_id, location_id, session_date, session_time, hall)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$movie_id, $location_id, $date, $t, $hallName]);
            }
        }
    }
}
?>
