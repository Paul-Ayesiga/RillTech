<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
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
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .footer {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        .greeting {
            margin-bottom: 20px;
        }
        .message {
            white-space: pre-wrap;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
    </div>

    <div class="content">
        <div class="greeting">
            <p>Hello {{ $user->name }},</p>
        </div>

        <div class="message">
            {{ $emailMessage }}
        </div>

        <p>Best regards,<br>
        {{ config('app.name') }} Team</p>
    </div>

    <div class="footer">
        <p>This email was sent from {{ config('app.name') }}.</p>
        <p>If you have any questions, please contact our support team.</p>
    </div>
</body>
</html>
