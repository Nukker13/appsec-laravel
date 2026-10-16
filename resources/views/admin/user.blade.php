@extends('layouts.app')
@section('title', 'Admin: '.$student->name)
@section('content')
<div class="container">
    <h1 class="text-heading-md">{{ $student->name }}</h1>
    <p>{{ $student->email }} | <a href="{{ route('admin.progress') }}">Progress matrix</a></p>
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif
    @error('reason')
        <p role="alert">{{ $message }}</p>
    @enderror
    @foreach ($labs as $lab)
        @php($cell = $cells[$lab->id] ?? null)
        <div class="card">
            <div class="card-header">{{ $lab->name }}</div>
            <div class="card-body">
                <p>
                    @if ($cell)
                        {{ $cell['status'] }}
                        @if ($cell['solve_seconds'] !== null) | {{ $metrics->formatDuration($cell['solve_seconds']) }} @endif
                        | {{ $cell['attempts'] }} attempts
                    @else
                        not started
                    @endif
                </p>
                @foreach (['reset' => ['admin.progress.reset', 'Reset progress'], 'complete' => ['admin.progress.complete', 'Mark complete']] as $key => [$routeName, $label])
                    <form method="POST" action="{{ route($routeName, [$student, $lab]) }}">
                        @csrf
                        <textarea name="reason" required placeholder="Reason" aria-label="Reason for {{ $key }}"></textarea>
                        <button type="submit" class="btn btn-secondary btn-compact">{{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection
