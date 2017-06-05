<form id="media-rename-popup-form" method="post" action="{{ route('media.index') }}" data-request="onApplyName"
      data-stripe-load-indicator="1"
      data-request-success="$el.trigger('close.oc.popup'); $('#MediaManager-manager-item-list').trigger('mediarefresh');">
    {{ csrf_field() }}
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="popup">&times;</button>
        <h4 class="modal-title">重命名</h4>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label>新名称</label>
            <input type="text" default-focus class="form-control" name="name" value="{{  $name }}"/>
            <input type="hidden" name="originalName" value="{{  $name }}">
            <input type="hidden" name="type" value="{{ $type }}">
        </div>

        <input type="hidden" name="originalPath" value="{{ $originalPath }}"/>
    </div>
    <div class="modal-footer">
        <button
                type="submit"
                class="btn btn-primary">
            确认操作
        </button>
        <button
                type="button"
                class="btn btn-default"
                data-dismiss="popup">
            放弃操作
        </button>
    </div>
    <script>
        setTimeout(
            function () {
                $('#media-rename-popup-form input.form-control').focus()
            },
            310
        )

        $('#media-rename-popup-form').on('oc.beforeRequest', function (ev) {
            var originalName = $('#media-rename-popup-form [name=originalName]').val(),
                newName = $.trim($('#media-rename-popup-form [name=name]').val())

            if (originalName == newName || newName.length == 0) {
                alert('Please enter a new name')

                ev.preventDefault()
            }
        })
    </script>
</form>