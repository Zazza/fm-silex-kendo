var UploadClass = function(Api, Files) {
    const UPLOAD_LIST_BLOCK = ".uploading-file-list .mCSB_container";
    const FILES_BLOCK = ".splitter-content-structure .mCSB_container";

    var Api = new ApiClass();
    var Files = new FilesClass(Api);

    $("#uploader-window").kendoWindow({title: "Upload files", width: 500, resizable: false, scrollable: false});

    $(".main-menu__btn-load").click(function () {
        $("#uploader-window").data("kendoWindow").open();
        $("#uploader-window").data("kendoWindow").center();

        $(".uploading-file-list").mCustomScrollbar({
            axis: "y",
            theme: "minimal-dark"
        });
    });

    $("body").on("click", "#uploader-window-close", function () {
        $("#uploader-window").data("kendoWindow").close();
    });

    $("body").on("click", "#uploader-window-clear", function () {
        $(".upload-status-progress").each(function (key, status) {
            var maxWidth = $(".upload-file-progress").width()
                + parseInt($(".upload-file-progress").css("padding-left").substr(0, $(".upload-file-progress").css("padding-left").indexOf("px")))
                + parseInt($(".upload-file-progress").css("padding-right").substr(0, $(".upload-file-progress").css("padding-right").indexOf("px")))

            if ($(status).width() == maxWidth) {
                $(status).closest(".upload-file-progress").remove();
            }
        });
    });

    $("#files").kendoUpload({
        showFileList: false,
        select: function (e) {
            var files = e.files;

            $.each(files, function (key, file) {
                upload(file);
            });
        }
    });

    function upload(file) {
        var path = Cookies.get("currentPath");

        var templateContent = $("#fileUploadTemplate").html();
        var template = kendo.template(templateContent);
        var data = [
            {name: file.name, path: path}
        ];
        var result = kendo.render(template, data);
        $(UPLOAD_LIST_BLOCK).append(result);

        Api.sendFile(path, file.rawFile,
            function (xhr, response) {
                $("body").on("click", ".uploaderRemove", function() {
                    xhr.abort();
                });

                var pc = parseInt(response.loaded / response.total * 100);
                if (!isNaN(pc)) {
                    $(".upload-file-progress[data-path='" + path + "'] .upload-status-progress").css("width", pc + "%");
                }
            },
            function (response) {
                if (response.result) {
                    $(".upload-file-progress[data-path='" + path + "'] .upload-status-progress").css("width", "100%");

                    var renderHTML = Files.addFileToFS(response.file[0], Cookies.get("view"));
                    $(FILES_BLOCK).append(renderHTML);

                    //$("#summarySize").text(summarySize);
                    //$("#numFiles").text(numFiles);

                    return true;
                }
            }
        );
    }
};
