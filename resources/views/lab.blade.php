@extends('layouts.app')

@section('title', $lab->name.' - '.config('app.name'))

@section('content')
<div class="container" id="lab"
     data-launch-url="{{ route('labs.launch', $lab) }}"
     data-status-url="{{ route('labs.status', $lab) }}"
     data-hints-url="{{ route('labs.hints', $lab) }}"
     data-flag-url="{{ route('labs.flag', $lab) }}">
    <div class="actions">
        <a href="{{ route('labs.index') }}">&larr; All labs</a>
        <h1>{{ $lab->name }}</h1>
        <span class="badge badge-{{ $lab->category }}">{{ $lab->category }}</span>
    </div>

    @include("labs.{$lab->slug}")

    <div class="card">
        <div class="card-header">Lab instance</div>
        <div class="card-body">
            <div class="actions">
                <span id="launchSpinner" class="spinner" aria-hidden="true" hidden></span>
                <span id="launchStatus" aria-live="polite">Starting lab...</span>
                <a id="openLab" class="btn btn-primary" href="#" target="_blank" rel="noopener noreferrer" hidden>Open lab</a>
            </div>
            <p id="launchNote" hidden>The lab opens in a new tab. If your browser blocked it, use the Open lab button. If the page shows a 404 right after opening, wait a couple of seconds and reload.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Hints</div>
        <div class="card-body">
            <p id="hintsEmpty">No hints yet. They appear a few minutes after you launch the lab.</p>
            <div id="hintsList"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Flag</div>
        <div class="card-body">
            <form id="flagForm" class="flag-row" autocomplete="off">
                <input type="text" name="flag" id="flagInput" placeholder="Enter flag" required>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
            <div id="flagResult" role="status"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/lab.js') }}"></script>
@endpush
