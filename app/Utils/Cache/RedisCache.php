<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils\Cache
 */

namespace App\Utils\Cache;

use Predis\Client;

/**
 * Driver de Cache via Redis - Armazena dados serializados em memória distribuída
 */
class RedisCache
{
    /**
     * Estabelece conexão com o servidor Redis
     * @return Client
     */
    private static function connect()
    {
        return new Client([
            'scheme'   => 'tcp',
            'host'     => REDIS_HOST,
            'port'     => REDIS_PORT,
            'password' => REDIS_PASS
        ]);
    }

    /**
     * Método principal para obter do cache ou executar a função e armazenar no Redis
     * @param string $hash
     * @param int $expiration
     * @param callable $function
     * @return mixed
     */
    public static function getCache($hash, $expiration, $function)
    {
        $redis = self::connect();

        $cached = $redis->get($hash);
        if ($cached) return unserialize($cached);

        $content = $function();
        $redis->setex($hash, $expiration, serialize($content));
        return $content;
    }
}
