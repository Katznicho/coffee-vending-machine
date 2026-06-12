#!/usr/bin/env php
<?php

/**
 * Local API Key Generation & Testing Script
 * This script generates API keys for a specific branch and tests endpoints
 */

// Get the Laravel app
require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

echo "\n╔════════════════════════════════════════╗\n";
echo "║  Local API Key Generator & Tester      ║\n";
echo "╚════════════════════════════════════════╝\n\n";

// Get the first branch (or create one if none exists)
$branch = Branch::first();

if (!$branch) {
    echo "❌ No branches found in database. Please create a branch first.\n";
    exit(1);
}

echo "📍 Branch: {$branch->name} (ID: {$branch->id})\n\n";

// Generate new API credentials
$apiUsername = 'api_' . strtolower(Str::random(8));
$apiPassword = Str::random(10);
$hashedPassword = Hash::make($apiPassword);

// Update the branch with new credentials
$branch->update([
    'api_username' => $apiUsername,
    'api_password' => $hashedPassword,
]);

echo "✅ API Credentials Generated:\n";
echo "   Username: {$apiUsername}\n";
echo "   Password: {$apiPassword}\n";
echo "   Hashed: {$hashedPassword}\n\n";

// Create Base64 header
$credentials = "{$apiUsername}:{$apiPassword}";
$base64 = base64_encode($credentials);

echo "🔐 Base64 Auth Header:\n";
echo "   Authorization: Basic {$base64}\n";
echo "   (Copy this for testing)\n\n";

// Generate the test commands
echo "🧪 Test Commands:\n\n";

$testEndpoints = [
    'Health Check' => ['POST', '/api/health', '{}'],
    'Get Context' => ['GET', '/api/context', ''],
    'Get Products' => ['GET', '/api/products', ''],
    'Get Tanks' => ['GET', '/api/tanks', ''],
    'Get Attendants' => ['GET', '/api/attendants', ''],
    'Get Current Shift' => ['GET', '/api/shifts/current', ''],
    'Get Pump Status' => ['POST', '/api/pump-get-status', '{"Pump": 1}'],
    'Get Pump Prices' => ['POST', '/api/pump-get-prices', '{"Pump": 1}'],
];

foreach ($testEndpoints as $name => $endpoint) {
    [$method, $path, $data] = $endpoint;
    $url = "http://127.0.0.1:8000{$path}";
    
    if ($method === 'POST' && $data) {
        echo "# {$name}\n";
        echo "curl -X {$method} \"{$url}\" \\\n";
        echo "  -H \"Authorization: Basic {$base64}\" \\\n";
        echo "  -H \"Content-Type: application/json\" \\\n";
        echo "  -d '{$data}'\n\n";
    } else {
        echo "# {$name}\n";
        echo "curl -X {$method} \"{$url}\" \\\n";
        echo "  -H \"Authorization: Basic {$base64}\" \\\n";
        echo "  -H \"Content-Type: application/json\"\n\n";
    }
}

echo "\n✅ Commands generated. Copy and paste them in your terminal.\n";
echo "   Or save to a file and run: bash test.sh\n\n";
