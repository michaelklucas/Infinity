<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

/**
 * Proteção CSRF - Cross-Site Request Forgery
 */
class CSRF
{
    /**
     * Tamanho em bytes do token gerado
     * @var int
     */
    private static $tokenLength = 32;

    /**
     * Nome do campo do token no formulário
     * @var string
     */
    private static $tokenName = '_token';

    /**
     * Nome do cabeçalho HTTP para o token
     * @var string
     */
    private static $headerName = 'X-CSRF-Token';

    /**
     * Método responsável por inicializar a sessão CSRF
     */
    public static function initialize()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Gerar token se não existir na sessão
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
    }

    /**
     * Método responsável por gerar um novo token aleatório
     * @return string
     */
    private static function generateToken()
    {
        return bin2hex(random_bytes(self::$tokenLength));
    }

    /**
     * Método responsável por retornar o token atual da sessão
     * @return string|null
     */
    public static function getToken()
    {
        self::initialize();
        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Método responsável por retornar o campo HTML hidden do token
     * @return string
     */
    public static function field()
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Método responsável por retornar a meta tag do token
     * @return string
     */
    public static function meta()
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    /**
     * Método responsável por verificar a validade do token recebido
     * @param string $token
     * @return bool
     */
    public static function verify($token = null)
    {
        self::initialize();

        // Se o token não for passado, tenta obter da requisição automaticamente
        if ($token === null) {
            $token = self::getTokenFromRequest();
        }

        if (!$token) {
            Logger::warning('CSRF: Token não fornecido na requisição');
            return false;
        }

        // Comparação segura contra ataques de tempo
        $valid = hash_equals($_SESSION['csrf_token'] ?? '', $token);

        if (!$valid) {
            Logger::warning('CSRF: Token inválido ou expirado', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ]);
        }

        return $valid;
    }

    /**
     * Método responsável por obter o token da requisição (POST, GET ou Cabeçalhos)
     * @return string|null
     */
    private static function getTokenFromRequest()
    {
        // Verifica nos campos POST e GET
        if (isset($_POST[self::$tokenName])) return $_POST[self::$tokenName];
        if (isset($_GET[self::$tokenName])) return $_GET[self::$tokenName];

        // Verifica nos cabeçalhos HTTP
        $headers = getallheaders();
        if (isset($headers[self::$headerName])) return $headers[self::$headerName];
        if (isset($headers['X-Csrf-Token'])) return $headers['X-Csrf-Token'];
        if (isset($headers['x-csrf-token'])) return $headers['x-csrf-token'];

        return null;
    }

    /**
     * Método auxiliar de Middleware para verificação automática de CSRF
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        self::initialize();

        // Ignora verificação para métodos seguros
        if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Verifica o token nas requisições de modificação (POST, PUT, DELETE, etc.)
        if (!self::verify()) {
            Logger::error('Falha na verificação CSRF', [
                'uri' => $_SERVER['REQUEST_URI'] ?? '/',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            http_response_code(419); // 419 Page Expired (padrão CSRF)
            echo json_encode([
                'error' => 'Falha na verificação do token CSRF',
                'message' => 'Sua sessão expirou ou o envio não é seguro. Por favor, tente novamente.'
            ]);
            exit;
        }

        return $next($request);
    }

    /**
     * Método responsável por regenerar o token CSRF (recomendado após login)
     */
    public static function regenerate()
    {
        self::initialize();
        $_SESSION['csrf_token'] = self::generateToken();
        Logger::debug('Token CSRF regenerado');
    }

    /**
     * Método responsável por limpar o token CSRF da sessão
     */
    public static function clear()
    {
        self::initialize();
        unset($_SESSION['csrf_token']);
    }
}
