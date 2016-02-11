var OpenClass = function(Api, Files) {
    const DOWNLOAD_LIST_BLOCK = ".download-file-list .mCSB_container";

    $(".main-menu__btn-open").click(function() {
        var fileName = $(".filemanager_select_node").data("name");

        getFileWindow(fileName);
    });

    this.getFileWindow = function(fileName) {
        getFileWindow(fileName);
    };

    function getFileWindow(fileName) {
        if (fileName == undefined) {
            return;
        }

        // if real file
        Files.getFile(fileName, function(data) {
            var path = Cookies.get("currentPath");

            $(".download-file").hide();
            $("#downloader-window").kendoWindow({title: "Download", width: 500, resizable: false, scrollable: false});

            $("#downloader-window").data("kendoWindow").open();
            $("#downloader-window").data("kendoWindow").center();

            $(".download-file-list").mCustomScrollbar({
                axis: "y",
                theme: "minimal-dark"
            });

            getFile(path, fileName);
        });
    };

    function getFile(path, fileName) {
        var templateContent = $("#fileDownloadTemplate").html();
        var template = kendo.template(templateContent);
        var data = [
            {name: fileName}
        ];
        var result = kendo.render(template, data);
        $(DOWNLOAD_LIST_BLOCK).append(result);

        Api.getFile(path + "/" + fileName,
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
};
