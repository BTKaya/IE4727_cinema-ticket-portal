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

// fetch sessions for this movie from sessions table
$sessionsStmt = $pdo->prepare("
    SELECT session_date, session_time, location_id
    FROM sessions
    WHERE movie_id = ?
    ORDER BY session_date, session_time
");
$sessionsStmt->execute([$movie_id]);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// we also need location names by id for the dropdowns
$locationsById = [];
foreach ($stmt2 as $loc) {
  $locationsById[$loc['id']] = $loc['name'];
}

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
    <select id="screening_date">
      <option value="">-- Select --</option>
    </select>

    <label style="margin-left:1rem;">Time:</label>
    <select id="screening_time">
      <option value="">-- Select --</option>
    </select>

    <label style="margin-left:1rem;">Location:</label>
    <select id="location">
      <option value="">-- Select --</option>
    </select>
  </div>

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

<!-- Popup Overlay -->
<div id="popup-overlay" class="popup-overlay">
  <div class="popup-box">
    <h2 id="popup-title"></h2>
    <p id="popup-message"></p>
    <div class="popup-buttons">
      <button id="popup-close" class="popup-btn">OK</button>
      <a href="cart.php" id="popup-cart" class="popup-btn cart-link" style="display:none;">Go to Cart</a>
    </div>
  </div>
</div>

<script>
  window.SESSIONS = <?= json_encode($sessions) ?>;
  window.LOCATIONS = <?= json_encode($locationsById) ?>;
  window.MOVIE_ID = <?= (int) $movie_id ?>;
  window.INIT_DATE = "<?= $date ?>";
  window.INIT_TIME = "<?= $time ?>";
  window.INIT_LOC = "<?= $location ?>";
</script>
<script src="movieDetail.js" defer></script>

<?php include 'footer.php'; ?>