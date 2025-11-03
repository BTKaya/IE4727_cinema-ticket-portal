<?php
include 'db.php';

// --- Fetch movie and related data ---
$movie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$location_id = isset($_GET['location_id']) ? (int) $_GET['location_id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);
$layout = $movie['location_type'] ?? 1;

$stmt2 = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// sessions for this movie
$sessionsStmt = $pdo->prepare("
    SELECT id, session_date, session_time, location_id
    FROM sessions
    WHERE movie_id = ?
    ORDER BY session_date, session_time
");
$sessionsStmt->execute([$movie_id]);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

$session_id = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

$locationsById = [];
foreach ($stmt2 as $loc) {
  $locationsById[$loc['id']] = $loc['name'];
}

include 'header.php';
include 'menu.php';

if (!$movie) {
  echo "<div class='movie-page'><p>Movie not found.</p></div>";
  include 'footer.php';
  exit;
}

$date = $_GET['date'] ?? "";
$time = $_GET['time'] ?? "";
$location = $_GET['location'] ?? "";

// Resolve location name from ID
if ($location_id != 0 && isset($locationsById[$location_id])) {
  $location = $locationsById[$location_id];
}

// If session_id provided, pull authoritative data
if ($session_id) {
  $sessStmt = $pdo->prepare("
      SELECT movie_id, location_id, session_date, session_time
      FROM sessions
      WHERE id = ?
      LIMIT 1
  ");
  $sessStmt->execute([$session_id]);
  $sessRow = $sessStmt->fetch(PDO::FETCH_ASSOC);
  if ($sessRow) {
    $movie_id = (int) $sessRow['movie_id'];
    $location_id = (int) $sessRow['location_id'];
    $date = $sessRow['session_date'];
    $time = substr($sessRow['session_time'], 0, 5);
  }
}

if (!$session_id && $movie_id && $date && $time && $location_id) {
  $sessStmt2 = $pdo->prepare("
      SELECT id FROM sessions
      WHERE movie_id = ?
        AND location_id = ?
        AND session_date = ?
        AND (session_time = ? OR session_time LIKE CONCAT(?, ':__'))
      LIMIT 1
  ");
  $sessStmt2->execute([$movie_id, $location_id, $date, $time, $time]);
  $session_id = (int) ($sessStmt2->fetchColumn() ?: 0);
}

/* ======== Fetch booked seats from sessions.booked_seats ======== */
function parseSeatList(string $s): array
{
  if ($s === '')
    return [];
  // no spaces expected; split and normalize
  $arr = array_map('trim', explode(',', $s));
  // de-dup + remove empties
  $arr = array_values(array_unique(array_filter($arr, fn($x) => $x !== '')));
  return $arr;
}

$bookedSeats = [];
if ($session_id > 0) {
  $q = $pdo->prepare("SELECT booked_seats FROM sessions WHERE id = ? LIMIT 1");
  $q->execute([$session_id]);
  $csv = (string) ($q->fetchColumn() ?: '');
  $bookedSeats = parseSeatList($csv);
}
/* ============================================================================ */

$rows = range('A', 'H');
?>

<div class="movie-page">
  <h1><?= htmlspecialchars($movie['title']); ?></h1>

  <div class="movie-info">
    <img src="<?= htmlspecialchars($movie['poster']); ?>" alt="Poster" style="margin-top: 0.75rem;">
    <table class="movie-info-table">
      <tr>
        <th>Title:</th>
        <td><?= htmlspecialchars($movie['title']); ?></td>
      </tr>
      <tr>
        <th>Summary:</th>
        <td><?= nl2br(htmlspecialchars($movie['summary'])); ?></td>
      </tr>
      <tr>
        <th>Director:</th>
        <td><?= htmlspecialchars($movie['director']); ?></td>
      </tr>
      <tr>
        <th>Actors:</th>
        <td><?= htmlspecialchars($movie['actors']); ?></td>
      </tr>
      <tr>
        <th>Release Date:</th>
        <td><?= htmlspecialchars($movie['release_date']); ?></td>
      </tr>
      <tr>
        <th>Trailer:</th>
        <td><a href="<?= htmlspecialchars($movie['trailer_url']); ?>" target="_blank" class="trailer-link">Watch on
            YouTube</a></td>
      </tr>
    </table>
  </div>

  <hr>

  <div class="dateTimeLocation-select">
    <label>Date:</label>
    <select id="screening_date">
      <option value="">-- Select --</option>
      <?php
      $uniqueDates = [];
      foreach ($sessions as $s) {
        if (!in_array($s['session_date'], $uniqueDates)) {
          $uniqueDates[] = $s['session_date'];
          echo "<option value='{$s['session_date']}'>{$s['session_date']}</option>";
        }
      }
      ?>
    </select>

    <label style="margin-left:1rem;">Time:</label>
    <select id="screening_time">
      <option value="">-- Select --</option>
      <?php
      foreach ($sessions as $s) {
        $timeShort = substr($s['session_time'], 0, 5);
        echo "<option value='{$timeShort}' data-date='{$s['session_date']}' data-loc='{$s['location_id']}'>{$timeShort}</option>";
      }
      ?>
    </select>

    <label style="margin-left:1rem;">Location:</label>
    <select id="location">
      <option value="">-- Select --</option>
      <?php
      foreach ($locationsById as $id => $name) {
        echo "<option value='{$id}'>{$name}</option>";
      }
      ?>
    </select>
  </div>

  <?php if ($date && $time && $location_id): ?>
    <hr>
    <h2 class="choose-seats-title">Select Your Seats</h2>
    <div class="screen-label">SCREEN</div>

    <div class="seat-container">
      <?php foreach ($rows as $r): ?>
        <div class="seat-row">
          <span class="row-label"><?= $r ?></span>
          <?php
          $seats = ($layout == 1) ? range(1, 8) : range(1, 10);
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

<input type="hidden" id="movieId" value="<?= $movie_id ?>">
<input type="hidden" id="initDate" value="<?= $date ?>">
<input type="hidden" id="initTime" value="<?= $time ?>">
<input type="hidden" id="initLocation" value="<?= $location_id ?>">
<input type="hidden" id="sessionId" value="<?= $session_id ?>">

<script src="movieDetail.js" defer></script>
<?php include 'footer.php'; ?>