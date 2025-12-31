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
use App\Model\Entity\Cliente as ClienteModel;

class Clientes extends Page
{
    /**
     * Renderiza a listagem de clientes
     * @param Request $request
     * @return string
     */
    public static function getList($request)
    {
        // Define o layout de dashboard
        View::setLayout('dashboard');

        // Obtém a página atual
        $queryParams = $request->getQueryParams();
        $page = $queryParams['page'] ?? 1;

        // Obtém a paginação
        $pagination = ClienteModel::getPagination(null, $page, 2);
        
        // Renderiza os itens
        $itens = '';
        foreach ($pagination['data'] as $cliente) {
            $itens .= View::render('pages/clientes/item', [
                'id'           => $cliente['id'],
                'nome'         => $cliente['nome'],
                'email'        => $cliente['email'],
                'telefone'     => $cliente['telefone'],
                'status'       => ucfirst($cliente['status']),
                'status_class' => ($cliente['status'] == 'ativo') ? 'badge-success' : 'badge-danger'
            ]);
        }

        // Renderiza a paginação
        $links = '';
        for ($i = 1; $i <= $pagination['pages']; $i++) {
            $links .= View::render('pages/clientes/pagination-link', [
                'page'         => $i,
                'link'         => '/clientes?page=' . $i,
                'active_bg'    => ($i == $page) ? 'var(--primary)' : 'rgba(255,255,255,0.05)',
                'active_style' => ($i == $page) ? 'color: white;' : 'color: var(--text-muted);'
            ]);
        }

        $paginationView = View::render('pages/clientes/pagination', [
            'links' => $links
        ]);

        // Retorna a view completa
        $content = View::render('pages/clientes/index', [
            'itens'      => $itens,
            'pagination' => $paginationView
        ]);

        return View::render('layouts/dashboard', [
            'title'   => 'Gerenciar Clientes',
            'content' => $content
        ]);
    }

    /**
     * Renderiza o formulário de novo cliente
     * @return string
     */
    public static function getNew($request)
    {
        View::setLayout('dashboard');

        $content = View::render('pages/clientes/form', [
            'form_title'      => 'Novo Cliente',
            'nome'            => '',
            'email'           => '',
            'telefone'        => '',
            'status_ativo'    => 'selected',
            'status_inativo'  => '',
            'csrf'            => CSRF::field()
        ]);

        return View::render('layouts/dashboard', [
            'title'   => 'Cadastrar Cliente',
            'content' => $content
        ]);
    }

    /**
     * Processa a inserção de um novo cliente
     */
    public static function insertNew($request)
    {
        $postVars = $request->getPostVars();

        // Validação
        $v = Validator::make($postVars, [
            'nome'  => 'required|min:3',
            'email' => 'required|email|unique:clientes'
        ]);

        if ($v->fails()) {
            View::setLayout('dashboard');
            
            $viewErrors = [];
            foreach ($v->getErrors() as $field => $msg) {
                $viewErrors['error.' . $field] = $msg;
            }

            $content = View::render('pages/clientes/form', array_merge([
                'form_title'      => 'Novo Cliente',
                'nome'            => $postVars['nome'] ?? '',
                'email'           => $postVars['email'] ?? '',
                'telefone'        => $postVars['telefone'] ?? '',
                'status_ativo'    => ($postVars['status'] ?? '') == 'ativo' ? 'selected' : '',
                'status_inativo'  => ($postVars['status'] ?? '') == 'inativo' ? 'selected' : '',
                'csrf'            => CSRF::field()
            ], $viewErrors));

            return View::render('layouts/dashboard', [
                'title'   => 'Cadastrar Cliente',
                'content' => $content
            ]);
        }

        try {
            // Instancia a model
            $obCliente = new ClienteModel();
            $obCliente->nome = $postVars['nome'];
            $obCliente->email = $postVars['email'];
            $obCliente->telefone = $postVars['telefone'];
            $obCliente->status = $postVars['status'] ?? 'ativo';
            $obCliente->cadastrar();

            Session::flash('success', 'Cliente cadastrado com sucesso!');
            header('Location: /clientes');
            exit;
        } catch (\Exception $e) {
            Session::flash('error', 'Erro ao cadastrar cliente: ' . $e->getMessage());
            header('Location: /clientes/novo');
            exit;
        }
    }

    /**
     * Renderiza o formulário de edição
     */
    public static function getEdit($request, $id)
    {
        $obCliente = ClienteModel::getClienteById($id);

        if (!$obCliente instanceof ClienteModel) {
            header('Location: /clientes');
            exit;
        }

        View::setLayout('dashboard');

        $content = View::render('pages/clientes/form', [
            'form_title'      => 'Editar Cliente: ' . $obCliente->nome,
            'nome'            => $obCliente->nome,
            'email'           => $obCliente->email,
            'telefone'        => $obCliente->telefone,
            'status_ativo'    => ($obCliente->status == 'ativo') ? 'selected' : '',
            'status_inativo'  => ($obCliente->status == 'inativo') ? 'selected' : '',
            'csrf'            => CSRF::field()
        ]);

        return View::render('layouts/dashboard', [
            'title'   => 'Editar Cliente',
            'content' => $content
        ]);
    }

    /**
     * Processa a atualização
     */
    public static function updateEdit($request, $id)
    {
        $obCliente = ClienteModel::getClienteById($id);
        if (!$obCliente instanceof ClienteModel) {
            header('Location: /clientes');
            exit;
        }

        $postVars = $request->getPostVars();

        // Validação
        $v = Validator::make($postVars, [
            'nome'  => 'required|min:3',
            'email' => 'required|email'
        ]);

        if ($v->fails()) {
            View::setLayout('dashboard');
            
            $viewErrors = [];
            foreach ($v->getErrors() as $field => $msg) {
                $viewErrors['error.' . $field] = $msg;
            }

            $content = View::render('pages/clientes/form', array_merge([
                'form_title'      => 'Editar Cliente: ' . $obCliente->nome,
                'nome'            => $postVars['nome'] ?? '',
                'email'           => $postVars['email'] ?? '',
                'telefone'        => $postVars['telefone'] ?? '',
                'status_ativo'    => ($postVars['status'] ?? '') == 'ativo' ? 'selected' : '',
                'status_inativo'  => ($postVars['status'] ?? '') == 'inativo' ? 'selected' : '',
                'csrf'            => CSRF::field()
            ], $viewErrors));

            return View::render('layouts/dashboard', [
                'title'   => 'Editar Cliente',
                'content' => $content
            ]);
        }

        try {
            $obCliente->nome = $postVars['nome'];
            $obCliente->email = $postVars['email'];
            $obCliente->telefone = $postVars['telefone'];
            $obCliente->status = $postVars['status'];
            $obCliente->atualizar();

            Session::flash('success', 'Cliente atualizado com sucesso!');
            header('Location: /clientes');
            exit;
        } catch (\Exception $e) {
            Session::flash('error', 'Erro ao atualizar cliente: ' . $e->getMessage());
            header('Location: /clientes/' . $id . '/editar');
            exit;
        }
    }

    /**
     * Exclui um cliente
     */
    public static function delete($request, $id)
    {
        $obCliente = ClienteModel::getClienteById($id);
        if ($obCliente instanceof ClienteModel) {
            $obCliente->excluir();
            Session::flash('success', 'Cliente excluído com sucesso!');
        }

        header('Location: /clientes');
        exit;
    }
}
