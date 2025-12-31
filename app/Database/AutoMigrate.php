<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Database
 */

namespace App\Database;

/**
 * Auto-executor de Migrations - Executa migrations pendentes na inicialização da aplicação
 */
class AutoMigrate
{
    /**
     * Instância única (Singleton)
     * @var AutoMigrate
     */
    private static $instance;

    /**
     * Método responsável por rodar o processo de auto-migration
     * @return bool
     */
    public static function run()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->executePendingMigrations();
    }

    /**
     * Executa as migrations que ainda não foram aplicadas ao banco
     * @return bool
     */
    private function executePendingMigrations()
    {
        try {
            // Verifica se está em ambiente de produção
            $isProduction = getenv('APP_ENV') === 'production';
            
            // Em produção, apenas verifica e notifica, não executa automaticamente para segurança
            if ($isProduction) {
                return $this->checkAndNotify();
            }

            // Em desenvolvimento, executa automaticamente
            $migrator = new Migration();
            $pending = $migrator->getPendingMigrations();

            if (!empty($pending)) {
                // Garante que a tabela de controle existe
                $migrator->createMigrationsTable();

                // Executa cada migration pendente
                foreach ($pending as $migration) {
                    $migrator->runMigration($migration['name'], $migration['path']);
                }

                $this->logMigration(count($pending) . " migration(s) executada(s) com sucesso");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logMigration("Erro ao executar migrations: " . $e->getMessage(), true);
            return false;
        }
    }

    /**
     * Verifica se há migrations pendentes e registra aviso (usado em produção)
     * @return bool
     */
    private function checkAndNotify()
    {
        try {
            $migrator = new Migration();
            $migrator->createMigrationsTable();
            $pending = $migrator->getPendingMigrations();

            if (!empty($pending)) {
                $count = count($pending);
                $this->logMigration(
                    "AVISO: {$count} migration(s) pendente(s) em produção! Execute: php bin/migrate.php",
                    true
                );
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Registra log de operações de migration
     * @param string $message
     * @param bool $isWarning
     */
    private function logMigration($message, $isWarning = false)
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/migrations.log';
        $timestamp = date('Y-m-d H:i:s');
        $prefix = $isWarning ? 'AVISO' : 'INFO';
        
        $logMessage = "[{$timestamp}] {$prefix}: {$message}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Execução automática na inicialização via Web
if (php_sapi_name() !== 'cli') {
    AutoMigrate::run();
}
