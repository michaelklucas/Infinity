<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Controller\Pages
 */

namespace App\Controller\Pages;

use App\Utils\View;

/**
 * Controlador Base - Gerencia o layout comum das páginas
 */
class Page
{
    /**
     * Método responsável por retornar o conteúdo (view) da nossa página genérica
     * @param string $title
     * @param string $content
     * @return string
     */
    public static function getPage($title, $content)
    {
        // Define o layout master (sempre usamos o main como shell principal)
        return View::render('layouts/main', [
            'title'   => $title,
            'content' => $content
        ]);
    }
}
