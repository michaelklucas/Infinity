<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

/**
 * Classe responsável por gerenciar a renderização de views e layouts
 */
class View
{
    /**
     * Variáveis globais da view
     * @var array
     */
    private static $vars = [];

    /**
     * Layout padrão
     * @var string
     */
    private static $layout = null;

    /**
     * Método responsável por inicializar as variáveis globais da view
     * @param array $vars
     */
    public static function init($vars = [])
    {
        self::$vars = $vars;
    }

    /**
     * Define o layout a ser utilizado
     * @param string $layout Nome do arquivo em resources/view/layouts/
     */
    public static function setLayout($layout)
    {
        self::$layout = $layout;
    }

    /**
     * Verifica se há um layout definido
     * @return bool
     */
    public static function hasLayout()
    {
        return !empty(self::$layout);
    }

    /**
     * Método responsável por retornar o conteúdo de uma view
     * @param string $view
     * @return string
     */
    private static function getContentView($view)
    {
        $file = __DIR__ . '/../../resources/view/' . $view . '.html';
        return file_exists($file) ? file_get_contents($file) : '';
    }

    /**
     * Cache de mensagens flash para a requisição atual
     * @var array
     */
    private static $flashCache = null;

    /**
     * Método responsável por renderizar o conteúdo de uma view
     * @param string $view
     * @param array $vars
     * @return string
     */
    public static function render($view, $vars = [])
    {
        // Conteúdo da view
        $contentView = self::getContentView($view);

        // Carrega as mensagens flash apenas uma vez por requisição
        if (self::$flashCache === null) {
            self::$flashCache = Session::allFlash();
        }

        // Adiciona mensagens flash automaticamente às variáveis
        foreach (self::$flashCache as $key => $message) {
            $vars['flash.' . $key] = $message;
        }

        // Merge de variáveis
        $vars = array_merge(self::$vars, $vars);

        // Chaves do array de variáveis
        $keys = array_map(fn($k) => '{{' . $k . '}}', array_keys($vars));

        // Conteúdo renderizado
        $rendered = str_replace($keys, array_values($vars), $contentView);

        // Limpa placeholders não preenchidos
        return preg_replace('/\{\{[a-z0-9._-]+\}\}/i', '', $rendered);
    }
}