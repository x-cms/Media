<div class="layout-row min-size">
    <div class="control-toolbar toolbar-padded">
        <div class="toolbar-item toolbar-primary">
            <div data-control="toolbar">
                <div class="btn-group offset-right">
                    <button type="button" class="btn btn-primary oc-icon-upload" data-control="upload"
                    >上传</button>
                    <button type="button" class="btn btn-primary oc-icon-folder" data-command="create-folder">
                        增加文件夹</button>
                </div>

                <button type="button" class="btn btn-default oc-icon-refresh empty offset-right" data-command="refresh"></button>

                <div class="btn-group offset-right">
                    <button type="button" class="btn btn-default oc-icon-reply-all" data-command="move"
                    >移动</button>
                    <button type="button" class="btn btn-default oc-icon-trash" data-command="delete"
                    >删除</button>
                </div>

                <div class="btn-group offset-right" id="MediaManager-manager-view-mode-buttons">
                    @include('media::partials.view-mode-buttons')
                </div>
            </div>
        </div>
        <div class="toolbar-item" data-calculate-width>
            <div class="relative loading-indicator-container size-input-text">
                <input
                        type="text"
                        name="search"
                        value=""
                        class="form-control icon search growable"
                        placeholder="搜索"
                        data-control="search"
                        autocomplete="off"
                        data-load-indicator
                        data-load-indicator-opaque
                />
            </div>
        </div>
    </div>
</div>