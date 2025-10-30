<?php
include 'db.php';


// Get movie ID
$movie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get movie info
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);
$layout = $movie['location_type'];

include 'header.php';
include 'menu.php';

if (!$movie) {
    echo "<div class='movie-page'><p>Movie not found.</p></div>";
    include 'footer.php';
    exit;
}

// Determine selected date & time (if user has chosen)
$date = $_GET['date'] ?? "";
$time = $_GET['time'] ?? "";

// Fetch already booked seats for this session (if date + time selected)
$bookedSeats = [];
if ($date && $time) {
    $seatQuery = $pdo->prepare("
        SELECT seats FROM bookings 
        WHERE movie_id = ? AND screening_date = ? AND screening_time = ? 
        AND status IN ('pending','confirmed')
    ");
    $seatQuery->execute([$movie_id, $date, $time]);
    $rows = $seatQuery->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $taken = json_decode($r['seats'], true);
        foreach ($taken as $s)
            $bookedSeats[] = $s;
    }
}

// Seat layout config
$rows = range('A', 'H');
?>

<!---------------- MOVIE DETAILS ---------------->
<div class="movie-page">

    <h1><?= htmlspecialchars($movie['title']); ?></h1>

    <div class="movie-info">
        <img src="<?= htmlspecialchars($movie['poster']); ?>" alt="Poster">
        <p><?= nl2br(htmlspecialchars($movie['summary'])); ?></p>
    </div>

    <hr>

    <div class="datetime-select">
        <label>Date:</label>
        <input type="date" id="screening_date" value="<?= htmlspecialchars($date); ?>">

        <label style="margin-left:1rem;">Time:</label>
        <select id="screening_time">
            <option value="">-- Select --</option>
            <option value="09:00" <?= $time == "09:00" ? "selected" : ""; ?>>9:00 AM</option>
            <option value="12:00" <?= $time == "12:00" ? "selected" : ""; ?>>12:00 PM</option>
            <option value="15:00" <?= $time == "15:00" ? "selected" : ""; ?>>3:00 PM</option>
            <option value="19:00" <?= $time == "19:00" ? "selected" : ""; ?>>7:00 PM</option>
        </select>
    </div>

    <script>
        function updateShowtimeRedirect() {
            const d = document.getElementById('screening_date').value;
            const t = document.getElementById('screening_time').value;
            const id = <?= $movie_id ?>;
            if (d && t) location.href = `movie.php?id=${id}&date=${d}&time=${t}`;
        }
        document.getElementById('screening_date').addEventListener('change', updateShowtimeRedirect);
        document.getElementById('screening_time').addEventListener('change', updateShowtimeRedirect);
    </script>

    <!---------------- SEAT MAP ---------------->
    <?php if ($date && $time): ?>
        <hr>
        <h2 class="choose-seats-title">Select Your Seats</h2>

        <div class="screen-label">SCREEN</div>

        <div class="seat-container">

            <?php foreach ($rows as $r): ?>
                <div class="seat-row">
                    <span class="row-label"><?= $r ?></span>

                    <?php
                    if ($layout == 1)
                        $seats = range(1, 8);
                    else
                        $seats = range(1, 10);

                    foreach ($seats as $i):
                        $seat_id = $r . $i;
                        $class = in_array($seat_id, $bookedSeats) ? "seat booked" : "seat available";
                        echo "<div class='$class' data-seat='$seat_id'></div>";

                        if ($layout == 1 && in_array($i, [2, 4, 6]))
                            echo "<div class='aisle-gap'></div>";
                        if ($layout == 2 && in_array($i, [2, 8]))
                            echo "<div class='aisle-gap'></div>";
                    endforeach;
                    ?>
                </div>
            <?php endforeach; ?>

        </div>

        <div class="seat-legend">
            <div class="legend-item">
                <div class="seat available static"></div><span>Available</span>
            </div>
            <div class="legend-item">
                <div class="seat selected static"></div><span>Selected</span>
            </div>
            <div class="legend-item">
                <div class="seat booked static"></div><span>Booked</span>
            </div>
        </div>

        <div class="selected-summary">
            <p><strong>Selected Seats:</strong> <span id="selectedSeatsText">None</span></p>
            <p><strong>Total Price:</strong> $ <span id="totalPrice">0</span></p>
        </div>



        <div class="confirm-wrap">
            <button id="confirmBooking" class="confirm-btn">Confirm Selection</button>
        </div>


    <?php else: ?>
        <p style="margin-top:2rem; opacity:0.8;">Select a date and time to view seat availability.</p>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>