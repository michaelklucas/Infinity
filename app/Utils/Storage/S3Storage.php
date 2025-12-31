<?php

/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils\Storage
 */

namespace App\Utils\Storage;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Storage
{
    private $s3;
    private $bucket;
    private $basePath;

    /**
     * Construtor da classe
     */
    public function __construct()
    {
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => S3_REGION,
            'credentials' => [
                'key'    => S3_KEY,
                'secret' => S3_SECRET,
            ],
            'endpoint' => S3_ENDPOINT ?: null,
            'use_path_style_endpoint' => true
        ]);

        $this->bucket = S3_BUCKET;
        $this->basePath = S3_BASE_PATH ?: '';
    }

     public function upload($file, $subDir, $fileName)
    {
        $key = rtrim($this->basePath . '/' . $subDir, '/') . '/' . $fileName;

        try {
            $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => fopen($file['tmp_name'], 'rb'),
                'ACL'    => 'public-read',
                'ContentType' => $file['type']
            ]);

            return $key;
        } catch (AwsException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function getUrl($key)
    {
        if (!$key) {
            return null;
        }

        $baseUrl = S3_ENDPOINT ?: "https://s3." . S3_REGION . ".amazonaws.com";
        return rtrim($baseUrl, '/') . '/' . $this->bucket . '/' . ltrim($key, '/');
    }
}
