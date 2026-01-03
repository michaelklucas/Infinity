<?php

/**
 * Documentation Generator - CLI Command
 * Gera e exporta documentação do framework
 * 
 * Usage: php bin/generate-docs.php [format] [output]
 * Formats: json, markdown, html
 * 
 * Examples:
 *   php bin/generate-docs.php json
 *   php bin/generate-docs.php markdown docs.md
 *   php bin/generate-docs.php html docs.html
 */

require_once dirname(__DIR__) . '/includes/app.php';

use App\Utils\Documentation;

// Carregar documentação
require_once dirname(__DIR__) . '/app/Config/documentation.php';

$format = $argv[1] ?? 'json';
$output = $argv[2] ?? null;

$formats = ['json', 'markdown', 'html'];

if (!in_array($format, $formats)) {
    echo "❌ Formato inválido: $format\n";
    echo "Formatos disponíveis: " . implode(', ', $formats) . "\n";
    exit(1);
}

echo "Gerando documentação em formato: $format\n";

$content = '';
$filename = '';

switch ($format) {
    case 'json':
        $content = Documentation::exportJSON();
        $filename = $output ?? 'documentation.json';
        break;
    
    case 'markdown':
        $content = Documentation::exportMarkdown();
        $filename = $output ?? 'DOCUMENTATION.md';
        break;
    
    case 'html':
        $content = generateHTML();
        $filename = $output ?? 'documentation.html';
        break;
}

if ($output) {
    $filepath = dirname(__DIR__) . '/' . $filename;
} else {
    $filepath = dirname(__DIR__) . '/' . $filename;
}

if (file_put_contents($filepath, $content)) {
    echo "✅ Documentação gerada com sucesso!\n";
    echo "📄 Arquivo: $filepath\n";
    echo "📊 Tamanho: " . formatBytes(filesize($filepath)) . "\n";
    
    // Estatísticas
    $stats = Documentation::stats();
    echo "\n📈 Estatísticas:\n";
    echo "  • Componentes: {$stats['total_components']}\n";
    echo "  • Métodos: {$stats['total_methods']}\n";
    echo "  • Categorias: {$stats['total_categories']}\n";
} else {
    echo "❌ Erro ao gerar documentação\n";
    exit(1);
}

function generateHTML()
{
    $stats = Documentation::stats();
    $docs = Documentation::list();
    
    $html = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Framework Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; margin-bottom: 20px; }
        h2 { color: #667eea; margin-top: 30px; margin-bottom: 15px; border-left: 3px solid #667eea; padding-left: 10px; }
        h3 { color: #764ba2; margin-top: 20px; margin-bottom: 10px; }
        p { margin-bottom: 10px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; margin: 10px 0; }
        .component { background: #f9f9f9; border-left: 4px solid #667eea; padding: 15px; margin-bottom: 20px; border-radius: 3px; }
        .meta { font-size: 0.9em; color: #666; margin: 10px 0; }
        .tags { display: flex; gap: 5px; flex-wrap: wrap; margin: 10px 0; }
        .tag { background: #667eea; color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.85em; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f4f4f4; font-weight: bold; }
        footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 0.9em; }
        .toc { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .toc ul { list-style: none; margin-left: 20px; }
        .toc a { text-decoration: none; color: #667eea; }
        .toc a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Framework Documentation</h1>
        <p style="font-size: 1.1em; margin-bottom: 30px;">
            Documentação completa do Infinity Framework com todos os componentes, métodos e exemplos.
        </p>

        <div class="toc">
            <h3>Sumário</h3>
            <ul>
HTML;
    
    foreach (Documentation::categories() as $category) {
        $html .= "<li><a href=\"#$category\">" . htmlspecialchars($category) . "</a></li>";
    }
    
    $html .= <<<'HTML'
            </ul>
        </div>

HTML;
    
    foreach (Documentation::categories() as $category) {
        $html .= '<h2 id="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</h2>';
        
        foreach (Documentation::listByCategory($category) as $name => $doc) {
            $html .= '<div class="component">';
            $html .= '<h3>' . htmlspecialchars($doc['title']) . '</h3>';
            $html .= '<div class="meta">';
            $html .= '<strong>Versão:</strong> ' . $doc['version'] . ' | ';
            $html .= '<strong>Autor:</strong> ' . $doc['author'] . ' | ';
            $html .= '<strong>Desde:</strong> ' . $doc['since'];
            $html .= '</div>';
            
            $html .= '<p>' . htmlspecialchars($doc['description']) . '</p>';
            
            if (!empty($doc['tags'])) {
                $html .= '<div class="tags">';
                foreach ($doc['tags'] as $tag) {
                    $html .= '<span class="tag">' . htmlspecialchars($tag) . '</span>';
                }
                $html .= '</div>';
            }
            
            if (!empty($doc['example'])) {
                $html .= '<h4>Exemplo:</h4>';
                $html .= '<pre><code>' . htmlspecialchars($doc['example']) . '</code></pre>';
            }
            
            if (!empty($doc['methods'])) {
                $html .= '<h4>Métodos:</h4>';
                $html .= '<table>';
                $html .= '<thead><tr><th>Método</th><th>Descrição</th><th>Retorno</th></tr></thead>';
                $html .= '<tbody>';
                foreach ($doc['methods'] as $method) {
                    $html .= '<tr>';
                    $html .= '<td><code>' . htmlspecialchars($method['name']) . '()</code></td>';
                    $html .= '<td>' . htmlspecialchars($method['description']) . '</td>';
                    $html .= '<td><code>' . htmlspecialchars($method['return']) . '</code></td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
                $html .= '</table>';
            }
            
            $html .= '</div>';
        }
    }
    
    $html .= <<<'HTML'
        <footer>
            <p>Infinity Framework Documentation © 2025</p>
            <p>Generated: <time datetime="2025-11-12">2025-11-12</time></p>
        </footer>
    </div>
</body>
</html>
HTML;
    
    return $html;
}

function formatBytes($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
