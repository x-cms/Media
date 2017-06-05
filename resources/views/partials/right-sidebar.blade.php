@include('media::partials.item-sidebar-preview')

<div class="panel hide" data-control="sidebar-labels">
    <label>标题</label>
    <p data-label="title"></p>

    <table class="name-value-list">
        <tr>
            <th>大小</th>
            <td data-label="size"></td>
        </tr>
        <tr>
            <th>公开URL</th>
            <td><a href="#" data-label="public-url" target="_blank">点击这里</a></td>
        </tr>
        <tr data-control="last-modified">
            <th>最近修改</th>
            <td data-label="last-modified"></td>
        </tr>

        <tr data-control="item-folder" class="hide">
            <th>文件夹</th>
            <td><a href="#" data-type="media-item" data-item-type="folder" data-label="folder" data-clear-search="true"></a></td>
        </tr>
    </table>
</div>