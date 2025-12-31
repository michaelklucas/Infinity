<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http
 */

namespace App\Http;

use Closure;
use Exception;
use ReflectionFunction;
use App\Http\Middleware\Queue;

/**
 * Classe responsável por gerenciar o roteamento da aplicação
 */
class Router
{
    /**
     * URL completa do projeto (raiz)
     * @var string
     */
    private string $url = '';

    /**
     * Prefixo das rotas
     * @var string
     */
    private string $prefix = '';

    /**
     * Índice de rotas
     * @var array
     */
    private array $routes = [];

    /**
     * Cache de rotas compiladas
     * @var array
     */
    private array $compiledRoutesCache = [];

    /**
     * Instância do Request
     * @var Request
     */
    private Request $request;

    /**
     * Content Type padrão da resposta
     * @var string
     */
    private $contentType = 'text/html';

    /**
     * Método responsável por iniciar a classe
     * @param string $url
     */
    public function __construct($url)
    {
        $this->request = new Request($this);
        $this->url = $url;
        $this->setPrefix();
    }

    /**
     * Método responsável por definir o Content Type
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Método responsável por definir o prefixo das rotas
     */
    private function setPrefix()
    {
        $parseUrl = parse_url($this->url);
        $this->prefix = $parseUrl['path'] ?? '';
    }

    /**
     * Método responsável por adicionar uma rota na classe
     * @param string $method
     * @param string $route
     * @param array $params
     */
    private function addRoute($method, $route, $params = [])
    {
        // Se $params é um Closure, converter para array
        if ($params instanceof Closure) {
            $params = ['controller' => $params];
        }

        foreach ($params as $key => $value) {
            if ($value instanceof Closure) {
                $params['controller'] = $value;
                unset($params[$key]);
                continue;
            }
        }

        $params['middlewares'] = $params['middlewares'] ?? [];
        $params['variables'] = [];

        // Variáveis da rota
        $patternVariable = '/{(.*?)}/';
        if (preg_match_all($patternVariable, $route, $matches)) {
            $route = preg_replace($patternVariable, '(.*?)', $route);
            $params['variables'] = $matches[1];
        }

        $route = rtrim($route, '/');

        // Padrão de validação da rota
        $patterRoute = '/^' . str_replace('/', '\/', $route) . '$/';
        $this->routes[$patterRoute][$method] = $params;
    }

    /**
     * Método responsável por definir uma rota de GET
     * @param string $route
     * @param array $params
     */
    public function get($route, $params = [])
    {
        return $this->addRoute('GET', $route, $params);
    }

    /**
     * Método responsável por definir uma rota de POST
     * @param string $route
     * @param array $params
     */
    public function post($route, $params = [])
    {
        return $this->addRoute('POST', $route, $params);
    }

    /**
     * Método responsável por definir uma rota de PUT
     * @param string $route
     * @param array $params
     */
    public function put($route, $params = [])
    {
        return $this->addRoute('PUT', $route, $params);
    }

    /**
     * Método responsável por definir uma rota de DELETE
     * @param string $route
     * @param array $params
     */
    public function delete($route, $params = [])
    {
        return $this->addRoute('DELETE', $route, $params);
    }

    /**
     * Método responsável por retornar a URI desconsiderando o prefixo
     * @return string
     */
    public function getUri()
    {
        $uri = $this->request->getUri();
        $xUri = strlen($this->prefix) ? explode($this->prefix, $uri) : [$uri];
        return rtrim(end($xUri), '/');
    }

    /**
     * Método responsável por retornar os dados da rota atual
     * @return array
     */
    private function getRoute()
    {
        $uri = $this->getUri();
        $httpMethod = $this->request->getHttpMethod();
        $cacheKey = $httpMethod . ':' . $uri;

        // Verificar cache da rota já compilada
        if (isset($this->compiledRoutesCache[$cacheKey])) {
            $cached = $this->compiledRoutesCache[$cacheKey];
            $cached['variables']['request'] = $this->request;
            return $cached;
        }

        foreach ($this->routes as $patternRoute => $methods) {
            if (preg_match($patternRoute, $uri, $metches)) {
                if (isset($methods[$httpMethod])) {
                    unset($metches[0]);
                    $keys = $methods[$httpMethod]['variables'];
                    $methods[$httpMethod]['variables'] = array_combine($keys, $metches);
                    
                    // Cache a rota compilada para próximas requisições
                    $this->compiledRoutesCache[$cacheKey] = $methods[$httpMethod];
                    
                    $methods[$httpMethod]['variables']['request'] = $this->request;
                    return $methods[$httpMethod];
                }
                throw new Exception("O método não é permitido", 405);
            }
        }
        
        throw new Exception("URL não encontrada", 404);
    }

    /**
     * Método responsável por executar a rota atual
     * @return Response
     */
    public function run()
    {
        try {
            $route =  $this->getRoute();

            if (!isset($route['controller'])) {
                throw new Exception("A URL não pode ser processada", 500);
            }

            $args = [];
            $reflection = new ReflectionFunction($route['controller']);

            foreach ($reflection->getParameters() as $parameter) {
                $name = $parameter->getName();
                $args[$name] = $route['variables'][$name] ?? '';
            }

            return (new Queue($route['middlewares'], $route['controller'], $args))->next($this->request);
        } catch (Exception $e) {
            $code = $e->getCode();
            if(!is_int($code) || $code < 100 || $code > 599) $code = 500;
            
            http_response_code($code);

            if ($this->contentType === 'application/json') {
                return new Response($code, ['error' => $e->getMessage()], $this->contentType);
            }

            $errorViewPath = __DIR__ . "/../../resources/view/errors/{$code}.html";

            if (file_exists($errorViewPath)) {
                ob_start();
                include $errorViewPath;
                $content = ob_get_clean();
            } else {
                $content = "<h1>Erro {$code}</h1><p>{$e->getMessage()}</p>";
            }

            return new Response($code, $content, $this->contentType);
        }
    }

    /**
     * Método responsável por retornar a URL atual
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->url . $this->getUri();
    }

    /**
     * Método responsável por realizar um redirecionamento
     * @param string $route
     */
    public function redirect($route)
    {
        $url = $this->url . $route;
        header('Location:' . $url);
        exit;
    }
}
