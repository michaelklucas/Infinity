<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

use App\Config\src\Database;

/**
 * Validador de Dados - Regras de validação para formulários e APIs
 */
class Validator
{
    /**
     * Erros encontrados durante a validação
     * @var array
     */
    private $errors = [];

    /**
     * Dados a serem validados
     * @var array
     */
    private $data = [];

    /**
     * Executa a validação
     * @param array $data Dados (ex: $_POST)
     * @param array $rules Regras (ex: ['nome' => 'required|min:3'])
     * @return Validator
     */
    public static function make($data, $rules)
    {
        $validator = new self();
        $validator->data = $data;

        foreach ($rules as $field => $fieldRules) {
            $rulesArray = explode('|', $fieldRules);
            foreach ($rulesArray as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramStr) = explode(':', $rule);
                    $params = explode(',', $paramStr);
                }

                $method = 'validate' . ucfirst($rule);
                if (method_exists($validator, $method)) {
                    $value = $data[$field] ?? null;
                    if (!$validator->$method($field, $value, $params)) {
                        // Se falhou em uma regra, para de validar este campo para não acumular erros redundantes
                        break; 
                    }
                }
            }
        }

        return $validator;
    }

    /**
     * Retorna se a validação falhou
     * @return bool
     */
    public function fails()
    {
        return !empty($this->errors);
    }

    /**
     * Retorna os erros da validação
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    // --- REGRAS DE VALIDAÇÃO ---

    private function validateRequired($field, $value)
    {
        if (is_null($value) || (is_string($value) && trim($value) === '') || (is_array($value) && count($value) === 0)) {
            $this->errors[$field] = "O campo {$field} é obrigatório.";
            return false;
        }
        return true;
    }

    private function validateMin($field, $value, $params)
    {
        $min = $params[0];
        if (strlen($value) < $min) {
            $this->errors[$field] = "O campo {$field} deve ter no mínimo {$min} caracteres.";
            return false;
        }
        return true;
    }

    private function validateMax($field, $value, $params)
    {
        $max = $params[0];
        if (strlen($value) > $max) {
            $this->errors[$field] = "O campo {$field} deve ter no máximo {$max} caracteres.";
            return false;
        }
        return true;
    }

    private function validateEmail($field, $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "O campo {$field} deve ser um e-mail válido.";
            return false;
        }
        return true;
    }

    private function validateInteger($field, $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = "O campo {$field} deve ser um número inteiro.";
            return false;
        }
        return true;
    }

    private function validateUnique($field, $value, $params)
    {
        $table = $params[0];
        $column = $params[1] ?? $field;
        
        $db = new Database($table);
        $result = $db->select("{$column} = ?", null, null, '*', [$value])->fetch();

        if ($result) {
            $this->errors[$field] = "Este {$field} já está sendo utilizado.";
            return false;
        }
        return true;
    }

    private function validateNumeric($field, $value)
    {
        if (!is_numeric($value)) {
            $this->errors[$field] = "O campo {$field} deve conter apenas números.";
            return false;
        }
        return true;
    }
}
