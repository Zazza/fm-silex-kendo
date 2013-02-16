<?php
namespace Fm\Components;

class Save extends Base {
    private $_filename = null;
    private $_ext = null;
    private $_source = null;
    private $_target = null;

    private function _img_resize($src, $dest, $width, $height) {
        if (!file_exists($src)) { return false; };

        $size = getimagesize($src);

        if ($size === false) { return false; };

        $format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));

        $icfunc = "imagecreatefrom" . $format;
        if (!function_exists($icfunc)) { return false; };

        $x_ratio = $width / $size[0];
        $y_ratio = $height / $size[1];


        $ratio       = min($x_ratio, $y_ratio);
        $use_x_ratio = ($x_ratio == $ratio);

        $new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio);
        $new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio);
        $new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2);
        $new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

        $isrc = $icfunc($src);
        $idest = imagecreatetruecolor($width, $height);

        imagefill($idest, 0, 0, $this->_app["conf"]["rgb"]);
        imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0,
            $new_width, $new_height, $size[0], $size[1]);

        imagejpeg($idest, $dest, $this->_app["conf"]["quality"]);

        imagedestroy($isrc);
        imagedestroy($idest);

        return true;
    }

    public function save() {
        move_uploaded_file($this->_source, $this->_target);

        return true;
    }

    public function handleUpload($uploadDirectory, $_thumbPath) {
        if (!is_writable($uploadDirectory)){
            $this->_error = 'Server error. Write in a directory: ' . $uploadDirectory . ' is impossible!';

            return false;
        }

        $this->_source = $_FILES['files']['tmp_name'];
        $this->_filename = $_FILES['files']['name'];
        $this->_ext = end(explode('.', strtolower($_FILES['files']['name'])));

        $this->_target = $uploadDirectory . "/" . $this->_filename;

        if ($this->save()) {
            if ( (strtolower($this->_ext) == "gif") or (strtolower($this->_ext) == "png") or (strtolower($this->_ext) == "jpg") or (strtolower($this->_ext) == "jpeg") ) {
                $this->_img_resize($uploadDirectory . "/" . $this->_filename, $_thumbPath . md5($uploadDirectory . "/" . $this->_filename), $this->_app["conf"]["pre_width"], $this->_app["conf"]["pre_height"]);
            };

            return true;
        } else {
            $this->_error = 'It is impossible to save the file.' . 'Cancelled, server error';

            return false;
        }

    }
}