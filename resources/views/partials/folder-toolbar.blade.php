<div class="panel padding-less border-bottom triangle-down">
    <div class="layout">
        <div class="layout-cell">
            <div class="layout-row" id="MediaManager-manager-folder-path">
                @include('media::partials.folder-path')
            </div>
        </div>
        <div class="layout-cell">
            <button
                    type="button"
                    data-command="toggle-sidebar"
                    class="oc-icon-sign-out btn-icon pull-right larger {{ !$sidebarVisible ? 'sidebar-hidden' : null }}">
            </button>
        </div>
    </div>
</div>