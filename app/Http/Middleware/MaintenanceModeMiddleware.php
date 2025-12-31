<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http\Middleware
 */

namespace App\Http\Middleware;

use App\Utils\MaintenanceMode;

/**
 * Middleware de Modo de Manutenção - Intercepta requisições quando o sistema está offline
 */
class MaintenanceModeMiddleware
{
    /**
     * Manipula a requisição para verificar o estado de manutenção
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        // Inicializa as configurações do utilitário
        MaintenanceMode::init();

        // Verifica se o modo de manutenção está ativado
        if (MaintenanceMode::isEnabled()) {
            
            // Verifica se o IP atual está na lista permitida (ex: IPs de administradores ou desenvolvedores)
            if (MaintenanceMode::isIpWhitelisted()) {
                return $next($request);
            }

            // Define o código HTTP 530 de Indisponibilidade para o navegador/cliente
            http_response_code(503);
            header('Retry-After: ' . MaintenanceMode::getRetryAfter());
            header('Content-Type: text/html; charset=UTF-8');

            // Renderiza a página oficial de manutenção
            echo MaintenanceMode::render();
            exit;
        }

        return $next($request);
    }
}
