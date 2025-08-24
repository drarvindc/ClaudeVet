{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Veterinary Clinic') - {{ config('app.name', 'VetClinic') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-container { box-shadow: none !important; margin: 0 !important; }
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    @yield('content')

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>