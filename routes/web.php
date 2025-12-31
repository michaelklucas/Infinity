<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package Routes\Web
 */

use App\Http\Response;
use App\Controller\Pages\Home;
use App\Controller\Pages\Register;
use App\Controller\Pages\Dashboard;
use App\Controller\Pages\Clientes;
use App\Controller\Pages\TestDebug;
use App\Utils\View;

/**
 * Rota Principal
 */
$obRouter->get('/', [
    function() {
        return new Response(200, Home::getHome());
    }
]);

/**
 * Rotas de Registro (Exemplo)
 */
$obRouter->get('/register', [
    function($request) {
        return new Response(200, Register::getRegister($request));
    }
]);

$obRouter->post('/register', [
    function($request) {
        return new Response(200, Register::insertRegister($request));
    }
]);

/**
 * Rota do Dashboard (SaaS)
 */
$obRouter->get('/dashboard', [
    'middlewares' => ['cache'],
    function($request) {
        return new Response(200, Dashboard::getDashboard($request));
    }
]);

/**
 * Rotas de CRUD de Clientes
 */
$obRouter->get('/clientes', [
    'middlewares' => ['cache'],
    function($request) {
        return new Response(200, Clientes::getList($request));
    }
]);

$obRouter->get('/clientes/novo', [
    function($request) {
        return new Response(200, Clientes::getNew($request));
    }
]);

$obRouter->post('/clientes/novo', [
    function($request) {
        return new Response(200, Clientes::insertNew($request));
    }
]);

$obRouter->get('/clientes/{id}/editar', [
    function($request, $id) {
        return new Response(200, Clientes::getEdit($request, $id));
    }
]);

$obRouter->post('/clientes/{id}/editar', [
    function($request, $id) {
        return new Response(200, Clientes::updateEdit($request, $id));
    }
]);

$obRouter->get('/clientes/{id}/excluir', [
    function($request, $id) {
        return new Response(200, Clientes::delete($request, $id));
    }
]);

/**
 * Rota de Documentação
 */
if (getenv('APP_DOCS') === 'true' || getenv('APP_DOCS') === '1') {
    $obRouter->get('/docs', [
        function($request) {
            $request->getRouter()->redirect('/docs/');
        }
    ]);
}

/**
 * Rota de Teste do DebugBar
 */
$obRouter->get('/debug-test', [
    function($request) {
        return new Response(200, TestDebug::getTest($request));
    }
]);

/**
 * Exemplo de Rota com Parâmetro
 */
$obRouter->get('/hello/{name}', [
    function($name) {
        return new Response(200, "Olá, $name! Bem-vindo ao Infinity Framework.");
    }
]);