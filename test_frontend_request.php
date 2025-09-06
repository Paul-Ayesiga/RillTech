<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Frontend Request Simulation ===\n\n";

// Test 1: Simulate exact frontend request to /api/chat
echo "1. Testing /api/chat endpoint (what frontend calls):\n";

try {
    // Create request exactly like frontend
    $request = \Illuminate\Http\Request::create('/api/chat', 'POST', [
        'message' => 'what features do you offer',
        'session_id' => 'frontend_sim_' . time(),
        'context' => [
            'page' => '/',
            'timestamp' => date('c'),
            'widget' => true
        ]
    ]);
    
    // Add headers like frontend
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request->headers->set('Accept', 'application/json');
    
    // Set the request for the application
    app()->instance('request', $request);
    
    $controller = new \App\Http\Controllers\ChatController();
    $response = $controller->sendMessage($request);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    
    echo "   Success: " . ($data['success'] ?? false ? 'YES' : 'NO') . "\n";
    echo "   Is Fallback: " . ($data['is_fallback'] ?? false ? 'YES ❌' : 'NO ✅') . "\n";
    echo "   AI Provider: " . ($data['ai_provider'] ?? 'unknown') . "\n";
    
    if ($data['is_fallback'] ?? false) {
        echo "   Fallback Reason: " . ($data['fallback_reason'] ?? 'unknown') . "\n";
        echo "\n❌ BACKEND IS RETURNING FALLBACK!\n";
    } else {
        echo "\n✅ Backend working correctly\n";
    }
    
    echo "   Response preview: " . substr($data['response'] ?? 'No response', 0, 100) . "...\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   This might be why frontend gets fallback responses!\n";
}

echo "\n2. Testing with different session IDs:\n";

// Test multiple session IDs to see if there's a session-specific issue
$sessionIds = [
    'test_session_1',
    'widget_session_' . time(),
    'frontend_' . uniqid(),
    null // Test without session ID
];

foreach ($sessionIds as $sessionId) {
    echo "\n   Testing session: " . ($sessionId ?: 'null') . "\n";
    
    try {
        $request = \Illuminate\Http\Request::create('/api/chat', 'POST', [
            'message' => 'hello',
            'session_id' => $sessionId
        ]);
        
        app()->instance('request', $request);
        
        $controller = new \App\Http\Controllers\ChatController();
        $response = $controller->sendMessage($request);
        $data = json_decode($response->getContent(), true);
        
        $isFallback = $data['is_fallback'] ?? false;
        echo "   Result: " . ($isFallback ? 'FALLBACK ❌' : 'LIVE AI ✅') . "\n";
        
    } catch (Exception $e) {
        echo "   Error: " . $e->getMessage() . "\n";
    }
}

echo "\n3. Testing CSRF and Headers:\n";

// Test if CSRF or headers are causing issues
try {
    $request = \Illuminate\Http\Request::create('/api/chat', 'POST');
    $request->merge([
        'message' => 'test message',
        'session_id' => 'csrf_test_' . time()
    ]);
    
    // Test without CSRF token first
    echo "   Without CSRF token:\n";
    app()->instance('request', $request);
    
    $controller = new \App\Http\Controllers\ChatController();
    $response = $controller->sendMessage($request);
    $data = json_decode($response->getContent(), true);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Success: " . ($data['success'] ?? false ? 'YES' : 'NO') . "\n";
    echo "   Is Fallback: " . ($data['is_fallback'] ?? false ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "   CSRF Error: " . $e->getMessage() . "\n";
    echo "   This might be the issue!\n";
}

echo "\n4. Checking Route Registration:\n";

// Check if the route is properly registered
$routes = app('router')->getRoutes();
$chatRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    if (str_contains($uri, 'chat')) {
        $chatRoutes[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $uri,
            'action' => $route->getActionName()
        ];
    }
}

echo "   Registered chat routes:\n";
foreach ($chatRoutes as $route) {
    echo "   - {$route['method']} {$route['uri']} → {$route['action']}\n";
}

echo "\n5. Testing Error Scenarios:\n";

// Test what happens with invalid input
$errorTests = [
    'empty_message' => ['message' => '', 'session_id' => 'test'],
    'no_message' => ['session_id' => 'test'],
    'invalid_json' => ['message' => 'test', 'invalid' => ['deeply' => ['nested' => 'data']]]
];

foreach ($errorTests as $testName => $data) {
    echo "\n   Testing $testName:\n";
    
    try {
        $request = \Illuminate\Http\Request::create('/api/chat', 'POST', $data);
        app()->instance('request', $request);
        
        $controller = new \App\Http\Controllers\ChatController();
        $response = $controller->sendMessage($request);
        $responseData = json_decode($response->getContent(), true);
        
        echo "   Status: " . $response->getStatusCode() . "\n";
        echo "   Success: " . ($responseData['success'] ?? false ? 'YES' : 'NO') . "\n";
        
        if (!($responseData['success'] ?? false)) {
            echo "   Error: " . ($responseData['message'] ?? 'Unknown') . "\n";
        }
        
    } catch (Exception $e) {
        echo "   Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n=== DIAGNOSIS ===\n";
echo "Look for patterns above:\n";
echo "- If ALL tests show 'FALLBACK ❌', the issue is in the backend logic\n";
echo "- If SOME tests work, there's a specific condition causing fallbacks\n";
echo "- If there are CSRF errors, that's likely the frontend issue\n";
echo "- If routes are missing, that's a routing issue\n";

echo "\n🔧 Next steps based on results above...\n";
