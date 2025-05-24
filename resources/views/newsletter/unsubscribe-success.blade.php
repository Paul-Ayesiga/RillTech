<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed Successfully - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ config('app.name') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Newsletter Unsubscribe
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <div class="text-center">
                    <div
                        class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h2 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">
                        Successfully Unsubscribed
                    </h2>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        You have been successfully unsubscribed from our newsletter.
                    </p>

                    @if (isset($email))
                        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Email: <span
                                    class="font-medium text-gray-900 dark:text-white">{{ $email }}</span>
                            </p>
                        </div>
                    @endif

                    <div class="mt-6 space-y-4">
                        <div class="text-left bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                                What happens next?
                            </h3>
                            <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                                <li>• You will no longer receive our newsletter emails</li>
                                <li>• This change is effective immediately</li>
                                <li>• You can resubscribe anytime if you change your mind</li>
                                <li>• We may still send important account-related emails</li>
                            </ul>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        We're sorry to see you go! If you have any feedback about why you unsubscribed,
                        we'd love to hear from you.
                    </p>
                </div>

                <div class="mt-6 space-y-3">
                    <a href="{{ url('/#newsletter') }}"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Resubscribe to Newsletter
                    </a>

                    <a href="{{ route('contact.show') }}"
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Send Feedback
                    </a>

                    <a href="{{ url('/') }}"
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Return to Homepage
                    </a>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Questions?
                        <a href="{{ route('contact.show') }}"
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">
                            Contact our support team
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>
