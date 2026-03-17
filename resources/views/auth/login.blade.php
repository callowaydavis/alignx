<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Alignx</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-gray-100 antialiased min-h-screen flex items-center justify-center">

<div class="w-full max-w-sm px-4">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-500 rounded-xl text-white font-bold text-lg mb-4">A</div>
        <h1 class="text-2xl font-semibold tracking-tight">Alignx</h1>
        <p class="text-gray-400 text-sm mt-1">Sign in to your account</p>
    </div>

    <div class="bg-gray-900 rounded-xl border border-gray-800 p-6">
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-900/50 border border-red-700 px-4 py-3 text-sm text-red-300">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror"
                    placeholder="you@example.com"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror"
                    placeholder="••••••••"
                >
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 bg-gray-800 border-gray-700 rounded text-blue-500 focus:ring-blue-500">
                <label for="remember" class="ml-2 text-sm text-gray-400">Remember me</label>
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900"
            >
                Sign in
            </button>
        </form>

        <div class="mt-4 relative">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-700"></div></div>
            <div class="relative flex justify-center text-xs text-gray-500 uppercase tracking-wide"><span class="bg-gray-900 px-2">or</span></div>
        </div>

        <a
            href="{{ route('azure.redirect') }}"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-200 hover:bg-gray-700 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" class="h-4 w-4" aria-hidden="true">
                <path fill="#f3f3f3" d="M0 0h23v23H0z"/>
                <path fill="#f35325" d="M1 1h10v10H1z"/>
                <path fill="#81bc06" d="M12 1h10v10H12z"/>
                <path fill="#05a6f0" d="M1 12h10v10H1z"/>
                <path fill="#ffba08" d="M12 12h10v10H12z"/>
            </svg>
            Sign in with Microsoft
        </a>
    </div>
</div>

</body>
</html>
