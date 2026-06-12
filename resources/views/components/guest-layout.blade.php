<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? config('app.name') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="{{ asset('logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.jpg') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="flex min-h-screen items-center justify-center bg-brand p-4 font-sans antialiased">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="text-center mb-8">
            <a href="/" class="mb-4 block transition-opacity hover:opacity-90">
                <img src="{{ asset('logo.jpg') }}" alt="La Patisserie Express" class="mx-auto h-24 w-24 rounded-full object-cover shadow-md">
            </a>
            <a href="/" class="text-sm text-gray-600 transition-colors hover:text-brand">← Back to Home</a>
        </div>
        
        {{ $slot }}
    </div>
    
    @stack('scripts')
</body>
</html>
