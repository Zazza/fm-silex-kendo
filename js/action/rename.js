var RenameClass = function(Api, Files) {
    $(".main-menu__btn-rename").click(function(){
        var oldFilename = $(".filemanager_select_node").data("name");
        $("#rename-window-textbox").val(oldFilename);

        $("#rename-window").kendoWindow({title: "Rename", minWidth: 300, resizable: false, scrollable: false});

        $("#rename-window").data("kendoWindow").open();
        $("#rename-window").data("kendoWindow").center();
    });

    $("#rename-confirm").click(function(){
        var oldFilename = $(".filemanager_select_node").data("name");
        var newFilename = $("#rename-window-textbox").val();
        var currentPath = Cookies.get("currentPath");

        var data = {
            path: encodeURIComponent(currentPath),
            oldFilename: encodeURIComponent(oldFilename),
            newFilename: encodeURIComponent(newFilename)
        };

        Api.rename(data, function(data){
            $(".filemanager_select_node").data("name", newFilename);

            var name;
            switch(Cookies.get("view")) {
                case "icon":
                    name = Files.getFileShortname(newFilename);
                    break;
                case "list":
                    name = newFilename;
                    break;
                default:
                    name = Files.getFileShortname(newFilename);
            }
            $(".filemanager_select_node").find(".node-open-link span").text(name);

            $("#rename-window").data("kendoWindow").close();
        });
    });

    $("body").on("click", "#rename-window-close", function(){
        $("#rename-window").data("kendoWindow").close();
    });

};
