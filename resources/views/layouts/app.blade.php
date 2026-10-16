<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
</head>
<body>
<header>
    <a href="{{ route('home') }}">{{ config('app.name') }}</a>
    @auth
        <a href="{{ route('labs.index') }}">Labs</a>
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit">Log out</button>
        </form>
    @endauth
</header>
@if (session('error'))
    <p role="alert">{{ session('error') }}</p>
@endif
<main>
    @yield('content')
</main>
</body>
</html>
