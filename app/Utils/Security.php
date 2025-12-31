<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

/**
 * Utilitários de Segurança - Proteção contra XSS, sanitização e validação
 */
class Security
{
    /**
     * Escapa HTML para saída segura
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function escape($string, $encoding = 'UTF-8')
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, $encoding);
    }

    /**
     * Atalho para o método escape
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function e($string, $encoding = 'UTF-8')
    {
        return self::escape($string, $encoding);
    }

    /**
     * Sanitiza entradas - remove caracteres potencialmente perigosos
     * @param string $string
     * @param string $type
     * @return mixed
     */
    public static function sanitize($string, $type = 'text')
    {
        switch ($type) {
            case 'email':
                return filter_var($string, FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var($string, FILTER_SANITIZE_URL);
            
            case 'integer':
                return filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($string, FILTER_SANITIZE_NUMBER_FLOAT, 
                    FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
            
            case 'html':
                return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
            
            case 'json':
                return json_encode($string);
            
            case 'text':
            default:
                $string = strip_tags($string);
                $string = str_replace("\0", '', $string);
                return trim($string);
        }
    }

    /**
     * Remove todas as tags HTML
     * @param string $string
     * @param string $allowed
     * @return string
     */
    public static function stripTags($string, $allowed = '')
    {
        return strip_tags($string, $allowed);
    }

    /**
     * Valida entradas contra padrões comuns
     * @param string $string
     * @param string $type
     * @return bool
     */
    public static function validate($string, $type = 'text')
    {
        switch ($type) {
            case 'email':
                return filter_var($string, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'url':
                return filter_var($string, FILTER_VALIDATE_URL) !== false;
            
            case 'integer':
                return filter_var($string, FILTER_VALIDATE_INT) !== false;
            
            case 'float':
                return filter_var($string, FILTER_VALIDATE_FLOAT) !== false;
            
            case 'ipv4':
                return filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
            
            case 'ipv6':
                return filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
            
            case 'phone':
                return (bool) preg_match('/^[\d\s\-\+\(\)]{10,}$/', $string);
            
            case 'cpf':
                return self::validateCPF($string);
            
            case 'cnpj':
                return self::validateCNPJ($string);
            
            case 'text':
            default:
                return is_string($string) && strlen($string) > 0;
        }
    }

    /**
     * Valida CPF
     * @param string $cpf
     * @return bool
     */
    private static function validateCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $digit = 11 - ($sum % 11);
        if ($digit > 9) $digit = 0;
        if ($cpf[9] != $digit) return false;

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $digit = 11 - ($sum % 11);
        if ($digit > 9) $digit = 0;
        if ($cpf[10] != $digit) return false;

        return true;
    }

    /**
     * Valida CNPJ
     * @param string $cnpj
     * @return bool
     */
    private static function validateCNPJ($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $sum = 0;
        $multiplier = 5;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $multiplier;
            $multiplier = $multiplier == 2 ? 9 : $multiplier - 1;
        }
        $digit = 11 - ($sum % 11);
        if ($digit > 9) $digit = 0;
        if ($cnpj[12] != $digit) return false;

        $sum = 0;
        $multiplier = 6;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $multiplier;
            $multiplier = $multiplier == 2 ? 9 : $multiplier - 1;
        }
        $digit = 11 - ($sum % 11);
        if ($digit > 9) $digit = 0;
        if ($cnpj[13] != $digit) return false;

        return true;
    }

    /**
     * Gera um hash seguro para senhas
     * @param string $password
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verifica uma senha contra um hash
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Gera um token aleatório seguro
     * @param int $length
     * @return string
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Define o cabeçalho Content Security Policy
     * @param array $directives
     */
    public static function setCSPHeader($directives = [])
    {
        $default = [
            "default-src" => "'self'",
            "script-src" => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src" => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "img-src" => "'self' data: https:",
            "font-src" => "'self' https://fonts.googleapis.com https://fonts.gstatic.com",
            "connect-src" => "'self' https:",
            "frame-ancestors" => "'none'",
            "base-uri" => "'self'",
            "form-action" => "'self'"
        ];

        $policy = array_merge($default, $directives);
        $header = '';

        foreach ($policy as $key => $value) {
            $header .= "$key $value; ";
        }

        header('Content-Security-Policy: ' . trim($header));
    }

    /**
     * Define diversos cabeçalhos de segurança padrão
     */
    public static function setSecurityHeaders()
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        if (!empty($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Sanitiza o nome de um arquivo
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename($filename)
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $filename = substr($filename, 0, 255);
        
        return $filename;
    }

    /**
     * Valida o upload de um arquivo
     * @param array $file
     * @param int $maxSize
     * @param array $allowedMimes
     * @return array
     */
    public static function validateFile($file, $maxSize = 10485760, $allowedMimes = [])
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Upload de arquivo inválido'];
        }

        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'Arquivo muito grande'];
        }

        if (!empty($allowedMimes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes)) {
                return ['valid' => false, 'error' => 'Tipo de arquivo não permitido'];
            }
        }

        return ['valid' => true, 'error' => null];
    }
}
