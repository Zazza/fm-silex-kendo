$(document).ready(function() {
    const SPLITTER_TREEVIEW_BLOCK = ".splitter-treeview .mCSB_container";

    const WINDOW_MIN_WIDTH = 700;
    const WINDOW_WIDTH = 1000;
    const WINDOW_MIN_HEIGHT = 300;
    const WINDOW_HEIGHT = 600;

    var Api = new ApiClass();
    var Files = new FilesClass(Api);

    var Copy = new CopyClass(Api, Files);
    var Move = new MoveClass(Api, Files);
    var Delete = new DeleteClass(Api, Files);
    var Download = new DownloadClass(Api, Files);
    var NewFolder = new NewFolderClass(Api, Files);
    var Open = new OpenClass(Api, Files);
    var Rename = new RenameClass(Api, Files);
    var Upload = new UploadClass(Api, Files);

    var currentPath = "/";
    if (Cookies.get('currentPath')) {
        currentPath = Cookies.get('currentPath');
    }
    Files.changeDirectory(currentPath);

    var sort = "name";
    if (Cookies.get('sort')) {
        sort = Cookies.get('sort');
    }
    $(".splitter-content-bottommenu__sort[data-id="+sort+"]").addClass("k-state-selected");

    var view = "icon";
    if (Cookies.get('view')) {
        view = Cookies.get('view');
    }

    $(".splitter-content-bottommenu__view[data-id="+view+"]").addClass("k-state-selected");

    new Selecter(".splitter-content-structure", ".filemanager_node", "fm_shift", "filemanager_select_node", "filemanager_unselect_node");

    $(".main-menu").kendoMenu();
    $(".main-menu_2d").removeClass("k-header");
    $(".main-menu_2d_down").removeClass("k-header").removeClass("k-widget");

    $("#configWin").kendoWindow({title: "Configuration", width: 400, resizable: false, scrollable: false});
    $(".main-menu__btn-about").click(function(){
        $("#configWin").data("kendoWindow").open();
    });
    $("#configWin_wnd_title").html('<span class="fa fa-gears"></span> Configuration');
    $("#configWin").data("kendoWindow").center();

    $("#tabstrip").kendoTabStrip();

    var isResizing = false;
    if (!$("#file-manager").data("kendoWindow")) {

        var w_width = WINDOW_WIDTH;
        if (Cookies.get('w_width')) {
            w_width = Cookies.get('w_width');
        };

        var w_height = WINDOW_HEIGHT;
        if (Cookies.get('w_height')) {
            w_height = Cookies.get('w_height');
        };

        $("#file-manager").kendoWindow({
            width: w_width,
            minWidth: WINDOW_MIN_WIDTH,
            height: w_height,
            minHeight: WINDOW_MIN_HEIGHT,
            actions: [
                "Minimize",
                "Maximize",
                "Close"
            ],
            resize: function() {
                isResizing = true;
                var splitter = $(".splitter").data("kendoSplitter");
                splitter.trigger("resize");
            }
        });
    }
    $("#file-manager").data("kendoWindow").center();
    $("#file-manager_wnd_title").html('File Manager');

    $('body').on('mouseup', '.k-window', function() {
        if(isResizing){
            var width = $("#file-manager").width();
            var height = $("#file-manager").height();

            Cookies.set('w_width', width);
            Cookies.set('w_height', height);

            isResizing = false;
        }
    });

    $(".splitter").kendoSplitter({
        panes: [
            { size: "200px", resizable: true, min: "100px", max: "300px" },
            { resizable: true, scrollable: false }
        ]
    });

    $(".splitter-content").kendoSplitter({
        orientation: "vertical",
        panes: [
            { size: "27px", resizable: false },
            { scrollable: false, resizable: false }
        ]
    });

    var fs = new kendo.data.HierarchicalDataSource({
        transport: {
            read: {
                url: "fs",
                dataType: "json"
            }
        },
        schema: {
            model: {
                spriteCssClass: "fa fa-folder"
            }
        }
    });

    $(".splitter-treeview").mCustomScrollbar({
        axis: "yx",
        theme: "minimal-dark"
    });
    $(SPLITTER_TREEVIEW_BLOCK).kendoTreeView({
        dataSource: fs,
        select: function(e) {
            var data = $(SPLITTER_TREEVIEW_BLOCK).data('kendoTreeView').dataItem(e.node);
            Files.changeDirectory(data.id);
        },
        dataBound: function(e) {

        },
        collapse: function(e) {
            $(e.node).find("ul").remove();
            $(e.node).find(".k-minus").removeClass("k-minus").addClass("k-plus");
        },
        expand: function(e) {
            var dataItem = this.dataItem(e.node);
            dataItem.loaded(false);
        },
        animation: {
            expand: false,
            collapse: false
        }
    });


    /**
     * Go directory up
     * Example: /test/: 1) /test 2) /
     */
    $(".splitter-content-topmenu__go_up").click(function() {
        var currentPath = Cookies.get("currentPath");

        var goUpPath = currentPath.substring(0, currentPath.lastIndexOf("/"));
        var goUpPath = goUpPath.substring(0, goUpPath.lastIndexOf("/")) + "/";

        Files.changeDirectory(goUpPath);
    });


    $(".splitter-content-bottommenu__view").click(function(){
        var view = $(this).data("id");
        Cookies.set('view', view);
        Files.changeDirectory(Cookies.get('currentPath'));

        $(".splitter-content-bottommenu__view").removeClass("k-state-selected");
        $(".splitter-content-bottommenu__view[data-id="+view+"]").addClass("k-state-selected");
    });

    $(".splitter-content-bottommenu__sort").click(function(){
        var sort = $(this).data("id");
        Cookies.set('sort', sort);

        Files.changeDirectory(Cookies.get('currentPath'));

        $(".splitter-content-bottommenu__sort").removeClass("k-state-selected");
        $(".splitter-content-bottommenu__sort[data-id="+sort+"]").addClass("k-state-selected");
    });

    $(".splitter-content-structure").on("dblclick", ".filemanager_node", function(){
        var currentPath = Cookies.get('currentPath');
        var nodeName = $(this).data("name");

        if ($(this).hasClass("filemanager_folder")) {
            Files.changeDirectory(currentPath + nodeName + "/");
        } else {
            //Files.getFile(nodeName, function(data) {
            //    console.log(data);
            //});
            Open.getFileWindow(nodeName);
        }
    });

    $("body").on("click", ".breadcrumb-path-li", function(){
        var path = $(this).data("id");
        Files.changeDirectory(path);
    });
});
