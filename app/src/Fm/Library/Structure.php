<?php
/**
 * Created by PhpStorm.
 * User: dsamotoy
 * Date: 01.02.16
 * Time: 9:33
 */
namespace Fm\Library;

use Fm\Exception\ExceptionUpload;
use Fm\Node\Node;

/**
 * Action functions for folders and files
 * Class Structure
 * @package Fm\Library
 */
class Structure extends Container
{
    const EXPANDED = true;
    const NOT_EXPANDED = false;

    /**
     * Get folder or file object
     * @param string $relativePath relative path to folder or file
     * @return Node\File|Node\Folder
     * @throws \Exception
     */
    public static function getNode($relativePath)
    {
        $absolutePath = Container::getUploaderFolder() . $relativePath;

        switch($absolutePath) {
            case is_file($absolutePath):
                $clearFilePath = self::clearFilename($relativePath);
                $nodeInstance = new Node\File($relativePath);

                break;

            case is_dir($absolutePath):
                $clearFolderPath = self::clearFilename($relativePath);
                $nodeInstance = new Node\Folder($clearFolderPath);

                break;

            default:
                throw new \Exception('Node not found');
        }

        return $nodeInstance;
    }

    /**
     * Save uploaded file
     * @param string $savePath relative path to file
     * @return string relative path to saved file
     * @throws ExceptionUpload
     * @throws \Exception
     */
    public static function fileUpload($savePath)
    {
        $clearFolderPath = self::clearFolderPath($savePath);

        if (!is_writable(Container::getUploaderFolder())) {
            throw new \Exception('Upload: server error. Write in a directory: ' . Container::getUploaderFolder() . ' is impossible!');
        }

        $source = $_FILES['files']['tmp_name'];
        $filename = urldecode($_FILES['files']['name']);

        $target = Container::getUploaderFolder() . $clearFolderPath . "/" . $filename;

        if (!move_uploaded_file($source, $target)) {
            throw new ExceptionUpload('Upload: save file error.');
        }

        return $clearFolderPath . "/" . $filename;
    }

    /**
     * Create folder
     * @param string $path relative path, where folder created
     * @param string $name folder name
     * @return Node\Folder
     * @throws \Exception
     */
    public static function createFolder($path, $name)
    {
        $clearPath = self::clearFolderPath($path);
        $clearName = self::clearFolderPath($name);

        $fullPath = Container::getUploaderFolder() . $clearPath . "/" . $clearName;
        if (mkdir($fullPath)) {
            return self::getNode($clearPath . "/" . $clearName);
        } else {
            throw new \Exception("Error: make directory: " . $fullPath);
        }
    }

    /**
     * Get root folders objects (for treeview)
     * @return array [0]=>object
     * @throws \Exception
     */
    public static function getRoot()
    {
        $tree[] = self::getNode("/")->toTreeview(self::EXPANDED);

        return $tree;
    }

    /**
     * Get folders objects (for treeview)
     * @param string $itemPath relative path
     * @return array  [0]=>object,[1]=>object,...
     * @throws \Exception
     */
    public static function getFolders($itemPath)
    {
        $clearFolderPath = self::clearFolderPath($itemPath);

        $tree = [];

        $neededPath = Container::getUploaderFolder() . $clearFolderPath;

        if ($handle = opendir($neededPath)) {
            while (false !== ($node = readdir($handle))) {

                if (($node != '.') and ($node != '..')) {
                    $path = $neededPath . "/" . $node;

                    if (is_dir($path)) {
                        $tree[] = self::getNode($clearFolderPath . "/" . $node)->toTreeview(self::NOT_EXPANDED);
                    }
                }
            }

            closedir($handle);
        }

        return $tree;
    }

    /**
     * Get files and folders objects
     * @param string $itemPath relative path
     * @return array  [0]=>object,[1]=>object,...
     * @throws \Exception
     */
    public static function getNodes($itemPath)
    {
        $clearFolderPath = self::clearFolderPath($itemPath);

        $return = [];

        $neededPath = Container::getUploaderFolder() . $clearFolderPath;

        if ($handle = opendir($neededPath)) {
            while (false !== ($file = readdir($handle))) {

                if (($file != '.') and ($file != '..') ) {
                    $return[] = self::getNode($clearFolderPath . "/" . $file)->get();
                }
            }

            closedir($handle);

            return $return;
        } else {
            throw new \Exception("Directory not readable: " . $neededPath);
        }
    }
}
