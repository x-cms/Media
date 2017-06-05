<form method="post" action="{{ route('media.index') }}">
<div class="modal-header">
    <button type="button" class="close" data-dismiss="popup">&times;</button>
    <h4 class="modal-title">移动资源</h4>
</div>
<div class="modal-body">
    <div class="form-group">
        <label>目标目录</label>
        <select
                class="form-control custom-select"
                name="dest"
                data-placeholder="请选择">
            <option></option>
            @foreach ($folders as $path=>$folder)
            <option value="{{ $path }}">{{ $folder }}</option>
            @endforeach
        </select>

        <input type="hidden" name="originalPath" value="{{ $originalPath }}">
    </div>
</div>
<div class="modal-footer">
    <button
            type="submit"
            class="btn btn-primary">
        移动
    </button>
    <button
            type="button"
            class="btn btn-default"
            data-dismiss="popup">
        取消
    </button>
</div>
</form>