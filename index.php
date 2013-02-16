<?php

require_once __DIR__.'/silex/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fm\Components\Files;
use Fm\Components\Save;

$app = new Silex\Application();

// /silex/views - for templates
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/silex/views',
));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// FALSE if use on production
$app['debug'] = true;

// INI files
$app['conf'] = parse_ini_file( __DIR__ . '/conf/app.ini', true );
$ini_path =  parse_ini_file( __DIR__ . '/conf/path.ini', true );

// REMOVE first and last chat, if char == DIRECTORY_SEPARATOR
if (mb_strpos($ini_path['upload'], DIRECTORY_SEPARATOR) == 0) {
    $ini_path['upload'] = mb_substr($ini_path['upload'], 1);
}
if (mb_strrpos($ini_path['upload'], DIRECTORY_SEPARATOR) == mb_strlen($ini_path['upload'])-1) {
    $ini_path['upload'] = mb_substr($ini_path['upload'], 0, mb_strlen($ini_path['upload'])-1);
}
$app["rel_upload"] = $ini_path['upload'];
$app["upload"] = __DIR__ . "/" . $ini_path['upload'];

// class Save
$app['save'] = $app->share(function () use ($app) {
    return new Save($app);
});
// class Files
$app['files'] = $app->share(function () use ($app) {
    return new Files($app);
});

// Set ROOT node
$app["root_tree"] = array(0 => array(
    "text" => "Upload",
    "id" => md5($app["upload"]),
    "path" => $app["upload"],
    "expanded" => true,
    "hasChildren" => true,
    "spriteCssClass" => "rootfolder"
));

// $app["session"]->get("folder"): treeview select folder absolute path
// $app["session"]->get("folder_id"): treeview select folderID
// $app["session"]->get("tree"): full tree array

// set default values
$app->before(function (Request $request) use ($app) {
    // set session $app['conf'] (app.ini)
    if (!$app["session"]->has("param")) {
        $app["session"]->set("param", $app['conf']);
    }

    // set default files view as icon
    if (!$app["session"]->has("view")) {
        $app["session"]->set("view", "icon");
    }

    if ($app["session"]->has("folderid")) {
        $array["folder_id"] = $app["session"]->get("folderid");
    } else {
        $array["folder_id"] = md5($app["upload"]);
        // set default tree array
        $app["session"]->set("tree", $app["root_tree"]);
    }

    // $app["tree"]
    if ($request->query->has("id")) {
        $app['files']->getPath($app["session"]->get("tree"), $request->query->get("id"));
        $dir = $app['files']->getPathVar();

        $tree = $app["files"]->getTree($dir);

        $app["session"]->set("tree", array_merge($app["session"]->get("tree"), $tree));
    } else {
        $tree = $app["root_tree"];
    }
    $app["tree"] = $tree;

    if ($app['request']->query->has("id")) {
        $app['files']->getPath($app["session"]->get("tree"), $app['request']->query->get("id"));
        $dir = $app['files']->getPathVar();
    } else {
        $dir = $app["upload"];
    }
    // absolute current folder
    $array["absolute"] = $dir;
    // relative current folder
    $array["relative"] = $app["rel_upload"] . mb_substr($dir, mb_strlen($app["upload"]));

    // $app["path"]["current"] - absolute path to select in treeview folder
    if ($app["session"]->has("folder")) {
        $array["current"] = $app["session"]->get("folder");
    } else {
        $array["current"] = $app['upload'];
    }

    $app["path"] = $array;
});

// default action
$app->get('/', function () use ($app) {
    return $app['twig']->render('index.twig', array(
        "param" => $app["session"]->get("param"),
        "folderid" => $app["path"]["folder_id"],
        "buffer" => $app['twig']->render('buffer.twig', array(
            "buffer" => $app["session"]->get("buffer")
        )),
        "buffer_count" => count($app["session"]->get("buffer"))
    ));
})->bind("home");

// save settings in /conf/app.ini
$app->post('/settings', function (Request $request) use ($app) {

    $app["session"]->set("param", array_merge($app["session"]->get("param"), $request->request->get("param")));

    $input = "";
    foreach( $app["session"]->get("param") as $k => $v )
    {
        $input .= $k . " = " . $v . "\n";
    }

    $f = fopen( __DIR__ . '/conf/app.ini' , "w+" );
    fwrite( $f , $input , strlen( $input ) );
    fclose( $f );

    return $app->redirect($app['url_generator']->generate('home'));
});

// get subdirs for dir with $request->query->has("id")
$app->get('/fs', function () use ($app) {
    return new Response(json_encode($app["tree"]), 200);
});

// upload files
$app->post('/save', function () use ($app) {
    $_thumbPath = $app['upload'] . "/_thumb/";
    $save = $app["save"];

    if ($save->handleUpload($app["path"]["current"], $_thumbPath)) {
        return new Response('', 200);
    } else {
        return new Response($save->getError(), 200);
    }
});

