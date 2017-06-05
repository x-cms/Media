<ul class="media-list {{ $listClass }}">
    @if (count($items) > 0 || !$isRootFolder)
    @if (!$isRootFolder && !$searchMode)
    <li tabindex="0" data-type="media-item" data-item-type="folder" data-root data-path="{{ dirname($currentFolder) }}">
        <div class="icon-container folder">
            <div class="icon-wrapper"><i class="icon-folder"></i></div>
        </div>
        <div class="info">
            <h4 title="返回上层文件夹">返回 ..</h4>
        </div>
    </li>
    @endif

    @foreach ($items as $item)
    <?php $itemType = $item->getFileType() ?>
    <li data-type="media-item"
        data-item-type="{{ $item->type }}"
        data-path="{{ $item->path }}"
        data-title="{{ basename($item->path) }}"
        data-size="{{ $item->sizeToString() }}"
        data-last-modified="{{ $item->lastModifiedAsString() }}"
        data-last-modified-ts="{{ $item->lastModified }}"
        data-public-url="{{ asset('storage/media'.$item->path) }}"
        data-document-type="{{ $itemType }}"
        data-folder="{{  dirname($item->path) }}"
        tabindex="0"
    >
        @include('media::partials.item-icon', ['itemType'=>$itemType, 'item'=>$item])

        <div class="info">
            <h4 title="{{  basename($item->path) }}">
                {{ basename($item->path) }}

                <a
                        href="#"
                        data-rename
                        data-control="popup"
                        data-z-index="1200"
                        data-request-data="path: '{{ $item->path }}', listId: '#MediaManager-manager-item-list', type: '{{ $item->type }}'"
                        data-handler="onLoadRenamePopup"
                ><i data-rename-control class="icon-terminal"></i></a>
            </h4>
            <p class="size">{{ $item->sizeToString() }}</p>
        </div>
    </li>
    @endforeach
    @endif

    @if (count($items) == 0 && $searchMode)
    <li class="no-data">
        没找到你请求的文件.
    </li>
    @endif
</ul>