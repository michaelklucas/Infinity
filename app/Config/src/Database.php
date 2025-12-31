<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Config\src
 */

namespace App\Config\src;

use \PDO;
use \PDOException;
use \Exception;

/**
 * Abstração de Banco de Dados - Wrapper para PDO (MySQL/MariaDB)
 */
class Database
{
    /**
     * Host de conexão com o banco de dados
     * @var string
     */
    private static $host;

    /**
     * Nome do banco de dados
     * @var string
     */
    private static $name;

    /**
     * Usuário do banco
     * @var string
     */
    private static $user;

    /**
     * Senha de acesso ao banco de dados
     * @var string
     */
    private static $pass;

    /**
     * Porta de acesso ao banco
     * @var int
     */
    private static $port;

    /**
     * Nome da tabela a ser manipulada na instância
     * @var string
     */
    private $table;

    /**
     * Instância de conexão PDO
     * @var PDO
     */
    private $connection;

    /**
     * Método responsável por configurar as credenciais de acesso
     * @param string $host
     * @param string $name
     * @param string $user
     * @param string $pass
     * @param int $port
     */
    public static function config($host, $name, $user, $pass, $port)
    {
        self::$host = $host;
        self::$name = $name;
        self::$user = $user;
        self::$pass = $pass;
        self::$port = $port;
    }

    /**
     * Define a tabela de trabalho e inicia a conexão
     * @param string $table
     */
    public function __construct($table = null)
    {
        $this->table = $table;
        $this->setConnection();
    }

    /**
     * Método interno para estabelecer a conexão com o PDO
     */
    private function setConnection()
    {
        try {
            $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$name . ';port=' . self::$port . ';charset=utf8mb4';
            $this->connection = new PDO($dsn, self::$user, self::$pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Erro na conexão com o banco de dados: ' . $e->getMessage());
        }
    }

    /**
     * Método responsável por executar queries (preparadas) no banco de dados
     * @param string $query
     * @param array $params
     * @return \PDOStatement
     */
    public function execute($query, $params = [])
    {
        try {
            $startTime = microtime(true);
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            $duration = microtime(true) - $startTime;

            // Integração com o Debug Bar para log de performance de queries
            if (class_exists('App\Utils\DebugBar')) {
                \App\Utils\DebugBar::logQuery($query, $params, $duration);
            }

            return $statement;
        } catch (PDOException $e) {
            throw new Exception('Erro na execução da query SQL: ' . $e->getMessage());
        }
    }

    /**
     * Insere dados na tabela definida
     * @param array $values [ campo => valor ]
     * @return int ID gerado pelo banco
     */
    public function insert($values)
    {
        $fields = array_keys($values);
        $binds  = array_pad([], count($fields), '?');

        $query = 'INSERT INTO ' . $this->table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $binds) . ')';

        $this->execute($query, array_values($values));

        return $this->connection->lastInsertId();
    }

    /**
     * Realiza uma consulta (SELECT) no banco
     * @param string $where Condições WHERE
     * @param string $order Ordenação
     * @param string $limit Limite de resultados
     * @param string $fields Campos a serem retornados
     * @return \PDOStatement
     */
    /**
     * Realiza uma consulta (SELECT) no banco
     * @param string $where Condições WHERE
     * @param string $order Ordenação
     * @param string $limit Limite de resultados
     * @param string $fields Campos a serem retornados
     * @param array $params Parâmetros para a query
     * @return \PDOStatement
     */
    public function select($where = null, $order = null, $limit = null, $fields = '*', $params = [])
    {
        $where = $where !== null ? 'WHERE ' . $where : '';
        $order = $order !== null ? 'ORDER BY ' . $order : '';
        $limit = $limit !== null ? 'LIMIT ' . $limit : '';

        $query = 'SELECT ' . $fields . ' FROM ' . $this->table . ' ' . $where . ' ' . $order . ' ' . $limit;

        return $this->execute($query, $params);
    }

