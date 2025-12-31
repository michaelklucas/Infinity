<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http\Middleware
 */

namespace App\Http\Middleware;

/**
 * Middleware de CORS - Controla o acesso Cross-Origin da aplicação
 */
class CORSMiddleware
{
    /**
     * Origens permitidas
     * @var array
     */
    private static $allowedOrigins = ['*'];

    /**
     * Métodos HTTP permitidos
     * @var array
     */
    private static $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];

    /**
     * Cabeçalhos permitidos
     * @var array
     */
    private static $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'];

    /**
     * Permite o envio de credenciais (cookies, headers auth)
     * @var bool
     */
    private static $allowCredentials = true;

    /**
     * Tempo em que os resultados do preflight são mantidos em cache (em segundos)
     * @var int
     */
    private static $maxAge = 3600;

    /**
     * Configura as políticas do CORS
     * @param array $origins
     * @param array $methods
     * @param array $headers
     */
    public static function configure($origins = [], $methods = [], $headers = [])
    {
        if (!empty($origins)) self::$allowedOrigins = $origins;
        if (!empty($methods)) self::$allowedMethods = $methods;
        if (!empty($headers)) self::$allowedHeaders = $headers;
    }

    /**
     * Manipula a requisição para adicionar os cabeçalhos CORS
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Verifica se a origem da requisição é permitida
        if (!self::isOriginAllowed($origin)) {
            // Se não for preflight (OPTIONS), apenas segue o fluxo (navegador bloqueará no cliente)
            if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
                return $next($request);
            }
            
            // Se for preflight de origem não autorizada, bloqueia imediatamente
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Origem não permitida por políticas de CORS']);
            exit;
        }

        // Define a origem permitida (ou a própria se for *)
        $allowedOrigin = in_array('*', self::$allowedOrigins) ? ($origin ?: '*') : $origin;

        // Adiciona cabeçalhos CORS na resposta
        header("Access-Control-Allow-Origin: $allowedOrigin");
        header('Access-Control-Allow-Methods: ' . implode(', ', self::$allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', self::$allowedHeaders));
        
        if (self::$allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Max-Age: ' . self::$maxAge);

        // Se for uma requisição de preflight, responde com 200 OK e encerra
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return $next($request);
    }

    /**
     * Verifica se uma origem específica é válida dentro das políticas configuradas
     * @param string $origin
     * @return bool
     */
    private static function isOriginAllowed($origin)
    {
        // Se permitir todas as origens
        if (in_array('*', self::$allowedOrigins)) return true;
        if (empty($origin)) return true;

        // Se a origem exata estiver na lista
        if (in_array($origin, self::$allowedOrigins)) return true;

        // Verifica padrões com wildcard (*.exemplo.com)
        foreach (self::$allowedOrigins as $allowed) {
            if (self::matchesDomain($allowed, $origin)) return true;
        }

        return false;
    }

    /**
     * Compara o domínio da origem contra um padrão wildcard
     * @param string $pattern
     * @param string $origin
     * @return bool
     */
    private static function matchesDomain($pattern, $origin)
    {
        $pattern = str_replace('*.', '.*', preg_quote($pattern, '/'));
        return (bool) preg_match('/^' . $pattern . '$/', $origin);
    }
}
