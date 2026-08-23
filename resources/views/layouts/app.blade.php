<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'MyWebService') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col justify-between">
    <!-- Navigation -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between h-16 items-center">
            <div class="flex items-center space-x-3">
                <a href="{{ url('/') }}" class="text-xl font-bold text-indigo-600 flex items-center gap-2">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>MyWebService</span>
                </a>
            </div>
            <nav class="flex items-center space-x-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600">ダッシュボード</a>
                    <span class="text-sm text-gray-500 font-semibold">({{ Auth::user()->name }})</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-md font-medium transition">
                            ログアウト
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600">ログイン</a>
                    <a href="{{ route('register') }}" class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition">
                        新規登録
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('status') && session('status') !== 'verification-link-sent')
            <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                <div class="flex items-center gap-2 mb-2 font-semibold">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>入力内容にエラーがあります:</span>
                </div>
                <ul class="list-disc list-inside text-sm space-y-1 text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} MyWebService. Laravel 12 &amp; Nginx on Port 7777.
    </footer>
</body>
</html>
