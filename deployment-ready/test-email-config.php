<?php
/**
 * Standalone Email Configuration Test
 * Access: https://dev.envirometrolestari.com/test-email-config.php
 */

// Email configuration
$config = [
    'host' => 'mail.envirometrolestari.com',
    'port' => 465,
    'username' => 'noreply@envirometrolestari.com',
    'password' => 'Notreppenvi25',
    'secure' => 'ssl',
    'from_email' => 'noreply@envirometrolestari.com',
    'from_name' => 'EnviliApps - Envirometro Lestari Indonesia'
];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
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
        .config-item {
            padding: 10px;
            margin: 10px 0;
            background: #ecf0f1;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }
        .config-label {
            font-weight: bold;
            color: #34495e;
        }
        .config-value {
            color: #2c3e50;
            font-family: 'Courier New', monospace;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .test-form {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        button:hover {
            background: #2980b9;
        }
        .password-masked {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration Test</h1>
        
        <div class="info">
            <strong>ℹ️ Info:</strong> This page shows your current email configuration and allows you to test email sending.
        </div>
        
        <h2>Current Configuration</h2>
        
        <div class="config-item">
            <span class="config-label">Host:</span>
            <span class="config-value"><?= htmlspecialchars($config['host']) ?></span>
        </div>
        
        <div class="config-item">
            <span class="config-label">Port:</span>
            <span class="config-value"><?= htmlspecialchars($config['port']) ?></span>
        </div>
        
        <div class="config-item">
            <span class="config-label">Security:</span>
            <span class="config-value"><?= strtoupper(htmlspecialchars($config['secure'])) ?></span>
        </div>
        
        <div class="config-item">
            <span class="config-label">Username:</span>
            <span class="config-value"><?= htmlspecialchars($config['username']) ?></span>
        </div>
        
        <div class="config-item">
            <span class="config-label">Password:</span>
            <span class="config-value password-masked">
                <?= str_repeat('*', strlen($config['password'])) ?> 
                (<?= strlen($config['password']) ?> characters)
            </span>
        </div>
        
        <div class="config-item">
            <span class="config-label">From Email:</span>
            <span class="config-value"><?= htmlspecialchars($config['from_email']) ?></span>
        </div>
        
        <div class="config-item">
            <span class="config-label">From Name:</span>
            <span class="config-value"><?= htmlspecialchars($config['from_name']) ?></span>
        </div>
        
        <?php
        // Test connection
        if (isset($_GET['test']) && $_GET['test'] === 'connection') {
            echo '<h2>Connection Test</h2>';
            
            $smtp = @fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
            
            if ($smtp) {
                echo '<div class="success">';
                echo '<strong>✅ Success!</strong><br>';
                echo 'Successfully connected to ' . htmlspecialchars($config['host']) . ':' . htmlspecialchars($config['port']);
                echo '</div>';
                fclose($smtp);
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Connection Failed!</strong><br>';
                echo 'Error: ' . htmlspecialchars($errstr) . ' (Code: ' . htmlspecialchars($errno) . ')';
                echo '</div>';
            }
        }
        
        // Send test email
        if (isset($_POST['send_test']) && !empty($_POST['test_email'])) {
            $to = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
            
            if ($to) {
                echo '<h2>Sending Test Email</h2>';
                
                // Use PHPMailer or mail() function
                $subject = 'Test Email from Envindo System';
                $message = '
                <html>
                <head><title>Test Email</title></head>
                <body>
                    <h1>Test Email</h1>
                    <p>This is a test email from Envindo Waste Management System.</p>
                    <p>If you received this email, your email configuration is working correctly!</p>
                    <p><strong>Configuration:</strong></p>
                    <ul>
                        <li>Host: ' . htmlspecialchars($config['host']) . '</li>
                        <li>Port: ' . htmlspecialchars($config['port']) . '</li>
                        <li>Security: ' . strtoupper(htmlspecialchars($config['secure'])) . '</li>
                    </ul>
                    <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
                </body>
                </html>
                ';
                
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=utf-8\r\n";
                $headers .= "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
                
                if (mail($to, $subject, $message, $headers)) {
                    echo '<div class="success">';
                    echo '<strong>✅ Email Sent!</strong><br>';
                    echo 'Test email has been sent to: ' . htmlspecialchars($to);
                    echo '<br><small>Note: Check spam folder if not received in inbox.</small>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<strong>❌ Failed to Send!</strong><br>';
                    echo 'Could not send email. Please check server mail configuration.';
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Invalid Email!</strong><br>';
                echo 'Please enter a valid email address.';
                echo '</div>';
            }
        }
        ?>
        
        <h2>Quick Tests</h2>
        
        <div style="margin: 20px 0;">
            <a href="?test=connection" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                🔌 Test Connection
            </a>
        </div>
        
        <div class="test-form">
            <h3>Send Test Email</h3>
            <form method="POST">
                <label for="test_email">Enter your email address:</label><br>
                <input type="email" id="test_email" name="test_email" placeholder="your-email@example.com" required>
                <button type="submit" name="send_test">📧 Send Test Email</button>
            </form>
        </div>
        
        <div class="info">
            <strong>💡 Tip:</strong> Use the CodeIgniter API endpoints for more advanced testing:
            <ul>
                <li><code>/api/test/email</code> - Test email configuration</li>
                <li><code>/api/test/send-email?to=your-email@example.com</code> - Send test email via API</li>
            </ul>
        </div>
        
        <hr style="margin: 30px 0;">
        
        <p style="text-align: center; color: #7f8c8d; font-size: 12px;">
            Envindo Email Configuration Test Tool<br>
            <?= date('Y-m-d H:i:s') ?>
        </p>
    </div>
</body>
</html>
 