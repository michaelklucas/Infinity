<?php

namespace App\Config;

class ExceptionHandler
{
    /**
     * Inicia o manipulador de erros e exceções
     */
    public static function init()
    {
        error_reporting(E_ALL);
        
        // Define configurações do ini baseadas no ambiente
        // Idealmente isso viria do .env, mas mantendo a lógica original por enquanto
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__.'/../../../storage/logs/php_errors.log');

        if (getenv('APP_DEBUG') === 'true') {
            ini_set('display_errors', 1);
        } else {
            ini_set('display_errors', 0);
        }

        // Criar diretório de logs se não existir
        if (!is_dir(__DIR__.'/../../../storage/logs')) {
            @mkdir(__DIR__.'/../../../storage/logs', 0755, true);
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Manipula erros PHP tradicionais
     */
    public static function handleError($severity, $message, $file, $line)
    {
        // Se for API, retornar JSON
        if (self::isApiRequest()) {
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

        // Se não for erro fatal e display_errors estiver off, loga mas não mostra
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Manipula Exceções não tratadas
     */
    public static function handleException($exception)
    {
        // Se for API, retornar JSON
        if (self::isApiRequest()) {
            $code = $exception->getCode();
            if(!is_numeric($code) || $code < 100 || $code > 599){
                $code = 500;
            }

            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'message' => $exception->getMessage(),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
                'trace' => getenv('APP_DEBUG') === 'true' ? $exception->getTrace() : null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Para web normal, renderizar uma página de erro bonita seria o ideal
        // Por enquanto, relançamos se debug estiver on, ou mostramos erro genérico
        if (getenv('APP_DEBUG') === 'true') {
            echo "<h1>Erro Fatal</h1>";
            echo "<p>{$exception->getMessage()}</p>";
            echo "<small>".basename($exception->getFile()).":{$exception->getLine()}</small>";
            echo "<pre>{$exception->getTraceAsString()}</pre>";
        } else {
             // Renderizar view de erro 500 amigável
             echo "<h1>Ops! Algo deu errado.</h1>";
             echo "<p>Por favor, tente novamente mais tarde.</p>";
        }
    }

    /**
     * Verifica se a requisição atual é para a API
     */
    private static function isApiRequest()
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    }
}
