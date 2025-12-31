<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http\Middleware
 */

namespace App\Http\Middleware;

/**
 * Middleware de API - Define o tipo de conteúdo da resposta como JSON
 */
class Api
{
    /**
     * Manipula a requisição para definir o Content-Type como JSON
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $request->getRouter()->setContentType('application/json');
        return $next($request);
    }
}