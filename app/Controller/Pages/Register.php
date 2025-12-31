<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Controller\Pages
 */

namespace App\Controller\Pages;

use App\Utils\View;
use App\Utils\Validator;
use App\Utils\Session;
use App\Utils\CSRF;
use App\Http\Response;

/**
 * Controller responsável por registro de usuários.
 *
 * @package App\Controller\Pages
 */
class Register extends Page
{
    /**
     * Renderiza a página de cadastro
     * @param Request $request
     * @return string
     */
    public static function getRegister($request)
    {
        // Renderiza a view de registro
        return parent::getPage('Cadastro', View::render('pages/register', [
            'csrf' => CSRF::field()
        ]));
    }

    /**
     * Processa o formulário de cadastro
     * @param Request $request
     */
    public static function insertRegister($request)
    {
        $postVars = $request->getPostVars();

        // 1. Validação usando o novo utilitário
        $v = Validator::make($postVars, [
            'nome' => 'required|min:3',
            'email' => 'required|email', // Nota: unique:usuarios só funcionaria se a tabela existisse
            'senha' => 'required|min:8'
        ]);

        if ($v->fails()) {
            // Mapeia os erros para o formato da view {{error.campo}}
            $viewErrors = [];
            foreach ($v->getErrors() as $field => $msg) {
                $viewErrors['error.' . $field] = $msg;
            }

            return parent::getPage('Cadastro', View::render('pages/register', array_merge([
                'csrf' => CSRF::field()
            ], $viewErrors)));
        }

        // 2. Simula o sucesso e usa Flash Message
        Session::flash('success', 'Conta criada com sucesso! Bem-vindo ao Infinity.');

        // 3. Redireciona para o Dashboard
        header('Location: /dashboard');
        exit;
    }
}
