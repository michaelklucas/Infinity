<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http
 */

namespace App\Http;

/**
 * Classe responsável por gerenciar as requisições HTTP da aplicação
 */
class Request
{
    /**
     * Método HTTP da requisição
     * @var string
     */
    private string $httpMethod;

    /**
     * URI da página
     * @var string
     */
    private string $uri;

    /**
     * Parâmetros da URL ($_GET)
     * @var array
     */
    private array $queryParams;

    /**
     * Variáveis recebidas no POST ($_POST)
     * @var array
     */
    private array $postVars = [];

    /**
     * Cabeçalhos da requisição
     * @var array
     */
    private array $headers;

    /**
     * Instância do Router
     * @var Router
     */
    private $router;

    /**
     * Usuário autenticado
     * @var mixed
     */
    public $user;

    /**
     * Método responsável por iniciar a classe
     * @param Router $router
     */
    public function __construct($router)
    {
        $this->router = $router;
        $this->queryParams = $_GET ?? [];
        
        // Capturar headers de forma robusta
        $rawHeaders = function_exists('getallheaders') ? getallheaders() : [];
        $this->headers = [];
        
        // Normalizar cabeçalhos para permitir busca case-insensitive
        foreach ($rawHeaders as $k => $v) {
            $this->headers[$k] = $v;
            $this->headers[strtolower($k)] = $v;
        }

        // Tratamento especial para Authorization em diferentes servidores
        if (empty($this->headers['Authorization']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
            $this->headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (empty($this->headers['Authorization']) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $this->headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            $this->headers['authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        $this->httpMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        $this->setUri();
        $this->setPostVars();
    }

    /**
     * Método responsável por definir as variáveis do POST
     */
    private function setPostVars(): void
    {
        if ($this->httpMethod !== 'GET') {
            $this->postVars = $_POST ?? [];
            
            // Suporte para requisições JSON
            $inputRaw = file_get_contents('php://input');
            if (strlen($inputRaw) && empty($_POST)) {
                $this->postVars = json_decode($inputRaw, true) ?? [];
            }
        }
    }

    /**
     * Método responsável por definir a URI
     */
    private function setUri(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $xURI = explode('?', $uri);
        $this->uri = $xURI[0];
    }

    /**
     * Método responsável por retornar o método HTTP da requisição
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * Método responsável por retornar a URI da requisição
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Método responsável por retornar os headers da requisição
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Método responsável por retornar os parâmetros da URL
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Método responsável por retornar as variáveis do POST
     * @return array
     */
    public function getPostVars(): array
    {
        return $this->postVars;
    }

    /**
     * Método responsável por retornar a instância do Router
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }
}
