#!/usr/bin/env php
<?php
/**
 * Migration Runner for LudraLeads
 * 
 * Executa todas as migrations SQL na pasta db/migrations/
 * Usage: php bin/migrate.php [status|migrate]
 *
 * Infinity Framework
 * @author Infinity
 * @package Bin
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/app.php';

use App\Database\Migration;

$command = $argv[1] ?? 'migrate';
$migrator = new Migration();

switch ($command) {
    case 'status':
        $migrator->status();
        break;

    case 'migrate':
        echo "\n=== Iniciando Migrations ===\n";
        if ($migrator->runPendingMigrations()) {
            echo "\n✓ Migrations executadas com sucesso!\n";
            exit(0);
        } else {
            echo "\n✗ Erro ao executar migrations!\n";
            exit(1);
        }
        break;

    default:
        echo "Comando desconhecido: {$command}\n";
        echo "Comandos disponíveis: migrate, status\n";
        exit(1);
}

