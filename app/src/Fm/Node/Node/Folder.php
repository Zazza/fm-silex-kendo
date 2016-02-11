<?php
/**
 * Created by PhpStorm.
 * User: dsamotoy
 * Date: 17.01.16
 * Time: 21:13
 */
namespace Fm\Node\Node;

use Fm\Library\Container;
use Fm\Library\Structure;
use Fm\Node\Node;

/**
 * Class Folder
 * @package Fm\Node\Node
 */
class Folder extends Node
{
    private $type = "folder";

    public function __construct($path)
    {
        parent::__construct($path);

        if ($this->relativePath == "/") {
            $this->name = "Upload";
        } else {
            $this->name = substr($this->relativePath, strrpos($this->relativePath, "/")+1);
        }

        $this->date = filemtime($this->absolutePath);

        $a = strlen($this->relativePath); $b = strlen($this->name);
        $this->relativePathWithoutName = substr($this->relativePath, 0, $a - $b);
    }

    public function toTreeview($expanded)
    {
        return [
            "id" => $this->relativePath,
            "text" => $this->name,
            "expanded" => $expanded,
            "hasChildren" => $this->hasChildren()
        ];
    }

    public function get()
    {
        return [
            "type" => $this->type,
            "path" => $this->relativePath,
            "name" => $this->name,
            "date" => $this->date,
            "size" => 0
        ];
    }

    public function remove()
    {
        if ($handle = opendir($this->absolutePath)) {
            while (false !== ($file = readdir($handle))) {

                if (($file != '.') and ($file != '..')) {
                    try {
                        Structure::getNode($this->relativePath . "/" . $file)->remove();
                    } catch(\Exception $e) {
                        throw new \Exception("Directory delete error");
                    }
                }
            }
        }

        rmdir($this->absolutePath);

        return true;
    }

    public function copy($destinationRelativePath)
    {
        $clearDestinationRelativePath = self::clearFolderPath($destinationRelativePath);
        $destination = self::genCopyName($clearDestinationRelativePath, $this->name);

        $sourceDir = $this->absolutePath;

        if (!file_exists($destination)) {
            mkdir($destination, 0770, true);
        }

        $dirIterator = new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator    = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $object) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            ($object->isDir()) ? mkdir($destPath) : copy($object, $destPath);
        }

        return true;
    }

    public function move($destinationRelativePath)
    {
        $clearDestinationRelativePath = self::clearFolderPath($destinationRelativePath);

        $dstFile = Container::getUploaderFolder() . $clearDestinationRelativePath . "/" . $this->name;
        if (!rename($this->absolutePath, $dstFile)) {
            throw new \Exception("Move files impossible, from: " . $this->relativePath . ' to: ' . $clearDestinationRelativePath . "/" . $this->name);
        }

        return true;
    }

    public function rename($newName) {
        $clearNewName = self::clearFilename($newName);

        if (!rename($this->absolutePath, Container::getUploaderFolder() . $this->relativePathWithoutName . $clearNewName)) {
            throw new \Exception("Error rename folder: " . $this->relativePath);
        }

        return true;
    }

    /**
     * Folder has children?
     * @return bool
     */
    private function hasChildren()
    {
        if ($handle = opendir($this->absolutePath)) {
            while (false !== ($file = readdir($handle))) {
                if (($file != '.') and ($file != '..')) {
                    if (is_dir($this->absolutePath . '/' . $file)) {
                        closedir($handle);

                        return true;
                    }
                }
            }
        }

        closedir($handle);

        return false;
    }
}
