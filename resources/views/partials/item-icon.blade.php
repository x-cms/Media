<div class="icon-container {{ $itemType }}">
    <div class="icon-wrapper"><i class="{{ $media->itemTypeToIconClass($item, $itemType) }}"></i></div>

    @if ($itemType == \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_IMAGE)
        <?php $thumbnailPath = $media->thumbnailExists($thumbnailParams, $item->path, $item->lastModified) ?>

        <div>
            @if (!$thumbnailPath)
                <div class="image-placeholder"
                     data-width="{{ $thumbnailParams['width'] }}"
                     data-height="{{ $thumbnailParams['height'] }}"
                     data-path="{{ $item->path }}"
                     data-last-modified="{{ $item->lastModified }}"
                     id="{{ $media->getPlaceholderId($item) }}"
                >
                    <div class="icon-wrapper"><i class="{{ $media->itemTypeToIconClass($item, $itemType) }}"></i></div>
                </div>
            @else
                @include('media::partials.thumbnail-image', [
                    'isError' => $media->thumbnailIsError($thumbnailPath),
                    'imageUrl' => $media->getThumbnailImageUrl($thumbnailPath)
                ])
            @endif
        </div>
    @endif
</div>