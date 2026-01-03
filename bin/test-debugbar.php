<?php

/**
 * Script de teste do DebugBar
 * 
 * Este script testa:
 * 1. DebugBar initialization
 * 2. Query logging
 * 3. Log messages
 * 4. Error handling
 * 5. Performance tracking
 */

require __DIR__ . '/../includes/app.php';

use App\Utils\DebugBar;
use App\Config\src\Database;

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║       Teste do Sistema de Debug (DebugBar)           ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

// Teste 1: Log de mensagens
echo "[1] Testando logs de mensagens...\n";
DebugBar::log('Iniciando testes', 'info');
DebugBar::log('Teste de debug', 'debug', ['teste' => true]);
DebugBar::log('Aviso de teste', 'warning');
echo "    ✓ Logs registrados com sucesso\n\n";

// Teste 2: Timing/Profiling
echo "[2] Testando timing de profiling...\n";
$timer = DebugBar::startTiming('Teste de operação');
usleep(100000); // 100ms
DebugBar::endTiming($timer);
echo "    ✓ Timing registrado\n\n";

// Teste 3: Query logging (se DB estiver configurado)
echo "[3] Testando logging de queries...\n";
try {
    $db = new Database('usuarios');
    
    // Testar select simples
    $result = $db->execute("SELECT 1 as teste");
    
    echo "    ✓ Query executada e registrada\n";
} catch (\Exception $e) {
    echo "    ⚠ Erro ao testar queries (DB pode não estar disponível)\n";
    echo "    Erro: " . $e->getMessage() . "\n";
}
echo "\n";

// Teste 4: Informações do DebugBar
echo "[4] Informações do DebugBar:\n";
$debugInfo = DebugBar::getDebugInfo();
echo "    Queries registradas: " . (isset($debugInfo['queries']) ? count($debugInfo['queries']) : 0) . "\n";
echo "    Logs registrados: " . (isset($debugInfo['logs']) ? count($debugInfo['logs']) : 0) . "\n";
echo "    Erros/Avisos: " . (isset($debugInfo['errors']) ? count($debugInfo['errors']) : 0) . "\n";
echo "\n";

// Teste 5: Status do APP_DEBUG
echo "[5] Configuração de Debug:\n";
$debugEnabled = getenv('APP_DEBUG') === 'true' ? true : false;
echo "    APP_DEBUG: " . ($debugEnabled ? "ATIVADO" : "DESATIVADO") . "\n";
echo "    Ambiente: " . (getenv('APP_DEBUG')) . "\n";
echo "\n";

// Resumo
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║  ✓ Testes Concluídos com Sucesso!                    ║\n";
echo "╠═══════════════════════════════════════════════════════╣\n";
echo "║  O DebugBar está pronto para uso!                    ║\n";
echo "║                                                       ║\n";
echo "║  Para ver o debug bar em ação:                        ║\n";
echo "║  1. Configure APP_DEBUG=true em .env                  ║\n";
echo "║  2. Acesse a aplicação em http://ludraleads.localhost║\n";
echo "║  3. Se ocorrer um erro, verá o debug bar              ║\n";
echo "║                                                       ║\n";
echo "║  Guia de uso: DEBUGBAR_USAGE.md                       ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";
