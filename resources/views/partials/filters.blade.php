<h3 class="section">显示</h3>

<ul class="nav nav-stacked selector-group">
    <li role="presentation" @if ($currentFilter == $media::FILTER_EVERYTHING)class="active"@endif>
        <a href="#" data-command="set-filter" data-filter="{{ $media::FILTER_EVERYTHING }}">
            <i class="fa fa-recycle"></i>

            所有
        </a>
    </li>
    <li role="presentation" @if ($currentFilter == \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_IMAGE)class="active"@endif>
        <a href="#" data-command="set-filter" data-filter="{{ \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_IMAGE }}">
            <i class="fa fa-picture-o"></i>

            图片
        </a>
    </li>
    <li role="presentation" @if ($currentFilter == \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_VIDEO)class="active"@endif>
        <a href="#" data-command="set-filter" data-filter="{{ \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_VIDEO }}">
            <i class="fa fa-video-camera"></i>

            视频
        </a>
    </li>
    <li role="presentation" @if ($currentFilter == \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_AUDIO)class="active"@endif>
        <a href="#" data-command="set-filter" data-filter="{{ \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_AUDIO }}">
            <i class="fa fa-volume-up"></i>

            音频
        </a>
    </li>
    <li role="presentation" @if ($currentFilter == \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_DOCUMENT)class="active"@endif>
        <a href="#" data-command="set-filter" data-filter="{{ \Xcms\Media\Support\MediaLibraryItem::FILE_TYPE_DOCUMENT }}">
            <i class="fa fa-file"></i>

            文档
        </a>
    </li>
</ul>