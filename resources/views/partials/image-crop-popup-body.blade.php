<form method="post" class="layout" onsubmit="return false">
<div class="layout-row min-size">
    @include('media::partials.crop-toolbar')
</div>
<div class="layout-row whiteboard">
    @include('media::partials.crop-tool-image-area')
</div>
<div class="layout-row min-size whiteboard">
    <div class="panel no-padding-bottom border-top">
        <div class="form-buttons">
            <div class="pull-right">
                <button
                        type="button"
                        data-command="insert"
                        class="btn btn-primary">
                    裁剪并插入
                </button>

                <button
                        type="button"
                        data-dismiss="popup"
                        class="btn btn-default no-margin-right">
                    取消
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="cropSessionKey" value="{{ $cropSessionKey }}">
<input type="hidden" name="path" value="{{ $path }}">

<input type="hidden" data-control="dimension-width" value="{{ $dimensions[0] }}">
<input type="hidden" data-control="dimension-height" value="{{ $dimensions[1] }}">

<input type="hidden" data-control="original-width" value="{{ $dimensions[0] }}">
<input type="hidden" data-control="original-height" value="{{ $dimensions[1] }}">

<input type="hidden" data-control="original-ratio" value="{{ $originalRatio }}">
<input type="hidden" data-control="original-url" value="{{ $imageUrl }}">

@include('media::partials.resize-image-form')
</form>