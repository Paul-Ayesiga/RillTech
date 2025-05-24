<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe Error - {{ config('app.name') }}</title>
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
                        class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>

                    <h2 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">
                        Unsubscribe Error
                    </h2>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ $message ?? 'There was an error processing your unsubscribe request.' }}
                    </p>

                    <div class="mt-6 space-y-4">
                        <div class="text-left bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                Possible reasons:
                            </h3>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• The unsubscribe link has expired</li>
                                <li>• The link has already been used</li>
                                <li>• The link is invalid or corrupted</li>
                                <li>• You may have already been unsubscribed</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <a href="{{ url('/#newsletter') }}"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Subscribe to Newsletter
                    </a>

                    <a href="{{ route('contact.show') }}"
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Contact Support
                    </a>

                    <a href="{{ url('/') }}"
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Return to Homepage
                    </a>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Need help?
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
