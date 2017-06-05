<div class="layout-row min-size hide" data-control="upload-ui">
    <div class="layout">
        <div class="upload-progress">
            <h5
                    data-label="file-number-and-progress"
                    data-message-template="上传 :number 文件... <span>:percents</span>"
                    data-success-template="上传完毕"
                    data-error-template="上传失败"
            ></h5>

            <div class="progress-controls">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0;" data-control="upload-progress-bar">
                    </div>
                </div>

                <div class="controls">
                    <a href="#" data-command="cancel-uploading"><i class="icon-times-circle" title=""></i></a>
                    <a class="hide" href="#" data-command="close-uploader"><i class="icon-check-circle" title=""></i></a>
                </div>
            </div>
        </div>
    </div>
</div>