// create new folder
$app->post('/create', function (Request $request) use ($app) {
    $dir = $app["path"]["current"] . '/' . $request->request->get("name");
    if ($app["files"]->mkdir($app["path"]["current"], $request->request->get("name"))) {
        $newdir["id"] = md5($dir);
        $newdir["name"] = $request->request->get("name");
        $newdir["date"] = date("Y-m-d H:i:s");

        //$app["session"]->set("tree", array_merge($app["session"]->get("tree"), $app["files"]->getTree($dir)));
        $folder = $app['twig']->render('dir.twig', array(
                "view" => $app["session"]->get("view"),
                "dir" => $newdir)
        );
        return new Response($folder, 200);

    } else {
        return new Response(json_encode($app["files"]->getError()), 500);
    }
});

// get files for folder with $request->query->get("id")
$app->get('/chdir', function (Request $request) use ($app) {
    if ($request->query->has("id")) {
        $app["files"]->getPath($app["session"]->get("tree"), $request->query->get("id"));
        $dir = $app["files"]->getPathVar();

        // select in treeview: absolute path to folder
        $app["session"]->set("folder", $dir);
        // select in treeview: folder id
        $app["session"]->set("folderid", $request->query->get("id"));
    } else {
        $dir = $app["upload"];
    }

    $app["files"]->getFiles($dir);

    if ($app["files"]->getError() != null) {
        return new Response($app["files"]->getError(), 200);
    }

    return $app['twig']->render('files.twig', array(
        "view" => $app["session"]->get("view"),
        "files" => $app["files"]->getFolderFiles(),
        "folderpath" => $app["path"]["relative"],
        "dirs" => $app["files"]->getFolderDirs(),
        "totalsize" => $app["files"]->getFolderTotalSize()
    ));
});

// copy files ( set $app["session"]->set("buffer") )
$app->post('/copy', function (Request $request) use ($app) {
    $array = array();
    if ($app["session"]->has("buffer")) {
        $i = count($app["session"]->get("buffer"));
        $array = $app["session"]->get("buffer");
    } else {
        $i=0;
    }
    if ($request->request->has("file")) {
        foreach($request->request->get("file") as $part) {
            $flag = true;
            foreach($app["session"]->get("buffer") as $sess) {
                if ($sess["path"] == $app["session"]->get("folder").'/'.$part) {
                    $flag = false;
                }
            }
            if ($flag) {
                $array[$i]["name"] = $part;
                $array[$i]["path"] = $app["session"]->get("folder").'/'.$part;
                $array[$i]["id"] = md5($app["session"]->get("folder").'/'.$part);
                $i++;
            }
        }

        $app["session"]->set("buffer", $array);
    }

    $res["buffer"] = $app['twig']->render('buffer.twig', array(
        "buffer" => $app["session"]->get("buffer")
    ));
    $res["count"] = count($app["session"]->get("buffer"));

    return new Response(json_encode($res), 200);
});

// past files ( from $app["session"]->get("buffer") to folder with absolute path = $app["session"]->get("folder") )
$app->post('/past', function (Request $request) use ($app) {
    $result = array();

    if ($app["files"]->past($app["session"]->get("buffer"), $app["session"]->get("folder"))) {
        foreach($app["session"]->get("buffer") as $part) {
            $result[] = $app['twig']->render('file.twig', array(
                "view" => $app["session"]->get("view"),
                "file" => $app["files"]->getFile($app["session"]->get("folder"),
                    $part["name"])
            ));
        }
        $app["session"]->set("buffer", array());

        return new Response(json_encode($result), 200);
    } else {
        return new Response($app["files"]->getError(), 500);
    }
});

// resize fm window and set size to $app["session"]->set("param")
$app->post('/resize', function (Request $request) use ($app) {
    $app["session"]->set("param", array_merge(
            $app["session"]->get("param"),
            array("height" => $request->request->get("height") + 14),
            array("width" => $request->request->get("width") + 10)
        )
    );

    return new Response('', 200);
});

// remove file $request->request->get("file")
$app->post('/rmfiles', function (Request $request) use ($app) {
    if ($app["files"]->rmFiles($app["session"]->get("folder")."/".$request->request->get("file"))) {
        return new Response('', 200);
    } else {
        return new Response('', 500);
    }
});

// remove folder $request->request->get("dir")
$app->post('/rmdirs', function (Request $request) use ($app) {
    $app["files"]->rmDirs($app["session"]->get("folder")."/".$request->request->get("dir"));

    return new Response(md5($app["session"]->get("folder")."/".$request->request->get("dir")), 200);
});

// render file view (file.twig) need for upload
$app->post('/file', function (Request $request) use ($app) {
    return $app['twig']->render('file.twig', array(
        "view" => $app["session"]->get("view"),
        "file" => $app["files"]->getFile($app["session"]->get("folder"), $request->request->get("file"))
    ));
});

// clear all sessions ( $app["session"]->clear() )
$app->post('/clear', function () use ($app) {
    $app["session"]->clear();

    return new Response("", 200);
});

// clear copy buffer ( $app["session"]->remove("buffer") )
$app->post('/clearbuffer', function () use ($app) {
    $app["session"]->remove("buffer");

    return new Response("", 200);
});

// set view ( icons or list )
$app->post('/view', function (Request $request) use ($app) {
    $app["session"]->set("view", $request->request->get("type"));

    return new Response("", 200);
});

// set type of sort ( name, date or size )
$app->post('/sort', function (Request $request) use ($app) {
    $app["session"]->set("sort", $request->request->get("type"));

    return new Response("", 200);
});

$app->run();
