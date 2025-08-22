<?php
// Test Android API endpoints
echo "<h2>Android API Test</h2>";

$baseUrl = "https://app.vetrx.in/api/android";
$testUid = "251097"; // Bear from your database

echo "<h3>Test Data:</h3>";
echo "Using UID: <strong>$testUid</strong> (Bear - Rajesh Kumar)<br><br>";

echo "<h3>1. Test Open Visit:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/visit/open");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['uid' => $testUid]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

if ($httpCode === 200) {
    echo "<span style='color: green;'>✓ Open Visit API working</span><br>";
} else {
    echo "<span style='color: red;'>✗ Open Visit API failed</span><br>";
}

echo "<h3>2. Test Get Today's Visit:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/visit/today?uid=$testUid");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

if ($httpCode === 200) {
    echo "<span style='color: green;'>✓ Get Today's Visit API working</span><br>";
} else {
    echo "<span style='color: orange;'>ⓘ No visit today (expected if no visit created yet)</span><br>";
}

echo "<h3>3. Storage Directory Check:</h3>";
$year = date('Y');
$storageDir = storage_path("app/public/patients/$year/$testUid");
$publicDir = public_path("storage/patients/$year/$testUid");

echo "Storage directory: $storageDir<br>";
echo "Public access: $publicDir<br>";

if (!is_dir($storageDir)) {
    echo "<span style='color: orange;'>ⓘ Storage directory will be created on first upload</span><br>";
} else {
    echo "<span style='color: green;'>✓ Storage directory exists</span><br>";
}

// Check if storage link exists
if (!is_dir(public_path('storage'))) {
    echo "<br><span style='color: red;'>✗ Storage symlink missing!</span><br>";
    echo "Create symlink by visiting: <a href='/storage-link.php'>storage-link.php</a><br>";
} else {
    echo "<br><span style='color: green;'>✓ Storage symlink exists</span><br>";
}

echo "<h3>4. File Upload Test (Manual):</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<strong>Use this curl command to test file upload:</strong><br><br>";
echo "<code style='background: white; padding: 10px; display: block; font-family: monospace;'>";
echo "curl -X POST '$baseUrl/visit/upload' \\<br>";
echo "&nbsp;&nbsp;-F 'uid=$testUid' \\<br>";
echo "&nbsp;&nbsp;-F 'type=photo' \\<br>";
echo "&nbsp;&nbsp;-F 'note=Test upload from API' \\<br>";
echo "&nbsp;&nbsp;-F 'file=@/path/to/your/image.jpg'";
echo "</code>";
echo "</div>";

echo "<h3>API Endpoints Ready:</h3>";
echo "<ul>";
echo "<li><strong>POST</strong> /api/android/visit/open - Open/create today's visit</li>";
echo "<li><strong>POST</strong> /api/android/visit/upload - Upload files</li>";
echo "<li><strong>GET</strong> /api/android/visit/today - Get today's visits</li>";
echo "</ul>";

echo "<br><a href='/patient/intake' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>← Back to Patient Intake</a>";
?>