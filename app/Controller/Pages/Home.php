<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Controller\Pages
 */

namespace App\Controller\Pages;

use App\Utils\View;

/**
 * Controlador da Página Inicial
 */
class Home extends Page
{
    /**
     * Método responsável por retornar o conteúdo (view) da nossa home
     * @return string
     */
    public static function getHome()
    {
        // Conteúdo da view Home
        $content = View::render('pages/home', [
            'name'        => 'Infinity Framework',
            'description' => 'Um framework PHP moderno, leve e extensível.',
            'features'    => ''
        ]);

        // Retorna a página completa utilizando o layout base
        return parent::getPage('Home > Infinity Framework', $content);
    }
}
