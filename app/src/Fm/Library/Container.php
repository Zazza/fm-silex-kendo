<?php
/**
 * Created by PhpStorm.
 * User: dsamotoy
 * Date: 01.02.16
 * Time: 9:32
 */
namespace Fm\Library;

/**
 * Class Container
 *
 * Parent class
 * Main paths getter and setters
 * @package Fm\Library
 */
class Container {
    private static $uploadDir;
    private static $thumbDir;

    private static $width = 320;
    private static $height = 240;
    private static $rgb = 0x000000;
    private static $quality = 60;

    /**
     * Setter: main pathes
     * @param string $uploadDir absolute path to upload dir
     * @param string $thumbDir absolute path to image preview dir
     */
    public static function setConfiguration($uploadDir, $thumbDir)
    {
        self::$uploadDir = self::clearFolderPath($uploadDir);
        self::$thumbDir = self::clearFolderPath($thumbDir);
    }

    /**
     * Setter: preview image settings
     * @param int $width example: 320 (px)
     * @param int $height example: 240 (px)
     * @param string $rgb example: 0x212a33
     * @param int $quality example: 70 (%)
     */
    public static function setThumbConfig($width, $height, $rgb, $quality)
    {
        self::$width = $width;
        self::$height = $height;
        self::$rgb = $rgb;
        self::$quality = $quality;
    }

    public static function getUploaderFolder()
    {
        return self::$uploadDir;
    }

    public static function getImagePreviewFolder()
    {
        return self::$thumbDir;
    }

    public static function getWidth()
    {
        return self::$width;
    }

    public static function getHeight()
    {
        return self::$height;
    }

    public static function getRgb()
    {
        return self::$rgb;
    }

    public static function getQuality()
    {
        return self::$quality;
    }

    /**
     * Get clear relative folder path
     *
     * Example: folder/path/ => /folder/path
     * @param string $path input
     * @return string output
     */
    protected static function clearFolderPath($path)
    {
        $fullArray = explode("/", $path);
        $clearArray = array_filter($fullArray, function($element) {
            return !empty($element);
        });

        return "/" . implode("/", $clearArray);
    }

    /**
     * Get clear relative file path
     *
     * Example: folder/path.jpg/ => /folder/path.jpg
     * @param string $path input
     * @return string output
     */
    protected static function clearFilename($path)
    {
        $fullArray = explode("/", $path);
        $clearArray = array_filter($fullArray, function($element) {
            return !empty($element);
        });

        return implode("/", $clearArray);
    }
}
