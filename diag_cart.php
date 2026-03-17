<?php
// Diagnostic page to verify which `cart.php` the server is using
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Diagnostic — vmilk-takeaway</h2>";
echo "<pre>";
echo "PHP version: " . PHP_VERSION . "\n";
$cartPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'cart.php');
if ($cartPath && file_exists($cartPath)) {
    echo "cart.php path: " . $cartPath . "\n";
    echo "cart.php last modified: " . date('c', filemtime($cartPath)) . "\n";
    echo "\n--- First 40 lines of cart.php ---\n";
    $lines = file($cartPath);
    foreach (array_slice($lines, 0, 40) as $i => $line) {
        printf("%4d: %s", $i + 1, htmlspecialchars($line));
    }
} else {
    echo "cart.php not found at expected location: " . __DIR__ . DIRECTORY_SEPARATOR . 'cart.php' . "\n";
}
echo "\nSession status: ";
switch (session_status()) {
    case PHP_SESSION_DISABLED: echo "disabled\n"; break;
    case PHP_SESSION_NONE: echo "none\n"; break;
    case PHP_SESSION_ACTIVE: echo "active\n"; break;
}

if (function_exists('opcache_get_status')) {
    $s = opcache_get_status(false);
    echo "OPcache enabled: " . (!empty($s['opcache_enabled']) ? 'YES' : 'NO') . "\n";
} else {
    echo "OPcache functions not available.\n";
}

// Try invalidating opcache for cart.php to ensure latest copy is used (if allowed)
if (function_exists('opcache_invalidate') && $cartPath) {
    @opcache_invalidate($cartPath, true);
    echo "Attempted to invalidate OPcache for cart.php\n";
} else {
    echo "Could not invalidate OPcache (function missing or path unknown).\n";
}

echo "</pre>";

?>