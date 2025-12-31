<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Database
 */

namespace App\Database;

use App\Config\src\Database;
use PDO;

/**
 * Sistema de Migrations - Gerencia o versionamento do banco de dados
 */
class Migration
{
    /**
     * Instância de Database
     * @var Database
     */
    protected $db;

    /**
     * Caminho das migrations
     * @var string
     */
    protected $migrationsPath;

    /**
     * Nome da tabela de controle de migrations
     * @var string
     */
    protected $migrationsTable = 'migrations';

    /**
     * Construtor da classe
     */
    public function __construct()
    {
        $this->db = new Database($this->migrationsTable);
        $this->migrationsPath = __DIR__ . '/../../db/migrations';
    }

    /**
     * Cria a tabela de controle de migrations caso ela não exista
     * @return bool
     */
    public function createMigrationsTable()
    {
        $connection = $this->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $connection->exec($sql);
            return true;
        } catch (\Exception $e) {
            echo "Erro ao criar tabela de migrations: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Retorna uma conexão PDO direta
     * @return PDO
     */
    private function getConnection()
    {
        $host = getenv('DB_HOST');
        $database = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASS');

        $dsn = "mysql:host={$host};dbname={$database}";
        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
    }

    /**
     * Obtém a lista de migrations já executadas no banco
     * @return array
     */
    public function getExecutedMigrations()
    {
        $connection = $this->getConnection();
        $stmt = $connection->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtém a lista de arquivos de migrations pendentes
     * @return array
     */
    public function getPendingMigrations()
    {
        $executed = $this->getExecutedMigrations();
        $migrations = [];

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return $migrations;
        }

        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $executed)) {
                $migrations[] = [
                    'name' => $filename,
                    'path' => $file
                ];
            }
        }

        return $migrations;
    }

    /**
     * Executa um arquivo de migration específico
     * @param string $migrationName
     * @param string $migrationPath
     * @return bool
     */
    public function runMigration($migrationName, $migrationPath)
    {
        try {
            $connection = $this->getConnection();
            $sql = file_get_contents($migrationPath);
            
            // Separa os comandos por ponto e vírgula
            $commands = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($commands as $command) {
                if (!empty($command)) {
                    $connection->exec($command);
                }
            }

            // Registra a execução na tabela de controle
            $stmt = $connection->prepare(
                "INSERT INTO {$this->migrationsTable} (migration) VALUES (:migration)"
            );
            $stmt->execute([':migration' => $migrationName]);

            return true;
        } catch (\Exception $e) {
            echo "Erro ao executar migration {$migrationName}: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Executa todas as migrations pendentes de uma vez
     * @return bool
     */
    public function runPendingMigrations()
    {
        $this->createMigrationsTable();
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            echo "INFO: Nenhuma migration pendente\n";
            return true;
        }

        echo "Executando " . count($pending) . " migration(s)...\n";

        $executed = 0;
        $failed = 0;

        foreach ($pending as $migration) {
            echo "Executando: {$migration['name']}... ";
            if ($this->runMigration($migration['name'], $migration['path'])) {
                echo "OK\n";
                $executed++;
            } else {
                echo "FALHA\n";
                $failed++;
            }
        }

        echo "\n--- Resultado ---\n";
        echo "Executadas: {$executed}\n";
        echo "Falhas: {$failed}\n";

        return $failed === 0;
    }

    /**
     * Exibe o status atual das migrations
     */
    public function status()
    {
        $this->createMigrationsTable();

        $executed = $this->getExecutedMigrations();
        $pending = $this->getPendingMigrations();

        echo "\n=== STATUS DE MIGRATIONS ===\n\n";

        if (!empty($executed)) {
            echo "Executadas:\n";
            foreach ($executed as $migration) {
                echo "  - {$migration}\n";
            }
        }

        if (!empty($pending)) {
            echo "\nPendentes:\n";
            foreach ($pending as $migration) {
                echo "  - {$migration['name']}\n";
            }
        } else {
            echo "\nINFO: Todas as migrations foram executadas!\n";
        }

        echo "\n";
    }
}
