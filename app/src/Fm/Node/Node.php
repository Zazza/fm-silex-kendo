<?php
/**
 * Created by PhpStorm.
 * User: dsamotoy
 * Date: 22.01.16
 * Time: 1:02
 */
namespace Fm\Node;

use Fm\Library\Container;

/**
 * Class Node
 * @package Fm\Node
 */
abstract class Node extends Container
{
    protected $absolutePath;
    protected $relativePath;
    protected $relativePathWithoutName;
    protected $name;
    protected $date;

    abstract public function get();
    abstract public function remove();
    abstract public function copy($destinationRelativePath);
    abstract public function move($destinationRelativePath);
    abstract public function rename($newName);

    protected function __construct($relativePath)
    {
        $clearRelativePath = self::clearFolderPath($relativePath);

        $absolutePath = Container::getUploaderFolder() . $clearRelativePath;
        $this->absolutePath = $absolutePath;
        $this->relativePath = $clearRelativePath;
    }

    /**
     * Return copy folder name
     *
     * Example: name or Copy[0]_name (if name is occupied)
     * @param $target
     * @param $nodeName
     * @return string
     */
    protected static function genCopyName($target, $nodeName)
    {
        $clearTarget = self::clearFolderPath($target);
        $clearNodeName = self::clearFilename($nodeName);

        $simpleName = Container::getUploaderFolder() . $clearTarget . "/" . $clearNodeName;
        if (file_exists($simpleName)) {
            static $i = 0;
            $dst = Container::getUploaderFolder() . $clearTarget . "/" . "Copy[" . $i . "]_" . $clearNodeName;
            $i++;

            if (file_exists($dst)) {
                $dst = self::genCopyName($clearTarget, $clearNodeName);
            }

            return $dst;
        } else {
            return $simpleName;
        }
    }

    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    public function getRelativePath()
    {
        return $this->relativePath;
    }

    public function getRelativePathWithoutName()
    {
        return $this->relativePathWithoutName;
    }
}
