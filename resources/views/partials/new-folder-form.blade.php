<script type="text/template" data-control="new-folder-template">
    <form method="POST" action="{{ Route('media.index') }}">
        {{ csrf_field() }}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="popup">&times;</button>
            <h4 class="modal-title">新文件</h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>文件夹名</label>
                <input type="text" class="form-control" name="name" value=""/>
            </div>
        </div>
        <div class="modal-footer">
            <button
                    type="submit"
                    class="btn btn-primary">
                应用
            </button>
            <button
                    type="button"
                    class="btn btn-default"
                    data-dismiss="popup">
                取消
            </button>
        </div>
    </form>
</script>
