<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'wellora');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Drop existing table
echo "Dropping table...\n";
$dropResult = $mysqli->query('DROP TABLE IF EXISTS daily_plan_exercises');
if (!$dropResult) {
    echo "Drop failed: " . $mysqli->error . "\n";
}

// Recreate the table
echo "Creating table...\n";
$createSQL = <<<SQL
CREATE TABLE daily_plan_exercises (
    daily_plan_id INT NOT NULL,
    exercises_id INT NOT NULL,
    PRIMARY KEY (daily_plan_id, exercises_id),
    INDEX IDX_9DAF22643778D36F (daily_plan_id),
    INDEX IDX_9DAF22641AFA70CA (exercises_id),
    CONSTRAINT FK_9DAF22643778D36F FOREIGN KEY (daily_plan_id) REFERENCES daily_plan (id) ON DELETE CASCADE,
    CONSTRAINT FK_9DAF22641AFA70CA FOREIGN KEY (exercises_id) REFERENCES exercises (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB
SQL;

$createResult = $mysqli->query($createSQL);
if ($createResult) {
    echo "Table created successfully!\n";
} else {
    echo "Create failed: " . $mysqli->error . "\n";
}

// Test the table
echo "Testing table...\n";
$result = $mysqli->query('SELECT * FROM daily_plan_exercises LIMIT 1');
if ($result) {
    echo "Query successful! Table is now accessible.\n";
    $result->close();
} else {
    echo "Query failed: " . $mysqli->error . "\n";
}

$mysqli->close();
