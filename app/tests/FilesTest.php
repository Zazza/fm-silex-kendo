<?php
use Fm\Exception\ExceptionUpload;
use Fm\Library\Container;
use Fm\Library\Structure;
use Fm\Library\ImagePreview;
use Fm\Library\Archive;
use Fm\Node;

class FilesTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app["upload"] = __DIR__ . '/upload';
        $this->app["thumb"] =  __DIR__ . '/thumb/';

        Container::setConfiguration(
            $this->app['upload'],
            $this->app['thumb']
        );

        Container::setThumbConfig(
            320,
            240,
            '0x212a33',
            90
        );
    }

    public function tearDown()
    {
        $nodes = [
            "/TestDir",
            "/TestDir2",
            "/Copy[0]_TestDir",
            "/unix.jpg",
            "/Copy[0]_unix.jpg",
            "/Copy[1]_unix.jpg",
            "/newUnix.jpg",
            "/tmp.zip"
        ];

        foreach($nodes as $currentNode) {
            try {
                Structure::getNode($currentNode)->remove();
            } catch(\Exception $e) {

            }
        }
    }

    public function testGetRoot()
    {
        $root = Structure::getRoot();

        $this->assertTrue(is_array($root));
        $this->assertEquals($root[0]["id"], "/");
        $this->assertEquals($root[0]["text"], "Upload");
        $this->assertEquals($root[0]["expanded"], true);
    }

    public function testGetTree()
    {
        Structure::createFolder('/', 'TestDir');
        $tree = Structure::getFolders("/");

        $this->assertTrue(is_array($tree));
        $this->assertTrue(count($tree) > 0);
    }

    public function testSave()
    {
        $_FILES = [
            'files' => [
                'name' => 'unix.jpg',
                'type' => 'image/jpeg',
                'size' => 78744,
                'tmp_name' => __DIR__ . '/unix.jpg',
                'error' => 0
            ]
        ];

        try {
            Structure::fileUpload("/");
            $this->assertTrue(false);
        } catch(ExceptionUpload $e) {
            $this->assertTrue(true);
        }
    }

    public function testCreateFolder()
    {
        $result = Structure::createFolder('/', 'TestDir');

        $this->assertTrue(is_object($result));
        $this->assertEquals($result->get()["name"], 'TestDir');
        $this->assertEquals($result->get()["path"], '/TestDir');
    }

    public function testChangeDirectory()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");
        $files = Structure::getNodes("/");

        $this->assertTrue(is_array($files));
        $this->assertTrue(count($files) > 0);
    }

    public function testGetThumb()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");

        $filename = "/unix.jpg";
        $result = ImagePreview::create($filename);

        $this->assertTrue($result);
    }

    public function testRemoveThumb()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");

        $filename = "/unix.jpg";
        $result = ImagePreview::create($filename);

        $this->assertTrue($result);

        $result = ImagePreview::remove($filename);

        $this->assertTrue($result);
    }

    public function testCopyFile()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");

        $result = Structure::getNode("/unix.jpg")->copy("/");

        $this->assertTrue($result);

        $newFile = [Structure::getNode("/Copy[0]_unix.jpg")->get()];
        $this->assertEquals($newFile[0]["type"], "file");
        $this->assertEquals($newFile[0]["path"], "/Copy[0]_unix.jpg");
        $this->assertEquals($newFile[0]["name"], "Copy[0]_unix.jpg");
        $this->assertEquals($newFile[0]["size"], 78744);
    }

    public function testMoveFile()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");
        Structure::createFolder('/', 'TestDir');

        $result = Structure::getNode("/unix.jpg")->move("/TestDir/");

        $this->assertTrue($result);

        $newFile = [Structure::getNode("/TestDir/unix.jpg")->get()];
        $this->assertEquals($newFile[0]["type"], "file");
        $this->assertEquals($newFile[0]["path"], "/TestDir/unix.jpg");
        $this->assertEquals($newFile[0]["name"], "unix.jpg");
        $this->assertEquals($newFile[0]["size"], 78744);
    }

    public function testRemoveFile()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");
        $result = Structure::getNode("/unix.jpg")->remove();

        $this->assertTrue($result);

        try {
            Structure::getNode("/unix.jpg");

            $this->assertTrue(false);
        } catch(\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testCopyFolder()
    {
        Structure::createFolder('/', 'TestDir');
        Structure::createFolder('/TestDir', 'TestDir2');
        copy("unix.jpg", $this->app["upload"] . "/TestDir/TestDir2/unix.jpg");

        $result = Structure::getNode("/TestDir")->copy("/");
        $this->assertTrue($result);
    }

    public function testMoveFolder()
    {
        Structure::createFolder('/', 'TestDir');
        Structure::createFolder('/', 'TestDir2');

        $result = Structure::getNode("/TestDir")->move("/TestDir2");
        $this->assertTrue($result);
    }

    public function testRemoveFolder()
    {
        Structure::createFolder('/', 'TestDir');
        $result = Structure::getNode("/TestDir")->remove();

        $this->assertTrue($result);
    }

    public function testRenameFile()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");

        $result = Structure::getNode("/unix.jpg")->rename("newUnix.jpg");

        $this->assertTrue($result);

        $newFile = [Structure::getNode("/newUnix.jpg")->get()];
        $this->assertEquals($newFile[0]["type"], "file");
        $this->assertEquals($newFile[0]["path"], "/newUnix.jpg");
        $this->assertEquals($newFile[0]["name"], "newUnix.jpg");
        $this->assertEquals($newFile[0]["size"], 78744);
    }

    public function testRenameFolder()
    {
        Structure::createFolder('/', 'TestDir');

        $result = Structure::getNode("/TestDir")->rename("TestDir2");

        $this->assertTrue($result);
    }


    public function testGetFile()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");

        $result = [Structure::getNode("/unix.jpg")->get()];
        $this->assertEquals($result[0]["type"], "file");
        $this->assertEquals($result[0]["path"], "/unix.jpg");
        $this->assertEquals($result[0]["name"], "unix.jpg");
        $this->assertEquals($result[0]["size"], 78744);
    }

    public function testDownloadFile()
    {
        copy("unix.jpg", $this->app["upload"] . "/unix.jpg");

        Structure::getNode("/unix.jpg")->copy("/");

        $zipName = "tmp.zip";

        $files = ["/unix.jpg", "/Copy[0]_unix.jpg"];

        $result = Archive::zip($files, $zipName);
        $this->assertEquals($result, $this->app["upload"] . "/" . $zipName);
    }
}
