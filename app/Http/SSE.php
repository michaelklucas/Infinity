<?php

namespace App\Http;

class SSE
{
    /**
     * Inicia a conexão SSE configurando os headers necessários
     */
    public static function start()
    {
        // Limpa buffers anteriores
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Para Nginx
        
        // Garante que o PHP não faça buffer do output
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', false);
    }

    /**
     * Envia um evento para o cliente
     * 
     * @param mixed $data Dados a serem enviados (array será convertido para JSON)
     * @param string|null $event Nome do evento (opcional)
     * @param string|int|null $id ID do evento (opcional)
     * @param int|null $retry Tempo de reconexão em ms (opcional)
     */
    public static function send($data, $event = null, $id = null, $retry = null)
    {
        if ($id !== null) {
            echo "id: {$id}\n";
        }

        if ($event !== null) {
            echo "event: {$event}\n";
        }

        if ($retry !== null) {
            echo "retry: {$retry}\n";
        }

        $payload = is_array($data) || is_object($data) ? json_encode($data) : $data;
        
        echo "data: {$payload}\n\n";

        // Força o envio do buffer
        self::flush();
    }

    /**
     * Envia um comentário (ping) para manter a conexão viva
     */
    public static function keepAlive()
    {
        echo ": keepalive\n\n";
        self::flush();
    }

    /**
     * Força o envio dos dados para o browser
     */
    private static function flush()
    {
        if(function_exists('flush')){
            @flush();
        }
        if(function_exists('ob_flush') && ob_get_level() > 0){
            @ob_flush();
        }
    }
}
