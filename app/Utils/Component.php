<?php

namespace App\Utils;

use App\Utils\View;

/**
 * Sistema simples de Componentes para Views
 */
class Component
{
    /**
     * Renderiza um componente blade-like
     * @param string $name Nome do componente (ex: 'alert')
     * @param array $props Dados passados para o componente
     * @return string HTML renderizado
     */
    public static function render($name, $props = [])
    {
        // Procura em resources/view/components/{name}.html
        $viewPath = 'components/' . $name;
        
        // Renderiza usando a engine de View padrão, passando as props como variáveis
        return View::render($viewPath, $props);
    }
}
