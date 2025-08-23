<?php
// debug_android_api.php
// Place this in your Laravel project root: /home/dpharmai54/app.vetrx.in/debug_android_api.php

echo "<h1>Android API Debug Report</h1>";
echo "<style>
body { font-family: monospace; margin: 20px; }
.section { border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #f9f9f9; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style>";

$errors = [];
$warnings = [];
$info = [];

// ================================================
// 1. ENVIRONMENT CHECK
// ================================================
echo "<div class='section'>";
echo "<h2>1. Environment Check</h2>";

echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Laravel Path:</strong> " . __DIR__ . "<br>";
echo "<strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "<br>";

// Check if we're in Laravel root
if (file_exists(__DIR__ . '/artisan')) {
    echo "<span class='success'>✓ Laravel project detected</span><br>";
    $info[] = "Laravel project found";
} else {
    echo "<span class='error'>✗ Not in Laravel root directory</span><br>";
    $errors[] = "Not in Laravel project root";
}

// Check composer autoload
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<span class='success'>✓ Composer autoload exists</span><br>";
    require_once __DIR__ . '/vendor/autoload.php';
    $info[] = "Composer autoload loaded";
} else {
    echo "<span class='error'>✗ Composer autoload missing</span><br>";
    $errors[] = "Composer autoload not found";
}

echo "</div>";

// ================================================
// 2. LARAVEL BOOTSTRAP
// ================================================
echo "<div class='section'>";
echo "<h2>2. Laravel Bootstrap</h2>";

