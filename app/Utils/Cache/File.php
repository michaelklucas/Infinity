<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils\Cache
 */

namespace App\Utils\Cache;

/**
 * Driver de Cache em Arquivo - Armazena dados serializados no sistema de arquivos
 */
class File
{
    /**
     * Recupera o conteúdo do cache se ele ainda for válido
     * @param string $hash
     * @param int $expiration Segundos
     * @return mixed|false
     */
    private static function getContentCache($hash, $expiration)
    {
        $cacheFile = self::getFilePath($hash);

        if (!file_exists($cacheFile)) {
            return false;
        }

        $createTime = filectime($cacheFile);
        $diffTime = time() - $createTime;

        if ($diffTime > $expiration) {
            return false;
        }

        $serialize = file_get_contents($cacheFile);

        return unserialize($serialize);
    }

    /**
     * Retorna o caminho absoluto do arquivo de cache para um determinado hash
     * @param string $hash
     * @return string
     */
    private static function getFilePath($hash)
    {
        // O hash já vem sanitizado de Cache.php
        $sanitizedHash = preg_replace('/[\/\\\\]/', '-', $hash);

        if (!file_exists(CACHE_DIR)) {
            mkdir(CACHE_DIR, 0755, true);
        }

        return CACHE_DIR . '/' . $sanitizedHash . '.txt';
    }

    /**
     * Armazena os dados serializados em um arquivo
     * @param string $hash
     * @param mixed $content
     * @return int|bool
     */
    private static function storageCache($hash, $content)
    {
        $serialize = serialize($content);
        $cacheFile = self::getFilePath($hash);

        return file_put_contents($cacheFile, $serialize);
    }

    /**
     * Método principal para obter do cache ou executar a função e armazenar
     * @param string $hash
     * @param int $expiration
     * @param callable $function
     * @return mixed
     */
    public static function getCache($hash, $expiration, $function)
    {
        if ($content = self::getContentCache($hash, $expiration)) {
            return $content;
        }

        $content = $function();
        self::storageCache($hash, $content);

        return $content;
    }
}