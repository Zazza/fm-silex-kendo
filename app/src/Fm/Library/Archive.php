<?php
/**
 * Created by PhpStorm.
 * User: dsamotoy
 * Date: 01.02.16
 * Time: 9:39
 */
namespace Fm\Library;

/**
 * Class Archive
 * @package Fm\Library
 */
class Archive extends Container
{
    /**
     * Add and save zip archive
     * @param array $data list files and folders
     * @param string $destinationFile relative path for destination zip file
     * @return string absolute path to zip file (if true)
     * @throws \Exception
     */
    public static function zip($data, $destinationFile)
    {
        $clearDestinationFile = self::clearFilename($destinationFile);

        if (!extension_loaded('zip')) {
            throw new \Exception("Error archive create: phpzip extension");
        }
        $archive = [];
        $zip = new \ZipArchive();
        if (!$zip->open(Container::getUploaderFolder() . "/" . $clearDestinationFile, \ZIPARCHIVE::CREATE)) {
            throw new \Exception("Error archive create: create file: " . $clearDestinationFile);
        }
        foreach($data as $file) {
            $source = str_replace('\\', '/', realpath(Container::getUploaderFolder() . $file));
            if (is_dir($source) === true)
            {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
                foreach ($files as $currentFile)
                {
                    $currentFile = str_replace('\\', '/', $currentFile);
                    // Ignore "." and ".." folders
                    if( in_array(substr($currentFile, strrpos($currentFile, '/')+1), array('.', '..')) )
                        continue;
                    $currentFile = realpath($currentFile);
                    if (is_dir($currentFile) === true)
                    {
                        $zip->addEmptyDir(str_replace($source . '/', '', $currentFile . '/'));

                        $archive[] = $currentFile;
                    }
                    else if (is_file($currentFile) === true)
                    {
                        $zip->addFromString(str_replace($source . '/', '', $currentFile), file_get_contents($currentFile));

                        $archive[] = $currentFile;
                    }
                }
            }
            else if (is_file($source) === true)
            {
                $zip->addFromString(basename($source), file_get_contents($source));

                $archive[] = $source;
            }
        }

        if (!$zip->close()) {
            throw new \Exception("Error archive create: save file: " . $clearDestinationFile);
        }

        if (count($archive) == 0) {
            throw new \Exception("Error archive create: empty");
        }

        return Container::getUploaderFolder() . "/" . $clearDestinationFile;
    }
}
