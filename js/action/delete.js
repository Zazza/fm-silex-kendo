var DeleteClass = function(Api, Files) {
    const SPLITTER_TREEVIEW_BLOCK = ".splitter-treeview .mCSB_container";

    $(".main-menu__btn-delete").click(function(){
        $("#delete-window").kendoWindow({title: "Delete", minWidth: 150, resizable: false, scrollable: false});

        $("#delete-window").data("kendoWindow").open();
        $("#delete-window").data("kendoWindow").center();
    });

    $("#delete-confirm").click(function(){
        var currentPath = Cookies.get("currentPath");
        var nodeName = {};
        $(".filemanager_select_node").each(function(key, file) {
            nodeName[key] = {
                path: encodeURIComponent($(file).data("name"))
            };
        });

        Api.remove(currentPath, nodeName, function(res){
            $(".filemanager_select_node").remove();

            $("#delete-window").data("kendoWindow").close();
        });
    });

    $("body").on("click", "#delete-window-close", function(){
        $("#delete-window").data("kendoWindow").close();
    });
};
