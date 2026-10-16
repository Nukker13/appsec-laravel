@extends('layouts.app')

@section('content')
    <h1>{{ config('app.name') }}</h1>
    @if (session('error'))
        <div class="alert alert-error" role="alert">{{ session('error') }}</div>
    @endif
    <p>Hands-on AppSec training labs. Sign in to launch a lab.</p>
    @guest
        <a href="{{ route('login') }}">Sign in with Google</a>
    @endguest
@endsection
