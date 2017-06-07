<div data-control="media-preview-container"></div>

<script type="text/template" data-control="audio-template">
    <div class="panel no-padding-bottom">
        <audio src="{src}" controls>
            <div class="media-player-fallback panel-embedded">Your browser doesn't support HTML5 audio.</div>
        </audio>
    </div>
</script>

<script type="text/template" data-control="video-template">
    <video src="{src}" controls poster="{{ asset('vendor/core/media/images/video-poster.png') }}">
        <div class="panel media-player-fallback">Your browser doesn't support HTML5 video.</div>
    </video>
</script>

<script type="text/template" data-control="image-template">
    <div class="sidebar-image-placeholder-container"><div class="sidebar-image-placeholder" data-path="{path}" data-last-modified="{last-modified}" data-loading="true" data-control="sidebar-thumbnail"></div></div>
</script>

<script type="text/template" data-control="no-selection-template">
    <div class="sidebar-image-placeholder-container">
        <div class="sidebar-image-placeholder no-border">
            <i class="icon-crop"></i>
            <p>没有选中.</p>
        </div>
    </div>
</script>

<script type="text/template" data-control="multi-selection-template">
    <div class="sidebar-image-placeholder-container">
        <div class="sidebar-image-placeholder no-border">
            <i class="icon-asterisk"></i>
            <p>多选.</p>
        </div>
    </div>
</script>

<script type="text/template" data-control="go-up">
    <div class="sidebar-image-placeholder-container">
        <div class="sidebar-image-placeholder no-border">
            <i class="icon-level-up"></i>
            <p>返回上层文件夹</p>
        </div>
    </div>
</script>