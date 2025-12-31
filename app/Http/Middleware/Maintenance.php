<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Http\Middleware
 */

namespace App\Http\Middleware;

/**
 * Middleware de Manutenção (Simples) - Bloqueia o acesso via variável de ambiente
 */
class Maintenance
{
    /**
     * Manipula a requisição para verificar se o sistema está em manutenção global
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        // Verifica a variável de ambiente MAINTENANCE
        if (getenv('MAINTENANCE') === 'true') {
            http_response_code(503);
            echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manutenção - Infinity</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: sans-serif; }
        body { background: #0f172a; color: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 20px; }
        .container { max-width: 600px; background: #1e293b; padding: 60px; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05); }
        .icon { font-size: 60px; margin-bottom: 20px; color: #6366f1; }
        h1 { font-size: 32px; margin-bottom: 20px; }
        p { color: #94a3b8; line-height: 1.6; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">M</div>
        <h1>Sistema em Manutenção</h1>
        <p>Estamos realizando melhorias programadas. Voltaremos em breve com novidades!</p>
    </div>
</body>
</html>
HTML;
            exit;
        }

        return $next($request);
    }
}
