<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use Fm\Library\Container;
use Fm\Library\Structure;
use Fm\Library\ImagePreview;
use Fm\Library\Archive;
use Fm\Node;

const STATUS_OK = 200;
const STATUS_ERROR = 500;

$app->before(function () use ($app) {
    Container::setConfiguration(
        $app['upload'],
        $app['loader']['thumb']
    );

    Container::setThumbConfig(
        $app["ini"]['thumb']["pre_width"],
        $app["ini"]['thumb']["pre_height"],
        $app["ini"]['thumb']["rgb"],
        $app["ini"]['thumb']["quality"]
    );
});

/**
 * Index (single page app)
 */
$app->get('/', function () use ($app) {
    return $app['twig']->render('index.twig', [
        "param" => $app["ini"]['thumb']
    ]);
})->bind("home");

/**
 * Get folders in $folderId
 * Treeview
 */
$app->get('/fs', function (Request $request) use ($app) {
    if ($request->query->has("id")) {
        $folderId = $request->query->get("id");
        $fullTree = Structure::getFolders($folderId);
    } else {
        $fullTree = Structure::getRoot();
    }

    return new JsonResponse($fullTree, STATUS_OK);
});

/**
 * Upload
 */
$app->post('/save', function (Request $request) use ($app) {
    $savePath = $request->request->get("path");
    $savePath = urldecode($savePath);

    try {
        $relativeFilePath = Structure::fileUpload($savePath);
        $file[] = Structure::getNode($relativeFilePath)->get();

        return new JsonResponse($file, STATUS_OK);
    } catch (\Exception $e) {
        return new Response($e->getMessage(), STATUS_ERROR);
    }
});

/**
 * Create new folder
 */
$app->post('/create', function (Request $request) use ($app) {
    $uploadDirectory = $request->request->get("path");
    $folderName = $request->request->get("name");
    try {
        Structure::createFolder($uploadDirectory, $folderName);
        $folder[] = Structure::getNode($uploadDirectory . "/" . $folderName)->get();

        return new JsonResponse($folder, STATUS_OK);
    } catch (\Exception $e) {
        return new Response($e->getMessage(), STATUS_ERROR);
    }
});

/**
 * Get files and folders in $folderId
 */
$app->get('/chdir', function (Request $request) use ($app) {
    if ($request->query->has("id")) {
        $folderId = urldecode($request->query->get("id"));
    } else {
        $folderId = $app["upload"];
    }

    try {
        $files = Structure::getNodes($folderId);

        return new JsonResponse($files, STATUS_OK);
    } catch (\Exception $e) {
        return new Response($e->getMessage(), STATUS_ERROR);
    }
});

/**
 * Get image preview
 * Generate and save
 */
$app->get('/getThumb/', function (Request $request) use ($app) {
    $sourceFile = urldecode($request->query->get("id"));

    try {
        ImagePreview::create($sourceFile);

        $previewFilePath = ImagePreview::getPath($sourceFile);
        return $app->sendFile($previewFilePath, STATUS_OK, ['Content-Type' => mime_content_type($previewFilePath)]);
    } catch (\Exception $e) {
        return new Response($e->getMessage(), STATUS_ERROR);
    };
});

/**
 * Copy files and folders
 */
$app->post('/copy', function (Request $request) use ($app) {
    $sourcePath = $request->request->get("sourcePath");
    $destination = $request->request->get("destination");
    $nodes = $request->request->get("nodes");
    foreach ($nodes as $fileName) {
        Structure::getNode(urldecode($sourcePath) . "/" . urldecode($fileName["path"]))
            ->copy(urldecode($destination));
    }

    return new Response("", STATUS_OK);
});

/**
 * Move files and folders
 */
$app->post('/move', function (Request $request) use ($app) {
    $sourcePath = $request->request->get("sourcePath");
    $destination = $request->request->get("destination");
    $nodes = $request->request->get("nodes");
    foreach ($nodes as $fileName) {
        Structure::getNode(urldecode($sourcePath) . "/" . urldecode($fileName["path"]))
            ->move(urldecode($destination));
    }

    return new Response("", STATUS_OK);
});

/**
 * Remove files and folders
 */
$app->post('/remove', function (Request $request) use ($app) {
    $path = $request->request->get("path");
    $removeNodes = $request->request->get("data");
    foreach($removeNodes as $file) {
        Structure::getNode($path . "/" . urldecode($file["path"]))->remove();
    }
    return new Response("", STATUS_OK);
});

/**
 * Get file
 */
$app->get('/get', function (Request $request) use ($app) {
    $filePath = $request->query->get("file");
    try {
        Structure::getNode(urldecode($filePath));
        $fileFullPath = $app["upload"] . $filePath;

        return $app->sendFile($fileFullPath, STATUS_OK, ['Content-Type' => mime_content_type($filePath)]);
    } catch(\Exception $e) {
        return new Response($e->getMessage(), STATUS_ERROR);
    }
});

/**
 * Add and send zip archive
 */
$app->post('/download', function (Request $request) use ($app) {
    $get = $request->request->get("files");

    $files = explode(",", urldecode($get));

    $zipName = time() . ".zip";

    try {
        $fileFullPath = Archive::zip($files, $zipName);
    } catch(\Exception $e) {
        return new Response($e->getMessage(), STATUS_ERROR);
    }

    //$app->after(function () use ($fileFullPath) {
    $app->on(Symfony\Component\HttpKernel\KernelEvents::TERMINATE, function() use ($fileFullPath) {
        unlink($fileFullPath);
    });

    return $app->sendFile($fileFullPath, STATUS_OK, ['Content-Type' => mime_content_type($fileFullPath)]);
});

/**
 * Rename file or folder
 */
$app->post('/rename', function (Request $request) use ($app) {
    $oldFilename = $request->request->get("oldFilename");
    $newFilename = $request->request->get("newFilename");
    $currentPath = $request->request->get("path");

    try {
        Structure::getNode(urldecode($currentPath) . "/" . urldecode($oldFilename))
            ->rename(urldecode($newFilename));

        return new Response("", STATUS_OK);
    } catch (\Exception $e) {
        return new Response($e->getMessage(), STATUS_ERROR);
    }
});
