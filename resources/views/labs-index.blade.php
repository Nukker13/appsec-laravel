@extends('layouts.app')

@section('title', 'Labs - '.config('app.name'))

@section('content')
<div class="container">
    <div class="actions">
        <a class="btn {{ $category === null ? 'btn-primary' : 'btn-secondary' }} btn-compact" href="{{ route('labs.index') }}">All</a>
        <a class="btn {{ $category === 'frontend' ? 'btn-primary' : 'btn-secondary' }} btn-compact" href="{{ route('labs.index', ['category' => 'frontend']) }}">Frontend</a>
        <a class="btn {{ $category === 'backend' ? 'btn-primary' : 'btn-secondary' }} btn-compact" href="{{ route('labs.index', ['category' => 'backend']) }}">Backend</a>
    </div>

    @foreach ($labs as $lab)
        @php($state = $status[$lab->id] ?? null)
        <div class="card">
            <div class="card-header">
                <span class="badge badge-{{ $lab->category }}">{{ $lab->category }}</span>
            </div>
            <div class="card-body">
                <p><a href="{{ route('labs.show', $lab) }}"><strong>{{ $lab->name }}</strong></a></p>
                <p>
                    @if ($state === 'completed')
                        Completed
                    @elseif ($state === 'started')
                        Started
                    @else
                        Not started
                    @endif
                </p>
            </div>
        </div>
    @endforeach
</div>
@endsection
