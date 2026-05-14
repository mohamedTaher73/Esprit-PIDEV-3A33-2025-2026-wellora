<?php
// Simple PHP test script to make a DELETE request and capture full response

$url = 'http://127.0.0.1:8000/appointment/delete/41';

echo "Testing DELETE endpoint: $url\n";
echo str_repeat("=", 80) . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Capture verbose output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$errorCode = curl_errno($ch);

// Get verbose output
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

echo "HTTP Status Code: $httpCode\n";
echo str_repeat("-", 80) . "\n\n";

if ($error) {
    echo "CURL ERROR:\n";
    echo "Error Code: $errorCode\n";
    echo "Error Message: $error\n\n";
}

echo "VERBOSE LOG:\n";
echo $verboseLog . "\n";
echo str_repeat("-", 80) . "\n\n";

echo "FULL RESPONSE (Headers + Body):\n";
echo $response . "\n";
echo str_repeat("=", 80) . "\n";

// Parse headers and body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

echo "\nPARSED HEADERS:\n";
echo $headers . "\n";
echo str_repeat("-", 80) . "\n";

echo "\nPARSED BODY:\n";
echo $body . "\n";

curl_close($ch);
?>
