<?php

$selectionModes = [
    \App\Modules\Media\Services\MediaManager::SELECTION_MODE_NORMAL => trans('cms::lang.media.selection_mode_normal'),
    \App\Modules\Media\Services\MediaManager::SELECTION_MODE_FIXED_RATIO => trans('cms::lang.media.selection_mode_fixed_ratio'),
    \App\Modules\Media\Services\MediaManager::SELECTION_MODE_FIXED_SIZE => trans('cms::lang.media.selection_mode_fixed_size')
];

$sizeDisabledAttr = $currentSelectionMode == \App\Modules\Media\Services\MediaManager::SELECTION_MODE_NORMAL ? 'disabled="disabled"' : null;
?>

<div class="control-toolbar toolbar-padded">
    <div class="toolbar-item toolbar-primary">
        <div data-control="toolbar">
            <label class="standalone">图片大小: <span data-label="width">{{ $dimensions[0] }}</span> x <span data-label="height">{{ $dimensions[1] }}</span></label>

            <div class="btn-group offset-right">
                <button type="button" class="btn btn-primary standalone" data-command="resize"
                >调整图片</button>

                <button type="button" class="btn btn-primary oc-icon-undo empty" data-command="undo-resizing"></button>
            </div>

            <label for="mmcropimagewidth">选择模式</label>
            <select name="selectionMode" class="form-control custom-select w-150" data-control="selection-mode">
                @foreach ($selectionModes as $mode=>$name)
                <option {{ $mode == $currentSelectionMode ? 'selected="selected"' : null }} value="{{ $mode }}">{{ $name }}</option>
                @endforeach
            </select>

            <label for="mmcropimagewidth">宽度</label>
            <input id="mmcropimagewidth" type="text" class="form-control w-50" data-control="crop-width-input" name="selectionWidth" value="{{ $currentSelectionWidth }}" {{ $sizeDisabledAttr }}/>

            <label for="mmcropimageheight">高度</label>
            <input id="mmcropimageheight" type="text" class="form-control w-50" data-control="crop-height-input" name="selectionHeight" value="{{  $currentSelectionHeight }}" {{ $sizeDisabledAttr }}/>

            <label class="standalone hide" data-label="selection-size">选中: <span data-label="selection-width"></span> x <span data-label="selection-height"></span></label>

        </div>
    </div>
</div>