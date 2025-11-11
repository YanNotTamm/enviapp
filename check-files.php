<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Files on Server</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 5px; }
        h2 { color: #dcdcaa; }
    </style>
</head>
<body>
    <h1>🔍 Server File Check</h1>
    
    <h2>1. Frontend Index.html Content:</h2>
    <?php
    $indexPath = __DIR__ . '/frontend/index.html';
    if (file_exists($indexPath)) {
        $content = file_get_contents($indexPath);
        // Extract JS file reference
        preg_match('/src="([^"]*main\.[^"]*\.js[^"]*)"/', $content, $matches);
        if ($matches) {
            echo '<div class="success">✅ Found JS reference: ' . htmlspecialchars($matches[1]) . '</div>';
        } else {
            echo '<div class="error">❌ No JS file reference found</div>';
        }
    } else {
        echo '<div class="error">❌ index.html not found</div>';
    }
    ?>
    
    <h2>2. JS Files in static/js/:</h2>
    <?php
    $jsDir = __DIR__ . '/frontend/static/js/';
    if (is_dir($jsDir)) {
        $files = scandir($jsDir);
        $mainFiles = array_filter($files, function($f) {
            return strpos($f, 'main.') === 0 && strpos($f, '.js') !== false && strpos($f, '.map') === false;
        });
        
        if ($mainFiles) {
            foreach ($mainFiles as $file) {
                $size = filesize($jsDir . $file);
                $sizeKB = round($size / 1024, 2);
                echo '<div class="success">✅ ' . htmlspecialchars($file) . ' (' . $sizeKB . ' KB)</div>';
            }
        } else {
            echo '<div class="error">❌ No main.*.js files found</div>';
        }
        
        echo '<h3>All JS files:</h3><pre>';
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo htmlspecialchars($file) . "\n";
            }
        }
        echo '</pre>';
    } else {
        echo '<div class="error">❌ JS directory not found</div>';
    }
    ?>
    
    <h2>3. File Timestamps:</h2>
    <?php
    if (file_exists($indexPath)) {
        echo '<div class="info">index.html modified: ' . date('Y-m-d H:i:s', filemtime($indexPath)) . '</div>';
    }
    
    if (is_dir($jsDir)) {
        $mainFiles = glob($jsDir . 'main.*.js');
        foreach ($mainFiles as $file) {
            echo '<div class="info">' . basename($file) . ' modified: ' . date('Y-m-d H:i:s', filemtime($file)) . '</div>';
        }
    }
    ?>
    
    <h2>4. Server Info:</h2>
    <div class="info">
        Server Time: <?php echo date('Y-m-d H:i:s'); ?><br>
        PHP Version: <?php echo PHP_VERSION; ?><br>
        Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
        Current Dir: <?php echo __DIR__; ?>
    </div>
    
    <h2>5. Test Direct Access:</h2>
    <div>
        <a href="/frontend/static/js/main.7ab369e1.js" target="_blank" style="color: #4ec9b0;">
            Test: main.7ab369e1.js (NEW)
        </a>
    </div>
    <div>
        <a href="/frontend/static/js/main.614a13f1.js" target="_blank" style="color: #f48771;">
            Test: main.614a13f1.js (OLD - should 404)
        </a>
    </div>
    
    <hr>
    <p><a href="/frontend/index.html?v=<?php echo time(); ?>" style="color: #569cd6;">🔄 Go to Frontend (with cache bust)</a></p>
</body>
</html>
 