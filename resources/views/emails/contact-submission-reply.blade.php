<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply to Your Contact Submission</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .greeting {
            font-size: 18px;
            color: #374151;
            margin-bottom: 20px;
        }
        .original-message {
            background-color: #f9fafb;
            border-left: 4px solid #6b7280;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .original-message h4 {
            margin: 0 0 10px 0;
            color: #6b7280;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .reply-message {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            margin: 20px 0;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            margin-top: 30px;
            font-size: 14px;
            color: #6b7280;
        }
        .signature {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
        }
        .signature-name {
            font-weight: 600;
            color: #1f2937;
        }
        .signature-title {
            color: #6b7280;
            font-size: 14px;
        }
        .contact-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .contact-info h4 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 16px;
        }
        .contact-details {
            font-size: 14px;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-new { background-color: #dbeafe; color: #1e40af; }
        .status-in_progress { background-color: #fef3c7; color: #d97706; }
        .status-resolved { background-color: #d1fae5; color: #059669; }
        .status-closed { background-color: #f3f4f6; color: #6b7280; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <p style="margin: 0; color: #6b7280;">Thank you for contacting us</p>
        </div>

        <div class="greeting">
            Hello {{ $contactSubmission->name }},
        </div>

        <p>Thank you for reaching out to us. We have reviewed your message and wanted to provide you with a response.</p>

        <div class="original-message">
            <h4>Your Original Message</h4>
            <p><strong>Subject:</strong> {{ $contactSubmission->subject }}</p>
            <p><strong>Submitted:</strong> {{ $contactSubmission->created_at->format('F j, Y \a\t g:i A') }}</p>
            <p><strong>Message:</strong></p>
            <p>{{ $contactSubmission->message }}</p>
        </div>

        <div class="reply-message">
            <h3 style="margin-top: 0; color: #1f2937;">Our Response</h3>
            {!! nl2br(e($replyMessage)) !!}
        </div>

        <div class="contact-info">
            <h4>Need Further Assistance?</h4>
            <div class="contact-details">
                <p>If you have any additional questions or concerns, please don't hesitate to reach out to us:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Reply directly to this email</li>
                    <li>Visit our website contact form</li>
                    <li>Call our support team</li>
                </ul>
                <p>We typically respond within 24 hours during business days.</p>
            </div>
        </div>

        <div class="signature">
            <div class="signature-name">{{ $adminUser->name }}</div>
            <div class="signature-title">Customer Support Team</div>
            <div class="signature-title">{{ config('app.name') }}</div>
        </div>

        <div class="footer">
            <p>This email was sent in response to your contact submission. If you believe you received this email in error, please contact us.</p>
            <p style="margin-top: 15px;">
                <strong>Submission Status:</strong> 
                <span class="status-badge status-{{ $contactSubmission->status }}">
                    {{ ucfirst(str_replace('_', ' ', $contactSubmission->status)) }}
                </span>
            </p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
