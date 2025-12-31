<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http
 */

namespace App\Http;

use App\Utils\DebugBar;

/**
 * Classe responsável por gerenciar a resposta HTTP enviada ao cliente
 */
class Response
{
    /**
     * Código de status HTTP
     * @var int
     */
    private int $httpCode;

    /**
     * Cabeçalhos da resposta
     * @var array
     */
    private array $headers;

    /**
     * Tipo de conteúdo da resposta
     * @var string
     */
    private string $contentType;

    /**
     * Conteúdo da resposta
     * @var mixed
     */
    private $content;

    /**
     * Método responsável por iniciar a classe e definir os valores
     * @param int $httpCode
     * @param mixed $content
     * @param string $contentType
     */
    public function __construct(int $httpCode, $content, string $contentType = 'text/html')
    {
        $this->httpCode = $httpCode;
        $this->content = $content;
        $this->contentType = $contentType;
        $this->headers = [];
        $this->addHeader('Content-Type', $contentType);
    }

    /**
     * Método responsável por alterar o Content Type da resposta
     * @param string $contentType
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
        $this->addHeader('Content-Type', $contentType);
    }

    /**
     * Método responsável por adicionar um registro no cabeçalho da resposta
     * @param string $key
     * @param string $value
     */
    public function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * Método responsável por retornar o código HTTP da resposta
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Método responsável por enviar os cabeçalhos para o navegador
     */
    private function sendHeaders(): void
    {
        // Status HTTP
        http_response_code($this->httpCode);

        // Enviar Cabeçalhos
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
    }

    /**
     * Método responsável por enviar a resposta para o usuário
     */
    public function sendResponse(): void
    {
        // Envia os cabeçalhos
        $this->sendHeaders();

        // Imprime o conteúdo
        switch ($this->contentType) {
            case 'application/json':
                echo json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;

            case 'text/html':
            default:
                $content = $this->content;
                
                // Injetar DebugBar se estiver habilitado
                if (DebugBar::isEnabled() && strpos($content, '<body') !== false) {
                    $debugBarHtml = DebugBar::renderDebugBar();
                    $content = str_replace('</body>', $debugBarHtml . '</body>', $content);
                }
                
                echo $content;
                break;
        }

        exit;
    }
}
