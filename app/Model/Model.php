<?php

namespace App\Model;

use App\Config\src\Database;
use App\Utils\Logger;
use PDO;

/**
 * Active Record Base Model
 * Dá poderes mágicos de ORM para as Models.
 */
abstract class Model
{
    /**
     * Nome da tabela associada ao Model
     * Deve ser sobrescrito na classe filha (Ex: protected static $table = 'usuarios')
     * @var string
     */
    protected static $table;

    /**
     * ID do registro atual
     * @var int|null
     */
    public $id;

    /**
     * Retorna o nome da tabela. Se não definido, tenta adivinhar.
     * @return string
     */
    public static function getTableName()
    {
        if (static::$table) {
            return static::$table;
        }

        // Tenta inferir nome da tabela: UserModel -> users
        $className = (new \ReflectionClass(static::class))->getShortName();
        return strtolower($className) . 's';
    }

    /**
     * Encontra um registro pelo ID
     * @param int $id
     * @return static|null Retorna uma instância do Model ou null
     */
    public static function find($id)
    {
        $table = static::getTableName();
        $db = new Database($table);
        
        $data = $db->select("id = ?", null, 1, '*', [$id])->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return self::hydrate($data);
    }

    /**
     * Retorna todos os registros com filtros opcionais
     * 
     * @param array $where Ex: ['email' => 'teste@teste.com']
     * @param string $order Ex: 'id DESC'
     * @param string $limit Ex: '10'
     * @return static[] Array de objetos do Model
     */
    public static function where(array $where = [], string $order = null, string $limit = null)
    {
        $table = static::getTableName();
        $db = new Database($table);
        $conditions = [];
        $values = [];

        foreach ($where as $field => $val) {
            $conditions[] = "{$field} = ?";
            $values[] = $val;
        }

        $whereStr = !empty($conditions) ? implode(' AND ', $conditions) : null;
        
        $result = $db->select($whereStr, $order, $limit, '*', $values);
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);

        $models = [];
        foreach ($rows as $row) {
            $models[] = self::hydrate($row);
        }

        return $models;
    }

    /**
     * Retorna o primeiro registro que bater com o filtro
     * @param array $where
     * @return static|null
     */
    public static function first(array $where = [])
    {
        $results = self::where($where, null, '1');
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Retorna TODOS os registros
     * @return static[]
     */
    public static function all()
    {
        return self::where([]);
    }

    /**
     * Cria e salva um novo registro
     * @param array $data
     * @return static
     */
    public static function create(array $data)
    {
        $table = static::getTableName();
        $db = new Database($table);
        
        $id = $db->insert($data);
        
        $data['id'] = $id;
        return self::hydrate($data);
    }

    /**
     * Atualiza o registro atual no banco
     * @return bool
     */
    public function save()
    {
        if (!$this->id) {
            // Se não tem ID, deveria ser create()
            return false;
        }

        $table = static::getTableName();
        $db = new Database($table);

        // Pega todas as propriedades públicas do objeto para salvar
        $data = get_object_vars($this);
        unset($data['id']); // Não atualiza o ID

        // Filtra nulls se necessário, mas aqui vamos salvar tudo que está no objeto
        return $db->update('id = ' . $this->id, $data);
    }

    /**
     * Deleta o registro atual
     * @return bool
     */
    public function delete()
    {
        if (!$this->id) return false;

        $table = static::getTableName();
        $db = new Database($table);
        return $db->delete('id = ' . $this->id);
    }

    /**
     * Transforma array em Objeto do Model
     * @param array $data
     * @return static
     */
    protected static function hydrate(array $data)
    {
        $model = new static();
        foreach ($data as $key => $value) {
            $model->$key = $value;
        }
        return $model;
    }

    // --- RELACIONAMENTOS (ORM) ---

    /**
     * Define um relacionamento de 1 para 1
     * @param string $relatedClass Classe do Model relacionado
     * @param string $foreignKey Nome da chave estrangeira na tabela relacionada
     * @return mixed
     */
    protected function hasOne($relatedClass, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($this))->getShortName()) . '_id';
        return $relatedClass::first([$foreignKey => $this->id]);
    }

    /**
     * Define um relacionamento de 1 para N (Um para Muitos)
     * @param string $relatedClass Classe do Model relacionado
     * @param string $foreignKey Nome da chave estrangeira na tabela relacionada
     * @return array
     */
    protected function hasMany($relatedClass, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($this))->getShortName()) . '_id';
        return $relatedClass::where([$foreignKey => $this->id]);
    }

    /**
     * Define um relacionamento inverso (Pertence a)
     * @param string $relatedClass Classe do Model pai
     * @param string $foreignKey Nome da chave estrangeira nesta tabela
     * @return mixed
     */
    protected function belongsTo($relatedClass, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: strtolower((new \ReflectionClass($relatedClass))->getShortName()) . '_id';
        return $relatedClass::find($this->$foreignKey);
    }
}
