<?php
// debug_api_simple.php
// Place this in: /home/dpharmai54/app.vetrx.in/public/debug_api_simple.php

echo "<h1>Android API Debug Report (Simple)</h1>";
echo "<style>
body { font-family: monospace; margin: 20px; }
.section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #f9f9f9; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style>";

// ================================================
// 1. BASIC ENVIRONMENT
// ================================================
echo "<div class='section'>";
echo "<h2>1. Basic Environment</h2>";

echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";
echo "<strong>Parent Directory:</strong> " . dirname(__DIR__) . "<br>";
echo "<strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "<br>";

// Check Laravel structure
$laravelRoot = dirname(__DIR__);
echo "<br><strong>Laravel Structure Check:</strong><br>";

$files = ['artisan', 'composer.json', 'bootstrap/app.php', 'vendor/autoload.php'];
foreach ($files as $file) {
    $path = $laravelRoot . '/' . $file;
    if (file_exists($path)) {
        echo "<span class='success'>✓ $file exists</span><br>";
    } else {
        echo "<span class='error'>✗ $file missing</span><br>";
    }
}

echo "</div>";

// ================================================
// 2. DIRECT DATABASE TEST
// ================================================
echo "<div class='section'>";
echo "<h2>2. Database Connection Test</h2>";

// Try to read .env file
$envPath = $laravelRoot . '/.env';
$dbConfig = [];

if (file_exists($envPath)) {
    echo "<span class='success'>✓ .env file found</span><br>";
    $envContent = file_get_contents($envPath);
    
    // Parse basic DB config
    preg_match('/DB_HOST=(.*)/', $envContent, $matches);
    $dbConfig['host'] = isset($matches[1]) ? trim($matches[1]) : 'localhost';
    
    preg_match('/DB_DATABASE=(.*)/', $envContent, $matches);
    $dbConfig['database'] = isset($matches[1]) ? trim($matches[1]) : '';
    
    preg_match('/DB_USERNAME=(.*)/', $envContent, $matches);
    $dbConfig['username'] = isset($matches[1]) ? trim($matches[1]) : '';
    
    preg_match('/DB_PASSWORD=(.*)/', $envContent, $matches);
    $dbConfig['password'] = isset($matches[1]) ? trim($matches[1]) : '';
    
    echo "<strong>DB Config:</strong><br>";
    echo "&nbsp;&nbsp;Host: {$dbConfig['host']}<br>";
    echo "&nbsp;&nbsp;Database: {$dbConfig['database']}<br>";
    echo "&nbsp;&nbsp;Username: {$dbConfig['username']}<br>";
    echo "&nbsp;&nbsp;Password: " . (empty($dbConfig['password']) ? 'empty' : 'set') . "<br>";
    
    // Try direct PDO connection
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        echo "<span class='success'>✓ Direct PDO connection successful</span><br>";
        
        // Test tables
        $tables = ['pets', 'visits', 'documents', 'owners'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<span class='success'>✓ Table '$table' exists ({$result['count']} records)</span><br>";
                
                if ($table === 'pets') {
                    // Check for our test pet
                    $stmt = $pdo->prepare("SELECT * FROM pets WHERE unique_id = ? LIMIT 1");
                    $stmt->execute(['251097']);
                    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($pet) {
                        echo "<span class='success'>&nbsp;&nbsp;✓ Test pet 251097 found: {$pet['name']}</span><br>";
                    } else {
                        echo "<span class='error'>&nbsp;&nbsp;✗ Test pet 251097 not found</span><br>";
                    }
                }
            } catch (Exception $e) {
                echo "<span class='error'>✗ Table '$table': " . $e->getMessage() . "</span><br>";
            }
        }
        
    } catch (Exception $e) {
        echo "<span class='error'>✗ Database connection failed: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>✗ .env file not found</span><br>";
}

echo "</div>";

// ================================================
// 3. FILE STRUCTURE CHECK
// ================================================
echo "<div class='section'>";
echo "<h2>3. File Structure Check</h2>";

$criticalFiles = [
    'app/Http/Controllers/Api/AndroidController.php',
    'app/Models/Pet.php',
    'app/Models/Visit.php', 
    'app/Models/Document.php',
    'routes/api.php'
];

