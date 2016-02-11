var MoveClass = function(Api, Files) {
    const MOVETREE_BLOCK = "#move-tree .mCSB_container";

    $("#move-tree").mCustomScrollbar({
        axis: "xy",
        theme: "minimal-dark"
    });

    $("#move-window").kendoWindow({title: "Copy", minWidth: 300, resizable: false, scrollable: false});
    $(MOVETREE_BLOCK).kendoTreeView({
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

    $(".main-menu__btn-move").click(function(){
        $("#move-window").data("kendoWindow").open();
        $("#move-window_wnd_title").html('<span class="fa fa-mail-forward"></span> Move');
        $("#move-window").data("kendoWindow").center();
    });

    $("#move-window").on("click", "#move-confirm", function(){
        var data = $(MOVETREE_BLOCK).data('kendoTreeView');
        var selected = data.select();
        var destination = data.dataItem(selected).id;

        var sourcePath = Cookies.get('currentPath');

        var nodeName = {};
        $(".filemanager_select_node").each(function(key, file) {
            nodeName[key] = {
                path: encodeURIComponent($(file).data("name"))
            };
        });

        Api.move(nodeName, sourcePath, destination, function(data){
            $(".filemanager_select_node").each(function(key, file) {
                $(file).remove();
            });

            $("#move-window").data("kendoWindow").close();
        });
    });

    $("#move-window").on("click", "#move-window-close", function(){
        $("#move-window").data("kendoWindow").close();
    });
};
