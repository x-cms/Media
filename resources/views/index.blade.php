@extends('base::layouts.master')

@push('css')
<link rel="stylesheet" href="{{ asset('vendor/core/media/css/storm.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/core/media/css/october.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/core/media/css/mediamanager.css') }}">
@endpush

@section('content')
    <div class="box box-info">
        <div class="layout">
            <div class="layout-row">
                {!! $media->render() !!}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/core/media/js/framework.js') }}"></script>
<script src="{{ asset('vendor/core/media/js/storm-min.js') }}"></script>
<script src="{{ asset('vendor/core/media/js/october-min.js') }}"></script>
<script src="{{ asset('vendor/core/media/js/mediamanager-browser-min.js') }}"></script>
@endpush

@push('js')
<script>
    let header_height = $(".main-header").outerHeight(true)
    let content_height = $(".content-header").outerHeight(true)
    let footer_height = $(".main-footer").outerHeight(true)
    let height = getViewPort().height - header_height - content_height - footer_height - 60

    $(".box").css("height", height);

    $( window ).resize(function() {
        $(".box").css("height", getViewPort().height - header_height - content_height - footer_height - 60);
    });

    function getViewPort() {
        let e = window,
            a = 'inner';
        if (!('innerWidth' in window)) {
            a = 'client';
            e = document.documentElement || document.body;
        }

        return {
            width: e[a + 'Width'],
            height: e[a + 'Height']
        };
    }
</script>
@endpush
