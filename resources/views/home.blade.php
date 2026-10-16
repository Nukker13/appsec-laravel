@extends('layouts.app')

@section('content')
    <h1>{{ config('app.name') }}</h1>
    <p>Hands-on AppSec training labs. Sign in to launch a lab.</p>
    @guest
        <a href="{{ route('login') }}">Sign in with Google</a>
    @endguest
@endsection
