var NewFolderClass = function(Api, Files) {
    const SPLITTER_TREEVIEW_BLOCK = ".splitter-treeview .mCSB_container";
    const FILES_BLOCK = ".splitter-content-structure .mCSB_container";

    $(".main-menu__btn-new-folder").click(function(){
        $("#new-folder-window").kendoWindow({title: "New folder", minWidth: 300, resizable: false, scrollable: false});

        $("#new-folder-window").data("kendoWindow").open();
        $("#new-folder-window").data("kendoWindow").center();
    });

    $("#new-folder-confirm").click(function(){
        var folderName = $("#new-folder-window-textbox").val();

        var treeview = $(SPLITTER_TREEVIEW_BLOCK).data("kendoTreeView");
        var selectNode = treeview.select();

        if ($(selectNode).parent().find(".k-icon").length) {
            if ($(selectNode).parent().find(".k-minus").length) {
                treeview.collapse(selectNode);
            }
        }

        var currentPath = Cookies.get('currentPath');

        Api.mkdir(folderName, currentPath, function(output){
            var view = Cookies.get('view');
            var renderHTML = Files.addFolderToFS(output[0], view);
            $(FILES_BLOCK).append(renderHTML);

            $("#new-folder-window").data("kendoWindow").close();
        });
    });

    $("body").on("click", "#new-folder-window-close", function(){
        $("#new-folder-window").data("kendoWindow").close();
    });
};
