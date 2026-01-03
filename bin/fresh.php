#!/usr/bin/env php
<?php
/**
 * Database Reset & Seed for Infinity Framework
 * 
 * Reseta o banco e aplica migrations + dados iniciais
 * Usage: php bin/fresh.php
 */

require_once __DIR__ . '/../includes/app.php';

echo "🔄 Iniciando reset do banco de dados...\n\n";

try {
    $db = new \App\Config\src\Database();

    $tables = $db->execute("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", 
        [getenv('DB_NAME')]
    )->fetchAll(\PDO::FETCH_ASSOC);

    echo "Removendo tabelas existentes...\n";
    $db->execute("SET FOREIGN_KEY_CHECKS=0");
    
    foreach ($tables as $table) {
        $tableName = $table['TABLE_NAME'];
        $db->execute("DROP TABLE IF EXISTS `{$tableName}`");
        echo "  ✓ Removida: $tableName\n";
    }
    
    $db->execute("SET FOREIGN_KEY_CHECKS=1");

    echo "\n✓ Banco limpo\n";

    echo "\nAplicando migrations...\n";
    system('php ' . __DIR__ . '/migrate.php');

    echo "\n✅ Reset e migrations concluídos!\n";

} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
