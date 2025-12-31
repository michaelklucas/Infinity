<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils\Storage
 */

namespace App\Utils\Storage;

/**
 * Driver de Armazenamento Local - Gerencia uploads no sistema de arquivos local
 */
class FileSystem
{
    /**
     * Caminho base para os arquivos
     * @var string
     */
    private $basePath;

    /**
     * Construtor da classe
     * @param string $basePath
     */
    public function __construct($basePath = 'resources/view/assets/')
    {
        $this->basePath = rtrim($basePath, '/') . '/';
    }

    /**
     * Realiza o upload de um arquivo para o disco local
     * @param array $file Matriz do arquivo ($_FILES)
     * @param string $subDir Subdiretório de destino
     * @param string $fileName Nome do arquivo final
     * @return string|null Caminho relativo do arquivo ou null em caso de falha
     */
    public function upload($file, $subDir, $fileName)
    {
        $directory = $this->basePath . $subDir . '/';

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return '/' . $subDir . '/' . $fileName;
        }

        return null;
    }
}
