<?php
require_once __DIR__ . '/app/vendor/autoload.php';

$app = require_once __DIR__ . '/app/app/services.php';
require_once __DIR__ . '/app/app/controllers.php';

$app->run();
