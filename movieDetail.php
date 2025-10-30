<?php
include 'db.php';


// Get movie ID
$movie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get location ID
$location_id = isset($_GET['location_id']) ? (int) $_GET['location_id'] : 0;

// Get movie info
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);
$layout = $movie['location_type'];

// Get Location info
$stmt2 = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'menu.php';

if (!$movie) {
  echo "<div class='movie-page'>
    <p>Movie not found.</p>
</div>";
  include 'footer.php';
  exit;
}

// Determine selected date & time (if user has chosen)
$date = $_GET['date'] ?? "";
$time = $_GET['time'] ?? "";
$location = $_GET['location'] ?? "";

if ($location_id != 0) {
  $location = '';
  foreach ($stmt2 as $loc) {
    if ((int) $loc['id'] === (int) $location_id) {
      $location = $loc['name'];
      break;
    }
  }
}

// Fetch already booked seats for this session (if date + time selected)
$bookedSeats = [];
if ($date && $time && $location) {
  $seatQuery = $pdo->prepare("
SELECT seats FROM bookings
WHERE movie_id = ? AND screening_date = ? AND screening_time = ?
AND location = ? AND status IN ('pending','confirmed')
");
  $seatQuery->execute([$movie_id, $date, $time, $location]);
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

  <div class="dateTimeLocation-select">
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

    <label style="margin-left:1rem;">Location:</label>
    <select id="location">
      <option value="">-- Select --</option>
      <?php foreach ($stmt2 as $loc): ?>
        <option value="<?= htmlspecialchars($loc['name']) ?>" <?= ($location === $loc['name']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($loc['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <script>
    function updateShowtimeRedirect() {
      const d = document.getElementById('screening_date').value;
      const t = document.getElementById('screening_time').value;
      const l = document.getElementById('location').value;
      const id = <?= $movie_id ?>;
      if (d && t && l) location.href = `movieDetail.php?id=${id}&date=${d}&time=${t}&location=${l}`;
    }
    document.getElementById('screening_date').addEventListener('change', updateShowtimeRedirect);
    document.getElementById('screening_time').addEventListener('change', updateShowtimeRedirect);
    document.getElementById('location').addEventListener('change', updateShowtimeRedirect);
  </script>

  <!---------------- SEAT MAP ---------------->
  <?php if ($date && $time && $location): ?>
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