<?php

error_reporting(E_ALL);

require __DIR__.'/includes/app.php';

use App\Config\ExceptionHandler;
use App\Http\Router;

// Inicializa o tratador de erros e exceções
ExceptionHandler::init();

$obRouter = new Router(URL);

include __DIR__.'/routes/web.php';
include __DIR__.'/routes/api.php';

$obRouter->run()->sendResponse();