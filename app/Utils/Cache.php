<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

use Exception;

/**
 * Sistema de Cache - Suporte a múltiplos drivers
 * 
 * Drivers suportados:
 * - file: Cache em arquivos locais
 * - redis: Cache via Redis (requer extensão redis)
 * - memcached: Cache via Memcached (requer extensão memcached)
 * - array: Cache em memória (para testes)
 * - null: Sem cache
 */
class Cache
{
    /**
     * Driver de cache atual
     * @var string
     */
    private static $driver = 'file';

    /**
     * Configurações do cache
     * @var array
     */
    private static $config = [];

    /**
     * Status de inicialização
     * @var bool
     */
    private static $initialized = false;

    /**
     * Prefixo das chaves de cache
     * @var string
     */
    private static $prefix = 'infinity_';

    /**
     * Método responsável por inicializar o cache
     * @param string $driver
     * @param array $config
     */
    public static function init($driver = 'file', $config = [])
    {
        if (self::$initialized) {
            return;
        }

        self::$driver = $driver;
        self::$config = $config;

        // Cria o diretório de cache se estiver usando o driver de arquivo
        if ($driver === 'file') {
            $cacheDir = $config['path'] ?? self::storage_path('cache');
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
        }

        self::$initialized = true;
    }

    /**
     * Obtém um valor do cache
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::ensureInitialized();

        try {
            switch (self::$driver) {
                case 'file':
                    return self::getFromFile($key, $default);
                case 'redis':
                    return self::getFromRedis($key, $default);
                case 'memcached':
                    return self::getFromMemcached($key, $default);
                case 'array':
                    return self::getFromArray($key, $default);
                default:
                    return $default;
            }
        } catch (Exception $e) {
            Logger::warning('Erro ao obter cache', ['key' => $key, 'error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * Define um valor no cache
     * @param string $key
     * @param mixed $value
     * @param int $ttl (segundos)
     * @return bool
     */
    public static function set($key, $value, $ttl = 3600)
    {
        self::ensureInitialized();

        try {
            switch (self::$driver) {
                case 'file':
                    self::setInFile($key, $value, $ttl);
                    break;
                case 'redis':
                    self::setInRedis($key, $value, $ttl);
                    break;
                case 'memcached':
                    self::setInMemcached($key, $value, $ttl);
                    break;
                case 'array':
                    self::setInArray($key, $value, $ttl);
                    break;
            }

            Logger::debug('Cache definido', ['key' => $key, 'ttl' => $ttl]);
            return true;
        } catch (Exception $e) {
            Logger::warning('Erro ao definir cache', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Verifica se uma chave existe no cache
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return self::get($key) !== null;
    }

    /**
     * Incrementa um valor no cache
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function increment($key, $value = 1)
    {
        $current = self::get($key, 0);
        $new = $current + $value;
        self::set($key, $new);
        return $new;
    }

    /**
     * Decrementa um valor no cache
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function decrement($key, $value = 1)
    {
        $current = self::get($key, 0);
        $new = $current - $value;
        self::set($key, $new);
        return $new;
    }

    /**
     * Remove um item do cache
     * @param string $key
     * @return bool
     */
    public static function forget($key)
    {
        self::ensureInitialized();

        try {
            switch (self::$driver) {
                case 'file':
                    self::forgetFile($key);
                    break;
                case 'redis':
                    self::forgetRedis($key);
                    break;
                case 'memcached':
                    self::forgetMemcached($key);
                    break;
                case 'array':
                    self::forgetArray($key);
                    break;
            }

            Logger::debug('Cache removido', ['key' => $key]);
            return true;
        } catch (Exception $e) {
            Logger::warning('Erro ao remover cache', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Lembra um cache ou executa callback para salvá-lo
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public static function remember($key, $ttl, $callback)
    {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * Limpa todo o cache
     * @return bool
     */
    public static function flush()
    {
        self::ensureInitialized();

        try {
            switch (self::$driver) {
                case 'file':
                    self::flushFile();
                    break;
                case 'redis':
                    self::flushRedis();
                    break;
                case 'memcached':
                    self::flushMemcached();
                    break;
                case 'array':
                    self::flushArray();
                    break;
            }

            Logger::info('Cache limpo', ['driver' => self::$driver]);
            return true;
        } catch (Exception $e) {
            Logger::warning('Erro ao limpar cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtém as estatísticas do cache
     * @return array
     */
    public static function stats()
    {
        self::ensureInitialized();

        $stats = [
            'driver' => self::$driver,
            'prefix' => self::$prefix,
            'count' => 0
        ];

        switch (self::$driver) {
            case 'file':
                $cacheDir = self::$config['path'] ?? self::storage_path('cache');
                $files = glob($cacheDir . '/*');
                $stats['count'] = count($files ?? []);
                $stats['size'] = array_sum(array_map('filesize', $files ?? []));
                break;
            case 'redis':
                try {
                    $redis = self::getRedisConnection();
                    $stats['count'] = $redis->dbSize();
                    $stats['info'] = $redis->info();
                } catch (Exception $e) {
                    $stats['error'] = 'Conexão com Redis falhou';
                }
                break;
        }

        return $stats;
    }

    // ==================== DRIVER: ARQUIVO ====================

    private static function getFromFile($key, $default)
    {
        $file = self::getCacheFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = json_decode(file_get_contents($file), true);

        // Verifica se expirou
        if ($data['expires_at'] && $data['expires_at'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    private static function setInFile($key, $value, $ttl)
    {
        $file = self::getCacheFilePath($key);
        $data = [
            'value' => $value,
            'created_at' => time(),
            'expires_at' => $ttl > 0 ? time() + $ttl : null
        ];

        file_put_contents($file, json_encode($data), LOCK_EX);
        chmod($file, 0644);
    }

    private static function forgetFile($key)
    {
        $file = self::getCacheFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private static function flushFile()
    {
        $cacheDir = self::$config['path'] ?? self::storage_path('cache');
        $files = glob($cacheDir . '/*');

        foreach ($files ?? [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function getCacheFilePath($key)
    {
        $cacheDir = self::$config['path'] ?? self::storage_path('cache');
        $fileName = md5(self::$prefix . $key) . '.cache';
        return $cacheDir . '/' . $fileName;
    }

    // ==================== DRIVER: REDIS ====================

    private static $redisConnection = null;

    private static function getRedisConnection()
    {
        if (self::$redisConnection !== null) {
            return self::$redisConnection;
        }

        $redis = new \Redis();
        $host = self::$config['host'] ?? 'localhost';
        $port = self::$config['port'] ?? 6379;
        $password = self::$config['password'] ?? null;

        if (!$redis->connect($host, $port)) {
            throw new Exception('Conexão com Redis falhou');
        }

        if ($password) {
            $redis->auth($password);
        }

        self::$redisConnection = $redis;
        return $redis;
    }

    private static function getFromRedis($key, $default)
    {
        $redis = self::getRedisConnection();
        $value = $redis->get(self::$prefix . $key);

        return $value !== false ? json_decode($value, true) : $default;
    }

    private static function setInRedis($key, $value, $ttl)
    {
        $redis = self::getRedisConnection();

        if ($ttl > 0) {
            $redis->setex(self::$prefix . $key, $ttl, json_encode($value));
        } else {
            $redis->set(self::$prefix . $key, json_encode($value));
        }
    }

    private static function forgetRedis($key)
    {
        $redis = self::getRedisConnection();
        $redis->del(self::$prefix . $key);
    }

    private static function flushRedis()
    {
        $redis = self::getRedisConnection();
        $redis->flushDB();
    }

    // ==================== DRIVER: MEMCACHED ====================

    private static $memcachedConnection = null;

    private static function getMemcachedConnection()
    {
        if (self::$memcachedConnection !== null) {
            return self::$memcachedConnection;
        }

        $memcached = new \Memcached();
        $host = self::$config['host'] ?? 'localhost';
        $port = self::$config['port'] ?? 11211;

        if (!$memcached->addServer($host, $port)) {
            throw new Exception('Conexão com Memcached falhou');
        }

        self::$memcachedConnection = $memcached;
        return $memcached;
    }

    private static function getFromMemcached($key, $default)
    {
        $memcached = self::getMemcachedConnection();
        $value = $memcached->get(self::$prefix . $key);

        return $value !== false ? $value : $default;
    }

    private static function setInMemcached($key, $value, $ttl)
    {
        $memcached = self::getMemcachedConnection();
        $memcached->set(self::$prefix . $key, $value, $ttl);
    }

    private static function forgetMemcached($key)
    {
        $memcached = self::getMemcachedConnection();
        $memcached->delete(self::$prefix . $key);
    }

    private static function flushMemcached()
    {
        $memcached = self::getMemcachedConnection();
        $memcached->flush();
    }

    // ==================== DRIVER: ARRAY (EM MEMÓRIA) ====================

    private static $arrayStore = [];

    private static function getFromArray($key, $default)
    {
        if (!isset(self::$arrayStore[$key])) {
            return $default;
        }

        $item = self::$arrayStore[$key];

        // Verifica se expirou
        if ($item['expires_at'] && $item['expires_at'] < time()) {
            unset(self::$arrayStore[$key]);
            return $default;
        }

        return $item['value'];
    }

    private static function setInArray($key, $value, $ttl)
    {
        self::$arrayStore[$key] = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : null
        ];
    }

    private static function forgetArray($key)
    {
        unset(self::$arrayStore[$key]);
    }

    private static function flushArray()
    {
        self::$arrayStore = [];
    }

    // ==================== AUXILIARES ====================

    /**
     * Garante que o cache foi inicializado
     */
    private static function ensureInitialized()
    {
        if (!self::$initialized) {
            self::init();
        }
    }

    /**
     * Retorna o caminho do storage
     * @param string $path
     * @return string
     */
    private static function storage_path($path = '')
    {
        $basePath = __DIR__ . '/../../storage';
        return $path ? $basePath . '/' . $path : $basePath;
    }
}
