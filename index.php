<?php

require_once __DIR__.'/silex/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fm\Components\Files;
use Fm\Components\Save;

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/silex/views',
));
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['debug'] = true;

$app['conf'] = $app->share(function () use ($app) {
    return parse_ini_file( __DIR__ . '/conf/app.ini', true );
});

$app['path'] = $app->share(function () use ($app) {
    return parse_ini_file( __DIR__ . '/conf/path.ini', true );
});

$app['save'] = function () use ($app) {
    return new Save($app);
};

$app['files'] = function () use ($app) {
    return new Files($app);
};



if ($app["session"]->has("param")) {
    $param = $app["session"]->get("param");
    $app["session"]->set("param", $param);
    $app["param"] = $param;
} else {
    $app["session"]->set("param", $app['conf']);
    $app["param"] = $app['conf'];
}
$app["upload"] = __DIR__ . $app['path']['upload'];
if (!$app["session"]->has("dir")) {
    $app["session"]->set("dir", array());
}
if (!$app["session"]->has("file")) {
    $app["session"]->set("file", array());
}
if (!$app["session"]->has("view")) {
    $app["session"]->set("view", "icon");
}



$app->get('/', function () use ($app) {
    if ($app["session"]->has("folderid")) {
        $select = $app["session"]->get("folderid");
    } else {
        $path = mb_substr($app["upload"], 0, mb_strlen($app["upload"])-1);
        $select = md5($path);
        $tree = array(0 => array("text" => "Upload", "id" => $select, "path" => $path, "expanded" => true, "hasChildren" => $app["files"]->hasChildren($path), "spriteCssClass" => "rootfolder"));
        $app["session"]->set("tree", $tree);
    }

    return $app['twig']->render('index.twig', array(
        "param" => $app["session"]->get("param"),
        "select" => $select,
        "buffer" => $app['twig']->render('buffer.twig', array("file" => $app["session"]->get("file"), "dir" => $app["session"]->get("dir"))),
        "buffer_count" => count($app["session"]->get("file")) + count($app["session"]->get("dir"))
    ));
})->bind("home");

