<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Exceptions
 */

namespace App\Exceptions;

use Throwable;

/**
 * ExceptionHandler - Gerenciador global de exceções e erros do framework
 * Responsável por capturar falhas e renderizar páginas de erro amigáveis
 */
class ExceptionHandler
{
    /**
     * Indica se o ambiente é de desenvolvimento (Debug ativo)
     * @var bool
     */
    private static $isDev = true;

    /**
     * Indica se o ambiente é de produção
     * @var bool
     */
    private static $isProd = false;

    /**
     * Inicializa os handlers de erro do PHP
     */
    public static function init()
    {
        self::$isDev = getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1';
        self::$isProd = !self::$isDev;

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Trata erros nativos do PHP (warnings, notices, etc) convertendo-os em Exception
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // Ignora erros de nível baixo em ambiente de produção
        if (self::$isProd && in_array($errno, [E_NOTICE, E_STRICT, E_DEPRECATED])) {
            return true;
        }

        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        return self::handleException($exception);
    }

    /**
     * Trata exceções não capturadas em blocos try-catch
     * @param Throwable $exception
     */
    public static function handleException(Throwable $exception)
    {
        // Registra o erro nos logs do sistema
        self::logError($exception);

        // Define os cabeçalhos de resposta
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(500);

        // Renderiza a página de erro adequada para o ambiente atual
        if (self::$isDev) {
            echo self::renderDebugPage($exception);
        } else {
            echo self::renderProductionPage();
        }

        exit(1);
    }

