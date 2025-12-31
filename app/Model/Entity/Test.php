<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Model\Entity
 */

namespace App\Model\Entity;

use App\Config\src\Database;

/**
 * Entidade de Teste - Utilizada para simulações e validações do framework
 */
class Test
{
    /**
     * Método que força um erro de SQL para testar o DebugBar
     * @return array
     */
    public static function getDatabaseError()
    {
        return (new Database('tabela_inexistente_para_teste'))->select()->fetchAll();
    }
}
