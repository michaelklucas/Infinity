<?php

require_once dirname(__DIR__) . '/includes/app.php';

use App\Utils\Documentation;

// Carregar documentação
require_once dirname(__DIR__) . '/app/Config/documentation.php';

$format = $_GET['format'] ?? 'json';

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="documentation.json"');
    echo Documentation::exportJSON();
    
} elseif ($format === 'markdown') {
    header('Content-Type: text/markdown');
    header('Content-Disposition: attachment; filename="DOCUMENTATION.md"');
    echo Documentation::exportMarkdown();
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid format']);
}
