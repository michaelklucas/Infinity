<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

/**
 * Sistema de Autodoc Embutido - Gerencia a documentação técnica dos componentes
 * Permite registrar classes, métodos e exemplos para exibição painel de desenvolvedor
 */
class Documentation
{
    /**
     * Armazena os registros de documentação
     * @var array
     */
    private static $docs = [];

    /**
     * Armazena as categorias únicas registradas
     * @var array
     */
    private static $categories = [];

    /**
     * Registra a documentação de um componente (classe/módulo)
     * @param string $name Nome identificador (ID)
     * @param array $config Configurações do componente
     * @return array
     */
    public static function register($name, $config)
    {
        $defaults = [
            'title'       => $name,
            'description' => '',
            'category'    => 'Geral',
            'version'     => '1.0.0',
            'author'      => 'Infinity',
            'tags'        => [],
            'example'     => '',
            'methods'     => [],
            'properties'  => [],
            'usage'       => '',
            'deprecated'  => false,
            'since'       => '1.0.0',
            'see'         => [],
            'params'      => []
        ];

        $doc = array_merge($defaults, $config);
        self::$docs[$name] = $doc;
        
        // Adiciona à lista de categorias caso não exista
        if (!in_array($doc['category'], self::$categories)) {
            self::$categories[] = $doc['category'];
        }

        return self::$docs[$name];
    }

    /**
     * Registra um método específico dentro de um componente
     * @param string $component Nome do componente pai
     * @param string $name Nome do método
     * @param array $config Atributos do método
     * @return array|null
     */
    public static function method($component, $name, $config = [])
    {
        if (!isset(self::$docs[$component])) {
            return null;
        }

        $method = array_merge([
            'name'        => $name,
            'description' => '',
            'params'      => [],
            'return'      => 'void',
            'example'     => '',
            'deprecated'  => false
        ], $config);

        self::$docs[$component]['methods'][$name] = $method;
        return $method;
    }

    /**
     * Registra uma propriedade de classe na documentação
     * @param string $component
     * @param string $name
     * @param array $config
     * @return array|null
     */
    public static function property($component, $name, $config = [])
    {
        if (!isset(self::$docs[$component])) {
            return null;
        }

        $property = array_merge([
            'name'        => $name,
            'type'        => 'mixed',
            'description' => '',
            'default'     => null,
            'access'      => 'public'
        ], $config);

        self::$docs[$component]['properties'][$name] = $property;
        return $property;
    }

    /**
     * Obtém os dados de documentação de um componente
     * @param string $name
     * @return array|null
     */
    public static function get($name)
    {
        return self::$docs[$name] ?? null;
    }

    /**
     * Retorna todos os componentes documentados
     * @return array
     */
    public static function list()
    {
        return self::$docs;
    }

    /**
     * Filtra componentes por uma categoria específica
     * @param string $category
     * @return array
     */
    public static function listByCategory($category)
    {
        return array_filter(self::$docs, function($doc) use ($category) {
            return $doc['category'] === $category;
        });
    }

    /**
     * Retorna a lista de todas as categorias registradas
     * @return array
     */
    public static function categories()
    {
        return self::$categories;
    }

    /**
     * Busca na documentação por termos de pesquisa
     * @param string $query
     * @return array
     */
    public static function search($query)
    {
        $query = strtolower($query);
        $results = [];

        foreach (self::$docs as $name => $doc) {
            $title = strtolower($doc['title']);
            $description = strtolower($doc['description']);
            $tags = implode(' ', array_map('strtolower', $doc['tags'] ?? []));

            if (strpos($title, $query) !== false || 
                strpos($description, $query) !== false ||
                strpos($tags, $query) !== false ||
                strpos(strtolower($name), $query) !== false) {
                $results[$name] = $doc;
            }
        }

        return $results;
    }

