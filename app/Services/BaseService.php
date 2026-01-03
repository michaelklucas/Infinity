<?php

namespace App\Services;

/**
 * Classe base para todos os serviços.
 * Padroniza o retorno de dados e tratamento de lógica de negócio.
 */
abstract class BaseService
{
    /**
     * Retorna sucesso na operação
     * 
     * @param mixed $data Dados a serem retornados
     * @param string $message Mensagem de sucesso
     * @return array
     */
    protected function success($data = [], string $message = 'Operação realizada com sucesso')
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Retorna erro na operação
     * 
     * @param string $message Mensagem de erro
     * @param mixed $errors Detalhes dos erros (array de validação, etc)
     * @return array
     */
    protected function error(string $message, $errors = [])
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];
    }
}
