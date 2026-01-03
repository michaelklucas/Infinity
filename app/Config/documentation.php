<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Config
 */

/**
 * Inicialização do Sistema de Documentação
 * Registra a documentação detalhada de todos os componentes do framework
 */

use App\Utils\Documentation;
use App\Utils\Logger;
use App\Utils\Cache;
use App\Utils\JWT;
use App\Utils\RateLimit;
use App\Utils\CSRF;
use App\Utils\Security;
use App\Utils\MaintenanceMode;

// ================== COMPONENTES CORE ==================

Documentation::register('Model', [
    'title' => 'Active Record ORM',
    'category' => 'Database',
    'description' => 'Mapeamento objeto-relacional (ORM) para simplificar operações de banco de dados.',
    'version' => '2.0.0',
    'author' => 'Infinity',
    'tags' => ['database', 'orm', 'active-record'],
    'since' => '2.0.0',
    'example' => 'use App\Model\Entity\User;
$user = User::create(["nome" => "Teste"]);
$pedidos = $user->hasMany(Pedido::class);',
    'usage' => 'class User extends Model {}'
]);

Documentation::method('Model', 'find', [
    'description' => 'Busca registro por ID',
    'return' => 'static|null'
]);

Documentation::method('Model', 'where', [
    'description' => 'Filtra registros',
    'return' => 'array'
]);

Documentation::method('Model', 'save', [
    'description' => 'Salva alterações do objeto',
    'return' => 'bool'
]);

Documentation::register('Queue', [
    'title' => 'Sistema de Filas',
    'category' => 'System',
    'description' => 'Gerenciamento de Jobs assíncronos no banco de dados.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['queue', 'jobs', 'background'],
    'since' => '2.0.0',
    'example' => 'Queue::push(EnviarEmail::class, ["to" => "a@b.com"]);',
    'usage' => 'use App\Queue\Queue; Queue::push($jobClass, $data);'
]);

Documentation::register('SSE', [
    'title' => 'Server-Sent Events (Real-Time)',
    'category' => 'Http',
    'description' => 'Envio unidirecional de eventos do servidor para o cliente em tempo real.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['sse', 'real-time', 'http'],
    'since' => '2.0.0',
    'example' => 'SSE::start();
SSE::send(["status" => "ok"], "update");',
    'usage' => 'use App\Http\SSE; SSE::send($data);'
]);

Documentation::register('Logger', [
    'title' => 'Sistema de Logger',
    'category' => 'Logging',
    'description' => 'Sistema de logging estruturado com níveis PSR-3, rotação automática de arquivos e suporte a contexto.',
    'version' => '2.0.0',
    'author' => 'Infinity',
    'tags' => ['logging', 'psr-3', 'debug'],
    'since' => '2.0.0',
    'example' => 'Logger::info("Login de usuário", ["user_id" => 1]);
Logger::error("Erro de banco", ["error" => $e->getMessage()]);',
    'usage' => 'use App\Utils\Logger; Logger::info($message, $context);'
]);

Documentation::method('Logger', 'debug', [
    'description' => 'Registra uma mensagem de depuração (debug)',
    'return' => 'void'
]);

Documentation::method('Logger', 'info', [
    'description' => 'Registra uma mensagem informativa',
    'return' => 'void'
]);

Documentation::method('Logger', 'warning', [
    'description' => 'Registra um aviso (warning)',
    'return' => 'void'
]);

Documentation::method('Logger', 'error', [
    'description' => 'Registra um erro no sistema',
    'return' => 'void'
]);

Documentation::method('Logger', 'critical', [
    'description' => 'Registra uma falha crítica',
    'return' => 'void'
]);

// ================== SISTEMA DE CACHE ==================

