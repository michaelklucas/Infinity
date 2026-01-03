<?php

namespace App\Database\Factory;

use Faker\Factory as FakerFactory;
use App\Config\src\Database;

abstract class Factory
{
    /**
     * Define o model associado a esta factory
     * @var string
     */
    protected $model;

    /**
     * Define a definição dos dados fake
     * @return array
     */
    abstract public function definition();

    /**
     * Cria N registros no banco
     * @param int $count
     * @return array IDs criados
     */
    public function create($count = 1)
    {
        $faker = FakerFactory::create('pt_BR');
        $this->faker = $faker;
        
        // Pega o nome da tabela do model
        $modelClass = $this->model;
        
        // Hack para acessar priedade protected table estática do Model
        // Idealmente o Model teria um método público getTableName()
        $table = $modelClass::getTableName();
        
        $db = new Database($table);
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $data = $this->definition();
            
            // Tratamento especial para senhas, timestamps, etc
            if (!isset($data['created_at'])) $data['created_at'] = date('Y-m-d H:i:s');
            
            $ids[] = $db->insert($data);
        }

        return $count === 1 ? $ids[0] : $ids;
    }
}
