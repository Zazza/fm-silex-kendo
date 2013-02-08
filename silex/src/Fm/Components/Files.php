<?php
namespace Fm\Components;

class Files extends Base {
    private $_files = array();
    private $_dirs = array();
    private $_path = "";
    private $_totalsize = 0;

    // ICO MIME TYPE
    private $_MIME = array(
        array("name" => "img", "ico" => "preview", "ext" => array("jpg", "jpeg", "gif", "png", "bmp")),
        array("name" => "doc", "ico" => "msword.png", "ext" => array("doc", "docx", "rtf", "oft")),
        array("name" => "pdf", "ico" => "pdf.png", "ext" =>  array("pdf", "djvu")),
        array("name" => "txt", "ico" => "text.png", "ext" =>  array("txt")),
        array("name" => "flv", "ico" => "flash.png", "ext" =>  array("flv")),
        array("name" => "exe", "ico" => "executable.png", "ext" =>  array("exe", "com", "bat")),
        array("name" => "xls", "ico" => "excel.png", "ext" =>  array("xls", "xlsx")),
        array("name" => "mp3", "ico" => "audio.png", "ext" =>  array("mp3", "wav", "flac")),
        array("name" => "html", "ico" => "html.png", "ext" =>  array("html", "htm", "php", "js")),
        array("name" => "zip", "ico" => "compress.png", "ext" =>  array("zip", "rar", "7z", "tar", "bz2", "gz"))
    );

    public function setIcon($ext, $fname) {
        $ico = "img/ftypes/unknown.png";

        for($i=0; $i<count($this->_MIME); $i++) {
            if (in_array(mb_strtolower($ext), $this->_MIME[$i]["ext"])) {
                $ico = $this->_MIME[$i]["ico"];
                if ($ico == "preview") {
// PATH
                    $ico = "upload/_thumb/" . $fname;
                    if (!is_readable($ico)) { $ico = "img/ftypes/image.png"; }
                } else {
                    $ico = "img/ftypes/" . $ico;
                }
            }
        }

        return $ico;
    }
    // END ICO MIME TYPE

    public function getFolderFiles() {
        return $this->_files;
    }

    public function getFolderDirs() {
        return $this->_dirs;
    }

    public function getFolderTotalSize() {
        return $this->_totalsize;
    }

    public function getPath($tree, $needle) {
        foreach($tree as $part) {
            if ($part["id"] == $needle) {
                $this->_path = $part["path"];
                return true;
            }
        }
        return false;
    }

    public function getPathVar() {
        return $this->_path;
    }

