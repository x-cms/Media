<table class="table data">
    <col/>
    <col width="130px"/>
    <col width="130px"/>
    <tbody class="icons clickable">
    @if(count($items) > 0 || !$isRootFolder)
        @if (!$isRootFolder && !$searchMode)
            <tr data-type="media-item" data-item-type="folder" data-root data-path="{{ dirname($currentFolder) }}"
                tabindex="0">
                <td><i class="icon-folder"></i>..</td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @foreach ($items as $item)
            <?php $itemType = $item->getFileType() ?>
            <tr data-type="media-item"
                data-item-type="{{ $item->type }}"
                data-path="{{  $item->path }}"
                data-title="{{  basename($item->path) }}"
                data-size="{{  $item->sizeToString() }}"
                data-last-modified="{{  $item->lastModifiedAsString() }}"
                data-last-modified-ts="{{ $item->lastModified }}"
                data-public-url="{{ asset('storage/media'.$item->path) }}"
                data-document-type="{{ $itemType }}"
                data-folder="{{ dirname($item->path) }}"
                tabindex="0"
            >
                <td>
                    <div class="item-title no-wrap-text">
                        <i class="{{ $media->itemTypeToIconClass($item, $itemType) }}"></i> {{ basename($item->path) }}
                        <a href="#"
                           data-rename
                           data-control="popup"
                           data-request-data="path: '{{ $item->path }}', listId: 'MediaManager-manager-item-list', type: '{{ $item->type }}'"
                           data-handler="onLoadRenamePopup"
                           data-z-index="1200"
                        ><i data-rename-control class="icon-terminal"></i></a>
                    </div>
                </td>
                <td>{{ $item->sizeToString() }}</td>
                <td>{{ $item->lastModifiedAsString() }}</td>
                @if ($searchMode)
                    <td title="{{ dirname($item->path) }}">
                        <div class="no-wrap-text">{{ dirname($item->path) }}</div>
                    </td>
                @endif
            </tr>
        @endforeach
    @endif
    </tbody>
</table>

@if (count($items) == 0 && $searchMode)
    <p class="no-data">
        没找到你请求的文件.
    </p>
@endif