    /**
     * Executa um SELECT com INNER JOIN
     * @param string $table1
     * @param string $table2
     * @param string $on
     * @param string $where
     * @param string $order
     * @param string $limit
     * @param string $fields
     * @return \PDOStatement
     */
    public function selectInnerJoin($table1, $table2, $on, $where = null,  $order = null, $limit = null, $fields = '*')
    {
        $where = $where !== null ? 'WHERE ' . $where : '';
        $order = $order !== null ? 'ORDER BY ' . $order : '';
        $limit = $limit !== null ? 'LIMIT ' . $limit : '';

        $query = 'SELECT ' . $fields . ' FROM ' . $table1 . ' INNER JOIN ' . $table2 . ' ON ' . $on . ' ' . $where . ' ' . $order . ' ' . $limit;

        return $this->execute($query);
    }

    /**
     * Método utilitário de paginação automática
     * @param array|string $where Filtros
     * @param int $page Página atual
     * @param int $perPage Resultados por página
     * @param string $order Ordenação
     * @return array
     */
    public function paginate($where = null, $page = 1, $perPage = 15, $order = null)
    {
        $page = max(1, (int)$page);
        $perPage = (int)$perPage;
        $offset = ($page - 1) * $perPage;

        // Calcula o total de registros para a paginação
        $countQuery = 'SELECT COUNT(*) as total FROM ' . $this->table;
        $params = [];

        if (is_array($where)) {
            $whereClause = implode(' AND ', array_map(fn($k) => "$k = ?", array_keys($where)));
            $countQuery .= ' WHERE ' . $whereClause;
            $params = array_values($where);
        } elseif ($where) {
            $countQuery .= ' WHERE ' . $where;
        }

        $resultCount = $this->execute($countQuery, $params);
        $total = (int)$resultCount->fetch(\PDO::FETCH_ASSOC)['total'];
        $pages = ceil($total / $perPage);

        // Busca os dados limitados para a página atual
        $query = 'SELECT * FROM ' . $this->table;

        if (is_array($where)) {
            $whereClause = implode(' AND ', array_map(fn($k) => "$k = ?", array_keys($where)));
            $query .= ' WHERE ' . $whereClause;
        } elseif ($where) {
            $query .= ' WHERE ' . $where;
        }

        if ($order) {
            $query .= ' ORDER BY ' . $order;
        }

        $query .= " LIMIT $perPage OFFSET $offset";

        $fetchParams = is_array($where) ? array_values($where) : [];
        $resultData = $this->execute($query, $fetchParams);
        $data = $resultData->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data'     => $data,
            'total'    => $total,
            'pages'    => $pages,
            'page'     => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Inserção em lote para alta performance
     * @param array $records
     * @param array $fields
     * @return int Quantidade inserida
     */
    public function insertBatch($records, $fields = [])
    {
        if (empty($records) || empty($fields)) return 0;

        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $fieldList = implode(',', $fields);
        $queryBase = "INSERT INTO {$this->table} ({$fieldList}) VALUES ";

        $batchSize = 50; 
        $inserted = 0;
        $batches = array_chunk($records, $batchSize);

        foreach ($batches as $batch) {
            $batchPlaceholders = implode(',', array_fill(0, count($batch), "($placeholders)"));
            $batchQuery = $queryBase . $batchPlaceholders;
            $batchValues = [];

            foreach ($batch as $record) {
                foreach ($fields as $field) {
                    $batchValues[] = $record[$field] ?? null;
                }
            }

            try {
                $this->execute($batchQuery, $batchValues);
                $inserted += count($batch);
            } catch (PDOException $e) {
                throw new Exception('Erro no Insert Batch: ' . $e->getMessage());
            }
        }

        return $inserted;
    }

    /**
     * Inicia transação SQL
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * Confirma todas as operações da transação
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Cancela todas as operações da transação por erro
     */
    public function rollBack()
    {
        $this->connection->rollBack();
    }

    /**
     * Atualiza dados no banco
     * @param string $where Condições WHERE
     * @param array $values [ campo => valor ]
     * @return bool
     */
    public function update($where, $values)
    {
        $fields = array_keys($values);
        $query = 'UPDATE ' . $this->table . ' SET ' . implode('=?,', $fields) . '=? WHERE ' . $where;

        $this->execute($query, array_values($values));

        return true;
    }

    /**
     * Exclui registros do banco de dados
     * @param string $where
     * @return bool
     */
    public function delete($where)
    {
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $where;
        $this->execute($query);
        return true;
    }
}
