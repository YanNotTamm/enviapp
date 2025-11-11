<?php
/**
 * Clear PHP OpCache and Config Cache
 * Access: https://dev.envirometrolestari.com/clear-cache.php
 */

header('Content-Type: text/html; charset=utf-8');

$results = [];

// Clear OpCache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $results[] = '✅ OpCache cleared successfully';
    } else {
        $results[] = '❌ Failed to clear OpCache';
    }
} else {
    $results[] = '⚠️ OpCache not available';
}

// Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        $results[] = '✅ APCu cache cleared';
    } else {
        $results[] = '❌ Failed to clear APCu cache';
    }
} else {
    $results[] = '⚠️ APCu not available';
}

// Clear CodeIgniter cache files
$cacheDir = __DIR__ . '/backend/writable/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            if (unlink($file)) {
                $count++;
            }
        }
    }
    $results[] = "✅ Cleared $count cache files";
} else {
    $results[] = '⚠️ Cache directory not found';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cache Cleared</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Cache Cleared</h1>
        
        <?php foreach ($results as $result): ?>
            <?php
            $class = 'result ';
            if (strpos($result, '✅') !== false) {
                $class .= 'success';
            } elseif (strpos($result, '⚠️') !== false) {
                $class .= 'warning';
            } elseif (strpos($result, '❌') !== false) {
                $class .= 'error';
            }
            ?>
            <div class="<?= $class ?>"><?= htmlspecialchars($result) ?></div>
        <?php endforeach; ?>
        
        <div class="info">
            <strong>ℹ️ Next Steps:</strong><br>
            1. Cache has been cleared<br>
            2. Test email configuration again<br>
            3. Email should now use correct SMTP server
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="/api/test/email" class="btn">🔍 Test Email Config</a>
            <a href="/api/test/send-email?to=your-email@example.com" class="btn">📧 Send Test Email</a>
            <a href="/" class="btn">🏠 Home</a>
        </div>
        
        <hr style="margin: 30px 0;">
        
        <p style="text-align: center; color: #7f8c8d; font-size: 12px;">
            Cache cleared at: <?= date('Y-m-d H:i:s') ?>
        </p>
    </div>
</body>
</html>
 