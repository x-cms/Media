<div
        data-control="media-manager"
        class="layout"
        data-alias="manager"
        data-unique-id="MediaManager-manager"
        data-delete-empty="请选择删除项."
        data-delete-confirm="你是否想要删除选中项?"
        data-move-empty="请选择移动项."
        data-select-single-image="请选择一张图片."
        data-selection-not-image="选择的不是一张图片."
        data-bottom-toolbar="{{ $media->bottomToolbar ? 'true' : 'false' }}"
        data-crop-and-insert-button="{{ $media->cropAndInsertButton ? 'true' : 'false' }}"
        tabindex="0"
>
    @include('media::partials.toolbar')
    @include('media::partials.upload-progress')
    <div class="layout-row whiteboard">
        <div class="layout">
            <div class="layout-row">
                <div class="layout-cell panel w-200 border-right" data-control="left-sidebar">
                    @include('media::partials.left-sidebar')
                </div>
                <div class="layout-cell">
                    <div class="layout">

                        <div class="layout-row min-size">
                            @include('media::partials.folder-toolbar')
                        </div>
                        <div class="layout-row">
                            <!-- Main area -->
                            <div class="layout">
                                <div class="layout-row">
                                    <div class="layout">
                                        <!-- Main area - list -->
                                        <div data-control="item-list">
                                            <div class="control-scrollpad">
                                                <div class="scroll-wrapper">
                                                    <!-- This element is required for the scrollpad control -->
                                                    <div id="MediaManager-manager-item-list">
                                                        @include('media::partials.item-list')
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layout-cell w-300 panel border-left no-padding {{ !$sidebarVisible ? 'hide' : null }}"
                                             data-control="preview-sidebar">
                                            <!-- Right sidebar -->
                                            @include('media::partials.right-sidebar')
                                        </div>
                                    </div>
                                </div>
                                <div class="layout-row min-size hide" data-control="bottom-toolbar">
                                    @include('media::partials.bottom-toolbar')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('media::partials.new-folder-form')
</div>