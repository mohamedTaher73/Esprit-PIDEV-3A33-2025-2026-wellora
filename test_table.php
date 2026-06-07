<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'wellora');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Test the daily_plan_exercises table
$result = $mysqli->query('SELECT * FROM daily_plan_exercises LIMIT 1');
if ($result) {
    echo "Query successful!\n";
    echo "Columns: " . $result->field_count . "\n";
    $result->close();
} else {
    echo "Query failed: " . $mysqli->error . "\n";
}

// Show table structure
$result = $mysqli->query('DESCRIBE daily_plan_exercises');
if ($result) {
    echo "\nTable structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    $result->close();
} else {
    echo "DESCRIBE failed: " . $mysqli->error . "\n";
}

$mysqli->close();
