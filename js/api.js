var ApiClass = function() {
    /**
     * Чтение директории
     *
     * @param folderId string относительный путь к директории
     * @param sort string date|name|size
     * @param view string icon|list
     * @param callbackAddFolderToFS array files
     * @param callbackAddFileToFS array dirs
     * @param callbackDone the end
     */
    this.changeDirectory = function(folderId, sort, view, callbackAddFolderToFS, callbackAddFileToFS, callbackDone) {
        file = [];

        $.ajax({type: "GET", url: 'chdir', dataType: "JSON", data: {id: folderId}})
            .done(function(files) {
                files = files.sort(function (a, b) {
                    switch (sort) {
                        case "name":
                            return sortName(a, b);

                            break;
                        case "date":
                            return sortDate(a, b);

                            break;
                        case "size":
                            return sortSize(a, b);

                            break;
                        default:
                            return sortName(a, b);

                            break;
                    }
                });

                $.each(files, function (key, value) {
                    if (value["type"] == "folder") {
                        callbackAddFolderToFS(value, view);
                    } else if (value["type"] == "file") {
                        callbackAddFileToFS(value, view);
                    }
                });

                callbackDone();
            });
    }

    function sortDate(a, b) {
        if (a["date"] > b["date"]) {
            return 1;
        } else if (a["date"] < b["date"]) {
            return -1;
        } else {
            return 0;
        }
    }

    function sortName(a, b) {
        if (a["name"] > b["name"]) {
            return 1;
        } else if (a["name"] < b["name"]) {
            return -1;
        } else {
            return 0;
        }
    }

    function sortSize(a, b) {
        if (a["size"] > b["size"]) {
            return 1;
        } else if (a["size"] < b["size"]) {
            return -1;
        } else {
            return 0;
        }
    }


    this.mkdir = function(folderName, currentPath, view, callback) {
        mkdir(folderName, currentPath, view, callback)
    };

    function mkdir(folderName, currentPath, callback) {
        var data = {
            name: folderName,
            path: currentPath
        };
        $.ajax({ type: "POST", url: "create", data: data, dataType: "JSON" })
            .done(function(output) {
                callback(output);
            })
    };

    this.sendFile = function(path, file, progressCallback, doneCallback) {
        var xhr = new XMLHttpRequest();

        if (xhr.upload && xhr.upload.addEventListener) {
            xhr.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
                    progressCallback(xhr, e);
                }
            }, false);

            xhr.onreadystatechange = function(e) {
                if (xhr.readyState == 4) {
                    doneCallback({result: true, file: JSON.parse(xhr.responseText)});
                }
            };

            var fd = new FormData();
            fd.append('files', file);
            fd.append('name', encodeURIComponent(file.name));
            fd.append('path', encodeURIComponent(path));

            var uri = 'save';
            xhr.open("POST", uri, true);

            xhr.setRequestHeader('Cache-Control', 'no-cache');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-File-Name', encodeURIComponent(file.name));

            xhr.send(fd);
        }
    };

    this.downloadFile = function(files, progressCallback, doneCallback) {
        var xhr = new XMLHttpRequest();

        if (xhr.upload && xhr.upload.addEventListener) {
            xhr.addEventListener("progress", function(e) {
                progressCallback(xhr, e);
            }, false);

            xhr.onreadystatechange = function (data) {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        doneCallback({result: true, file: xhr.response});
                    }
                }
            };

            var fd = new FormData();
            fd.append('files', files);

            var uri = 'download';
            xhr.open("POST", uri, true);

            xhr.responseType = 'blob';

            xhr.setRequestHeader('Cache-Control', 'no-cache');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.send(fd);
        };
    };

    this.getFile = function(file, progressCallback, doneCallback) {
        var xhr = new XMLHttpRequest();

        if (xhr.upload && xhr.upload.addEventListener) {
            xhr.addEventListener("progress", function(e) {
                progressCallback(xhr, e);
            }, false);

            xhr.onreadystatechange = function (data) {
                if (xhr.readyState == 4) {
                    doneCallback({result: true, file: xhr.response});
                }
            };

            var uri = 'get?file=' + encodeURIComponent(file);
            xhr.open("GET", uri, true);

            xhr.responseType = 'blob';

            xhr.setRequestHeader('Cache-Control', 'no-cache');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.send();
        };
    };

    this.remove = function(currentPath, nodeName, callback){
        $.ajax({ type: "POST", url: 'remove', data: {path: currentPath, data: nodeName} })
            .done(function(data) {
                callback(data);
            });
    };

    this.rename = function(data, callback){
        $.ajax({type: "POST", url: 'rename', data: data})
            .done(function(data) {
                callback(data);
            });
    };

    this.copy = function(nodeName, sourcePath, destination, callback){
        $.ajax({ type: "POST", url: "copy", data: {sourcePath: encodeURIComponent(sourcePath), destination: encodeURIComponent(destination), nodes: nodeName }})
            .done(function(data) {
                callback(data);
            });
    };

    this.move = function(nodeName, sourcePath, destination, callback){
        $.ajax({ type: "POST", url: "move", data: {sourcePath: encodeURIComponent(sourcePath), destination: encodeURIComponent(destination), nodes: nodeName }})
            .done(function(data) {
                callback(data);
            });
    };
};