try {
    if (file_exists(__DIR__ . '/bootstrap/app.php')) {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        echo "<span class='success'>✓ Laravel app bootstrapped</span><br>";
        $info[] = "Laravel app loaded successfully";
    } else {
        echo "<span class='error'>✗ Laravel bootstrap file missing</span><br>";
        $errors[] = "Cannot bootstrap Laravel";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Laravel bootstrap failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "Laravel bootstrap error: " . $e->getMessage();
}

echo "</div>";

// ================================================
// 3. DATABASE CONNECTION
// ================================================
echo "<div class='section'>";
echo "<h2>3. Database Connection</h2>";

try {
    if (isset($app)) {
        $db = $app->make('db');
        $connection = $db->connection();
        $pdo = $connection->getPdo();
        echo "<span class='success'>✓ Database connected</span><br>";
        echo "<strong>Driver:</strong> " . $connection->getDriverName() . "<br>";
        $info[] = "Database connection successful";
        
        // Test tables exist
        $tables = ['pets', 'visits', 'documents', 'owners'];
        foreach ($tables as $table) {
            try {
                $count = $connection->table($table)->count();
                echo "<span class='success'>✓ Table '$table' exists ($count records)</span><br>";
            } catch (Exception $e) {
                echo "<span class='error'>✗ Table '$table' missing or error</span><br>";
                $errors[] = "Table $table issue: " . $e->getMessage();
            }
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Database connection failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "Database error: " . $e->getMessage();
}

echo "</div>";

// ================================================
// 4. ROUTES CHECK
// ================================================
echo "<div class='section'>";
echo "<h2>4. Routes Check</h2>";

try {
    if (isset($app)) {
        $router = $app->make('router');
        $routes = $router->getRoutes();
        
        $androidRoutes = [];
        foreach ($routes as $route) {
            if (strpos($route->uri(), 'api/android') !== false) {
                $androidRoutes[] = [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $route->getActionName()
                ];
            }
        }
        
        if (count($androidRoutes) > 0) {
            echo "<span class='success'>✓ Found " . count($androidRoutes) . " Android API routes</span><br>";
            echo "<pre>";
            foreach ($androidRoutes as $route) {
                echo "{$route['method']} /{$route['uri']} -> {$route['action']}\n";
            }
            echo "</pre>";
        } else {
            echo "<span class='error'>✗ No Android API routes found</span><br>";
            $errors[] = "Android API routes not registered";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Routes check failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "Routes error: " . $e->getMessage();
}

echo "</div>";

// ================================================
// 5. MODELS CHECK
// ================================================
echo "<div class='section'>";
echo "<h2>5. Models Check</h2>";

$modelChecks = [
    'App\Models\Pet' => 'Pet',
    'App\Models\Visit' => 'Visit', 
    'App\Models\Document' => 'Document',
    'App\Models\Owner' => 'Owner'
];

foreach ($modelChecks as $class => $name) {
    try {
        if (class_exists($class)) {
            echo "<span class='success'>✓ Model $name exists</span><br>";
            
            // Check if we can create an instance
            $model = new $class();
            $fillable = $model->getFillable();
            echo "&nbsp;&nbsp;Fillable fields: " . implode(', ', $fillable) . "<br>";
        } else {
            echo "<span class='error'>✗ Model $name missing</span><br>";
            $errors[] = "Model $name not found";
        }
    } catch (Exception $e) {
        echo "<span class='error'>✗ Model $name error: " . $e->getMessage() . "</span><br>";
        $errors[] = "Model $name error: " . $e->getMessage();
    }
}

echo "</div>";

// ================================================
// 6. CONTROLLER CHECK
// ================================================
echo "<div class='section'>";
echo "<h2>6. Controller Check</h2>";

try {
    $controllerClass = 'App\Http\Controllers\Api\AndroidController';
    if (class_exists($controllerClass)) {
        echo "<span class='success'>✓ AndroidController exists</span><br>";
        
        $controller = new $controllerClass();
        $methods = get_class_methods($controller);
        $publicMethods = array_filter($methods, function($method) {
            $reflection = new ReflectionMethod($controller, $method);
            return $reflection->isPublic() && !$reflection->isConstructor();
        });
        
        echo "&nbsp;&nbsp;Public methods: " . implode(', ', $publicMethods) . "<br>";
        
        $requiredMethods = ['openVisit', 'uploadFile', 'getTodayVisit'];
        foreach ($requiredMethods as $method) {
            if (in_array($method, $publicMethods)) {
                echo "<span class='success'>&nbsp;&nbsp;✓ Method $method exists</span><br>";
            } else {
                echo "<span class='error'>&nbsp;&nbsp;✗ Method $method missing</span><br>";
                $errors[] = "Controller method $method missing";
            }
        }
    } else {
        echo "<span class='error'>✗ AndroidController missing</span><br>";
        $errors[] = "AndroidController class not found";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Controller check failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "Controller error: " . $e->getMessage();
}

echo "</div>";

// ================================================
// 7. TEST DATA CHECK
// ================================================
echo "<div class='section'>";
echo "<h2>7. Test Data Check</h2>";

try {
    if (isset($connection)) {
        // Check for our test pet
        $pet = $connection->table('pets')->where('unique_id', '251097')->first();
        if ($pet) {
            echo "<span class='success'>✓ Test pet 251097 exists</span><br>";
            echo "&nbsp;&nbsp;Name: {$pet->name}<br>";
            echo "&nbsp;&nbsp;Owner ID: {$pet->owner_id}<br>";
            
            // Check owner
            $owner = $connection->table('owners')->where('id', $pet->owner_id)->first();
            if ($owner) {
                echo "<span class='success'>&nbsp;&nbsp;✓ Owner exists: {$owner->name}</span><br>";
            }
            
            // Check visits
            $visits = $connection->table('visits')->where('pet_id', $pet->id)->count();
            echo "&nbsp;&nbsp;Visits: $visits<br>";
            
            // Check documents
            $docs = $connection->table('documents')->where('pet_id', $pet->id)->count();
            echo "&nbsp;&nbsp;Documents: $docs<br>";
            
        } else {
            echo "<span class='error'>✗ Test pet 251097 not found</span><br>";
            $errors[] = "Test pet not found in database";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Test data check failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "Test data error: " . $e->getMessage();
}

echo "</div>";

// ================================================
// 8. API ENDPOINTS TEST
// ================================================
echo "<div class='section'>";
echo "<h2>8. API Endpoints Test (Internal)</h2>";

try {
    if (isset($app)) {
        // Test open visit endpoint
        echo "<strong>Testing /api/android/visit/open:</strong><br>";
        
        $request = Illuminate\Http\Request::create('/api/android/visit/open', 'POST', [
            'uid' => '251097'
        ]);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('Accept', 'application/json');
        
        try {
            $response = $kernel->handle($request);
            $content = $response->getContent();
            $status = $response->getStatusCode();
            
            echo "&nbsp;&nbsp;Status: $status<br>";
            echo "&nbsp;&nbsp;Response: <pre>" . htmlspecialchars($content) . "</pre>";
            
            if ($status == 200) {
                echo "<span class='success'>&nbsp;&nbsp;✓ Open visit endpoint working</span><br>";
            } else {
                echo "<span class='error'>&nbsp;&nbsp;✗ Open visit endpoint failed</span><br>";
                $errors[] = "Open visit endpoint returned status $status";
            }
        } catch (Exception $e) {
            echo "<span class='error'>&nbsp;&nbsp;✗ Open visit test failed: " . $e->getMessage() . "</span><br>";
            $errors[] = "Open visit test error: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ API test failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "API test error: " . $e->getMessage();
}

echo "</div>";

// ================================================
// 9. EXTERNAL API TEST
// ================================================
echo "<div class='section'>";
echo "<h2>9. External API Test (cURL)</h2>";

$baseUrl = 'https://app.vetrx.in';
$endpoints = [
    'GET /api/android/visit/today?uid=251097',
    'POST /api/android/visit/open'
];

foreach ($endpoints as $endpoint) {
    echo "<strong>Testing $endpoint:</strong><br>";
    
    if (strpos($endpoint, 'GET') === 0) {
        $url = $baseUrl . str_replace('GET ', '', $endpoint);
        $cmd = "curl -s -w 'HTTP_CODE:%{http_code}' '$url'";
    } else {
        $url = $baseUrl . str_replace('POST ', '', $endpoint);
        $cmd = "curl -s -w 'HTTP_CODE:%{http_code}' -X POST -H 'Content-Type: application/json' -d '{\"uid\":\"251097\"}' '$url'";
    }
    
    echo "&nbsp;&nbsp;Command: <code>$cmd</code><br>";
    
    $output = shell_exec($cmd . ' 2>&1');
    echo "&nbsp;&nbsp;Response: <pre>" . htmlspecialchars($output) . "</pre>";
}

echo "</div>";

// ================================================
// 10. SUMMARY
// ================================================
echo "<div class='section'>";
echo "<h2>10. Summary</h2>";

if (count($errors) == 0) {
    echo "<span class='success'><strong>✓ All checks passed! API should be working.</strong></span><br>";
} else {
    echo "<span class='error'><strong>✗ " . count($errors) . " errors found:</strong></span><br>";
    foreach ($errors as $error) {
        echo "<span class='error'>&nbsp;&nbsp;• $error</span><br>";
    }
}

if (count($warnings) > 0) {
    echo "<br><span class='warning'><strong>⚠ Warnings:</strong></span><br>";
    foreach ($warnings as $warning) {
        echo "<span class='warning'>&nbsp;&nbsp;• $warning</span><br>";
    }
}

echo "<br><strong>Debug completed at:</strong> " . date('Y-m-d H:i:s');
echo "</div>";

echo "<hr><small>Debug script generated by Claude AI</small>";
?>