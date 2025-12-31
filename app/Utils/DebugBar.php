<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

use Exception;
use Throwable;

/**
 * Classe responsável por gerenciar a barra de depuração
 */
class DebugBar
{
    private static $enabled = true;
    private static $data = [
        'queries' => [],
        'logs' => [],
        'errors' => [],
        'timings' => [],
        'request' => [],
        'response' => [],
        'environment' => [],
        'memory' => []
    ];
    private static $startTime = null;
    private static $startMemory = null;

    public static function init()
    {
        // Verificar se debug está habilitado
        self::$enabled = getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1';
        
        if (!self::$enabled) {
            return;
        }

        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage(true);

        // Capturar erros/warnings
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'shutdown']);

        // Registrar ambiente
        self::recordEnvironment();

        // Verificar se é uma requisição de pop-out
        if (isset($_GET['debug_bar_popout'])) {
            self::renderPopout();
        }
    }

    /**
     * Renderiza apenas os dados de debug em formato JSON para o popout
     */
    private static function renderPopout() {
        header('Content-Type: application/json');
        self::shutdown();
        echo json_encode(self::$data);
        exit;
    }

    /**
     * Registrar query SQL
     */
    public static function logQuery($sql, $params = [], $duration = 0, $connection = 'default')
    {
        if (!self::$enabled) return;

        self::$data['queries'][] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration,
            'connection' => $connection,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Registrar log
     */
    public static function log($message, $level = 'info', $context = [])
    {
        if (!self::$enabled) return;

        self::$data['logs'][] = [
            'message' => $message,
            'level' => $level,
            'context' => $context,
            'timestamp' => microtime(true),
            'trace' => self::getShortTrace()
        ];
    }

    /**
     * Registrar timing
     */
    public static function startTiming($label)
    {
        if (!self::$enabled) return;

        return [
            'label' => $label,
            'start' => microtime(true)
        ];
    }

    public static function endTiming($timing)
    {
        if (!self::$enabled) return;

        $duration = (microtime(true) - $timing['start']) * 1000; // ms

        self::$data['timings'][] = [
            'label' => $timing['label'],
            'duration' => $duration
        ];
    }

    /**
     * Registrar informações da requisição
     */
    public static function recordRequest($request)
    {
        if (!self::$enabled) return;

        self::$data['request'] = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'headers' => getallheaders(),
            'get' => $_GET,
            'post' => $_POST,
            'cookies' => $_COOKIE
        ];
    }

    /**
     * Registrar informações da resposta
     */
    public static function recordResponse($statusCode, $headers = [])
    {
        if (!self::$enabled) return;

        self::$data['response'] = [
            'status_code' => $statusCode,
            'headers' => $headers,
            'time' => microtime(true) - self::$startTime
        ];
    }

    /**
     * Handler de erro
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!self::$enabled) return;

        self::$data['errors'][] = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'timestamp' => microtime(true),
            'trace' => self::getFullTrace()
        ];

        return false; // Deixar PHP tratar normalmente
    }

    /**
     * Handler de exception
     */
    public static function handleException(Throwable $exception)
    {
        if (!self::$enabled) {
            throw $exception;
        }

        self::$data['errors'][] = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'timestamp' => microtime(true),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode()
        ];

        // Renderizar página de erro
        self::renderErrorPage($exception);
    }

    /**
     * Registrar ambiente
     */
    private static function recordEnvironment()
    {
        self::$data['environment'] = [
            'php_version' => phpversion(),
            'os' => php_uname(),
            'debug_mode' => getenv('APP_DEBUG'),
            'environment' => getenv('APP_ENV') ?? 'local'
        ];
    }

    /**
     * Shutdown - Capturar erros fatais
     */
    public static function shutdown()
    {
        if (!self::$enabled) return;

        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::$data['errors'][] = [
                'type' => self::getErrorType($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => microtime(true),
                'trace' => 'Fatal error - no trace available',
                'fatal' => true
            ];
        }

        // Registrar memória final
        self::$data['memory'] = [
            'start' => self::formatBytes(self::$startMemory),
            'current' => self::formatBytes(memory_get_usage(true)),
            'peak' => self::formatBytes(memory_get_peak_usage(true))
        ];
    }

    /**
     * Renderizar página de erro estilo Laravel
     */
    private static function renderErrorPage(Throwable $exception)
    {
        $lastError = end(self::$data['errors']);

        ?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error: <?php echo htmlspecialchars($lastError['type']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --error: #f87171;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .error-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .error-header {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 60px 20px;
            text-align: center;
        }

        .error-header h1 {
            font-size: 3em;
            margin-bottom: 15px;
            font-weight: 600;
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-code {
            display: inline-block;
            background: rgba(248, 113, 113, 0.1);
            color: var(--error);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 1em;
            margin-top: 10px;
            border: 1px solid rgba(248, 113, 113, 0.2);
        }

        .error-content {
            flex: 1;
            max-width: 1200px;
            margin: 40px auto;
            width: 100%;
            padding: 0 20px;
        }

        .error-box {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .error-type {
            color: var(--error);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.2em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .error-message {
            font-size: 1.3em;
            line-height: 1.6;
            margin-bottom: 25px;
            color: var(--text);
            word-break: break-all;
        }

        .file-info {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            font-family: 'Consolas', monospace;
            font-size: 0.95em;
            border: 1px solid var(--glass-border);
        }

        .file-info strong {
            color: var(--primary);
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--glass-border);
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 12px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            color: var(--text-muted);
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
            font-family: 'Outfit', sans-serif;
        }

        .tab-btn:hover {
            color: var(--text);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stack-trace {
            background: #000;
            color: #d1d5db;
            padding: 30px;
            border-radius: 15px;
            overflow-x: auto;
            font-family: 'Consolas', monospace;
            font-size: 0.9em;
            line-height: 1.7;
            border: 1px solid var(--glass-border);
        }

        .query-item {
            padding: 20px;
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            margin-bottom: 15px;
            border-radius: 15px;
            transition: 0.3s;
        }

        .query-item:hover {
            border-color: var(--primary);
        }

        .query-sql {
            background: #000;
            color: #ec4899;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Consolas', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin-bottom: 12px;
            border: 1px solid var(--glass-border);
        }

        .query-time {
            color: #10b981;
            font-weight: 600;
            font-size: 0.9em;
        }

        .debug-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid var(--glass-border);
        }

        .info-card h3 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--glass-border);
            font-size: 0.95em;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .info-value {
            color: var(--text);
            font-family: 'Consolas', monospace;
            text-align: right;
            word-break: break-all;
            padding-left: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px dashed var(--glass-border);
        }

        footer {
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9em;
            border-top: 1px solid var(--glass-border);
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .error-header h1 { font-size: 2em; }
            .debug-info { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <h1><?php echo htmlspecialchars($lastError['type']); ?></h1>
            <div class="error-code"><?php echo htmlspecialchars($lastError['message']); ?></div>
        </div>

        <div class="error-content">
            <div class="error-box">
                <div class="error-type"><?php echo $lastError['type']; ?></div>
                <div class="error-message"><?php echo htmlspecialchars($lastError['message']); ?></div>
                <div class="file-info">
                    <strong>File:</strong> <?php echo htmlspecialchars($lastError['file']); ?><br>
                    <strong>Line:</strong> <?php echo $lastError['line']; ?>
                </div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('trace')">Stack Trace</button>
                <button class="tab-btn" onclick="switchTab('queries')">Queries (<?php echo count(self::$data['queries']); ?>)</button>
                <button class="tab-btn" onclick="switchTab('logs')">Logs (<?php echo count(self::$data['logs']); ?>)</button>
                <button class="tab-btn" onclick="switchTab('request')">Request</button>
                <button class="tab-btn" onclick="switchTab('response')">Response</button>
            </div>

            <!-- Stack Trace Tab -->
            <div id="trace" class="tab-content active">
                <div class="stack-trace">
                    <?php
                    if (!empty($lastError['trace'])) {
                        echo nl2br(htmlspecialchars($lastError['trace']));
                    } else {
                        echo '<div class="empty-state"><p>No stack trace available</p></div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Queries Tab -->
            <div id="queries" class="tab-content">
                <?php
                if (!empty(self::$data['queries'])) {
                    foreach (self::$data['queries'] as $i => $query) {
                        ?>
                        <div class="query-item">
                            <div class="query-sql"><?php echo htmlspecialchars($query['sql']); ?></div>
                            <div class="query-time">
                                <?php echo number_format($query['duration'] * 1000, 2); ?>ms
                            </div>
                            <?php if (!empty($query['params'])) { ?>
                                <div style="margin-top: 8px; font-size: 0.85em; color: #666;">
                                    <strong>Params:</strong> <?php echo htmlspecialchars(json_encode($query['params'])); ?>
                                </div>
                            <?php } ?>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="empty-state"><p>No queries recorded</p></div>';
                }
                ?>
            </div>

            <!-- Logs Tab -->
            <div id="logs" class="tab-content">
                <?php
                if (!empty(self::$data['logs'])) {
                    foreach (self::$data['logs'] as $log) {
                        $colors = [
                            'error' => '#e74c3c',
                            'warning' => '#f39c12',
                            'info' => '#3498db',
                            'debug' => '#95a5a6'
                        ];
                        $color = $colors[$log['level']] ?? '#333';
                        ?>
                        <div class="query-item" style="border-left-color: <?php echo $color; ?>;">
                            <div style="color: <?php echo $color; ?>; font-weight: 600; margin-bottom: 5px;">
                                [<?php echo strtoupper($log['level']); ?>] <?php echo htmlspecialchars($log['message']); ?>
                            </div>
                            <?php if (!empty($log['context'])) { ?>
                                <div style="font-size: 0.85em; color: #666;">
                                    <?php echo htmlspecialchars(json_encode($log['context'])); ?>
                                </div>
                            <?php } ?>
                            <div style="font-size: 0.8em; color: #999; margin-top: 5px;">
                                <?php echo htmlspecialchars($log['trace']); ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="empty-state"><p>No logs recorded</p></div>';
                }
                ?>
            </div>

            <!-- Request Tab -->
            <div id="request" class="tab-content">
                <div class="debug-info">
                    <div class="info-card">
                        <h3>Basic Info</h3>
                        <div class="info-item">
                            <span class="info-label">Method</span>
                            <span class="info-value"><?php echo htmlspecialchars(self::$data['request']['method'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">URI</span>
                            <span class="info-value" style="text-align: left;"><?php echo htmlspecialchars(self::$data['request']['uri'] ?? 'N/A'); ?></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>GET Parameters</h3>
                        <?php if (!empty(self::$data['request']['get'])) {
                            foreach (self::$data['request']['get'] as $key => $value) {
                                ?>
                                <div class="info-item">
                                    <span class="info-label"><?php echo htmlspecialchars($key); ?></span>
                                    <span class="info-value"><?php echo htmlspecialchars($value); ?></span>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p style="color: #999;">No GET parameters</p>';
                        } ?>
                    </div>

                    <div class="info-card">
                        <h3>POST Parameters</h3>
                        <?php if (!empty(self::$data['request']['post'])) {
                            foreach (self::$data['request']['post'] as $key => $value) {
                                if (is_array($value)) {
                                    $value = json_encode($value);
                                }
                                ?>
                                <div class="info-item">
                                    <span class="info-label"><?php echo htmlspecialchars($key); ?></span>
                                    <span class="info-value"><?php echo htmlspecialchars(substr($value, 0, 50)); ?></span>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p style="color: #999;">No POST parameters</p>';
                        } ?>
                    </div>

                    <div class="info-card">
                        <h3>Cookies</h3>
                        <?php if (!empty(self::$data['request']['cookies'])) {
                            foreach (self::$data['request']['cookies'] as $key => $value) {
                                ?>
                                <div class="info-item">
                                    <span class="info-label"><?php echo htmlspecialchars($key); ?></span>
                                    <span class="info-value"><?php echo htmlspecialchars(substr($value, 0, 30)); ?></span>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p style="color: #999;">No cookies</p>';
                        } ?>
                    </div>
                </div>
            </div>

            <!-- Response Tab -->
            <div id="response" class="tab-content">
                <div class="debug-info">
                    <div class="info-card">
                        <h3>Response Info</h3>
                        <div class="info-item">
                            <span class="info-label">Status Code</span>
                            <span class="info-value"><?php echo htmlspecialchars(self::$data['response']['status_code'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Response Time</span>
                            <span class="info-value"><?php echo number_format((self::$data['response']['time'] ?? 0) * 1000, 2); ?>ms</span>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Memory Usage</h3>
                        <div class="info-item">
                            <span class="info-label">Start</span>
                            <span class="info-value"><?php echo self::$data['memory']['start'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Current</span>
                            <span class="info-value"><?php echo self::$data['memory']['current'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Peak</span>
                            <span class="info-value"><?php echo self::$data['memory']['peak'] ?? 'N/A'; ?></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Environment</h3>
                        <div class="info-item">
                            <span class="info-label">PHP Version</span>
                            <span class="info-value"><?php echo htmlspecialchars(self::$data['environment']['php_version'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">OS</span>
                            <span class="info-value"><?php echo htmlspecialchars(substr(self::$data['environment']['os'] ?? 'N/A', 0, 40)); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Environment</span>
                            <span class="info-value"><?php echo htmlspecialchars(self::$data['environment']['environment'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debug Info -->
            <div style="margin-top: 40px;">
                <h2 style="margin-bottom: 20px; font-size: 1.3em; color: #333;">Debug Information</h2>
                <div class="debug-info">
                    <div class="info-card">
                        <h3>Timings</h3>
                        <?php
                        if (!empty(self::$data['timings'])) {
                            foreach (self::$data['timings'] as $timing) {
                                ?>
                                <div class="info-item">
                                    <span class="info-label"><?php echo htmlspecialchars($timing['label']); ?></span>
                                    <span class="info-value"><?php echo number_format($timing['duration'], 2); ?>ms</span>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p style="color: #999;">No timings recorded</p>';
                        }
                        ?>
                    </div>

                    <div class="info-card">
                        <h3>Statistics</h3>
                        <div class="info-item">
                            <span class="info-label">Total Queries</span>
                            <span class="info-value"><?php echo count(self::$data['queries']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Logs</span>
                            <span class="info-value"><?php echo count(self::$data['logs']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Errors</span>
                            <span class="info-value"><?php echo count(self::$data['errors']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Execution Time</span>
                            <span class="info-value"><?php echo number_format((microtime(true) - self::$startTime) * 1000, 2); ?>ms</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <p>Infinity Debug Bar - Modo Desenvolvimento</p>
            <p style="margin-top: 10px; font-size: 0.8em;">Desative definindo <code>APP_DEBUG=false</code> no .env</p>
        </footer>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Remove active from buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Show selected tab
            document.getElementById(tabName).classList.add('active');

            // Mark button as active
            event.target.classList.add('active');
        }
    </script>
</body>
</html><?php

        exit;
    }

    /**
     * Obter tipo de erro
     */
    private static function getErrorType($errno)
    {
        $types = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $types[$errno] ?? 'Unknown Error';
    }

    /**
     * Obter trace curto (apenas arquivo/linha)
     */
    private static function getShortTrace()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        if (isset($trace[1])) {
            return ($trace[1]['file'] ?? 'unknown') . ':' . ($trace[1]['line'] ?? '?');
        }
        return 'unknown';
    }

    /**
     * Obter trace completo
     */
    private static function getFullTrace()
    {
        $trace = debug_backtrace();
        $result = [];

        foreach ($trace as $i => $frame) {
            $file = $frame['file'] ?? 'unknown';
            $line = $frame['line'] ?? '?';
            $function = $frame['function'] ?? 'unknown';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';

            $result[] = "#$i {$class}{$type}{$function}() called at [{$file}:{$line}]";
        }

        return implode("\n", $result);
    }

    /**
     * Formatar bytes
     */
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Renderizar o Debug Bar no final da página.
     * Retorna o HTML e JavaScript do Debug Bar.
     */
    public static function renderDebugBar()
    {
        if (!self::$enabled) {
            return '';
        }

        // Assegurar que os dados finais sejam capturados
        self::shutdown();

        $data = self::$data;
        $totalQueries = count($data['queries']);
        $totalLogs = count($data['logs']);
        $totalErrors = count($data['errors']);
        $execTime = number_format((microtime(true) - self::$startTime) * 1000, 2);
        $memoryPeak = self::$data['memory']['peak'] ?? 'N/A';

        ob_start();
        ?>
        <style id="debug-bar-styles">
            #debug-bar-main {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background: rgba(15, 23, 42, 0.95);
                backdrop-filter: blur(10px);
                color: #f8fafc;
                font-family: 'Outfit', sans-serif;
                font-size: 13px;
                z-index: 999999;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.5);
                display: flex;
                flex-direction: column;
                visibility: hidden;
            }
            #debug-bar-resizer {
                width: 100%;
                height: 6px;
                cursor: ns-resize;
                background: transparent;
                position: absolute;
                top: -3px;
                left: 0;
                z-index: 1000000;
            }
            #debug-bar-resizer:hover {
                background: rgba(99, 102, 241, 0.5);
            }
            #debug-bar-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 20px;
                cursor: default;
                background: rgba(255, 255, 255, 0.03);
                transition: background 0.3s;
            }
            #debug-bar-header .title-area {
                display: flex;
                align-items: center;
                gap: 15px;
                cursor: pointer;
            }
            #debug-bar-header .title {
                font-weight: 600;
                background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                font-size: 1.1em;
            }
            #debug-bar-header .metrics {
                display: flex;
                align-items: center;
                gap: 20px;
            }
            #debug-bar-header .metrics span {
                color: #94a3b8;
            }
            #debug-bar-header .metrics span strong {
                color: #f8fafc;
            }
            #debug-bar-content {
                display: none;
                flex: 1;
                overflow-y: auto;
                padding: 15px 20px;
                background: rgba(0, 0, 0, 0.2);
            }
            .tab-nav {
                display: flex;
                gap: 5px;
                margin-bottom: 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                padding-bottom: 5px;
            }
            .tab-nav button {
                background: none;
                border: none;
                color: #94a3b8;
                padding: 10px 15px;
                cursor: pointer;
                font-family: 'Outfit', sans-serif;
                font-size: 0.9em;
                font-weight: 500;
                border-bottom: 2px solid transparent;
                transition: all 0.3s;
            }
            .tab-nav button:hover {
                color: #f8fafc;
            }
            .tab-nav button.active {
                color: #6366f1;
                border-bottom-color: #6366f1;
            }
            .debug-tab-pane { display: none; }
            .debug-tab-pane.active { display: block; }
            
            .debug-section h4 {
                color: #6366f1;
                margin-bottom: 15px;
                font-size: 1em;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .debug-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .debug-table th, .debug-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
            .debug-table th {
                color: #94a3b8;
                font-weight: 600;
                font-size: 0.8em;
                text-transform: uppercase;
            }
            .sql-code {
                font-family: 'Consolas', monospace;
                color: #ec4899;
                background: rgba(0, 0, 0, 0.3);
                padding: 10px;
                border-radius: 8px;
            }
            .duration { color: #10b981; font-weight: 600; }
            .debug-pre {
                background: rgba(0, 0, 0, 0.3);
                padding: 15px;
                border-radius: 10px;
                font-family: 'Consolas', monospace;
                color: #cbd5e1;
                white-space: pre-wrap;
                word-break: break-all;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .debug-error-item {
                background: rgba(248, 113, 113, 0.05);
                border-left: 4px solid #f87171;
                padding: 15px;
                border-radius: 0 10px 10px 0;
                margin-bottom: 10px;
            }
            .debug-error-type { color: #f87171; font-weight: 600; margin-bottom: 5px; }
            .debug-error-file { color: #94a3b8; font-size: 0.85em; }

            .debug-bar-btn {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #f8fafc;
                width: 28px;
                height: 28px;
                border-radius: 6px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }
            .debug-bar-btn:hover {
                background: rgba(99, 102, 241, 0.2);
                border-color: #6366f1;
            }

            #debug-bar-toggle-icon {
                transition: transform 0.3s;
                display: inline-block;
            }

            /* Estilo Popout */
            body.debug-popout { margin: 0; padding: 0; background: #0f172a; overflow-x: hidden; }
            body.debug-popout #debug-bar-main {
                position: fixed !important;
                top: 0 !important; left: 0 !important;
                height: 100vh !important;
                width: 100vw !important;
                border-top: none !important;
                visibility: visible !important;
                background: #0f172a !important;
                display: flex !important;
            }
            body.debug-popout #debug-bar-content {
                display: block !important;
            }
            body.debug-popout #debug-bar-resizer, 
            body.debug-popout #debug-bar-toggle-icon,
            body.debug-popout #hide-btn {
                display: none;
            }
            body.debug-popout #popout-btn i {
                transform: rotate(180deg);
            }
        </style>

        <div id="debug-bar-main">
            <div id="debug-bar-resizer"></div>
            <div id="debug-bar-header">
                <div class="title-area" onclick="toggleDebugBar()">
                    <div class="title">Infinity DebugBar</div>
                    <div class="metrics">
                        <span>Queries: <strong><?php echo $totalQueries; ?></strong></span>
                        <span>Logs: <strong><?php echo $totalLogs; ?></strong></span>
                        <span>Errors: <strong><?php echo $totalErrors; ?></strong></span>
                        <span>Time: <strong><?php echo $execTime; ?>ms</strong></span>
                    </div>
                </div>
                <div class="metrics" style="gap: 10px;">
                    <span>Memory: <strong><?php echo $memoryPeak; ?></strong></span>
                    <button id="popout-btn" class="debug-bar-btn" onclick="popoutDebugBar()" title="Desacoplar">
                        <i class='bx bx-export' style="font-size: 1.1rem;"></i>
                    </button>
                    <button class="debug-bar-btn" id="hide-btn" onclick="toggleDebugBar()">
                        <span id="debug-bar-toggle-icon" style="transform: rotate(180deg)">▲</span>
                    </button>
                </div>
            </div>
            <div id="debug-bar-content">
                <div class="tab-nav">
                    <button id="btn-queries-tab" class="active" onclick="switchDebugTab(event, 'queries-tab')">Queries</button>
                    <button id="btn-logs-tab" onclick="switchDebugTab(event, 'logs-tab')">Logs</button>
                    <button id="btn-errors-tab" onclick="switchDebugTab(event, 'errors-tab')">Errors</button>
                    <button id="btn-request-tab" onclick="switchDebugTab(event, 'request-tab')">Request</button>
                    <button id="btn-response-tab" onclick="switchDebugTab(event, 'response-tab')">Response</button>
                    <button id="btn-environment-tab" onclick="switchDebugTab(event, 'environment-tab')">Env</button>
                </div>

                <div id="queries-tab" class="debug-tab-pane active debug-section">
                    <h4>SQL Queries</h4>
                    <?php if (empty($data['queries'])): ?>
                        <p>Nenhuma query SQL executada.</p>
                    <?php else: ?>
                        <table class="debug-table">
                            <thead>
                                <tr>
                                    <th>SQL</th>
                                    <th>Parâmetros</th>
                                    <th>Tempo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['queries'] as $query): ?>
                                    <tr>
                                        <td class="sql-code"><?php echo htmlspecialchars($query['sql']); ?></td>
                                        <td><?php echo htmlspecialchars(json_encode($query['params'])); ?></td>
                                        <td class="duration"><?php echo number_format($query['duration'] * 1000, 2); ?>ms</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div id="logs-tab" class="debug-tab-pane debug-section">
                    <h4>Logs da Aplicação</h4>
                    <?php if (empty($data['logs'])): ?>
                        <p>Nenhum log registrado.</p>
                    <?php else: ?>
                        <?php foreach ($data['logs'] as $log): ?>
                            <div class="debug-error-item" style="border-left-color: <?php 
                                echo match($log['level']){ 
                                    'error' => '#e74c3c', 
                                    'warning' => '#f1c40f', 
                                    'info' => '#3498db', 
                                    'debug' => '#95a5a6', 
                                    default => '#ecf0f1' 
                                }; ?>;">
                                <div class="log-level-<?php echo $log['level']; ?>"><strong>[<?php echo strtoupper($log['level']); ?>]</strong> <?php echo htmlspecialchars($log['message']); ?></div>
                                <?php if (!empty($log['context'])): ?><div class="debug-pre">Contexto: <?php echo htmlspecialchars(json_encode($log['context'])); ?></div><?php endif; ?>
                                <div class="debug-error-file"><?php echo htmlspecialchars($log['trace']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="errors-tab" class="debug-tab-pane debug-section">
                    <h4>Erros e Exceções</h4>
                    <?php if (empty($data['errors'])): ?>
                        <p>Nenhum erro ou exceção capturado.</p>
                    <?php else: ?>
                        <?php foreach ($data['errors'] as $error): ?>
                            <div class="debug-error-item">
                                <div class="debug-error-type"><?php echo htmlspecialchars($error['type']); ?>: <?php echo htmlspecialchars($error['message']); ?></div>
                                <div class="debug-error-file">Arquivo: <?php echo htmlspecialchars($error['file']); ?> (Linha: <?php echo $error['line']; ?>)</div>
                                <?php if (isset($error['trace'])): ?><div class="debug-pre">StackTrace: <?php echo nl2br(htmlspecialchars($error['trace'])); ?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="request-tab" class="debug-tab-pane debug-section">
                    <h4>Request Info</h4>
                    <table class="debug-table">
                        <tr><th>Método</th><td><?php echo htmlspecialchars($data['request']['method'] ?? 'N/A'); ?></td></tr>
                        <tr><th>URI</th><td><?php echo htmlspecialchars($data['request']['uri'] ?? 'N/A'); ?></td></tr>
                        <tr><th>GET</th><td class="debug-pre"><?php echo htmlspecialchars(json_encode($data['request']['get'] ?? [])); ?></td></tr>
                        <tr><th>POST</th><td class="debug-pre"><?php echo htmlspecialchars(json_encode($data['request']['post'] ?? [])); ?></td></tr>
                        <tr><th>Cookies</th><td class="debug-pre"><?php echo htmlspecialchars(json_encode($data['request']['cookies'] ?? [])); ?></td></tr>
                        <tr><th>Headers</th><td class="debug-pre"><?php echo htmlspecialchars(json_encode($data['request']['headers'] ?? [])); ?></td></tr>
                    </table>
                </div>

                <div id="response-tab" class="debug-tab-pane debug-section">
                    <h4>Response Info</h4>
                    <table class="debug-table">
                        <tr><th>Status Code</th><td><?php echo htmlspecialchars($data['response']['status_code'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Tempo de Resposta</th><td><?php echo number_format(($data['response']['time'] ?? 0) * 1000, 2); ?>ms</td></tr>
                        <tr><th>Memória Usada</th><td><?php echo $data['memory']['current'] ?? 'N/A'; ?></td></tr>
                        <tr><th>Pico de Memória</th><td><?php echo $data['memory']['peak'] ?? 'N/A'; ?></td></tr>
                    </table>
                </div>

                <div id="environment-tab" class="debug-tab-pane debug-section">
                    <h4>Environment Info</h4>
                    <table class="debug-table">
                        <tr><th>PHP Version</th><td><?php echo htmlspecialchars($data['environment']['php_version'] ?? 'N/A'); ?></td></tr>
                        <tr><th>OS</th><td><?php echo htmlspecialchars($data['environment']['os'] ?? 'N/A'); ?></td></tr>
                        <tr><th>APP_ENV</th><td><?php echo htmlspecialchars($data['environment']['environment'] ?? 'N/A'); ?></td></tr>
                        <tr><th>APP_DEBUG</th><td><?php echo htmlspecialchars($data['environment']['debug_mode'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div id="debug-bar-launcher" style="display:none; position:fixed; bottom:10px; right:10px; z-index:999999;">
             <button class="debug-bar-btn" onclick="restoreDebugBar()" title="Restaurar DebugBar" style="width:40px; height:40px; border-radius:50%; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                <i class='bx bx-bug' style="font-size: 1.5rem; color: #6366f1;"></i>
             </button>
        </div>

        <script>
            // Lógica de Inicialização e Comunicação
            (function() {
                const bar = document.getElementById('debug-bar-main');
                const content = document.getElementById('debug-bar-content');
                const resizer = document.getElementById('debug-bar-resizer');
                const launcher = document.getElementById('debug-bar-launcher');
                const icon = document.getElementById('debug-bar-toggle-icon');
                const isPopoutWindow = document.body.classList.contains('debug-popout');
                
                if (isPopoutWindow) {
                    // Sincronizar tab ativa se vier da principal
                    const activeTab = localStorage.getItem('infinity-debugbar-active-tab');
                    if (activeTab) {
                        const btn = document.getElementById('btn-' + activeTab);
                        if (btn) btn.click();
                    }
                    return;
                }

                // Tentar sincronizar com janela existente se estiver em modo popped
                const isPopped = localStorage.getItem('infinity-debugbar-popped') === 'true';
                if (isPopped) {
                    // Pequeno delay para garantir que o navegador está pronto
                    setTimeout(() => {
                        const popWin = window.open('', 'InfinityDebugPopout');
                        if (popWin && popWin.location.href !== 'about:blank') {
                            popoutDebugBar(false); // Sincroniza sem roubar foco agressivamente
                            bar.style.display = 'none';
                            launcher.style.display = 'block';
                        } else {
                            // Janela foi fechada ou não existe
                            localStorage.setItem('infinity-debugbar-popped', 'false');
                            initNormalFlow();
                        }
                    }, 100);
                } else {
                    initNormalFlow();
                }

                function initNormalFlow() {
                    const savedHeight = localStorage.getItem('infinity-debugbar-height') || '300';
                    const isExpanded = localStorage.getItem('infinity-debugbar-expanded') === 'true';
                    
                    bar.style.visibility = 'visible';
                    if (isExpanded) {
                        bar.style.height = savedHeight + 'px';
                        content.style.display = 'block';
                        icon.style.transform = 'rotate(0deg)';
                    } else {
                        bar.style.height = 'auto';
                        content.style.display = 'none';
                        icon.style.transform = 'rotate(180deg)';
                    }
                }

                // Resizer
                resizer.addEventListener('mousedown', (e) => {
                    if (!(localStorage.getItem('infinity-debugbar-expanded') === 'true')) return;
                    isResizing = true;
                    document.body.style.cursor = 'ns-resize';
                    document.body.style.userSelect = 'none';
                });

                let isResizing = false;
                document.addEventListener('mousemove', (e) => {
                    if (!isResizing) return;
                    let height = window.innerHeight - e.clientY;
                    if (height < 100) height = 100;
                    if (height > window.innerHeight * 0.9) height = window.innerHeight * 0.9;
                    
                    bar.style.height = height + 'px';
                    localStorage.setItem('infinity-debugbar-height', height);
                });

                document.addEventListener('mouseup', () => {
                    isResizing = false;
                    document.body.style.cursor = 'default';
                    document.body.style.userSelect = 'auto';
                });
            })();

            function toggleDebugBar() {
                const bar = document.getElementById('debug-bar-main');
                const content = document.getElementById('debug-bar-content');
                const icon = document.getElementById('debug-bar-toggle-icon');
                
                if (content.style.display === 'none' || content.style.display === '') {
                    content.style.display = 'block';
                    icon.style.transform = 'rotate(0deg)';
                    const savedHeight = localStorage.getItem('infinity-debugbar-height') || '300';
                    bar.style.height = savedHeight + 'px';
                    localStorage.setItem('infinity-debugbar-expanded', 'true');
                } else {
                    content.style.display = 'none';
                    icon.style.transform = 'rotate(180deg)';
                    bar.style.height = 'auto';
                    localStorage.setItem('infinity-debugbar-expanded', 'false');
                }
            }

            function switchDebugTab(event, tabId) {
                const tabPanes = document.querySelectorAll('.debug-tab-pane');
                tabPanes.forEach(pane => pane.classList.remove('active'));

                const tabButtons = document.querySelectorAll('.tab-nav button');
                tabButtons.forEach(button => button.classList.remove('active'));

                document.getElementById(tabId).classList.add('active');
                if (event) {
                    event.target.classList.add('active');
                    localStorage.setItem('infinity-debugbar-active-tab', tabId);
                }
            }

            function popoutDebugBar(manual = true) {
                const width = 1100;
                const height = 800;
                const left = (window.screen.width / 2) - (width / 2);
                const top = (window.screen.height / 2) - (height / 2);
                
                const popWin = window.open('', 'InfinityDebugPopout', 
                    manual ? `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes` : '');
                
                if (popWin) {
                    const headLinks = Array.from(document.head.querySelectorAll('link, style')).map(el => el.outerHTML).join('\n');
                    const debugStyles = document.getElementById('debug-bar-styles').outerHTML;
                    const debugHTML = document.getElementById('debug-bar-main').outerHTML;
                    
                    document.getElementById('debug-bar-main').style.display = 'none';
                    document.getElementById('debug-bar-launcher').style.display = 'block';
                    localStorage.setItem('infinity-debugbar-popped', 'true');

                    popWin.document.open();
                    popWin.document.write(`
                        <!DOCTYPE html>
                        <html>
                            <head>
                                <title>Infinity DebugBar Popout</title>
                                ${headLinks}
                                ${debugStyles}
                            </head>
                            <body class="debug-popout">
                                ${debugHTML}
                                <script>
                                    window.popoutDebugBar = function() {
                                        if (window.opener && !window.opener.closed) {
                                            window.opener.restoreDebugBar();
                                            window.close();
                                        }
                                    };

                                    function switchDebugTab(event, tabId) {
                                        const tabPanes = document.querySelectorAll('.debug-tab-pane');
                                        tabPanes.forEach(pane => pane.classList.remove('active'));
                                        const tabButtons = document.querySelectorAll('.tab-nav button');
                                        tabButtons.forEach(button => button.classList.remove('active'));
                                        const target = document.getElementById(tabId);
                                        if(target) target.classList.add('active');
                                        if(event) event.target.classList.add('active');
                                        
                                        // Sincronizar tab de volta para a principal para persistência
                                        localStorage.setItem('infinity-debugbar-active-tab', tabId);
                                    }
                                    
                                    document.getElementById('popout-btn').title = "Acoplar no Navegador";
                                    
                                    // Restaurar tab ativa na atualização
                                    const savedTab = localStorage.getItem('infinity-debugbar-active-tab');
                                    if(savedTab) {
                                        const btn = document.getElementById('btn-' + savedTab);
                                        if(btn) btn.click();
                                    }
                                <\/script>
                            </body>
                        </html>
                    `);
                    popWin.document.close();
                }
            }

            function restoreDebugBar() {
                localStorage.setItem('infinity-debugbar-popped', 'false');
                const bar = document.getElementById('debug-bar-main');
                bar.style.display = 'flex';
                bar.style.visibility = 'visible';
                document.getElementById('debug-bar-launcher').style.display = 'none';
                localStorage.setItem('infinity-debugbar-expanded', 'true');
                toggleDebugBar(); toggleDebugBar(); // Refresh view
            }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Obter dados do debug bar
     */
    public static function getData()
    {
        return self::$data;
    }

    /**
     * Obter informações de debug (alias para getData)
     */
    public static function getDebugInfo()
    {
        return self::getData();
    }

    /**
     * Obter status
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }
}
