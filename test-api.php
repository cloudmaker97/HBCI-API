<?php

// Test script for the HBCI FinTS REST API
// Usage: php test-api.php

$apiUrl = 'http://localhost:8000/api/balance';
$apiPassword = 'your-secure-api-password'; // Change this to your actual API password

echo "Testing HBCI FinTS REST API...\n";
echo "URL: $apiUrl\n\n";

// Test without authentication (should fail)
echo "1. Testing without authentication (should fail):\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test with authentication (should succeed if configured correctly)
echo "2. Testing with authentication:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiPassword"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

echo "Test completed!\n";
