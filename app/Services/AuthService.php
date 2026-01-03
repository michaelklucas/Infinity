<?php

namespace App\Services;

use App\Utils\Security;
use App\Utils\Session;
use App\Model\Entity\User; // Assumindo que você criará este Model
use App\Http\Request;

class AuthService extends BaseService
{
    /**
     * Tenta autenticar um usuário
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login(string $email, string $password)
    {
        // 1. Busca usuário pelo email (usando o novo ORM)
        // Nota: Você precisa garantir que User estende Model
        $user = User::first(['email' => $email]);

        if (!$user) {
            // Delay para evitar ataque de força bruta (Timing Attack)
            usleep(random_int(100000, 300000)); 
            return $this->error('Credenciais inválidas.');
        }

        // 2. Verifica senha
        if (!Security::verifyPassword($password, $user->senha)) {
             return $this->error('Credenciais inválidas.');
        }

        // 3. Cria Sessão
        Session::init();
        Session::put('user', [
            'id' => $user->id,
            'nome' => $user->nome,
            'email' => $user->email,
            'role' => $user->tipo ?? 'user', // Exemplo de role
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success([], 'Login realizado com sucesso!');
    }

    /**
     * Faz logout do usuário
     */
    public function logout()
    {
        Session::init();
        Session::destroy();
        return $this->success([], 'Logout realizado.');
    }

    /**
     * Retorna o usuário logado atualmente (ou null)
     * @return array|null
     */
    public static function user()
    {
        Session::init();
        return Session::get('user');
    }

    /**
     * Verifica se está logado
     * @return bool
     */
    public static function check()
    {
        return !empty(self::user());
    }

    /**
     * Middleware Check: Redireciona se não estiver logado
     */
    public static function requireLogin()
    {
        if (!self::check()) {
           header('Location: /login');
           exit;
        }
    }
}
