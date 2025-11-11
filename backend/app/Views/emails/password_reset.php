<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f4f4f4;
            border-radius: 5px;
            padding: 30px;
        }
        .header {
            background-color: #2196F3;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        .token {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <h2>Reset Your Password</h2>
            <p>Hello <?= esc($username) ?>,</p>
            <p>We received a request to reset your password for your Envindo account. Click the button below to reset your password:</p>
            
            <div style="text-align: center;">
                <a href="<?= esc($resetUrl) ?>" class="button">Reset Password</a>
            </div>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class="token"><?= esc($resetUrl) ?></div>
            
            <div class="warning">
                <strong>Important:</strong> This password reset link will expire in 1 hour for security reasons.
            </div>
            
            <p>If you did not request a password reset, please ignore this email or contact support if you have concerns about your account security.</p>
            
            <p>Best regards,<br>The Envindo Team</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Envindo Waste Management System. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
