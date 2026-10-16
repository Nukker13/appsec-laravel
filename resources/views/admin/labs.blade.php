@extends('layouts.app')
@section('title', 'Admin: lab analytics')
@section('content')
<div class="container">
    <h1 class="text-heading-md">Lab analytics</h1>
    <p><a href="{{ route('admin.progress') }}">Progress matrix</a></p>
    <table>
        <thead>
        <tr>
            <th>Lab</th><th>Started</th><th>Completed</th><th>Completion</th>
            <th>Median solve time</th><th>Avg attempts</th><th>Avg hints released</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['lab']->name }}</td>
                <td>{{ $row['started'] }}</td>
                <td>{{ $row['completed'] }}</td>
                <td>{{ $row['completion_pct'] === null ? '-' : $metrics->formatNumber($row['completion_pct']).'%' }}</td>
                <td>{{ $metrics->formatDuration($row['median_seconds']) }}</td>
                <td>{{ $metrics->formatNumber($row['avg_attempts']) }}</td>
                <td>{{ $metrics->formatNumber($row['avg_hints']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
