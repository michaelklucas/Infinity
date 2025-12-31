<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package Project
 */

error_reporting(E_ALL);
// Desativar display_errors para APIs (erros serão tratados pela Response)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/storage/logs/php_errors.log');

// Criar diretório de logs se não existir
if (!is_dir(__DIR__.'/storage/logs')) {
    @mkdir(__DIR__.'/storage/logs', 0755, true);
}

// Handler de erros para APIs
set_error_handler(function($severity, $message, $file, $line) {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        // Se for API, retornar JSON
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'message' => 'Erro interno do servidor',
            'error' => $message,
            'file' => basename($file),
            'line' => $line
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Para outras requisições, usar handler padrão
    return false;
});

set_exception_handler(function($exception) {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        // Se for API, retornar JSON
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'message' => 'Erro interno do servidor',
            'error' => $exception->getMessage(),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Para outras requisições, lançar exceção normalmente
    throw $exception;
});

ob_start();

date_default_timezone_set('America/Sao_Paulo');

require __DIR__.'/includes/app.php';

use App\Http\Router;
$obRouter = new Router(URL);

include __DIR__.'/routes/web.php';
include __DIR__.'/routes/api.php';

$obRouter->run()->sendResponse();