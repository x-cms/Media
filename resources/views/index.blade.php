@extends('base::layouts.master')

@push('css')
<link rel="stylesheet" href="{{ asset('vendor/core/media/css/storm.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/core/media/css/october.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/core/media/css/mediamanager.css') }}">
@endpush

@section('content')
    <div class="box box-info">
        {!! $media->render() !!}
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/core/media/js/framework.js') }}"></script>
<script src="{{ asset('vendor/core/media/js/storm-min.js') }}"></script>
<script src="{{ asset('vendor/core/media/js/october-min.js') }}"></script>
<script src="{{ asset('vendor/core/media/js/mediamanager-browser-min.js') }}"></script>
@endpush
