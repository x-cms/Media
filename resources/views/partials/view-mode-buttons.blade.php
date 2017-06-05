<button
        type="button"
        class="btn btn-default oc-icon-align-justify empty {{ $viewMode == $media::VIEW_MODE_GRID ? 'on' : '' }}"
        data-command="change-view"
        data-view="grid">
</button>
<button
        type="button"
        class="btn btn-default oc-icon-th empty {{ $viewMode == $media::VIEW_MODE_LIST ? 'on' : '' }}"
        data-command="change-view"
        data-view="list">
</button>
<button
        type="button"
        class="btn btn-default oc-icon-th-large empty {{ $viewMode == $media::VIEW_MODE_TILES ? 'on' : '' }}"
        data-command="change-view"
        data-view="tiles">
</button>