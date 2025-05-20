<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        /* Base styles matching app.css */
        :root {
            --background: oklch(0.96 0.02 90.24);
            --foreground: oklch(0.38 0.02 64.34);
            --card: oklch(0.99 0.01 87.47);
            --card-foreground: oklch(0.38 0.02 64.34);
            --popover: oklch(0.99 0.01 87.47);
            --popover-foreground: oklch(0.38 0.02 64.34);
            --primary: oklch(0.62 0.08 65.54);
            --primary-foreground: oklch(1.00 0 0);
            --secondary: oklch(0.88 0.03 85.57);
            --secondary-foreground: oklch(0.43 0.03 64.93);
            --muted: oklch(0.92 0.02 83.06);
            --muted-foreground: oklch(0.54 0.04 71.17);
            --accent: oklch(0.83 0.04 88.81);
            --accent-foreground: oklch(0.38 0.02 64.34);
            --destructive: oklch(0.63 0.26 29.23);
            --destructive-foreground: oklch(1.00 0 0);
            --border: oklch(0.86 0.03 84.59);
            --input: oklch(0.86 0.03 84.59);
            --ring: oklch(0.62 0.08 65.54);
            --radius: 0.25rem;
            --shadow: 2px 3px 5px 0px hsl(28 13% 20% / 0.12), 2px 1px 2px -1px hsl(28 13% 20% / 0.12);
            --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }

        /* Base styles */
        body {
            font-family: var(--font-sans);
            line-height: 1.6;
            color: var(--foreground);
            background-color: var(--background);
            margin: 0;
            padding: 0;
        }

        * {
            border-color: var(--border);
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .email-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 16px;
        }

        h1 {
            color: var(--card-foreground);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .email-body {
            padding: 0 16px;
        }

        p {
            margin-bottom: 16px;
            color: var(--foreground);
        }

        ul {
            color: var(--foreground);
            margin-bottom: 16px;
            padding-left: 20px;
        }

        li {
            margin-bottom: 8px;
        }

        a {
            color: var(--primary);
            text-decoration: none;
        }

        .cta-button {
            display: inline-block;
            background-color: var(--primary);
            color: var(--primary-foreground) !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: var(--radius);
            font-weight: 500;
            margin: 24px 0;
            text-align: center;
        }

        .email-footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            margin-top: 24px;
            font-size: 14px;
            color: var(--muted-foreground);
        }

        .social-links {
            margin: 16px 0;
        }

        .social-link {
            display: inline-block;
            margin: 0 8px;
            color: var(--primary);
            text-decoration: none;
        }

        .help-text {
            font-size: 12px;
            color: var(--muted-foreground);
        }

        /* Dark mode support matching app.css dark theme */
        @media (prefers-color-scheme: dark) {
            :root {
                --background: oklch(0.27 0.01 57.65);
                --foreground: oklch(0.92 0.02 83.06);
                --card: oklch(0.32 0.02 59.06);
                --card-foreground: oklch(0.92 0.02 83.06);
                --popover: oklch(0.32 0.02 59.06);
                --popover-foreground: oklch(0.92 0.02 83.06);
                --primary: oklch(0.73 0.06 66.70);
                --primary-foreground: oklch(0.27 0.01 57.65);
                --secondary: oklch(0.38 0.02 57.13);
                --secondary-foreground: oklch(0.92 0.02 83.06);
                --muted: oklch(0.32 0.02 59.06);
                --muted-foreground: oklch(0.80 0.02 82.11);
                --accent: oklch(0.42 0.03 56.34);
                --accent-foreground: oklch(0.92 0.02 83.06);
                --destructive: oklch(0.63 0.26 29.23);
                --destructive-foreground: oklch(1.00 0 0);
                --border: oklch(0.38 0.02 57.13);
                --input: oklch(0.38 0.02 57.13);
                --ring: oklch(0.73 0.06 66.70);
            }
        }

        /* Responsive styles */
        @media screen and (max-width: 600px) {
            .email-wrapper {
                width: 100% !important;
                border-radius: 0;
            }

            .email-body {
                padding: 0 12px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-header">
            <img src="{{ config('app.url') }}/images/logo.png" alt="{{ config('app.name') }} Logo" class="logo">
            <h1>Welcome to {{ config('app.name') }}!</h1>
        </div>

        <div class="email-body">
            <p>Hello {{ $name }},</p>

            <p>Thank you for joining {{ config('app.name') }}! We're excited to have you as part of our community.</p>

            <p>With your new account, you can:</p>
            <ul>
                <li>Browse and purchase products</li>
                <li>Receive exclusive offers and updates</li>
            </ul>

            <p>To get started, click the button below to explore our platform:</p>

            <a href="{{ config('app.url') }}/dashboard" class="cta-button">Go to Dashboard</a>

            <p>If you have any questions or need assistance, please don't hesitate to contact our support team at <a
                    href="mailto:support@rilltech.com">support@rilltech.com</a>.</p>

            <p>We're glad you're here!</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>

        <div class="email-footer">
            <div class="social-links">
                <a href="#" class="social-link">Twitter</a>
                <a href="#" class="social-link">Facebook</a>
                <a href="#" class="social-link">Instagram</a>
                <a href="#" class="social-link">LinkedIn</a>
            </div>

            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>

            <p class="help-text">
                This email was sent to {{ $email }}. If you didn't create an account with us, please ignore this
                email.
            </p>
        </div>
    </div>
</body>

</html>