    /**
     * Captura erros fatais que encerram a execução do script no shutdown
     */
    public static function handleShutdown()
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::handleException($exception);
        }
    }

    /**
     * Grava o log detalhado do erro no arquivo definido
     * @param Throwable $exception
     */
    private static function logError(Throwable $exception)
    {
        $log = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        $logFile = __DIR__ . '/../../storage/logs/errors.log';
        @file_put_contents($logFile, $log, FILE_APPEND);
    }

    /**
     * Renderiza a página de Debug detalhada para desenvolvedores
     * @param Throwable $exception
     * @return string
     */
    private static function renderDebugPage(Throwable $exception)
    {
        $type = get_class($exception);
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTrace();

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro - Infinity Framework</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                
                body {
                    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    min-height: 100vh;
                    padding: 20px;
                    color: #f1f5f9;
                }
                
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                }
                
                .error-header {
                    background: #ef4444;
                    color: white;
                    padding: 40px;
                    border-radius: 8px 8px 0 0;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                
                .error-type {
                    font-size: 14px;
                    opacity: 0.9;
                    margin-bottom: 10px;
                }
                
                .error-message {
                    font-size: 32px;
                    font-weight: bold;
                    word-break: break-word;
                    margin-bottom: 20px;
                }
                
                .error-location {
                    font-size: 12px;
                    opacity: 0.8;
                    padding: 10px;
                    background: rgba(0, 0, 0, 0.1);
                    border-radius: 4px;
                }
                
                .error-content {
                    background: #1e293b;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    margin-bottom: 20px;
                    border: 1px solid rgba(255,255,255,0.05);
                }
                
                .tabs {
                    display: flex;
                    border-bottom: 2px solid rgba(255,255,255,0.1);
                    background: rgba(255,255,255,0.05);
                }
                
                .tab-button {
                    flex: 1;
                    padding: 16px 20px;
                    background: none;
                    border: none;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    color: #94a3b8;
                    border-bottom: 3px solid transparent;
                    transition: all 0.3s;
                }
                
                .tab-button:hover {
                    color: #f1f5f9;
                    background: rgba(255,255,255,0.05);
                }
                
                .tab-button.active {
                    color: #6366f1;
                    border-bottom-color: #6366f1;
                }
                
                .tab-content {
                    padding: 30px;
                    display: none;
                }
                
                .tab-content.active {
                    display: block;
                }
                
                .stack-trace {
                    background: rgba(0,0,0,0.2);
                    padding: 20px;
                    border-radius: 4px;
                    overflow-x: auto;
                }
                
                .stack-item {
                    margin-bottom: 15px;
                    padding: 12px;
                    background: #0f172a;
                    border-left: 4px solid #6366f1;
                    border-radius: 2px;
                }
                
                .stack-number {
                    font-size: 12px;
                    color: #64748b;
                    font-weight: bold;
                }
                
                .stack-function {
                    color: #818cf8;
                    font-weight: bold;
                }
                
                .stack-file {
                    color: #94a3b8;
                    font-size: 12px;
                    margin-top: 8px;
                }
                
                .code-line {
                    background: #0f172a;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 10px 0;
                    border-left: 4px solid #334155;
                }
                
                .line-number {
                    color: #64748b;
                    font-weight: bold;
                    margin-right: 15px;
                }
                
                .environment {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                }
                
                .env-item {
                    background: rgba(255,255,255,0.05);
                    padding: 15px;
                    border-radius: 4px;
                    border-left: 4px solid #10b981;
                }
                
                .env-label {
                    font-weight: bold;
                    color: #f1f5f9;
                    margin-bottom: 5px;
                }
                
                .env-value {
                    color: #94a3b8;
                    font-size: 12px;
                    word-break: break-all;
                }
                
                .footer {
                    background: #1e293b;
                    padding: 20px 30px;
                    border-radius: 0 0 8px 8px;
                    text-align: center;
                    font-size: 12px;
                    color: #94a3b8;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    border: 1px solid rgba(255,255,255,0.05);
                    border-top: none;
                }
                
                code {
                    background: rgba(0,0,0,0.3);
                    padding: 2px 6px;
                    border-radius: 2px;
                    font-size: 12px;
                    color: #e2e8f0;
                }
                
                @media (max-width: 768px) {
                    .tabs { flex-wrap: wrap; }
                    .tab-button { flex: 0 1 50%; }
                    .error-message { font-size: 20px; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-header">
                    <div class="error-type"><?php echo htmlspecialchars($type); ?></div>
                    <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
                    <div class="error-location">
                        Arquivo: <?php echo htmlspecialchars($file); ?> : <strong>Linha <?php echo $line; ?></strong>
                    </div>
                </div>
                
                <div class="error-content">
                    <div class="tabs">
                        <button class="tab-button active" onclick="showTab(event, 'stack')">Rastro da Pilha</button>
                        <button class="tab-button" onclick="showTab(event, 'code')">Arquivo e Código</button>
                        <button class="tab-button" onclick="showTab(event, 'env')">Servidor / Ambiente</button>
                    </div>
                    
                    <div id="stack" class="tab-content active">
                        <div class="stack-trace">
                            <?php foreach ($trace as $index => $frame): ?>
                                <div class="stack-item">
                                    <div>
                                        <span class="stack-number">#<?php echo $index; ?></span>
                                        <span class="stack-function">
                                            <?php echo htmlspecialchars($frame['function'] ?? 'unknown'); ?>()
                                        </span>
                                    </div>
                                    <?php if (isset($frame['file'])): ?>
                                        <div class="stack-file">
                                            Arquivo: <?php echo htmlspecialchars($frame['file']); ?>:<?php echo $frame['line']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div id="code" class="tab-content">
                        <?php if (file_exists($file)): ?>
                            <div class="code-line">
                                <strong>Contexto do Erro:</strong> <?php echo htmlspecialchars($file); ?>
                            </div>
                            <?php
                                $fileLines = file($file);
                                $startLine = max(0, $line - 5);
                                $endLine = min(count($fileLines), $line + 5);
                            ?>
                            <?php for ($i = $startLine; $i < $endLine; $i++): ?>
                                <div class="code-line" <?php echo ($i + 1) === $line ? 'style="background: #450a0a; border-left-color: #ef4444;"' : ''; ?>>
                                    <span class="line-number"><?php echo str_pad($i + 1, 4, ' ', STR_PAD_LEFT); ?></span>
                                    <code><?php echo htmlspecialchars($fileLines[$i]); ?></code>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div id="env" class="tab-content">
                        <div class="environment">
                            <div class="env-item">
                                <div class="env-label">Versão do PHP</div>
                                <div class="env-value"><?php echo htmlspecialchars(PHP_VERSION); ?></div>
                            </div>
                            <div class="env-item">
                                <div class="env-label">Servidor Histórico</div>
                                <div class="env-value"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'unknown'); ?></div>
                            </div>
                            <div class="env-item">
                                <div class="env-label">Método da Requisição</div>
                                <div class="env-value"><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'unknown'); ?></div>
                            </div>
                            <div class="env-item">
                                <div class="env-label">URI da Requisição</div>
                                <div class="env-value"><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'unknown'); ?></div>
                            </div>
                            <div class="env-item">
                                <div class="env-label">IP do Cliente</div>
                                <div class="env-value"><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown'); ?></div>
                            </div>
                            <div class="env-item">
                                <div class="env-label">Data e Hora</div>
                                <div class="env-value"><?php echo date('Y-m-d H:i:s'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="footer">
                    <strong>Infinity Framework - Modo Debug</strong> • Ambiente de Desenvolvimento
                    <br>
                    Para segurança, desative definindo <code>APP_DEBUG=false</code> no arquivo .env
                </div>
            </div>
            
            <script>
                function showTab(event, tabName) {
                    const buttons = document.querySelectorAll('.tab-button');
                    const contents = document.querySelectorAll('.tab-content');
                    buttons.forEach(btn => btn.classList.remove('active'));
                    contents.forEach(content => content.classList.remove('active'));
                    event.target.classList.add('active');
                    document.getElementById(tabName).classList.add('active');
                }
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza a página de erro para usuários finais em produção
     * @return string
     */
    private static function renderProductionPage()
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Algo deu errado - Infinity Framework</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    color: #f1f5f9;
                }
                
                .error-box {
                    background: #1e293b;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                    max-width: 500px;
                    text-align: center;
                    padding: 60px 40px;
                    border: 1px solid rgba(255,255,255,0.05);
                }
                
                .error-icon {
                    font-size: 80px;
                    margin-bottom: 20px;
                    color: #ef4444;
                }
                
                h1 {
                    font-size: 28px;
                    color: #f1f5f9;
                    margin-bottom: 15px;
                }
                
                p {
                    color: #94a3b8;
                    font-size: 16px;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                
                .button {
                    display: inline-block;
                    background: #6366f1;
                    color: white;
                    padding: 12px 30px;
                    border-radius: 6px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: all 0.3s;
                }
                
                .button:hover {
                    background: #4f46e5;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <div class="error-icon">FALHA</div>
                <h1>Oops! Algo deu errado</h1>
                <p>Desculpe, encontramos um erro interno ao processar sua solicitação. Nossa equipe técnica já foi notificada para resolver o problema o quanto antes.</p>
                <a href="/" class="button">Voltar ao Início</a>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
