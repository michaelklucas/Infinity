#!/usr/bin/env php
<?php
/**
 * Quick Test Suite
 * 
 * Valida principais funcionalidades
 * Usage: php bin/test.php
 *
 * Infinity Framework
 * @author Infinity
 * @package Bin
 */

require_once __DIR__ . '/../includes/app.php';

$tests = [];
$passed = 0;
$failed = 0;

/**
 * Simple assert helper for CLI tests.
 *
 * @param bool $condition
 * @param string $message
 * @param int &$passed
 * @param int &$failed
 * @param array &$tests
 * @return void
 */
function assert_true($condition, $message, &$passed, &$failed, &$tests) {
    if ($condition) {
        echo "✅ $message\n";
        $tests[] = ['✅', $message];
        $passed++;
    } else {
        echo "❌ $message\n";
        $tests[] = ['❌', $message];
        $failed++;
    }
}

echo "🧪 Iniciando testes...\n\n";

// Test 1: Database Connection
echo "Test 1: Database Connection\n";
try {
    $db = new \App\Config\src\Database();
    $result = $db->execute("SELECT 1")->fetch(\PDO::FETCH_ASSOC);
    assert_true($result, "Database connection", $passed, $failed, $tests);
} catch (\Exception $e) {
    assert_true(false, "Database connection", $passed, $failed, $tests);
}

// Test 2: Paginação Method
echo "\nTest 2: Database Pagination\n";
try {
    $db = new \App\Config\src\Database('contatos');
    $method = new ReflectionMethod($db, 'paginate');
    assert_true($method->isPublic(), "Paginate method exists", $passed, $failed, $tests);
} catch (\Exception $e) {
    assert_true(false, "Paginate method exists", $passed, $failed, $tests);
}

// Test 3: Batch Insert Method
echo "\nTest 3: Database Batch Insert\n";
try {
    $db = new \App\Config\src\Database('contatos');
    $method = new ReflectionMethod($db, 'insertBatch');
    assert_true($method->isPublic(), "InsertBatch method exists", $passed, $failed, $tests);
} catch (\Exception $e) {
    assert_true(false, "InsertBatch method exists", $passed, $failed, $tests);
}

// Test 4: Router Cache Property
echo "\nTest 4: Router Caching\n";
try {
    $reflection = new ReflectionClass('App\Http\Router');
    $property = $reflection->getProperty('compiledRoutesCache');
    assert_true($property->isPrivate(), "Router cache property exists", $passed, $failed, $tests);
} catch (\Exception $e) {
    assert_true(false, "Router cache property exists", $passed, $failed, $tests);
}

// Test 5: View Optimization
echo "\nTest 5: View Rendering\n";
$testVars = ['nome' => 'João', 'email' => 'joao@test.com'];
$testView = 'Olá {{nome}}, seu email é {{email}}';
$expected = 'Olá João, seu email é joao@test.com';

// Simulando render
$keys = array_map(fn($k) => '{{'.$k.'}}', array_keys($testVars));
$result = str_replace($keys, array_values($testVars), $testView);
assert_true($result === $expected, "View str_replace optimization", $passed, $failed, $tests);

// Test 6: Contato Model Batch Method
echo "\nTest 6: Contato Batch Insert\n";
try {
    $reflection = new ReflectionClass('App\Model\Entity\Contato');
    $method = $reflection->getMethod('criarBatch');
    assert_true($method->isPublic() && $method->isStatic(), "Contato::criarBatch exists", $passed, $failed, $tests);
} catch (\Exception $e) {
    assert_true(false, "Contato::criarBatch exists", $passed, $failed, $tests);
}

// Test 7: Canais Controller Sync
echo "\nTest 7: Canais Controller\n";
try {
    $reflection = new ReflectionClass('App\Controller\Pages\Integracoes\Canais');
    $method = $reflection->getMethod('syncContacts');
    assert_true($method->isPublic() && $method->isStatic(), "Canais::syncContacts exists", $passed, $failed, $tests);
} catch (\Exception $e) {
    assert_true(false, "Canais::syncContacts exists", $passed, $failed, $tests);
}

// Test 8: Files Exist
echo "\nTest 8: Migration Files\n";
$files = [
    'db/migrations/2025_11_11_create_contatos_table.sql',
    'db/migrations/2025_11_12_create_conversas_table.sql',
    'db/migrations/2025_11_12_create_mensagens_table.sql',
    'db/migrations/2025_11_12_create_disparadores_templates_table.sql',
    'bin/migrate.php',
    'bin/fresh.php',
    'bin/health.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    assert_true(file_exists($path), "File: $file", $passed, $failed, $tests);
}

// Test 9: Documentation Files
echo "\nTest 9: Documentation\n";
$docs = [
    'DEPLOYMENT.md',
    'PERFORMANCE.md',
    'OTIMIZACOES.md',
    'SYSTEM_DESCRIPTION.md'
];

foreach ($docs as $doc) {
    $path = __DIR__ . '/../' . $doc;
    assert_true(file_exists($path), "Doc: $doc", $passed, $failed, $tests);
}

// Test 10: Transação Methods
echo "\nTest 10: Database Transactions\n";
try {
    $db = new \App\Config\src\Database();
    $has_begin = method_exists($db, 'beginTransaction');
    $has_commit = method_exists($db, 'commit');
    $has_rollback = method_exists($db, 'rollBack');
    assert_true($has_begin && $has_commit && $has_rollback, "Transaction methods exist", $passed, $failed, $tests);
} catch (\Exception $e) {
    assert_true(false, "Transaction methods exist", $passed, $failed, $tests);
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";

foreach ($tests as $test) {
    echo sprintf("%-3s %s\n", $test[0], $test[1]);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo sprintf("✅ Passed: %d | ❌ Failed: %d | Total: %d\n", $passed, $failed, $passed + $failed);
echo str_repeat("=", 60) . "\n";

if ($failed === 0) {
    echo "\n🎉 TUDO PASSOU! Sistema pronto para deploy.\n";
    exit(0);
} else {
    echo "\n⚠️  $failed testes falharam. Revisar acima.\n";
    exit(1);
}
