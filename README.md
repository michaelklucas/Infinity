# ♾️ Infinity Framework 2.0

![Banner](./resources/view/assets/screenshots/banner.png)

![PHP Version](https://img.shields.io/badge/PHP-8.2+-777bb4?style=for-the-badge&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)
![Status](https://img.shields.io/badge/Status-Production%20Ready-success?style=for-the-badge)

### 🇧🇷 A Evolução do Framework PHP.
**Elegância de Laravel, Velocidade de Slim, Simplicidade de CodeIgniter.**

O **Infinity Framework** é uma ferramenta moderna para desenvolvedores que buscam produtividade sem o "peso" de frameworks Enterprise. Com uma arquitetura **MVC** sólida, **Active Record ORM**, **Filas Assíncronas**, e **Real-Time** nativo, ele está pronto para tudo: de MVPs rápidos a SaaS complexos.

---

## 🚀 Novidades da Versão 2.0

-   🛠️ **Infinity Console (CLI):** Gere Controllers, Models, Services e Jobs com um comando.
-   💾 **Active Record ORM:** Banco de dados sem SQL manual. Suporte a relacionamentos (`hasOne`, `hasMany`).
-   ⚡ **Real-Time (SSE):** Server-Sent Events nativo para atualizações em tempo real sem WebSockets complexos.
-   T **Filas & Jobs:** Processamento em segundo plano (Background Workers) nativo no banco de dados.
-   🛡️ **Security First:** Headers de segurança automáticos, Auth Service blindado e Proteção CSRF.
-   🧪 **Testes & Factories:** Integração com Pest PHP e Faker para testes automatizados.

---

## 🛠️ Infinity Console (CLI)

O framework possui uma ferramenta de linha de comando poderosa na raiz do projeto.

```bash
# Criar um novo Controller
php infinity make:controller Dashboard

# Criar um Model com ORM
php infinity make:model Produto

# Criar um Service (Regra de Negócio)
php infinity make:service Pagamento

# Criar um Job (Processamento em Fila)
php infinity make:job EnviarEmail

# Criar uma Factory para Testes
php infinity make:factory UserFactory

# Rodar as Migrations (Auto-Migrate)
php infinity migrate

# Rodar o Worker de Filas
php infinity queue:work
```

---

## 📚 Guia Rápido de Uso

### 1. Active Record (ORM)
Esqueça SQL manual. Use Models fluentes.

```php
use App\Model\Entity\User;

// Criar
$user = User::create(['nome' => 'Michael', 'email' => 'michael@infinity.com']);

// Buscar e Atualizar
$user = User::find(1);
$user->nome = "Michael Simão";
$user->save();

// Relacionamentos
$pedidos = $user->hasMany(Pedido::class);
```

### 2. Services & Auth
Mantenha seus Controllers limpos movendo a lógica para Services.

```php
use App\Services\AuthService;

// No Controller
public function login($request) {
    $auth = new AuthService();
    return $auth->login($request->post('email'), $request->post('senha'));
}

// Proteger Rota
AuthService::requireLogin();
```

### 3. Real-Time (SSE)
Envie atualizações do servidor para o cliente instantaneamente.

```php
use App\Http\SSE;

// Na Rota
SSE::start();
SSE::send(['status' => 'Processando...'], 'update');
sleep(2);
SSE::send(['status' => 'Concluído!'], 'complete');
```

### 4. Filas (Jobs)
Processe tarefas pesadas em segundo plano sem travar o navegador.

```php
use App\Queue\Queue;
use App\Jobs\ProcessarVideo;

// Despachar Job
Queue::push(ProcessarVideo::class, ['video_id' => 50]);

// No Terminal (Rode em background)
// php infinity queue:work
```

---

## ⚡ Instalação

### Requisitos
-   PHP 8.2 ou superior
-   Extensão PDO, Fileinfo
-   Composer

### Passo a Passo

1.  **Clone o repositório:**
    ```bash
    git clone https://github.com/michaelklucas/Infinity.git
    cd Infinity
    ```

2.  **Instale as dependências:**
    ```bash
    composer install
    ```

3.  **Configure o ambiente:**
    Renomeie `.env.example` para `.env` e configure o banco de dados.

4.  **Inicie o Banco de Dados:**
    ```bash
    php infinity migrate    # Cria tabelas essenciais
    php infinity queue:table # Cria tabela de Jobs
    ```

5.  **Rode o servidor:**
    Use XAMPP, Laragon ou o servidor embutido:
    ```bash
    php -S localhost:8000
    ```

---

## 📸 Galeria

| Login (Glass) | Home (Showcase) |
|:---:|:---:|
| ![Login](./resources/view/assets/screenshots/login.png) | ![Home](./resources/view/assets/screenshots/home.png) |

---

## 🤝 Contribuindo

Pull Requests são bem-vindos! O Infinity é Open Source e feito para a comunidade.

### 💰 Apoie o Projeto
Mantenha o framework vivo e evoluindo! Se este projeto te ajudou, considere fazer uma doação de qualquer valor:

**Chave PIX:** `michael16klucas@gmail.com`

### Créditos
Desenvolvido com ❤️ por [Michael Simão](https://github.com/michaelklucas)
