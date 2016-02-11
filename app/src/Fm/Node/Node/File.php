<?php
/**
 * Created by PhpStorm.
 * User: dsamotoy
 * Date: 17.01.16
 * Time: 21:13
 */
namespace Fm\Node\Node;

use Fm\Library\Container;
use Fm\Node\Node;

/**
 * Class File
 * @package Fm\Node\Node
 */
class File extends Node
{
    private $type = "file";
    private $size;

    public function __construct($path)
    {
        parent::__construct($path);

        $this->name = substr($this->relativePath, strrpos($this->relativePath, "/")+1);
        $this->date = filemtime($this->absolutePath);
        $this->size = filesize($this->absolutePath);

        $a = strlen($this->relativePath); $b = strlen($this->name);
        $this->relativePathWithoutName = substr($this->relativePath, 0, $a - $b);
    }

    public function get()
    {
        return [
            "type" => $this->type,
            "path" => $this->relativePath,
            "name" => $this->name,
            "date" => $this->date,
            "size" => $this->size
        ];
    }

    public function remove()
    {
        if (unlink($this->absolutePath)) {

            $previewImage = Container::getImagePreviewFolder() . crc32($this->getRelativePathWithoutName()) . "/" . $this->name;
            if (file_exists($previewImage)) {
                unlink($previewImage);
            }

            return true;
        } else {
            throw new \Exception("File delete error");
        }
    }

    public function copy($destinationRelativePath)
    {
        $clearDestinationRelativePath = self::clearFolderPath($destinationRelativePath);

        $destination = self::genCopyName($clearDestinationRelativePath, $this->name);

        if (!copy($this->absolutePath, $destination)) {
            throw new \Exception("Copy files impossible, from: " . $this->absolutePath . ' to: ' . $destination);
        }

        return true;
    }

    public function move($destinationRelativePath)
    {
        $clearDestinationRelativePath = self::clearFolderPath($destinationRelativePath);

        $dstFile = Container::getUploaderFolder() . $clearDestinationRelativePath . "/" . $this->name;
        if (self::copy($clearDestinationRelativePath)) {
            $previewImage = Container::getImagePreviewFolder() . crc32($this->getRelativePathWithoutName()) . "/" . $this->name;
            if (file_exists($previewImage)) {
                unlink($previewImage);
            }

            if (!self::remove()) {
                throw new \Exception("Error remove directory: " . $this->relativePath);
            }
        } else {
            throw new \Exception("Move files impossible, from: " . $this->relativePath . ' to: ' . $dstFile);
        }

        return true;
    }

    public function rename($newName) {
        $clearNewName = self::clearFilename($newName);

        if (!rename($this->absolutePath, Container::getUploaderFolder() . $this->relativePathWithoutName . $clearNewName)) {
            throw new \Exception("Error rename file: " . $this->relativePath);
        }

        return true;
    }
}
