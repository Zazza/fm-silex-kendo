<?php
/**
 * Created by PhpStorm.
 * User: dsamotoy
 * Date: 01.02.16
 * Time: 9:14
 */
namespace Fm\Library;

use Fm\Library\Container;
use Fm\Library\Structure;

/**
 * Class ImagePreview
 * @package Fm\Library
 */
class ImagePreview extends Container
{
    /**
     * Generate and create thumbnail image file
     * @param string $sourceFile relative path to original image
     * @return bool true if success
     * @throws \Exception
     */
    public static function create($sourceFile)
    {
        $clearSourceFile = self::clearFolderPath($sourceFile);

        try {
            $File = Structure::getNode($clearSourceFile);
        } catch(\Exception $e) {
            throw new \Exception("Image preview create: source file doesn't exist: " . $clearSourceFile);
        }

        $size = getimagesize($File->getAbsolutePath());

        if ($size === false) {
            throw new \Exception('Image preview create: image error');
        };

        $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
        $icfunc = "imagecreatefrom" . $format;
        if (!function_exists($icfunc)) {
            throw new \Exception('Image preview create: error function: "imagecreatefrom' . $format . '()". Install GD lib');
        };

        $xRatio = Container::getWidth() / $size[0];
        $yRatio = Container::getHeight() / $size[1];

        $ratio = min($xRatio, $yRatio);
        $useXRatio = ($xRatio == $ratio);

        $new_width = $useXRatio ? Container::getWidth() : floor($size[0] * $ratio);
        $new_height = !$useXRatio ? Container::getHeight() : floor($size[1] * $ratio);
        $new_left = $useXRatio ? 0 : floor((Container::getWidth() - $new_width) / 2);
        $new_top = !$useXRatio ? 0 : floor((Container::getHeight() - $new_height) / 2);

        $isrc = $icfunc($File->getAbsolutePath());
        $idest = imagecreatetruecolor(Container::getWidth(), Container::getHeight());

        imagefill($idest, 0, 0, Container::getRgb());
        imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);

        if (!is_dir(Container::getImagePreviewFolder() . "/" . crc32($File->getRelativePathWithoutName()))) {
            mkdir(Container::getImagePreviewFolder() . "/" . crc32($File->getRelativePathWithoutName()));
        }
        $destinationFile = Container::getImagePreviewFolder() . "/" . crc32($File->getRelativePathWithoutName()) . "/" . $File->get()["name"];

        if (!imagejpeg($idest, $destinationFile, Container::getQuality())) {
            throw new \Exception('Image preview create: "imagejpeg" return false. Destination file: ' . $destinationFile);
        }

        imagedestroy($isrc);
        imagedestroy($idest);

        return true;
    }

    /**
     * Get absolute path to thumbnail image file
     * @param string $sourceFile relative path to original image
     * @return string path absolute path
     * @throws \Exception
     */
    public static function getPath($sourceFile)
    {
        $clearSourceFile = self::clearFolderPath($sourceFile);

        try {
            $File = Structure::getNode($clearSourceFile);
        } catch(\Exception $e) {
            throw new \Exception("Image preview getPath: source file doesn't exist: " . $clearSourceFile);
        }

        $destinationFile = Container::getImagePreviewFolder() . "/" . crc32($File->getRelativePathWithoutName()) . "/" . $File->get()["name"];

        return $destinationFile;
    }

    /**
     * Delete thumbnail file
     * @param string $sourceFile relative path to original image
     * @return bool true if success
     * @throws \Exception
     */
    public static function remove($sourceFile)
    {
        $clearSourceFile = self::clearFolderPath($sourceFile);

        try {
            $File = Structure::getNode($clearSourceFile);
        } catch(\Exception $e) {
            throw new \Exception("Image preview remove: source file doesn't exist: " . $clearSourceFile);
        }

        $thumbFolder = crc32($File->getRelativePathWithoutName());

        $destinationFile = Container::getImagePreviewFolder() . "/" . $thumbFolder . "/" . $File->get()["name"];
        if (!file_exists($destinationFile)) {
            throw new \Exception("Image preview remove: not found: " . "/" . $thumbFolder . "/" . $File->get()["name"]);
        }

        if (!unlink($destinationFile)) {
            throw new \Exception("Image preview remove: remove: " . "/" . $thumbFolder . "/" . $File->get()["name"]);
        }

        if (self::isThumbFolderEmpty($thumbFolder)) {
            $thumbFolderAbsolutePath = Container::getImagePreviewFolder() . "/" . $thumbFolder;
            rmdir($thumbFolderAbsolutePath);
        }

        return true;
    }

    /**
     * @param string $thumbFolder relative path
     * @return bool
     */
    private static function isThumbFolderEmpty($thumbFolder)
    {
        $thumbFolderAbsolutePath = Container::getImagePreviewFolder() . "/" . $thumbFolder;

        if ($handle = opendir($thumbFolderAbsolutePath)) {
            while (false !== ($file = readdir($handle))) {
                if (($file != '.') and ($file != '..')) {
                    return false;
                }
            }
        }

        return true;
    }
}
