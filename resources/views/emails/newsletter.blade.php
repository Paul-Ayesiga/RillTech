<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
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
            padding: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .tagline {
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #374151;
            margin-bottom: 25px;
        }
        .newsletter-content {
            background-color: #ffffff;
            border-radius: 6px;
            margin: 25px 0;
            line-height: 1.7;
        }
        .newsletter-content h1,
        .newsletter-content h2,
        .newsletter-content h3 {
            color: #1f2937;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .newsletter-content h1 {
            font-size: 24px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .newsletter-content h2 {
            font-size: 20px;
        }
        .newsletter-content h3 {
            font-size: 18px;
        }
        .newsletter-content p {
            margin-bottom: 15px;
        }
        .newsletter-content ul,
        .newsletter-content ol {
            margin: 15px 0;
            padding-left: 25px;
        }
        .newsletter-content li {
            margin-bottom: 8px;
        }
        .newsletter-content blockquote {
            border-left: 4px solid #667eea;
            margin: 20px 0;
            padding: 15px 20px;
            background-color: #f8fafc;
            font-style: italic;
        }
        .newsletter-content a {
            color: #667eea;
            text-decoration: none;
        }
        .newsletter-content a:hover {
            text-decoration: underline;
        }
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
        }
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #6b7280;
            text-decoration: none;
        }
        .unsubscribe {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
        }
        .unsubscribe a {
            color: #6b7280;
            text-decoration: none;
        }
        .unsubscribe a:hover {
            text-decoration: underline;
        }
        .signature {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
            text-align: left;
        }
        .signature-name {
            font-weight: 600;
            color: #1f2937;
        }
        .signature-title {
            color: #6b7280;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 25px 20px;
            }
            .header {
                padding: 25px 20px;
            }
            .footer {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <div class="tagline">Stay informed with our latest updates</div>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $subscription->name ?? 'Valued Subscriber' }},
            </div>

            <div class="newsletter-content">
                {!! nl2br(e($emailContent)) !!}
            </div>

            @if(str_contains(strtolower($emailContent), 'visit') || str_contains(strtolower($emailContent), 'learn more') || str_contains(strtolower($emailContent), 'read more'))
            <div class="cta-section">
                <h3 style="margin-top: 0; color: white;">Ready to Learn More?</h3>
                <p style="margin-bottom: 0; opacity: 0.9;">Visit our website for more information and updates.</p>
                <a href="{{ config('app.url') }}" class="cta-button">Visit Our Website</a>
            </div>
            @endif

            <div class="signature">
                <div class="signature-name">{{ $adminUser->name }}</div>
                <div class="signature-title">{{ config('app.name') }} Team</div>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 15px 0; color: #6b7280;">
                Thank you for being a valued subscriber to our newsletter.
            </p>

            <div class="social-links">
                <a href="#">Facebook</a>
                <a href="#">Twitter</a>
                <a href="#">LinkedIn</a>
                <a href="#">Instagram</a>
            </div>

            <p style="margin: 15px 0; color: #6b7280; font-size: 14px;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>

            <div class="unsubscribe">
                <p>
                    You're receiving this email because you subscribed to our newsletter.
                    <br>
                    <a href="{{ $unsubscribeUrl }}">Unsubscribe from this list</a> | 
                    <a href="{{ config('app.url') }}">Update your preferences</a>
                </p>
                <p style="margin-top: 10px;">
                    {{ config('app.name') }}<br>
                    Our mailing address<br>
                    City, State ZIP
                </p>
            </div>
        </div>
    </div>
</body>
</html>
