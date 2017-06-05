<ul class="tree-path">
    <li class="root"><a href="#" data-type="media-item" data-item-type="folder" data-path="/"
                        data-clear-search="true">库</a></li>

    @if (!$searchMode)
        @foreach ($pathSegments as $folder=>$path)
            @if ($path != '/')
                <li><a href="#" data-type="media-item" data-item-type="folder"
                       data-path="{{ $path }}">{{ basename($folder) }}</a></li>
            @endif
        @endforeach
    @else
        <li><a href="#" data-type="media-item">搜索</a></li>
    @endif
</ul>