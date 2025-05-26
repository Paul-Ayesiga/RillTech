<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CSRF Token Debug ===\n\n";

// Test 1: Check CSRF token generation
echo "1. Testing CSRF token generation:\n";

try {
    $token = csrf_token();
    echo "   ✅ CSRF token generated: " . substr($token, 0, 20) . "...\n";
    echo "   Token length: " . strlen($token) . " chars\n";
} catch (Exception $e) {
    echo "   ❌ Error generating CSRF token: " . $e->getMessage() . "\n";
}

// Test 2: Check session configuration
echo "\n2. Session configuration:\n";
echo "   Session driver: " . config('session.driver') . "\n";
echo "   Session cookie: " . config('session.cookie') . "\n";
echo "   Session domain: " . (config('session.domain') ?: 'null') . "\n";
echo "   Session secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "   Session same_site: " . config('session.same_site') . "\n";

// Test 3: Simulate a request with CSRF token
echo "\n3. Testing CSRF verification:\n";

try {
    // Create a request with proper CSRF token
    $token = csrf_token();
    
    $request = \Illuminate\Http\Request::create('/api/chat', 'POST', [
        '_token' => $token
    ]);
    
    // Add the token as header too (like Axios would)
    $request->headers->set('X-CSRF-TOKEN', $token);
    $request->headers->set('X-XSRF-TOKEN', $token);
    
    // Test CSRF verification
    $middleware = new \App\Http\Middleware\VerifyCsrfToken();
    
    echo "   Token in request: " . substr($request->input('_token'), 0, 20) . "...\n";
    echo "   X-CSRF-TOKEN header: " . substr($request->header('X-CSRF-TOKEN'), 0, 20) . "...\n";
    echo "   X-XSRF-TOKEN header: " . substr($request->header('X-XSRF-TOKEN'), 0, 20) . "...\n";
    
} catch (Exception $e) {
    echo "   ❌ Error in CSRF test: " . $e->getMessage() . "\n";
}

// Test 4: Check if XSRF-TOKEN cookie would be set
echo "\n4. Testing XSRF-TOKEN cookie setup:\n";

try {
    // Simulate what Laravel does for XSRF-TOKEN
    $encrypter = app('encrypter');
    $token = csrf_token();
    
    // This is how Laravel creates the XSRF-TOKEN cookie value
    $xsrfToken = $encrypter->encrypt($token, false);
    
    echo "   ✅ XSRF-TOKEN would be: " . substr($xsrfToken, 0, 30) . "...\n";
    echo "   XSRF token length: " . strlen($xsrfToken) . " chars\n";
    
} catch (Exception $e) {
    echo "   ❌ Error creating XSRF token: " . $e->getMessage() . "\n";
}

// Test 5: Check middleware configuration
echo "\n5. Middleware configuration:\n";

$middlewareFile = app_path('Http/Middleware/VerifyCsrfToken.php');
if (file_exists($middlewareFile)) {
    echo "   ✅ VerifyCsrfToken middleware exists\n";
    
    $content = file_get_contents($middlewareFile);
    if (str_contains($content, 'except')) {
        echo "   ✅ Middleware has except array\n";
        
        // Extract the except array
        preg_match('/protected \$except = \[(.*?)\];/s', $content, $matches);
        if ($matches) {
            echo "   Excluded routes: " . trim($matches[1]) . "\n";
        }
    }
} else {
    echo "   ❌ VerifyCsrfToken middleware not found\n";
}

echo "\n=== DIAGNOSIS ===\n";
echo "Based on the Inertia.js documentation:\n";
echo "1. Laravel should automatically set XSRF-TOKEN cookie\n";
echo "2. Axios should automatically read this cookie\n";
echo "3. Axios should automatically send X-XSRF-TOKEN header\n";
echo "4. Laravel should verify the X-XSRF-TOKEN header\n";

echo "\n🔧 If CSRF tokens are working above, the issue might be:\n";
echo "- XSRF-TOKEN cookie not being set by Laravel\n";
echo "- Axios not reading the cookie properly\n";
echo "- Domain/path mismatch for cookies\n";

echo "\n🎯 Next step: Check browser dev tools for XSRF-TOKEN cookie\n";
