<?php

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'dbname' => 'wellora',
    'user' => 'root',
    'password' => '',
    'driver' => 'pdo_mysql',
    'host' => '127.0.0.1',
];

$conn = DriverManager::getConnection($connectionParams);

$query = "SELECT email, first_name, last_name, is_active FROM users WHERE roles LIKE '%ADMIN%' OR roles LIKE '%ROLE_ADMIN%'";
$stmt = $conn->executeQuery($query);

echo "Admin accounts in database:\n";
echo "==========================\n";

while ($row = $stmt->fetchAssociative()) {
    echo "Email: " . $row['email'] . "\n";
    echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
    echo "Active: " . ($row['is_active'] ? 'Yes' : 'No') . "\n";
    echo "\n";
}
