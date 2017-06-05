<div class="panel no-padding padding-top">
    <input type="hidden" data-type="current-folder" value="{{ $currentFolder }}"/>
    <input type="hidden" data-type="search-mode" value="{{ $searchMode ? 'true' : 'false' }}"/>
    <div class="list-container">
        @if (count($items) == 0 && $isRootFolder && !$searchMode)
            <div class="empty-library">媒体库是空的. 从上传文件或创建文件夹开始.</div>
        @endif

        @if ($viewMode == $media::VIEW_MODE_GRID)
            @include('media::partials.list-grid')
        @elseif ($viewMode == $media::VIEW_MODE_LIST)
            @include('media::partials.list-list')
        @else
            @include('media::partials.list-tiles')
        @endif
    </div>
</div>