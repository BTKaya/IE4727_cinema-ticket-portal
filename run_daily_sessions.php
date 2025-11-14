<?php
require_once __DIR__ . '/db.php';

// file storing last run date
$flagFile = __DIR__ . "/last_run.txt";
$today = date("Y-m-d");

// read previous run date
$lastRun = file_exists($flagFile) ? trim(file_get_contents($flagFile)) : "";

// only run once per day
if ($lastRun !== $today) {

    include __DIR__ . "/generate_sessions.php";

    // update flag file
    file_put_contents($flagFile, $today);
}
?>
