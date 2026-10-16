<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
</head>
<body>
<header>
    <a href="{{ route('home') }}">{{ config('app.name') }}</a>
    @auth
        <span class="header-divider">/</span>
        <a href="{{ route('labs.index') }}">Labs</a>
        @can('admin')
            <a href="{{ route('admin.progress') }}">Admin</a>
        @endcan
        <form method="POST" action="{{ route('logout') }}" style="display:inline;margin-left:auto">
            @csrf
            <button type="submit" class="btn btn-secondary btn-compact">Log out</button>
        </form>
    @endauth
</header>
@if (session('error'))
    <p role="alert">{{ session('error') }}</p>
@endif
<main>
    @yield('content')
</main>
<script>
    if (window.hljs) { document.querySelectorAll('pre code').forEach(function (el) { hljs.highlightElement(el); }); }
</script>
@stack('scripts')
</body>
</html>
