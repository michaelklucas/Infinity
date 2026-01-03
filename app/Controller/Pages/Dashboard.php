<?php

namespace App\Controller\Pages;

use App\Utils\View;

class Dashboard extends Page
{
    /**
     * Renderiza a página do Dashboard com layout exclusivo
     */
    public static function getDashboard($request)
    {
        // Aqui está o segredo: Muda o layout para 'dashboard' 
        // em vez do 'main' padrão que o Page::getPage usa normalmente.
        View::setLayout('dashboard');

        // Renderiza o conteúdo interno
        $content = View::render('pages/dashboard');

        // Retorna a página completa
        return View::render('layouts/dashboard', [
            'title'   => 'Painel de Controle',
            'content' => $content
        ]);
    }
}
