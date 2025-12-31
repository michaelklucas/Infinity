<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http\Middleware
 */

namespace App\Http\Middleware;

use App\Utils\Cache\File as CacheFile;
use App\Utils\Cache\RedisCache;

/**
 * Middleware de Cache - Gerência de cache de páginas e respostas
 */
class Cache
{
    /**
     * Retorna o driver de cache configurado no ambiente
     * @return string
     */
    private function getDriver()
    {
        return getenv('CACHE_DRIVER') === 'redis' ? RedisCache::class : CacheFile::class;
    }

    /**
     * Verifica se a requisição atual é passível de cache
     * @param Request $request
     * @return bool
     */
    private function isCachable($request)
    {
        // Verifica se o tempo de cache está habilitado
        if (getenv('CACHE_TIME') <= 0) return false;

        // Apenas requisições GET são cacheadas
        if ($request->getHttpMethod() != 'GET') return false;

        // Verifica headers de controle de cache
        $headers = $request->getHeaders();
        if (isset($headers['Cache-control']) && $headers['Cache-control'] == 'no-cache') return false;
        if (isset($headers['cache-control']) && $headers['cache-control'] == 'no-cache') return false;

        return true;
    }

    /**
     * Gera uma chave única (hash) para a requisição atual
     * @param Request $request
     * @return string
     */
    private function getHash($request)
    {
        $uri = $request->getRouter()->getUri();
        $queryParams = $request->getQueryParams();
        $uri .= !empty($queryParams) ? '?' . http_build_query($queryParams) : '';

        // Identificadores de contexto
        $userId = $_SESSION['usuarios']['id'] ?? 'guest';
        $domain = URL ?? 'default.local';

        $base = $userId . '-' . str_replace([':', '/'], '-', $domain . $uri);

        // Sanitiza o hash: mantém apenas caracteres seguros para sistemas de arquivos
        $hash = preg_replace('/[^0-9a-zA-Z-]/', '-', $base);
        $hash = preg_replace('/-+/', '-', $hash);
        $hash = trim($hash, '-');

        // Garante que o comprimento não exceda limites comuns
        return substr($hash, 0, 200);
    }

    /**
     * Manipula a requisição para verificar ou salvar em cache
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        // Verifica se pode cachear
        if (!$this->isCachable($request)) return $next($request);

        $hash = $this->getHash($request);
        $driver = $this->getDriver();

        // Tenta obter do cache ou executa e salva caso não exista
        return $driver::getCache($hash, getenv('CACHE_TIME'), function () use ($request, $next) {
            return $next($request);
        });
    }
}