    /**
     * Exporta toda a documentação no formato JSON
     * @return string
     */
    public static function exportJSON()
    {
        return json_encode(self::$docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Exporta a documentação formatada para Markdown
     * @return string
     */
    public static function exportMarkdown()
    {
        $md = "# Documentação do Framework\n\n";
        $md .= "Gerado em: " . date('d/m/Y H:i:s') . "\n\n";

        foreach (self::$categories as $category) {
            $docs = self::listByCategory($category);
            $md .= "## Categoria: $category\n\n";

            foreach ($docs as $name => $doc) {
                $md .= "### " . $doc['title'] . "\n\n";
                $md .= $doc['description'] . "\n\n";

                if (!empty($doc['example'])) {
                    $md .= "**Exemplo:**\n```php\n" . $doc['example'] . "\n```\n\n";
                }

                if (!empty($doc['methods'])) {
                    $md .= "**Métodos Disponíveis:**\n";
                    foreach ($doc['methods'] as $method) {
                        $md .= "- `" . $method['name'] . "()`: " . $method['description'] . "\n";
                    }
                    $md .= "\n";
                }
            }
        }

        return $md;
    }

    /**
     * Retorna estatísticas gerais da documentação carregada
     * @return array
     */
    public static function stats()
    {
        $total = count(self::$docs);
        $methods = 0;
        $properties = 0;

        foreach (self::$docs as $doc) {
            $methods += count($doc['methods'] ?? []);
            $properties += count($doc['properties'] ?? []);
        }

        return [
            'total_components' => $total,
            'total_methods' => $methods,
            'total_properties' => $properties,
            'total_categories' => count(self::$categories),
            'categories' => self::$categories
        ];
    }

    /**
     * Gera uma visualização HTML pré-formatada para os documentos
     * @param string|null $component Se informado, renderiza apenas este componente
     * @return string
     */
    public static function renderHTML($component = null)
    {
        if ($component && $doc = self::get($component)) {
            return self::renderComponent($doc);
        }

        $html = '<div class="docs-list">';
        foreach (self::$categories as $category) {
            $html .= '<div class="docs-category">';
            $html .= '<h3>' . $category . '</h3>';
            foreach (self::listByCategory($category) as $name => $doc) {
                $html .= '<div class="doc-item">';
                $html .= '<h4>' . $doc['title'] . '</h4>';
                $html .= '<p>' . $doc['description'] . '</p>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza o HTML detalhado de um componente individual
     * @param array $doc
     * @return string
     */
    private static function renderComponent($doc)
    {
        $html = '<div class="doc-component">';
        $html .= '<h2>' . $doc['title'] . '</h2>';
        $html .= '<p class="version">Versão ' . $doc['version'] . ' por ' . $doc['author'] . '</p>';
        $html .= '<p>' . $doc['description'] . '</p>';

        if (!empty($doc['example'])) {
            $html .= '<h4>Exemplo de Uso:</h4>';
            $html .= '<pre><code>' . htmlspecialchars($doc['example']) . '</code></pre>';
        }

        if (!empty($doc['methods'])) {
            $html .= '<h4>Métodos:</h4>';
            $html .= '<ul>';
            foreach ($doc['methods'] as $method) {
                $html .= '<li><code>' . $method['name'] . '()</code> - ' . $method['description'] . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Verifica se um componente possui documentação registrada
     * @param string $name
     * @return bool
     */
    public static function isDocumented($name)
    {
        return isset(self::$docs[$name]);
    }

    /**
     * Retorna a contagem de componentes por status de depreciação
     * @return array
     */
    public static function countByStatus()
    {
        $deprecated = 0;
        foreach (self::$docs as $doc) {
            if ($doc['deprecated']) $deprecated++;
        }

        return [
            'total'      => count(self::$docs),
            'active'     => count(self::$docs) - $deprecated,
            'deprecated' => $deprecated
        ];
    }

    /**
     * Obtém componentes relacionados (links cruzados)
     * @param string $name
     * @return array
     */
    public static function related($name)
    {
        $doc = self::get($name);
        if (!$doc || empty($doc['see'])) {
            return [];
        }

        $related = [];
        foreach ($doc['see'] as $related_name) {
            if ($related_doc = self::get($related_name)) {
                $related[$related_name] = $related_doc;
            }
        }

        return $related;
    }

    /**
     * Gera um mapa estruturado da documentação organizada por categoria
     * @return array
     */
    public static function generateMap()
    {
        $map = [];
        foreach (self::$categories as $category) {
            $map[$category] = [];
            foreach (self::listByCategory($category) as $name => $doc) {
                $map[$category][] = [
                    'name'       => $name,
                    'title'      => $doc['title'],
                    'methods'    => count($doc['methods'] ?? []),
                    'deprecated' => $doc['deprecated']
                ];
            }
        }
        return $map;
    }
}
