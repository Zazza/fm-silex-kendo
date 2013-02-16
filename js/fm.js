$(document).ready(function() {

    $("#menu").kendoMenu();

    $("#loadWin").kendoWindow({title: "Upload files"});
    $("#load").click(function(){
        $("#loadWin").data("kendoWindow").open();
    });
    $("#loadWin").data("kendoWindow").center();

    $("#settingsWin").kendoWindow({title: "Settings", height: 250, width: 400, resizable: false, scrollable: false});
    $("#settings").click(function(){
        $("#settingsWin").data("kendoWindow").open();
    });
    $("#settingsWin_wnd_title").html("<img src='img/menu/gear.png' style='height: 16px;' /> <span style='position: relative; top: -2px;'>Settings</span>");
    $("#settingsWin").data("kendoWindow").center();

    $("#fileRes").kendoWindow({title: "URL:", resizable: false });

    $("#tabstrip").kendoTabStrip({
        animation:	{
            open: {
                effects: "fadeIn"
            }
        }

    });

    $("#files").kendoUpload({
        async: {
            saveUrl: "save",
            autoUpload: true
        },
        success: function(e) {
            $.each(e.files, function(key, value) {
                $.ajax({type:"POST",url:"file",data:"file="+encodeURIComponent(value.name),success: function(e){$("#fm_uploadDir").append(e);}});
            })
        },
        error: function(e) {
            alert(e.XMLHttpRequest.responseText);
        }
    });

    var isResizing = false;
    if (!$("#window").data("kendoWindow")) {
        $("#window").kendoWindow({
            width: $("#wwidth").val(),
            minWidth: 700,
            height: $("#wheight").val(),
            resize: function() {
                isResizing = true;
                var splitter = $("#splitter").data("kendoSplitter");
                splitter.trigger("resize");
                $("#splitter").height($("#window").height() - 32);
            }
        });
    }
    $("#window").data("kendoWindow").center();
    $("#window_wnd_title").html("<img src='favicon.png' style='height: 16px;' /> <span style='position: relative; top: -2px;'>File Manager</span>");

    $('body').on('mouseup', '.k-window', function() {
        if(isResizing){
            $.ajax({ type: "POST", url: "resize", data: "&height=" + $("#window").height() + "&width=" + $("#window").width() });
            isResizing = false;
        }
    });

    $("#splitter").height($("#window").height() - 32);

    $("#splitter").kendoSplitter({
        panes: [
            { size: "200px", resizable: true, min: "100px", max: "300px" },
            { resizable: true, scrollable: false }
        ]
    });

    $("#vertical").kendoSplitter({
        orientation: "vertical",
        panes: [
            { size: "21px", resizable: false },
            { scrollable: true, resizable: false }
        ]
    });

    var splitter = $("#splitter").data("kendoSplitter");
    splitter.ajaxRequest("#structure", "chdir", { id: $("#start_dir").val() });

    var fs = new kendo.data.HierarchicalDataSource({
        transport: {
            read: {
                url: "fs",
                dataType: "json"
            }
        },
        schema: {}
    });

   $("#treeview").kendoTreeView({
        dataSource: fs,
        select: function(e) {
            var data = $('#treeview').data('kendoTreeView').dataItem(e.node);

            var splitter = $("#splitter").data("kendoSplitter");
            splitter.ajaxRequest("#structure", "chdir", { id: data.id });
        },
        animation: {
            expand: {
                duration: 0,
                hide: false,
                show: false
            },
            collapse: {
                duration: 0,
                show: false
            }
        },
        expand: function(e) {
            var dataItem = this.dataItem(e.node);
            dataItem.loaded(false);
        }
    });

    $("#newDirWin").click(function(){
        createDirDialog();
    });

    function createDirDialog() {
        var fname = prompt("Folder name:", "");
        if (fname != null) {

            var treeview = $("#treeview").data("kendoTreeView");
            var selectNode = treeview.select();

            if ($(selectNode).parent().find(".k-icon").length) {
                if ($(selectNode).parent().find(".k-minus").length) {
                    treeview.collapse(selectNode);
                }
            }

            var data = "name=" + fname;
            $.ajax({ type: "POST", url: "create", data: data })
                .done(function(res) {
                    treeview.dataItem(selectNode).load();

                    if ($(selectNode).parent().find(".k-icon").length) {
                        treeview.expand(selectNode);
                    }

                    // added folder to right div splitter
                    $("#fm_uploadDir").append(res);
                })
                .fail(function(res) { alert(res.responseText); })
        }

        return true;
    }

    $("#splitter").on("click", "#fm_sel", function() {
        $(".fm_unsellabel").removeClass("fm_unsellabel").addClass("fm_sellabel");
    });

    $("#splitter").on("click", "#fm_unsel", function() {
        $(".fm_sellabel").removeClass("fm_sellabel").addClass("fm_unsellabel");
    });

    $("#structure").on("click", ".fm_unsellabel", function(){
        $(this).removeClass("fm_unsellabel").addClass("fm_sellabel");
    });

    $("#structure").on("click", ".fm_sellabel", function(){
        $(this).removeClass("fm_sellabel").addClass("fm_unsellabel");
    });

    $("#remove").click(function(){
        var treeview = $("#treeview").data("kendoTreeView");

        if (confirm("Remove?")) {
            $(".ddir > div").each(function() {
                if ($(this).attr("class") == "fm_sellabel") {
                    var fname = $(this).attr("id");
                    $.ajax({ type: "POST", url: 'rmdirs', data: "dir=" + encodeURIComponent(fname) })
                        .done(function(res) {
                            var dataSource = treeview.dataSource;
                            var dataItem = dataSource.get(res);
                            treeview.remove(treeview.findByUid(dataItem.uid));

                            $(".fm_sellabel").parent().fadeOut("fast");
                            $(".fm_sellabel").parent().removeClass("fm_sellabel");
                        })
                }
            });

            $(".dfile > div").each(function() {
                if ($(this).attr("class") == "fm_sellabel") {
                    var fname = $(this).attr("id");
                    $.ajax({ type: "POST", url: 'rmfiles', data: "file=" + encodeURIComponent(fname) })
                        .done(function(res) {
                            $(".fm_sellabel").parent().fadeOut("fast");
                            $(".fm_sellabel").removeClass("fm_sellabel");
                        })
                }
            });
        }
    });

    $("#copy").click(function(){
        copyFiles();
    });

    $("#past").click(function(){
        pastFiles();
    });

    function copyFiles() {
        var selfiles = "";

        $(".dfile > div").each(function(value) {
            if ($(this).attr("class") == "fm_sellabel") {
                selfiles += "&file[]=" + encodeURIComponent($(this).attr("id"));
            }
        });

        $.ajax({ type: "POST", url: "copy", data: selfiles, dataType: "JSON" })
            .done(function(res) {
                $.each(res, function(key, value) {
                    if (key == "buffer")
                        $("#buffer").html(value);
                    if (key == "count")
                        $("#buffer_count").html("("+value+")");
                });
            })
    }

    function pastFiles() {
        $.ajax({ type: "POST", url: "past", dataType: "JSON" })
            .done(function(res) {
                $("#buffer").html("");
                $("#buffer_count").html("(0)");
                $.each(res, function(key, value) {
                    $("#fm_uploadDir").append(value);
                });
            })
            .fail(function(res) {
                alert(res.responseText);
            })
    }

    $(".view").click(function(){
        $.ajax({ type: "POST", url: "view", data: "type=" + $(this).attr("data-id"), dataType: "JSON" })
            .done(function(res) { location.reload(); })
    });

    $(".sort").click(function(){
        $.ajax({ type: "POST", url: "sort", data: "type=" + $(this).attr("data-id"), dataType: "JSON" })
            .done(function(res) { location.reload(); })
    });

    $("#structure").on("dblclick", ".ddir", function(){
        var splitter = $("#splitter").data("kendoSplitter");
        splitter.ajaxRequest("#structure", "chdir", { id: $(this).attr("id") });
    });

    $("#structure").on("dblclick", ".dfile", function(){
        $("#fileRes").data("kendoWindow").open();
        $("#fileRes").data("kendoWindow").center();

        $("#fullurl").val(location.href + $("#cur_dir").val() + "/" + $(this).attr("title"));
    });
});