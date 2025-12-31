<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Model\Entity
 */

namespace App\Model\Entity;

use App\Config\src\Database;

/**
 * Entidade Cliente - Representa a tabela de clientes no banco de dados
 */
class Cliente
{
    /**
     * ID do cliente
     * @var int
     */
    public $id;

    /**
     * Nome do cliente
     * @var string
     */
    public $nome;

    /**
     * E-mail do cliente
     * @var string
     */
    public $email;

    /**
     * Telefone do cliente
     * @var string
     */
    public $telefone;

    /**
     * Status do cliente (ativo/inativo)
     * @var string
     */
    public $status = 'ativo';

    /**
     * Data de criação
     * @var string
     */
    public $created_at;

    /**
     * Data de atualização
     * @var string
     */
    public $updated_at;

    /**
     * Método responsável por cadastrar um novo cliente no banco
     * @return bool
     */
    public function cadastrar()
    {
        $this->id = (new Database('clientes'))->insert([
            'nome'     => $this->nome,
            'email'    => $this->email,
            'telefone' => $this->telefone,
            'status'   => $this->status
        ]);

        return true;
    }

    /**
     * Método responsável por atualizar os dados do cliente no banco
     * @return bool
     */
    public function atualizar()
    {
        return (new Database('clientes'))->update('id = ' . $this->id, [
            'nome'     => $this->nome,
            'email'    => $this->email,
            'telefone' => $this->telefone,
            'status'   => $this->status
        ]);
    }

    /**
     * Método responsável por excluir o cliente do banco
     * @return bool
     */
    public function excluir()
    {
        return (new Database('clientes'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por retornar os clientes do banco
     * @param string $where
     * @param string $order
     * @param string $limit
     * @param string $fields
     * @return \PDOStatement
     */
    public static function getClientes($where = null, $order = null, $limit = null, $fields = '*')
    {
        return (new Database('clientes'))->select($where, $order, $limit, $fields);
    }

    /**
     * Método responsável por retornar um cliente específico com base no ID
     * @param int $id
     * @return Cliente
     */
    public static function getClienteById($id)
    {
        return self::getClientes('id = ' . $id)->fetchObject(self::class);
    }

    /**
     * Retorna a paginação de clientes
     * @param string $where
     * @param int $page
     * @param int $perPage
     * @param string $order
     * @return array
     */
    public static function getPagination($where = null, $page = 1, $perPage = 10, $order = 'id DESC')
    {
        return (new Database('clientes'))->paginate($where, $page, $perPage, $order);
    }
}