$app->post('/settings', function (Request $request) use ($app) {

    $app["param"] = $request->request->get("param");
    $app["session"]->set("param", array_merge($app["session"]->get("param"), $app["param"]));

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

$app->get('/fs', function (Request $request) use ($app) {
    $model = $app["files"];
    if ($request->query->has("id")) {
        $model->getPath($app["session"]->get("tree"), $request->query->get("id"));
        $dir = $model->getPathVar();

        $tree = $app["files"]->getTree($dir);

        $app["session"]->set("tree", array_merge($app["session"]->get("tree"), $tree));
    } else {
        $path = mb_substr($app["upload"], 0, mb_strlen($app["upload"])-1);
        $tree = array(0 => array("text" => "Upload", "id" => md5($path), "path" => $path, "expanded" => true, "hasChildren" => $app["files"]->hasChildren($path), "spriteCssClass" => "rootfolder"));
    }

    return new Response(json_encode($tree), 200);
});

$app->post('/save', function () use ($app) {
    if ($app["session"]->has("folder")) {
        $sPath = $app["session"]->get("folder");
    } else {
        $sPath = $app['upload'];
    }

    $_thumbPath = $app['upload'] . "/_thumb/";
    $save = $app["save"];

    if ($save->handleUpload($sPath, $_thumbPath)) {
        return new Response('', 200);
    } else {
        return new Response($save->getError(), 200);
    }
});

$app->post('/create', function (Request $request) use ($app) {
    $model = $app["files"];
    if ($app["session"]->has("folder")) {
        $path = $app["session"]->get("folder");
    } else {
        $path = $app["upload"];
    }

    $dir = $path . '/' . $request->request->get("name");

    if ($model->mkdir($dir)) {
        $array["tree"] = $request->request->get("name");

        $newdir["id"] = md5($dir);
        $newdir["name"] = $request->request->get("name");
        $newdir["date"] = date("Y-m-d H:i:s");
        $array["structure"] = $app['twig']->render('dir.twig', array("view" => $app["session"]->get("view"), "dir" => $newdir));
        return new Response(json_encode($array), 200);

    } else {
        return new Response(json_encode($model->getError()), 500);
    }
});

$app->get('/chdir', function (Request $request) use ($app) {
    $model = $app["files"];
    if ($request->query->has("id")) {
        $model->getPath($app["session"]->get("tree"), $request->query->get("id"));
        $dir = $model->getPathVar();

        $app["session"]->set("folder", $dir);
        $app["session"]->set("folderid", $request->query->get("id"));
    } else {
        $dir = mb_substr($app["upload"], 0, mb_strlen($app["upload"])-1);
    }

    $model->getFiles($dir);

    if ($model->getError() != null) {
        return new Response($model->getError(), 200);
    }

    return $app['twig']->render('files.twig', array(
        "view" => $app["session"]->get("view"),
        "files" => $model->getFolderFiles(),
        "dirs" => $model->getFolderDirs(),
        "totalsize" => $model->getFolderTotalSize()
    ));
});

$app->post('/copy', function (Request $request) use ($app) {
    $array = array();
    if ($app["session"]->has("dir")) {
        $i = count($app["session"]->get("dir"));
        $array = $app["session"]->get("dir");
    } else {
        $i=0;
    }
    if ($request->request->has("dir")) {
        foreach($request->request->get("dir") as $part) {
            $flag = true;
            foreach($app["session"]->get("dir") as $sess) {
                if ($sess["path"] == $app["session"]->get("folder").'/'.$part) {
                    $flag = false;
                }
            }
            if ($flag) {
                $array[$i]["name"] = $part;
                $array[$i]["path"] = $app["session"]->get("folder").'/'.$part;
                $i++;
            }
        }
        $app["session"]->set("dir", $array);
    }

    $array = array();
    if ($app["session"]->has("file")) {
        $i = count($app["session"]->get("file"));
        $array = $app["session"]->get("file");
    } else {
        $i=0;
    }
    if ($request->request->has("file")) {
        foreach($request->request->get("file") as $part) {
            $flag = true;
            foreach($app["session"]->get("file") as $sess) {
                if ($sess["path"] == $app["session"]->get("folder").'/'.$part) {
                    $flag = false;
                }
            }
            if ($flag) {
                $array[$i]["name"] = $part;
                $array[$i]["path"] = $app["session"]->get("folder").'/'.$part;
                $i++;
            }
        }

        $app["session"]->set("file", $array);
    }

    $res["buffer"] = $app['twig']->render('buffer.twig', array("file" => $app["session"]->get("file"), "dir" => $app["session"]->get("dir")));
    $res["count"] = count($app["session"]->get("file")) + count($app["session"]->get("dir"));

    return new Response(json_encode($res), 200);
});

$app->post('/past', function (Request $request) use ($app) {
    $result = array();

    $model=$app["files"];
    $source = array_merge($app["session"]->get("dir"), $app["session"]->get("file"));
    if ($model->past($source, $app["session"]->get("folder"))) {
        foreach($app["session"]->get("file") as $part) {
            $result[] = $app['twig']->render('file.twig', array("view" => $app["session"]->get("view"), "file" => $app["files"]->getFile($app["session"]->get("folder"), $part["name"])));
        }
        $app["session"]->set("file", array());

        foreach($app["session"]->get("dir") as $part) {
            $dir["name"] = $part["name"];
            $dir["date"] = $model->getDate($part["path"]);
            $result[] = $app['twig']->render('dir.twig', array("view" => $app["session"]->get("view"), "dir" => $dir));
        }
        $app["session"]->set("dir", array());


        return new Response(json_encode($result), 200);
    } else {
        return new Response($model->getError(), 500);
    }
});

$app->post('/resize', function (Request $request) use ($app) {
    $app["session"]->set("param", array_merge(
        $app["session"]->get("param"),
        array("height" => $request->request->get("height") + 14),
        array("width" => $request->request->get("width") + 10)
        )
    );

    return new Response('', 200);
});

$app->post('/rmfiles', function (Request $request) use ($app) {
    if ($app["files"]->rmFiles($app["session"]->get("folder")."/".$request->request->get("file"))) {
        return new Response('', 200);
    } else {
        return new Response('', 500);
    }
});

$app->post('/rmdirs', function (Request $request) use ($app) {
    $app["files"]->rmDirs($app["session"]->get("folder")."/".$request->request->get("dir"));

    return new Response('', 200);
});

$app->post('/file', function (Request $request) use ($app) {
    return $app['twig']->render('file.twig', array("view" => $app["session"]->get("view"), "file" => $app["files"]->getFile($app["session"]->get("folder"), $request->request->get("file"))));
});

$app->post('/clear', function () use ($app) {
    $app["session"]->clear();

    return new Response("", 200);
});

$app->post('/clearbuffer', function () use ($app) {
    $app["session"]->remove("dir");
    $app["session"]->remove("file");

    return new Response("", 200);
});

$app->post('/view', function (Request $request) use ($app) {
    $app["session"]->set("view", $request->request->get("type"));

    return new Response("", 200);
});

$app->post('/sort', function (Request $request) use ($app) {
    $app["session"]->set("sort", $request->request->get("type"));

    return new Response("", 200);
});
$app->run();
