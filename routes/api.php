<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package Routes\API
 */

use App\Http\Response;

/**
 * Rota de Diagnóstico da API
 */
$obRouter->get('/api/v1/status', [
    'middlewares' => [
        'api'
    ],
    function() {
        return new Response(200, [
            'status' => 'online',
            'version' => '1.0.0',
            'framework' => 'Infinity'
        ], 'application/json');
    }
]);
