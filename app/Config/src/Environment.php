<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Config\src
 */

namespace App\Config\src;

/**
 * Classe de Gerenciamento de Variáveis de Ambiente (.env)
 */
class Environment
{
    /**
     * Carrega as variáveis de um arquivo .env para o sistema
     * @param string $dir Caminho do diretório que contém o arquivo .env
     * @return bool
     */
    public static function load($dir)
    {
        // Define o caminho completo do arquivo
        $path = rtrim($dir, '/') . '/.env';

        // Verifica se o arquivo existe
        if (!file_exists($path)) return false;

        // Lê as linhas do arquivo ignorando linhas vazias
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignora comentários ou linhas que começam com #
            if ($line === '' || str_starts_with($line, '#')) continue;

            // Separa a chave e o valor na primeira ocorrência de '='
            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Remove aspas simples ou duplas externas do valor
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Substitui referências internas a outras variáveis do tipo ${OUTRA_VAR}
            $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/i', function($matches) {
                return getenv($matches[1]) ?: ($_ENV[$matches[1]] ?? '');
            }, $value);

            // Define a variável no ambiente global do PHP
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        return true;
    }
}
