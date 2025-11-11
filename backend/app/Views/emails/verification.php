<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
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
            background-color: #4CAF50;
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
            background-color: #4CAF50;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Envindo!</h1>
        </div>
        <div class="content">
            <h2>Verify Your Email Address</h2>
            <p>Hello <?= esc($username) ?>,</p>
            <p>Thank you for registering with Envindo Waste Management System. To complete your registration, please verify your email address by clicking the button below:</p>
            
            <div style="text-align: center;">
                <a href="<?= esc($verificationUrl) ?>" class="button">Verify Email Address</a>
            </div>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class="token"><?= esc($verificationUrl) ?></div>
            
            <p>This verification link will expire in 24 hours.</p>
            
            <p>If you did not create an account with Envindo, please ignore this email.</p>
            
            <p>Best regards,<br>The Envindo Team</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Envindo Waste Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