foreach ($criticalFiles as $file) {
    $path = $laravelRoot . '/' . $file;
    if (file_exists($path)) {
        echo "<span class='success'>✓ $file exists</span><br>";
        
        if ($file === 'routes/api.php') {
            $content = file_get_contents($path);
            if (strpos($content, 'android') !== false) {
                echo "<span class='success'>&nbsp;&nbsp;✓ Android routes found in api.php</span><br>";
            } else {
                echo "<span class='error'>&nbsp;&nbsp;✗ No android routes in api.php</span><br>";
            }
        }
        
        if ($file === 'app/Http/Controllers/Api/AndroidController.php') {
            $content = file_get_contents($path);
            $methods = ['openVisit', 'uploadFile', 'getTodayVisit'];
            foreach ($methods as $method) {
                if (strpos($content, "function $method") !== false) {
                    echo "<span class='success'>&nbsp;&nbsp;✓ Method $method found</span><br>";
                } else {
                    echo "<span class='error'>&nbsp;&nbsp;✗ Method $method missing</span><br>";
                }
            }
        }
    } else {
        echo "<span class='error'>✗ $file missing</span><br>";
    }
}

echo "</div>";

// ================================================
// 4. DIRECT API TESTS (cURL)
// ================================================
echo "<div class='section'>";
echo "<h2>4. Direct API Tests</h2>";

$baseUrl = 'https://app.vetrx.in';
$tests = [
    [
        'name' => 'Open Visit',
        'cmd' => "curl -s -w '|HTTP:%{http_code}|' -X POST -H 'Content-Type: application/json' -d '{\"uid\":\"251097\"}' '$baseUrl/api/android/visit/open'"
    ],
    [
        'name' => 'Today Visit',  
        'cmd' => "curl -s -w '|HTTP:%{http_code}|' '$baseUrl/api/android/visit/today?uid=251097'"
    ],
    [
        'name' => 'Laravel Health',
        'cmd' => "curl -s -w '|HTTP:%{http_code}|' '$baseUrl/'"
    ]
];

foreach ($tests as $test) {
    echo "<strong>{$test['name']}:</strong><br>";
    echo "&nbsp;&nbsp;Command: <code>" . htmlspecialchars($test['cmd']) . "</code><br>";
    
    $output = shell_exec($test['cmd'] . ' 2>&1');
    
    // Extract HTTP code
    if (preg_match('/\|HTTP:(\d+)\|/', $output, $matches)) {
        $httpCode = $matches[1];
        $response = str_replace($matches[0], '', $output);
        echo "&nbsp;&nbsp;HTTP Code: <strong>$httpCode</strong><br>";
        echo "&nbsp;&nbsp;Response: <pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        
        if ($httpCode == '200' || $httpCode == '201') {
            echo "<span class='success'>&nbsp;&nbsp;✓ Success</span><br>";
        } elseif ($httpCode[0] == '3') {
            echo "<span class='warning'>&nbsp;&nbsp;⚠ Redirect</span><br>";
        } else {
            echo "<span class='error'>&nbsp;&nbsp;✗ Failed</span><br>";
        }
    } else {
        echo "&nbsp;&nbsp;Full output: <pre>" . htmlspecialchars($output) . "</pre>";
    }
    echo "<br>";
}

echo "</div>";

// ================================================
// 5. ROUTES DISCOVERY
// ================================================
echo "<div class='section'>";
echo "<h2>5. Route Discovery</h2>";

echo "<strong>Attempting to run: php artisan route:list</strong><br>";
$routeCmd = "cd " . escapeshellarg($laravelRoot) . " && php artisan route:list --columns=method,uri,name 2>&1";
$routeOutput = shell_exec($routeCmd);

if ($routeOutput) {
    echo "<pre>" . htmlspecialchars($routeOutput) . "</pre>";
    
    if (strpos($routeOutput, 'android') !== false) {
        echo "<span class='success'>✓ Android routes found in artisan output</span><br>";
    } else {
        echo "<span class='warning'>⚠ No android routes in artisan output</span><br>";
    }
} else {
    echo "<span class='error'>✗ Could not run artisan route:list</span><br>";
}

echo "</div>";

// ================================================
// 6. WEB SERVER CHECK
// ================================================
echo "<div class='section'>";
echo "<h2>6. Web Server Check</h2>";

echo "<strong>Server Variables:</strong><br>";
echo "&nbsp;&nbsp;HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "<br>";
echo "&nbsp;&nbsp;REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "<br>";
echo "&nbsp;&nbsp;DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "<br>";
echo "&nbsp;&nbsp;SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'not set') . "<br>";

// Check .htaccess
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo "<br><strong>.htaccess file found:</strong><br>";
    $htaccess = file_get_contents($htaccessPath);
    echo "<pre>" . htmlspecialchars(substr($htaccess, 0, 1000)) . "</pre>";
} else {
    echo "<br><span class='warning'>⚠ No .htaccess file in public directory</span><br>";
}

echo "</div>";

echo "<hr><small>Simple debug completed at " . date('Y-m-d H:i:s') . "</small>";
?>