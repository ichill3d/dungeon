<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Dungeon Explorer') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-screen flex flex-col bg-gray-100">

<!-- Header -->
<header class="sticky top-0 z-50 w-full bg-white shadow-md flex items-center justify-between px-4 py-2">
    <h1 class="text-lg font-bold">Dungeon Explorer</h1>
    <nav>
        @auth
            <a href="{{ route('dungeons.user') }}" class="text-gray-700 px-2">My Dungeons</a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-gray-700 px-2">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="text-gray-700 px-2">Login</a>
            <a href="{{ route('register') }}" class="text-gray-700 px-2">Register</a>
        @endauth
    </nav>
</header>

<!-- Main Content -->
<div class="flex flex-1 overflow-hidden">
    <!-- Sidebar -->
    <!-- Sidebar Navigation -->
    <aside class="w-64 bg-white shadow-md h-full flex-shrink-0 p-4">
        <x-sidebar-nav />
    </aside>


    <!-- Content Panel -->
    <main class="flex-1 overflow-auto bg-gray-50 p-4">
        {{ $slot }}
    </main>
</div>

@livewireScripts
</body>
</html>
