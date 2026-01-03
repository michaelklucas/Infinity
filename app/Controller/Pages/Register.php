<?php

namespace App\Controller\Pages;

use App\Utils\View;
use App\Utils\Validator;
use App\Utils\Session;
use App\Utils\CSRF;
use App\Http\Response;

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
    /**
     * Processa o formulário de cadastro
     * @param Request $request
     */
    public static function insertRegister($request)
    {
        $postVars = $request->getPostVars();

        // Instancia o serviço
        $userService = new \App\Services\UserService();
        $result = $userService->register($postVars);

        if (!$result['success']) {
            // Mapeia os erros para o formato da view {{error.campo}}
            $viewErrors = [];
            foreach ($result['errors'] as $field => $msg) {
                $viewErrors['error.' . $field] = $msg;
            }

            return parent::getPage('Cadastro', View::render('pages/register', array_merge([
                'csrf' => CSRF::field()
            ], $viewErrors)));
        }

        // Sucesso
        Session::flash('success', $result['message']);

        // Redireciona para o Dashboard
        header('Location: /dashboard');
        exit;
    }
}
