@extends('layouts.app')

@section('title', $lab->name.' - '.config('app.name'))

@section('content')
<div class="container" id="labOpen" style="text-align:center"
     data-launch-url="{{ route('labs.launch', $lab) }}"
     data-status-url="{{ route('labs.status', $lab) }}">
    <h1>{{ $lab->name }}</h1>
    <p><span id="loaderSpinner" class="spinner" aria-hidden="true"></span></p>
    <p id="loaderStatus" aria-live="polite">Starting lab...</p>
    <p><a id="loaderLink" class="btn btn-primary" href="{{ route('labs.show', $lab) }}" hidden>Open lab</a></p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/lab-open.js') }}"></script>
@endpush
