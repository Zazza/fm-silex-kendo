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
    $("#settingsWin_wnd_title").html("<img src='img/menu/gear.png' style='height: 16px;' /> <span style='position: relative; top: -2px;'>Ostora FM Light</span>");
    $("#settingsWin").data("kendoWindow").center();

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

    if (!$("#window").data("kendoWindow")) {
        $("#window").kendoWindow({
            width: $("#wwidth").val(),
            minWidth: 700,
            height: $("#wheight").val(),
            resize: function() {
                var splitter = $("#splitter").data("kendoSplitter");
                splitter.trigger("resize");
                $("#splitter").height($("#window").height() - 32);
            }
        });
    }
    $("#window").data("kendoWindow").center();
    $("#window_wnd_title").html("<img src='favicon.png' style='height: 16px;' /> <span style='position: relative; top: -2px;'>Ostora FM Light</span>");

    $('body').on('mouseup', '.k-window', function() {
        $.ajax({ type: "POST", url: "resize", data: "&height=" + $("#window").height() + "&width=" + $("#window").width() });
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
        }
    });

    $("#newDirWin").click(function(){
        createDirDialog();
    });

    function createDirDialog() {
        var fname = prompt("Folder name:", "");

        if (fname != null) {
            var data = "name=" + fname;
            $.ajax({
                type: "POST",
                url: "create",
                data: data,
                dataType: "JSON",
                success: function(res) {
                    var treeview = $("#treeview").data("kendoTreeView");
                    $.each(res, function(key, value){
                        if (key == "tree")
                            treeview.append({text: value, spriteCssClass: 'folder'}, treeview.select());
                        if (key == "structure")
                            $("#fm_uploadDir").append(value);
                    })
                },
                error: function(res) {
                    alert(res.responseText);
                }
            });
        }

        return true;
    }

    $("#fm_sel").live("click", function() {
        $(".fm_unsellabel").removeClass("fm_unsellabel").addClass("fm_sellabel");
    });

    $("#fm_unsel").live("click", function() {
        $(".fm_sellabel").removeClass("fm_sellabel").addClass("fm_unsellabel");
    });

    $(".fm_unsellabel").live("click", function(){
        $(this).removeClass("fm_unsellabel").addClass("fm_sellabel");
    });

    $(".fm_sellabel").live("click", function(){
        $(this).removeClass("fm_sellabel").addClass("fm_unsellabel");
    });

    $("#remove").click(function(){
        if (confirm("Remove?")) {
            $(".ddir > div").each(function(value) {
                if ($(this).attr("class") == "fm_sellabel") {
                    var fname = $(this).attr("id");
                    $.ajax({
                        type: "POST",
                        url: 'rmdirs',
                        data: "dir=" + encodeURIComponent(fname),
                        success: function(res) {
                            $(".fm_sellabel").parent().fadeOut("fast");
                            $(".fm_sellabel").parent().removeClass("fm_sellabel");
                        }
                    });
                }
            });

            $(".dfile > div").each(function(value) {
                if ($(this).attr("class") == "fm_sellabel") {
                    var fname = $(this).attr("id");
                    $.ajax({
                        type: "POST",
                        url: 'rmfiles',
                        data: "file=" + encodeURIComponent(fname),
                        success: function(res) {
                            $(".fm_sellabel").parent().fadeOut("fast");
                            $(".fm_sellabel").removeClass("fm_sellabel");
                        }
                    });
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

        $(".ddir > div").each(function(value) {
            if ($(this).attr("class") == "fm_sellabel") {
                selfiles += "&dir[]=" + encodeURIComponent($(this).attr("id"));
            }
        });

        $.ajax({
            type: "POST",
            url: "copy",
            data: selfiles,
            dataType: "JSON",
            success: function(res) {
                $.each(res, function(key, value) {
                    if (key == "buffer")
                        $("#buffer").html(value);
                    if (key == "count")
                        $("#buffer_count").html("("+value+")");
                });
            }
        });
    }

    function pastFiles() {
        $.ajax({
            type: "POST",
            url: "past",
            dataType: "JSON",
            success: function(res) {
                $("#buffer").html("");
                $("#buffer_count").html("(0)");
                $.each(res, function(key, value) {
                    $("#fm_uploadDir").append(value);
                });
            },
            error: function(res) {
                alert(res.responseText);
            }
        });
    }

    $(".view").click(function(){
        $.ajax({
            type: "POST",
            url: "view",
            data: "type=" + $(this).attr("data-id"),
            dataType: "JSON",
            success: function(res) {
                location.reload();
            }
        });
    });

    $(".sort").click(function(){
        $.ajax({
            type: "POST",
            url: "sort",
            data: "type=" + $(this).attr("data-id"),
            dataType: "JSON",
            success: function(res) {
                location.reload();
            }
        });
    });

    $(".ddir").live("dblclick", function(){
        var splitter = $("#splitter").data("kendoSplitter");
        splitter.ajaxRequest("#structure", "chdir", { id: $(this).attr("id") });
    });
});