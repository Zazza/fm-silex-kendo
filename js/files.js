var FilesClass = function(Api) {
    const FILES_BLOCK = ".splitter-content-structure .mCSB_container"

    var files = [];

    var extension = {
        image: ['bmp', 'jpg', 'jpeg', 'gif', 'png'],
        audio: ['ogg', 'mp3'],
        video: ['mp4', 'mov', 'wmv', 'flv', 'avi', 'mpg', '3gp', 'ogv', 'webm'],
        text: ['txt'],
        doc: ['doc', 'rtf', 'docx'],
        pdf: ['pdf', 'djvu'],
        txt: ['txt', 'lst', 'ini'],
        exe: ['exe', 'com', ' bat', 'sh'],
        xls: ['xls', 'xlsx'],
        html: ['htm', 'html', 'shtml'],
        archive: ['zip', 'rar', 'tar', 'gz', '7z', 'bz2', 'gz']
    };


    var mediaTypes = {
        image: 'fa fa-file-image-o',
        doc: 'fa fa-file-word-o',
        pdf: 'fa fa-file-pdf-o',
        txt: 'fa fa-file-text-o',
        exe: 'fa fa-file-o',
        xls: 'fa fa-file-excel-o',
        audio: 'fa fa-file-audio-o',
        html: 'fa fa-file-code-o',
        archive: 'fa fa-file-archive-o',
        video: 'fa fa-file-movie-o',
        any: 'fa fa-file-o',
        folder: 'fa fa-folder'
    };

    var mimetypes = {
        'ogv': 'video/ogg',
        '3gp': 'video/3gpp'
    };

    var summarySize = 0;
    var numFiles = 0;
    var numDirs = 0;

    $(".splitter-content-structure").mCustomScrollbar({
        axis: "y",
        theme: "minimal-dark"
    });

    function setFile(data) {
        files[files.length] = data;
    }

    this.getFile = function(name, callback) {
        return getFile(name, callback);
    };
    function getFile(name, callback) {
        $.each(files, function (key, value) {
            if (value[0].name == name) {
                callback(value[0]);
            }
        });
    }

    this.changeDirectory = function (path) {
        summarySize = 0;
        numFiles = 0;
        numDirs = 0;

        Cookies.set('currentPath', path);

        var templateContent = $("#breadcrubmPathFirstTemplate").html();
        var template = kendo.template(templateContent);
        var breadcrumbPath = kendo.render(template, [{}]);
        $(".breadcrumb-path").html(breadcrumbPath);

        var sort = Cookies.get('sort');
        var view = Cookies.get('view');

        var currentPath = path.split("/");
        currentPath.shift();
        currentPath.pop();
        var pathToDir = "/";
        $.each(currentPath, function (key, value) {
            pathToDir = pathToDir + value + "/";
            var templateContent = $("#breadcrubmPathTemplate").html();
            var template = kendo.template(templateContent);
            var data = [
                {
                    id: pathToDir,
                    name: value
                }
            ];
            var breadcrumbPath = kendo.render(template, data);
            $(".breadcrumb-path").append(breadcrumbPath);
        });


        $(".splitter-content-structure .mCSB_container").html("");
        Api.changeDirectory(path, sort, view,
            function (value, view) {
                var renderHTML = addFolderToFS(value, view);

                $(".splitter-content-structure .mCSB_container").append(renderHTML);
            },
            function (value, view) {
                var renderHTML = addFileToFS(value, view);

                $(".splitter-content-structure .mCSB_container").append(renderHTML);
            },
            function () {
                $(".splitter-content-bottommenu__summarySize").text(formatSize(summarySize));
                $(".splitter-content-bottommenu__numFiles").text(numFiles);
                $(".splitter-content-bottommenu__numDirs").text(numDirs);
            }
        );
    };

    this.addFolderToFS = function (value, view) {
        return addFolderToFS(value, view);
    };
    function addFolderToFS(value, view) {
        numDirs += 1;

        if (view == "list") {
            var templateContent = $("#folderListTemplate").html();
        } else {
            var templateContent = $("#folderGridTemplate").html();
        }
        var template = kendo.template(templateContent);

        var foldername = decodeURIComponent((value["name"] + '').replace(/\+/g, '%20'));

        var data = [
            {
                id: value["id"],
                name: foldername,
                shortname: getFolderShortname(foldername),
                ico: getIco("folder"),
                date: formatDate(value["date"])
            }
        ];

        var result = kendo.render(template, data);

        return result;
    }

    this.addFileToFS = function (value, view) {
        return addFileToFS(value, view);
    };
    function addFileToFS(value, view) {
        numFiles += 1;
        summarySize += value["size"];

        var filename = decodeURIComponent((value["name"] + '').replace(/\+/g, '%20'));
        var extension = getExtension(filename);
        var type = getType(extension);

        var mimetype = getMimetype(extension, type);

        var ico = getIco(type);

        if (view == "list") {
            var templateContent = $("#fileListTemplate").html();
        } else {
            if (type == "image") {
                var templateContent = $("#imageGridTemplate").html();
            } else {
                var templateContent = $("#fileGridTemplate").html();
            }
        }

        var template = kendo.template(templateContent);

        var data = [
            {
                name: filename,
                shortname: getFileShortname(filename),
                path: decodeURIComponent(value["path"]),
                date: formatDate(value["date"]),
                size: formatSize(value["size"]),
                ico: ico,
                ext: extension,
                mimetype: mimetype,
                type: type
            }
        ];

        setFile(data);

        var result = kendo.render(template, data);

        return result;
    }

    this.getFileShortname = function (name) {
        return getFileShortname(name);
    };
    function getFileShortname(name) {
        if (name.length > 20) {
            return name.substr(0, 10) + ".." + name.substr(name.length - 2);
        } else {
            return name;
        }
    }

    this.getFolderShortname = function (name) {
        return getFolderShortname(name);
    };
    function getFolderShortname(name) {
        if (name.length > 20) {
            return name.substr(0, 10) + ".." + name.substr(name.length - 3);
        } else {
            return name;
        }
    }

    function getExtension(filename) {
        return filename.substr(filename.lastIndexOf(".") + 1);
    }

    function getType(needle) {
        var found = false, part, key;
        for (part in extension) {
            for (key in extension[part]) {
                if ((extension[part][key] === needle.toLowerCase()) || (extension[part][key] == needle.toLowerCase())) {
                    found = part;
                    break;
                }
            }
        }

        return found;
    }

    function getIco(type) {
        if (mediaTypes[type])
            return mediaTypes[type];
        else
            return mediaTypes["any"];
    }

    function getMimetype(extension, type) {
        if (mimetypes[extension]) {
            return mimetypes[extension];
        } else {
            return type + "/" + extension;
        }
    }


    function formatDate(date) {
         var timestamp = new Date(date*1000);
         var monthNames = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];
         if (timestamp.getMinutes() < 10) {
         var min = '0' + timestamp.getMinutes();
         } else {
         var min = timestamp.getMinutes() + '';
         }
         return timestamp.getHours() + ":" + min + ", " + timestamp.getDate() + "-" + monthNames[timestamp.getMonth()] + "-" + timestamp.getFullYear();
    }


    function formatSize(byteSize) {
        var size;
        if ((byteSize / 1024 / 1024) > 1) {
            size = (byteSize / 1024 / 1024).toFixed(2) + " Mb";
        } else if ((byteSize / 1024) > 1) {
            size = (byteSize / 1024).toFixed(2) + " Kb";
        } else {
            size = byteSize + " Б";
        }

        return size
    };

};
