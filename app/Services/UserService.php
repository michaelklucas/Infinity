<?php

namespace App\Services;

use App\Utils\Validator;

class UserService extends BaseService
{
    /**
     * Registra um novo usuário no sistema
     * 
     * @param array $data Dados do usuário (nome, email, senha)
     * @return array
     */
    public function register(array $data)
    {
        // 1. Validação dos dados
        $validator = Validator::make($data, [
            'nome' => 'required|min:3',
            'email' => 'required|email',
            'senha' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return $this->error('Erro de validação', $validator->getErrors());
        }

        // 2. Lógica de inserção (Simulada por enquanto)
        // Aqui você chamaria o Model, ex: User::create($data);
        
        // Simulação de verificação se email existe
        if ($data['email'] === 'erro@teste.com') {
            return $this->error('Erro ao criar conta', ['email' => 'Este e-mail já está em uso (simulado)']);
        }

        // 3. Retorno de sucesso
        return $this->success([
            'id' => 1,
            'nome' => $data['nome'],
            'email' => $data['email']
        ], 'Conta criada com sucesso! Bem-vindo ao Infinity.');
    }
}
