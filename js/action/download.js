var DownloadClass = function(Api, Files) {
    const DOWNLOAD_LIST_BLOCK = ".download-file-list .mCSB_container";

    $(".main-menu__btn-download").click(function(){
        var path = Cookies.get("currentPath");

        var files = [];
        $(".filemanager_select_node").each(function(key, file) {
            files[key] = encodeURIComponent(path + "/" + $(file).data("name"));
        });

        if (files.length == 0) {
            return;
        }

        var arc = new Date();
        var fileName = "archive" + arc.getFullYear() + arc.getMonth() + arc.getDay() + arc.getHours() + arc.getMinutes() + arc.getSeconds() + ".zip";

        $(".download-file").hide();
        $("#downloader-window").kendoWindow({title: "Download", width: 500, resizable: false, scrollable: false});

        $("#downloader-window").data("kendoWindow").open();
        $("#downloader-window").data("kendoWindow").center();

        $(".download-file-list").mCustomScrollbar({
            axis: "y",
            theme: "minimal-dark"
        });

        download(files, fileName);
    });

    function download(files, fileName) {
        var templateContent = $("#fileDownloadTemplate").html();
        var template = kendo.template(templateContent);
        var data = [
            {name: fileName}
        ];
        var result = kendo.render(template, data);
        $(DOWNLOAD_LIST_BLOCK).append(result);

        Api.downloadFile(files,
            function(xhr, response) {
                $("body").on("click", ".downloadRemove", function() {
                    xhr.abort();
                });

                var pc = parseInt(response.loaded / response.total * 100);
                if (!isNaN(pc)) {
                    $(".download-file-progress[title='"+fileName+"'] .download-status-progress").css("width", pc + "%");
                }
            },
            function(response) {
                if (response.result) {
                    $(".download-file-progress[title='"+fileName+"'] .download-status-progress").css("width", "100%");

                    var blob = new Blob([response.file]);
                    saveAs(blob, fileName);

                    return true;
                }
            }
        );
    }

    $("body").on("click", "#download-window-clear", function(){
        $(".download-status-progress").each(function(key, status) {
            var maxWidth = $(".download-file-progress").width()
                + parseInt($(".download-file-progress").css("padding-left").substr(0, $(".download-file-progress").css("padding-left").indexOf("px")))
                + parseInt($(".download-file-progress").css("padding-right").substr(0, $(".download-file-progress").css("padding-right").indexOf("px")));

            if ($(status).width() == maxWidth) {
                $(status).closest(".download-file-progress").remove();
            }
        });
    });

    $("body").on("click", "#download-window-close", function(){
        $("#downloader-window").data("kendoWindow").close();
    });
};
