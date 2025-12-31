#!/usr/bin/env php
<?php
/**
 * Health Check
 * 
 * Verifica status de todos os componentes críticos
 * Usage: php bin/health.php
 *
 * Infinity Framework
 * @author Infinity
 * @package Bin
 */

require_once __DIR__ . '/../includes/app.php';

$checks = [];
$status = 0; // 0 = ok, 1 = warning, 2 = error

echo "🏥 Iniciando Health Check...\n\n";

// 1. PHP Version
echo "1️⃣  PHP Version: ";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0') >= 0) {
    echo "✅ $phpVersion\n";
    $checks[] = ['PHP Version', 'OK', 'OK'];
} else {
    echo "❌ $phpVersion (requerido 7.4+)\n";
    $status = 2;
    $checks[] = ['PHP Version', 'ERROR', "Requerido 7.4+, encontrado $phpVersion"];
}

// 2. Extensões PHP
echo "\n2️⃣  Extensões PHP:\n";
$requiredExtensions = ['pdo', 'curl', 'json', 'gd'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "  ✅ $ext\n";
        $checks[] = ["Ext: $ext", 'OK', 'OK'];
    } else {
        echo "  ❌ $ext\n";
        $status = max($status, 2);
        $checks[] = ["Ext: $ext", 'ERROR', "Extensão não carregada"];
    }
}

// 3. Database
echo "\n3️⃣  Conexão com Banco:\n";
try {
    $db = new \App\Config\src\Database();
    $result = $db->execute("SELECT 1")->fetch(\PDO::FETCH_ASSOC);
    echo "  ✅ Conectado\n";
    $checks[] = ['Database', 'OK', 'OK'];
    
    // Verificar tabelas necessárias
    $tables = $db->execute("
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('contatos', 'conversas', 'mensagens', 'canais')
    ", [getenv('DB_NAME')])->fetchAll(\PDO::FETCH_ASSOC);
    
    $requiredTables = ['contatos', 'conversas', 'mensagens', 'canais'];
    $foundTables = array_map(fn($t) => $t['TABLE_NAME'], $tables);
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $foundTables)) {
            echo "  ✅ Tabela: $table\n";
            $checks[] = ["Table: $table", 'OK', 'OK'];
        } else {
            echo "  ⚠️  Tabela não encontrada: $table (execute: php bin/migrate.php)\n";
            $status = max($status, 1);
            $checks[] = ["Table: $table", 'WARNING', 'Não encontrada'];
        }
    }
    
} catch (\Exception $e) {
    echo "  ❌ Erro: " . $e->getMessage() . "\n";
    $status = 2;
    $checks[] = ['Database', 'ERROR', $e->getMessage()];
}

// 4. Diretórios
echo "\n4️⃣  Permissões de Diretórios:\n";
$directories = [
    'storage/cache' => ['read', 'write'],
    'storage/logs' => ['read', 'write'],
    'app/Cache' => ['read', 'write'],
    'resources/view' => ['read']
];

foreach ($directories as $dir => $permissions) {
    $path = __DIR__ . '/../' . $dir;
    if (is_dir($path)) {
        echo "  ✅ Existe: $dir\n";
        $checks[] = ["Dir: $dir (exists)", 'OK', 'OK'];
        
        if (in_array('write', $permissions) && !is_writable($path)) {
            echo "  ⚠️  Sem permissão de escrita: $dir\n";
            $status = max($status, 1);
            $checks[] = ["Dir: $dir (write)", 'WARNING', 'Sem permissão de escrita'];
        }
    } else {
        echo "  ⚠️  Não encontrado: $dir\n";
        $status = max($status, 1);
        $checks[] = ["Dir: $dir", 'WARNING', 'Não encontrado'];
    }
}

// 5. Arquivos Críticos
echo "\n5️⃣  Arquivos Críticos:\n";
$criticalFiles = [
    '.env' => 'Configurações',
    'composer.json' => 'Dependências',
    'includes/app.php' => 'Bootstrap'
];

foreach ($criticalFiles as $file => $desc) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        echo "  ✅ $file\n";
        $checks[] = ["File: $file", 'OK', 'OK'];
    } else {
        echo "  ❌ Faltando: $file\n";
        $status = 2;
        $checks[] = ["File: $file", 'ERROR', 'Arquivo não encontrado'];
    }
}

// 6. Evolution API (se configurado)
echo "\n6️⃣  Integração Evolution API:\n";
$baseUrl = getenv('EVOLUTION_API_BASE_URL');
$apiKey = getenv('EVOLUTION_API_KEY');

if ($baseUrl && $apiKey) {
    echo "  ℹ️  Configurado: $baseUrl\n";
    
    // Tentar ping
    try {
        $ch = curl_init($baseUrl . '/api/v1/server');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "  ✅ Respondendo (HTTP $httpCode)\n";
            $checks[] = ['Evolution API', 'OK', 'Respondendo'];
        } else {
            echo "  ⚠️  Response $httpCode\n";
            $status = max($status, 1);
            $checks[] = ['Evolution API', 'WARNING', "HTTP $httpCode"];
        }
    } catch (\Exception $e) {
        echo "  ❌ Erro ao conectar: " . $e->getMessage() . "\n";
        $status = max($status, 1);
        $checks[] = ['Evolution API', 'ERROR', $e->getMessage()];
    }
} else {
    echo "  ⚠️  Não configurado\n";
    $checks[] = ['Evolution API', 'WARNING', 'Não configurado'];
}

// 7. Espaço em Disco
echo "\n7️⃣  Espaço em Disco:\n";
$storagePath = __DIR__ . '/../storage';
$diskFree = disk_free_space($storagePath);
$diskTotal = disk_total_space($storagePath);
$diskUsedPercent = round(((($diskTotal - $diskFree) / $diskTotal) * 100), 2);

echo "  📊 Usado: {$diskUsedPercent}%\n";
echo "  📈 Livre: " . number_format($diskFree / 1024 / 1024 / 1024, 2) . " GB\n";

if ($diskUsedPercent > 90) {
    echo "  ❌ Disco quase cheio!\n";
    $status = 2;
    $checks[] = ['Disk Space', 'ERROR', "Disk {$diskUsedPercent}% full"];
} else if ($diskUsedPercent > 75) {
    echo "  ⚠️  Considere limpeza\n";
    $status = max($status, 1);
    $checks[] = ['Disk Space', 'WARNING', "Disk {$diskUsedPercent}% full"];
} else {
    echo "  ✅ Disponível\n";
    $checks[] = ['Disk Space', 'OK', 'OK'];
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 RESUMO DE CHECKS\n";
echo str_repeat("=", 60) . "\n\n";

$table = array_map(fn($c) => sprintf("| %-30s | %-8s | %-30s |", $c[0], $c[1], $c[2]), $checks);
echo "| Component                      | Status   | Details                    |\n";
echo "|" . str_repeat("-", 58) . "|\n";
foreach ($table as $row) {
    echo $row . "\n";
}
echo "|" . str_repeat("-", 58) . "|\n";

// Status final
echo "\n";
if ($status === 0) {
    echo "✅ ✅ ✅ TUDO OK! Sistema pronto para produção. ✅ ✅ ✅\n";
} else if ($status === 1) {
    echo "⚠️  WARNINGS encontrados. Revisar itens acima.\n";
} else {
    echo "❌ ERROS encontrados. Sistema pode não funcionar corretamente.\n";
}

exit($status);