Documentation::register('Cache', [
    'title' => 'Sistema de Cache',
    'category' => 'Performance',
    'description' => 'Sistema de cache com suporte a múltiplos drivers: File, Redis, Memcached e Array. Suporta TTL e padrão Remember.',
    'version' => '2.0.0',
    'author' => 'Infinity',
    'tags' => ['cache', 'performance', 'redis', 'memcached'],
    'since' => '2.0.0',
    'example' => '$value = Cache::remember("users", function() {
    return User::all();
}, 3600);',
    'usage' => 'use App\Utils\Cache; Cache::get($key); Cache::put($key, $value, $ttl);'
]);

Documentation::method('Cache', 'get', [
    'description' => 'Obtém um valor do cache',
    'return' => 'mixed'
]);

Documentation::method('Cache', 'put', [
    'description' => 'Armazena um valor no cache',
    'return' => 'bool'
]);

Documentation::method('Cache', 'remember', [
    'description' => 'Obtém ou executa e armazena caso não exista',
    'return' => 'mixed'
]);

Documentation::method('Cache', 'forget', [
    'description' => 'Remove um item específico do cache',
    'return' => 'bool'
]);

Documentation::method('Cache', 'flush', [
    'description' => 'Limpa todos os registros de cache',
    'return' => 'bool'
]);

// ================== AUTENTICAÇÃO JWT ==================

Documentation::register('JWT', [
    'title' => 'Autenticação JWT',
    'category' => 'Security',
    'description' => 'Autenticação segura via JSON Web Tokens usando algoritmos HMAC. Suporta expiração e validação automática.',
    'version' => '1.5.0',
    'author' => 'Infinity',
    'tags' => ['auth', 'jwt', 'token', 'security'],
    'since' => '3.0.0',
    'example' => '$token = JWT::encode(["id" => 1], 3600);
$payload = JWT::decode($token);',
    'usage' => 'use App\Utils\JWT; JWT::encode($payload, $expiration);'
]);

Documentation::method('JWT', 'encode', [
    'description' => 'Gera um token JWT assinado',
    'return' => 'string'
]);

Documentation::method('JWT', 'decode', [
    'description' => 'Decodifica e valida a assinatura do token',
    'return' => 'array|null'
]);

Documentation::method('JWT', 'verify', [
    'description' => 'Verifica se o token é estruturalmente válido',
    'return' => 'bool'
]);

// ================== LIMITAÇÃO DE TAXA ==================

Documentation::register('RateLimit', [
    'title' => 'Rate Limiting',
    'category' => 'Security',
    'description' => 'Proteção contra abuso e ataques de força bruta limitando requisições por IP ou usuário.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['rate-limit', 'security', 'ddos'],
    'since' => '3.0.0',
    'example' => '$status = RateLimit::check("api", 60, 60);',
    'usage' => 'use App\Utils\RateLimit; RateLimit::check($key, $limit, $window);'
]);

Documentation::method('RateLimit', 'check', [
    'description' => 'Verifica se o limite foi excedido',
    'return' => 'array'
]);

// ================== PROTEÇÃO CSRF ==================

Documentation::register('CSRF', [
    'title' => 'Proteção CSRF',
    'category' => 'Security',
    'description' => 'Geração e validação de tokens contra ataques de Cross-Site Request Forgery em formulários.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['csrf', 'security', 'forms'],
    'since' => '3.0.0',
    'example' => '<?= CSRF::field() ?>',
    'usage' => 'use App\Utils\CSRF; CSRF::verify($token);'
]);

Documentation::method('CSRF', 'field', [
    'description' => 'Gera um campo HTML hidden com o token',
    'return' => 'string'
]);

// ================== UTILITÁRIOS DE SEGURANÇA ==================

Documentation::register('Security', [
    'title' => 'Segurança Geral',
    'category' => 'Security',
    'description' => 'Funções utilitárias para sanitização, escaping de saída e hashing de senhas.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['security', 'xss', 'hash'],
    'since' => '3.0.0',
    'usage' => 'use App\Utils\Security; Security::escape($html);'
]);

Documentation::method('Security', 'hashPassword', [
    'description' => 'Gera hash seguro de senha (Bcrypt)',
    'return' => 'string'
]);

