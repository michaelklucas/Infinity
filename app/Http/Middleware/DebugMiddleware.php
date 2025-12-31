<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http\Middleware
 */

namespace App\Http\Middleware;

use Closure;
use App\Utils\DebugBar;

/**
 * Middleware de Depuração - Captura dados para a Debug Bar
 */
class DebugMiddleware
{
    /**
     * Manipula a requisição para registrar informações de debug
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Registra os dados da requisição no DebugBar
        DebugBar::recordRequest($request);

        // Executa o próximo passo da aplicação
        $response = $next($request);

        // Registra os dados da resposta caso seja uma instância válida
        if ($response instanceof \App\Http\Response) {
            DebugBar::recordResponse($response->getHttpCode());
        }

        return $response;
    }
}
