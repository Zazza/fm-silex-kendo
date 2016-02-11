var CopyClass = function(Api, Files) {
    const COPYTREE_BLOCK = "#copy-tree .mCSB_container";

    $("#copy-tree").mCustomScrollbar({
        axis: "yx",
        theme: "minimal-dark"
    });

    $("#copy-window").kendoWindow({title: "Copy", minWidth: 300, resizable: false, scrollable: false});
    $(COPYTREE_BLOCK).kendoTreeView({
        dataSource: new kendo.data.HierarchicalDataSource({
            transport: {
                read: {
                    url: "fs",
                    dataType: "json"
                }
            },
            schema: {}
        })
    });

    $(".main-menu__btn-copy").click(function(){
        $("#copy-window").data("kendoWindow").open();
        $("#copy-window_wnd_title").html('<span class="fa fa-copy"></span> Copy');
        $("#copy-window").data("kendoWindow").center();
    });

    $("#copy-window").on("click", "#copy-confirm", function(){
        var data = $(COPYTREE_BLOCK).data('kendoTreeView');
        var selected = data.select();
        var destination = data.dataItem(selected).id;

        var sourcePath = Cookies.get('currentPath');

        var nodeName = {};
        $(".filemanager_select_node").each(function(key, file) {
            nodeName[key] = {
                path: encodeURIComponent($(file).data("name"))
            };
        });

        Api.copy(nodeName, sourcePath, destination, function(data){
            Files.changeDirectory(sourcePath);

            $("#copy-window").data("kendoWindow").close();
        });
    });

    $("#copy-window").on("click", "#copy-window-close", function(){
        $("#copy-window").data("kendoWindow").close();
    });
};
