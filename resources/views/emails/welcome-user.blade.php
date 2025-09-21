<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to Activity Tracking System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .credentials {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to Activity Tracking System</h1>
    </div>

    <div class="content">
        <p>Hello {{ $user->name }},</p>

        <p>Your account has been created in the Activity Tracking System. You can now access the system using the credentials below:</p>

        <div class="credentials">
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Temporary Password:</strong> {{ $temporaryPassword }}
        </div>

        <p><strong>Important:</strong> For security reasons, you will be required to change your password upon first login.</p>

        <a href="{{ $loginUrl }}" class="button">Login to Your Account</a>

        <p>If you have any questions or need assistance, please contact your system administrator.</p>

        <p>Best regards,<br>Activity Tracking System Team</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>