<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

/**
 * Limitador de Requisições - Proteção contra abuso e ataques DoS
 */
class RateLimit
{
    /**
     * Instância do Cache para armazenamento
     * @var mixed
     */
    private static $cache;

    /**
     * Identificador do cliente (ip ou user)
     * @var string
     */
    private static $identifier = 'ip';

    /**
     * Define se deve enviar cabeçalhos HTTP de rate limit
     * @var bool
     */
    private static $headers = true;

    /**
     * Método responsável por inicializar o limitador
     * @param mixed $cache
     * @param string $identifier
     * @param bool $addHeaders
     */
    public static function init($cache = null, $identifier = 'ip', $addHeaders = true)
    {
        self::$cache = $cache ?? Cache::class;
        self::$identifier = $identifier;
        self::$headers = $addHeaders;
    }

    /**
     * Verifica e incrementa o limite de requisições
     * @param string $key
     * @param int $limit
     * @param int $window (segundos)
     * @param string $identifier
     * @return array
     */
    public static function check($key, $limit = 100, $window = 3600, $identifier = null)
    {
        $identifier = $identifier ?? self::getIdentifier();
        
        // Se estiver na whitelist, não limita
        if (self::isWhitelisted($identifier)) {
            return ['limit' => $limit, 'remaining' => $limit, 'exceeded' => false];
        }

        $cacheKey = "ratelimit_{$key}_{$identifier}";

        // Obtém contagem atual
        $current = Cache::get($cacheKey, 0);

        // Incrementa
        Cache::increment($cacheKey);

        // Define o tempo de expiração na primeira requisição da janela
        if ($current === 0) {
            Cache::set($cacheKey, 1, $window);
        }

        $current = Cache::get($cacheKey, 0);
        $remaining = max(0, $limit - $current);
        $resetAt = time() + $window;

        $result = [
            'limit' => $limit,
            'current' => $current,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'exceeded' => $current > $limit
        ];

        // Logs
        if ($current > $limit) {
            Logger::warning('Limite de requisições excedido', [
                'key' => $key,
                'identifier' => $identifier,
                'current' => $current,
                'limit' => $limit
            ]);
        } else {
            Logger::debug('Verificação de Rate Limit', [
                'key' => $key,
                'remaining' => $remaining
            ]);
        }

        // Adiciona cabeçalhos de controle na resposta
        if (self::$headers) {
            header("X-RateLimit-Limit: $limit");
            header("X-RateLimit-Remaining: $remaining");
            header("X-RateLimit-Reset: $resetAt");
        }

        return $result;
    }

    /**
     * Verifica limite por endpoint específico
     * @param string $endpoint
     * @param int $limit
     * @param int $window
     * @return array
     */
    public static function checkEndpoint($endpoint, $limit = 50, $window = 3600)
    {
        $key = "endpoint_" . md5($endpoint);
        return self::check($key, $limit, $window);
    }

    /**
     * Verifica limite por usuário
     * @param int $userId
     * @param int $limit
     * @param int $window
     * @return array
     */
    public static function checkUser($userId, $limit = 1000, $window = 3600)
    {
        $key = "user_$userId";
        return self::check($key, $limit, $window);
    }

    /**
     * Obtém o identificador atual (IP ou ID de Usuário)
     * @return string
     */
    private static function getIdentifier()
    {
        switch (self::$identifier) {
            case 'user':
                return 'auth_user'; // Integração futura com sistema de Auth
            case 'ip':
            default:
                return self::getClientIp();
        }
    }

    /**
     * Retorna o endereço de IP real do cliente
     * @return string
     */
    private static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    /**
     * Reseta o limite para uma chave e identificador específicos
     * @param string $key
     * @param string $identifier
     */
    public static function reset($key, $identifier = null)
    {
        $identifier = $identifier ?? self::getIdentifier();
        $cacheKey = "ratelimit_{$key}_{$identifier}";
        Cache::forget($cacheKey);

        Logger::info('Rate limit resetado', ['key' => $key, 'identifier' => $identifier]);
    }

    /**
     * Verifica se o identificador está na lista branca
     * @param string $identifier
     * @return bool
     */
    public static function isWhitelisted($identifier = null)
    {
        $identifier = $identifier ?? self::getIdentifier();

        $whitelist = [
            '127.0.0.1',
            'localhost',
            '::1'
        ];

        return in_array($identifier, $whitelist);
    }
}