Documentation::method('Security', 'verifyPassword', [
    'description' => 'Valida uma senha contra um hash',
    'return' => 'bool'
]);

// ================== MODO DE MANUTENÇÃO ==================

Documentation::register('MaintenanceMode', [
    'title' => 'Modo de Manutenção',
    'category' => 'System',
    'description' => 'Gerencia o estado offline do sistema com bypass para IPs permitidos.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['maintenance', 'admin'],
    'since' => '2.0.0',
    'usage' => 'use App\Utils\MaintenanceMode; MaintenanceMode::enable();'
]);

Documentation::method('MaintenanceMode', 'enable', [
    'description' => 'Ativa o bloqueio do sistema',
    'return' => 'bool'
]);

Documentation::method('MaintenanceMode', 'disable', [
    'description' => 'Retorna o sistema ao estado online',
    'return' => 'bool'
]);

// ================== TRATAMENTO DE EXCEÇÕES ==================

Documentation::register('ExceptionHandler', [
    'title' => 'Gerenciador de Exceções',
    'category' => 'Error Handling',
    'description' => 'Interface visual premium para visualização de erros em tempo de desenvolvimento.',
    'version' => '2.0.0',
    'author' => 'Infinity',
    'tags' => ['exception', 'error', 'debug'],
    'since' => '1.0.0',
    'usage' => 'App\Exceptions\ExceptionHandler::init();'
]);

// ================== GERENCIAMENTO DE SESSÃO ==================

Documentation::register('Session', [
    'title' => 'Gerenciador de Sessão',
    'category' => 'System',
    'description' => 'Abstração sobre $_SESSION com suporte a Flash Messages (mensagens de uma única requisição).',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['session', 'flash', 'auth'],
    'since' => '3.5.0',
    'example' => 'Session::flash("success", "Item salvo!");
$msg = Session::getFlash("success");',
    'usage' => 'use App\Utils\Session; Session::set($key, $value);'
]);

Documentation::method('Session', 'flash', [
    'description' => 'Define uma mensagem que durará apenas até a próxima requisição',
    'return' => 'void'
]);

// ================== VALIDADOR DE DADOS ==================

Documentation::register('Validator', [
    'title' => 'Validador de Dados',
    'category' => 'Security',
    'description' => 'Motor de validação de inputs com regras encadeadas (required, email, unique, min, max, etc).',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['validation', 'security', 'input'],
    'since' => '3.5.0',
    'example' => '$v = Validator::make($_POST, ["email" => "required|email|unique:usuarios"]);
if ($v->fails()) { $errors = $v->getErrors(); }',
    'usage' => 'use App\Utils\Validator; Validator::make($data, $rules);'
]);

// ================== SISTEMA DE LAYOUTS ==================

Documentation::register('Layouts', [
    'title' => 'Herança de Layouts',
    'category' => 'View',
    'description' => 'Permite definir layouts mestre (Master Pages) para evitar repetição de cabeçalhos e rodapés.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['view', 'layout', 'render'],
    'since' => '3.5.0',
    'example' => 'View::setLayout("main");
return View::render("pages/home");',
    'usage' => 'App\Utils\View::setLayout($nome);'
]);

// ================== CLI CONSOLE ==================

Documentation::register('Console', [
    'title' => 'Infinity Console (CLI)',
    'category' => 'System',
    'description' => 'Interface de linha de comando para automação de tarefas do framework.',
    'version' => '1.0.0',
    'author' => 'Infinity',
    'tags' => ['cli', 'terminal', 'automation'],
    'since' => '3.5.0',
    'example' => 'php infinity migrate
php infinity make:controller User',
    'usage' => 'Executar via terminal na raiz do projeto.'
]);

// ================== ESTATÍSTICAS ==================

$stats = Documentation::stats();

Logger::info('Sistema de documentação carregado com sucesso', [
    'componentes' => $stats['total_components'],
    'metodos' => $stats['total_methods'],
    'categorias' => $stats['total_categories']
]);