    public function getFiles($dir) {
        $files = array(); $dirs = array(); $i = 0; $k = 0; $total = 0;

        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {

                if ( ($file != '.') and ($file != '..') and ($file != '_thumb') ) {
                    $path = $dir . '/' . $file;

                    if(is_file($path)) {

                        if ($this->_app["session"]->has("sort")) {
                            if ($this->_app["session"]->get("sort") == "date") {
                                $sort_file[] = date("H:i d-m-Y",  filemtime($path));
                            } else if ($this->_app["session"]->get("sort") == "name") {
                                $sort_file[] = $file;
                            } else if ($this->_app["session"]->get("sort") == "size") {
                                $sort_file[] = filesize($path);
                            }
                        }

                        $files[$i]["name"] = $file;
                        if (mb_strlen($file) > 20) {
                            $files[$i]["shortname"] = mb_substr($file, 0, 10) . ".." . mb_substr($file, mb_strrpos($file, ".")-1, mb_strlen($file)-mb_strrpos($file, ".")+1);
                        } else {
                            $files[$i]["shortname"] = $file;
                        }

                        $ext = mb_substr($files[$i]["name"], mb_strrpos($files[$i]["name"], ".") + 1);
                        $files[$i]["ico"] = $this->setIcon($ext, $file);

                        $size = filesize($path);
                        $total += $size;
                        if (($size / 1024) > 1) { $size = round($size / 1024, 2) . " Kb"; } else { $size = round($size, 2) . " Б"; };
                        if (($size / 1024) > 1) { $size = round($size / 1024, 2) . " Mb"; };
                        $files[$i]["size"] = $size;

                        $files[$i]["date"] = date("H:i d-m-Y",  filemtime($path));

                        $i++;
                    } else {
                        if ($this->_app["session"]->has("sort")) {
                            if ($this->_app["session"]->get("sort") == "date") {
                                $sort_dir[] = date("H:i d-m-Y",  filemtime($path));
                            } else if ($this->_app["session"]->get("sort") == "name") {
                                $sort_dir[] = $file;
                            }
                        }

                        $dirs[$k]["id"] = md5($path);
                        $dirs[$k]["name"] = $file;
                        $dirs[$k]["date"] = date("H:i d-m-Y",  filemtime($path));
                        $k++;
                    }
                }
            }

            closedir($handle);

            if ($this->_app["session"]->has("sort")) {
                if ($this->_app["session"]->get("sort") == "date") {
                    array_multisort($sort_file, SORT_DESC, $files);
                } else if ($this->_app["session"]->get("sort") == "name") {
                    array_multisort($sort_file, $files);
                } else if ($this->_app["session"]->get("sort") == "size") {
                    array_multisort($sort_file, $files);
                }
            }

            $this->_files = $files;

            if ($this->_app["session"]->has("sort")) {
                if ($this->_app["session"]->get("sort") == "date") {
                    array_multisort($sort_dir, SORT_DESC, $dirs);
                } else if ($this->_app["session"]->get("sort") == "name") {
                    array_multisort($sort_dir, $dirs);
                }
            }

            $this->_dirs = $dirs;

            if (($total / 1024) > 1) { $total = round($total / 1024, 2) . " Kb"; } else { $total = round($total, 2) . " Б"; };
            if (($total / 1024) > 1) { $total = round($total / 1024, 2) . " Mb"; };
            $this->_totalsize = $total;

            return true;
        } else {
            $this->_error = "Directory not readable";
        }
    }

    public function hasChildren($path) {
        $flag = false;
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ( ($file != '.') and ($file != '..') and ($file != '_thumb') ) {
                    if (is_dir($path . '/' . $file)) {
                        $flag = true;
                    }
                }
            }
        }
        closedir($handle);

        return $flag;
    }


    public function getTree($dir) {
        $array = array(); $i = 0;

        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {

                if ( ($file != '.') and ($file != '..') and ($file != '_thumb') ) {
                    $path = $dir . '/' . $file;

                    if(is_dir($path)) {
                        $array[$i]["text"] = $file;
                        $array[$i]["id"] = md5($path);
                        $array[$i]["path"] = $path;
                        $array[$i]["hasChildren"] = $this->hasChildren($path);
                        $array[$i]["spriteCssClass"] = "folder";

                        $i++;
                    }
                }
            }

            closedir($handle);

            return $array;
        }
    }

    public function rmFiles($file) {
        if (unlink($file)) {
            return true;
        } else {
            return false;
        }
    }

    public function rmDirs($dir) {
        if ($objs = glob($dir."/*")) {
            foreach($objs as $obj) {
                is_dir($obj) ? $this->rmDirs($obj) : unlink($obj);
            }
        }

        rmdir($dir);
    }

    public function getFile($folder, $file) {
        $array = array();
        $path = $folder . "/" . $file;
        if(is_file($path)) {
            $array["name"] = $file;
            if (mb_strlen($file) > 20) {
                $array["shortname"] = mb_substr($file, 0, 10) . ".." . mb_substr($file, mb_strrpos($file, ".")-1, mb_strlen($file)-mb_strrpos($file, ".")+1);
            } else {
                $array["shortname"] = $file;
            }

            $ext = mb_substr($array["name"], mb_strrpos($array["name"], ".") + 1);
            $array["ico"] = $this->setIcon($ext, $file);

            $size = filesize($path);
            if (($size / 1024) > 1) { $size = round($size / 1024, 2) . " Kb"; } else { $size = round($size, 2) . " Б"; };
            if (($size / 1024) > 1) { $size = round($size / 1024, 2) . " Mb"; };
            $array["size"] = $size;

            $array["date"] = date("H:i d-m-Y",  filemtime($path));
        }

        return $array;
    }

    public function getDate($file) {
        return filemtime($file);
    }

    public function mkdir($dir) {
        if (mkdir($dir)) {
            return true;
        } else {
            $this->_error = "Create directory error: " . $dir;

            return false;
        }
    }

    public function past($source, $target) {
        foreach($source as $part) {
            if (is_dir($part["path"])) {
                if (!rename($part["path"], $target."/".$part["name"])) {
                    $this->_error = "Move dir impossible, from: ".$part["path"].' to: '.$target."/".$part["name"];
                    return false;
                }
            }

            if (is_file($part["path"])) {
                if (copy($part["path"], $target."/".$part["name"])) {
                    if (is_file($part["path"])) {
                        if (!unlink($part["path"])) {
                            $this->_error = "Error remove directory: " . $part["path"];
                        }
                    }
                    if (is_dir($part["path"])) {
                        if (!rmdir($part["path"])) {
                            $this->_error = "Error remove file: " . $part["path"];
                        }
                    }
                } else {
                    $this->_error = "Past files impossible, from: ".$part["path"].' to: '.$target."/".$part["name"];
                    return false;
                }
            }
        }

        return true;
    }
}