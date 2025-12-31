<?php

/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils\Storage
 */

namespace App\Utils\Storage;

class StorageManager
{
    /**
     * Retorna a URL de um arquivo
     * @param string $path
     * @param string $folder
     * @return string
     */
    public static function getUrl($path, $folder = '')
    {
        $driver = STORAGE_DRIVER ?: 'local';

        if ($driver === 's3') {
            $storage = new S3Storage();
            return $storage->getUrl($path);
        }
        return URL . '/resources/view/assets/' . ltrim($path, '/');
    }
}
