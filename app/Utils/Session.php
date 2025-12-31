<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

/**
 * Gerenciador de Sessão - Abstração sobre $_SESSION com suporte a Flash Messages
 */
class Session
{
    /**
     * Inicializa a sessão se ainda não estiver ativa
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Define um valor na sessão
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::init();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtém um valor da sessão
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::init();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica se uma chave existe na sessão
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        self::init();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove um item da sessão
     * @param string $key
     */
    public static function forget($key)
    {
        self::init();
        unset($_SESSION[$key]);
    }

    /**
     * Define uma mensagem flash (dura apenas uma requisição)
     * @param string $key
     * @param string $message
     */
    public static function flash($key, $message)
    {
        self::init();
        $_SESSION['__flash'][$key] = $message;
    }

    /**
     * Obtém uma mensagem flash e a remove da sessão
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getFlash($key, $default = null)
    {
        self::init();
        if (isset($_SESSION['__flash'][$key])) {
            $message = $_SESSION['__flash'][$key];
            unset($_SESSION['__flash'][$key]);
            return $message;
        }
        return $default;
    }

    /**
     * Retorna todas as mensagens flash e as limpa
     * @return array
     */
    public static function allFlash()
    {
        self::init();
        $flashes = $_SESSION['__flash'] ?? [];
        unset($_SESSION['__flash']);
        return $flashes;
    }

    /**
     * Destrói a sessão completamente
     */
    public static function destroy()
    {
        self::init();
        session_destroy();
        $_SESSION = [];
    }
}
