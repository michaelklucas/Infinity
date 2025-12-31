<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http\Middleware
 */

namespace App\Http\Middleware;

/**
 * Fila de Middlewares - Gerencia a execução sequencial dos middlewares
 */
class Queue
{
    /**
     * Middlewares padrões (executados em todas as rotas)
     * @var array
     */
    private static $default = [];

    /**
     * Mapeamento de middlewares
     * @var array
     */
    private static $map = [];

    /**
     * Fila de middlewares a serem executados na rota atual
     * @var array
     */
    private $middlewares = [];

    /**
     * Função controladora da rota
     * @var callable
     */
    private $controller;

    /**
     * Argumentos da função controladora
     * @var array
     */
    private $controllersArgs = [];

    /**
     * Construtor da classe de fila de middlewares
     * @param array $middlewares
     * @param callable $controller
     * @param array $controllersArgs
     */
    public function __construct($middlewares, $controller, $controllersArgs)
    {
        $this->middlewares = array_merge(self::$default, $middlewares);
        $this->controller = $controller;
        $this->controllersArgs = $controllersArgs;
    }

    /**
     * Define o mapeamento de middlewares
     * @param array $map
     */
    public static function setMap($map)
    {
        self::$map = $map;
    }

    /**
     * Define os middlewares padrões
     * @param array $default
     */
    public static function setDefault($default)
    {
        self::$default = $default;
    }

    /**
     * Executa o próximo nível da fila de middlewares
     * @param Request $request
     * @return Response
     */
    public function next($request)
    {
        // Verifica se a fila está vazia (executa o controlador final)
        if (empty($this->middlewares)) {
            return call_user_func_array($this->controller, $this->controllersArgs);
        }

        // Obtém o próximo middleware da fila
        $middleware = array_shift($this->middlewares);

        // Verifica se o middleware está mapeado
        if (!isset(self::$map[$middleware])) {
            throw new \Exception("Problemas ao carregar o middleware: {$middleware}", 500);
        }

        // Próximo middleware (closure)
        $queue = $this;
        $next = function ($request) use ($queue) {
            return $queue->next($request);
        };

        // Instancia e executa o middleware
        return (new self::$map[$middleware])->handle($request, $next);
    }
}
