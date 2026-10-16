@extends('layouts.app')
@section('title', 'Admin: progress')
@section('content')
<div class="container">
    <h1 class="text-heading-md">Progress matrix</h1>
    <p><a href="{{ route('admin.labs') }}">Lab analytics</a></p>
    <table>
        <thead>
        <tr>
            <th>User</th>
            @foreach ($labs as $lab)
                <th>{{ $lab->name }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>
                @foreach ($labs as $lab)
                    @php($cell = $cells[$user->id][$lab->id] ?? null)
                    <td>
                        @if ($cell)
                            {{ $cell['status'] }}
                            @if ($cell['solve_seconds'] !== null)
                                <br>{{ $metrics->formatDuration($cell['solve_seconds']) }}
                            @endif
                            <br>{{ $cell['attempts'] }} attempts
                        @else
                            -
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
