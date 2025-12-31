<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

use DateTime;
use Exception;

/**
 * Autenticação JWT - JSON Web Tokens para APIs
 */
class JWT
{
    /**
     * Chave secreta para assinatura
     * @var string
     */
    private static $secret;

    /**
     * Algoritmo de assinatura
     * @var string
     */
    private static $algorithm = 'HS256';

    /**
     * Emissor do token
     * @var string
     */
    private static $issuer = 'infinity';

    /**
     * Público do token
     * @var string
     */
    private static $audience = 'api';

    /**
     * Tempo de expiração padrão (24 horas)
     * @var int
     */
    private static $expiration = 86400;

    /**
     * Método responsável por inicializar as configurações do JWT
     * @param string $secret
     * @param int $expiration
     */
    public static function init($secret = null, $expiration = 86400)
    {
        self::$secret = $secret ?? getenv('JWT_SECRET') ?? 'chave-padrao-infinity-framework';
        self::$expiration = $expiration;
        
        if (self::$secret === 'chave-padrao-infinity-framework') {
            Logger::warning('JWT utilizando chave padrão - configure JWT_SECRET no seu arquivo .env');
        }
    }

    /**
     * Método responsável por gerar um novo token JWT
     * @param array $payload
     * @param int $expiration
     * @return string
     */
    public static function encode($payload = [], $expiration = null)
    {
        if (!self::$secret) {
            self::init();
        }

        $expiration = $expiration ?? self::$expiration;

        // Cabeçalho
        $header = [
            'alg' => self::$algorithm,
            'typ' => 'JWT'
        ];

        // Payload padrão
        $payload['iss'] = self::$issuer;
        $payload['aud'] = self::$audience;
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiration;

        // Codificação
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Assinatura
        $message = "$headerEncoded.$payloadEncoded";
        $signature = self::sign($message);
        $signatureEncoded = self::base64UrlEncode($signature);

        $token = "$message.$signatureEncoded";

        Logger::debug('Token JWT gerado', ['exp' => $payload['exp']]);

        return $token;
    }

    /**
     * Método responsável por decodificar e validar um token JWT
     * @param string $token
     * @return array|null
     */
    public static function decode($token)
    {
        if (!self::$secret) {
            self::init();
        }

        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new Exception('Formato de token inválido');
            }

            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

            // Verificar assinatura
            $message = "$headerEncoded.$payloadEncoded";
            $signature = self::base64UrlDecode($signatureEncoded);
            $expectedSignature = self::sign($message);

            if (!hash_equals($signature, $expectedSignature)) {
                throw new Exception('Assinatura inválida');
            }

            // Decodificar payload
            $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

            if (!$payload) {
                throw new Exception('Payload inválido');
            }

            // Verificar expiração
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token expirado');
            }

            Logger::debug('Token JWT decodificado com sucesso');

            return $payload;

        } catch (Exception $e) {
            Logger::warning('Falha ao decodificar JWT', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Método responsável por verificar se um token é válido
     * @param string $token
     * @return bool
     */
    public static function verify($token)
    {
        $payload = self::decode($token);
        return $payload !== null;
    }

    /**
     * Método responsável por extrair o token do cabeçalho Authorization
     * @param array $headers
     * @return string|null
     */
    public static function extractFromHeader($headers = null)
    {
        $headers = $headers ?? getallheaders();

        if (!isset($headers['Authorization'])) {
            return null;
        }

        $authHeader = $headers['Authorization'];

        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Método responsável por renovar um token
     * @param string $token
     * @return string
     */
    public static function refresh($token)
    {
        $payload = self::decode($token);

        if (!$payload) {
            throw new Exception('Token inválido para renovação');
        }

        // Remove chaves de controle do payload antigo
        unset($payload['iat']);
        unset($payload['exp']);
        unset($payload['iss']);
        unset($payload['aud']);

        // Gera novo token com o mesmo payload
        return self::encode($payload);
    }

    /**
     * Método responsável por visualizar o payload sem validação (apenas depuração)
     * @param string $token
     * @return array|null
     */
    public static function peek($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            return json_decode(self::base64UrlDecode($parts[1]), true);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Assina a mensagem com HMAC SHA256
     * @param string $message
     * @return string
     */
    private static function sign($message)
    {
        return hash_hmac('sha256', $message, self::$secret, true);
    }

    /**
     * Codificador Base64 compatível com URL
     * @param string $data
     * @return string
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodificador Base64 compatível com URL
     * @param string $data
     * @return string
     */
    private static function base64UrlDecode($data)
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
