<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

use DateTime;
use Exception;

/**
 * Sistema de Logs - Registro estruturado com múltiplos manipuladores
 */
class Logger
{
    // Níveis de log (RFC 5424)
    const EMERGENCY = 'EMERGENCY';
    const ALERT     = 'ALERT';
    const CRITICAL  = 'CRITICAL';
    const ERROR     = 'ERROR';
    const WARNING   = 'WARNING';
    const NOTICE    = 'NOTICE';
    const INFO      = 'INFO';
    const DEBUG     = 'DEBUG';

    /**
     * Hierarquia dos níveis de log (para filtragem)
     * @var array
     */
    private static $levels = [
        'EMERGENCY' => 0,
        'ALERT'     => 1,
        'CRITICAL'  => 2,
        'ERROR'     => 3,
        'WARNING'   => 4,
        'NOTICE'    => 5,
        'INFO'      => 6,
        'DEBUG'     => 7,
    ];

    /**
     * Caminho do diretório de logs
     * @var string
     */
    private static $logPath;

    /**
     * Nome do arquivo de log atual
     * @var string
     */
    private static $logFile;

    /**
     * Nível mínimo para registro
     * @var string
     */
    private static $minLevel = 'DEBUG';

    /**
     * Tamanho máximo do arquivo de log antes da rotação (10MB)
     * @var int
     */
    private static $maxFileSize = 10485760;

    /**
     * Contexto global dos logs
     * @var array
     */
    private static $context = [];

    /**
     * Manipuladores de log registrados
     * @var array
     */
    private static $handlers = [];

    /**
     * Status de inicialização
     * @var bool
     */
    private static $initialized = false;

    /**
     * Método responsável por inicializar o logger
     * @param string $logPath
     * @param string $minLevel
     */
    public static function init($logPath = null, $minLevel = 'DEBUG')
    {
        if (self::$initialized) {
            return;
        }

        self::$logPath = $logPath ?? __DIR__ . '/../../storage/logs';
        self::$minLevel = $minLevel;
        self::$logFile = self::$logPath . '/' . date('Y-m-d') . '.log';

        // Cria o diretório de logs se não existir
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }

        // Adiciona manipuladores padrão
        self::addHandler('file', [self::class, 'handleFile']);
        self::addHandler('console', [self::class, 'handleConsole']);

        // Verifica e realiza a rotação de arquivos
        self::rotateLogFiles();

        self::$initialized = true;
    }

    /**
     * Registra uma mensagem de emergência
     * @param string $message
     * @param array $context
     */
    public static function emergency($message, $context = [])
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    /**
     * Registra uma mensagem de alerta
     * @param string $message
     * @param array $context
     */
    public static function alert($message, $context = [])
    {
        self::log(self::ALERT, $message, $context);
    }

    /**
     * Registra uma mensagem crítica
     * @param string $message
     * @param array $context
     */
    public static function critical($message, $context = [])
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Registra uma mensagem de erro
     * @param string $message
     * @param array $context
     */
    public static function error($message, $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Registra uma mensagem de aviso
     * @param string $message
     * @param array $context
     */
    public static function warning($message, $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Registra uma mensagem de nota
     * @param string $message
     * @param array $context
     */
    public static function notice($message, $context = [])
    {
        self::log(self::NOTICE, $message, $context);
    }

    /**
     * Registra uma mensagem informativa
     * @param string $message
     * @param array $context
     */
    public static function info($message, $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Registra uma mensagem de debug
     * @param string $message
     * @param array $context
     */
    public static function debug($message, $context = [])
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Método principal de registro de log
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public static function log($level, $message, $context = [])
    {
        if (!self::$initialized) {
            self::init();
        }

        // Verifica se a mensagem deve ser registrada com base no nível
        if (!self::shouldLog($level)) {
            return;
        }

        $logEntry = self::formatLogEntry($level, $message, $context);

        // Chama todos os manipuladores registrados
        foreach (self::$handlers as $handler) {
            call_user_func($handler, $logEntry, $level, $message, $context);
        }
    }

    /**
     * Verifica se o nível do log é suficiente para registro
     * @param string $level
     * @return bool
     */
    private static function shouldLog($level)
    {
        return self::$levels[$level] <= self::$levels[self::$minLevel];
    }

    /**
     * Formata a entrada do log
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    private static function formatLogEntry($level, $message, $context = [])
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s.u');
        $context = array_merge(self::$context, $context);
        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $file = self::getCallerFile();
        $line = self::getCallerLine();

        return "[$timestamp] [$level] [$file:$line] $message$contextStr";
    }

    /**
     * Obtém o arquivo de origem da chamada
     * @return string
     */
    private static function getCallerFile()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'Logger.php') === false) {
                return basename($trace['file']);
            }
        }
        return 'unknown';
    }

    /**
     * Obtém a linha de origem da chamada
     * @return int
     */
    private static function getCallerLine()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'Logger.php') === false) {
                return $trace['line'] ?? 0;
            }
        }
        return 0;
    }

    /**
     * Manipulador de arquivo - escreve o log no disco
     * @param string $logEntry
     */
    public static function handleFile($logEntry)
    {
        try {
            if (!file_exists(self::$logFile)) {
                touch(self::$logFile);
                chmod(self::$logFile, 0644);
            }
            file_put_contents(self::$logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log('Erro no manipulador de arquivo do Logger: ' . $e->getMessage());
        }
    }

    /**
     * Manipulador de console - envia para o error_log do PHP (útil em debug)
     * @param string $logEntry
     */
    public static function handleConsole($logEntry)
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log($logEntry);
        }
    }

    /**
     * Adiciona um manipulador customizado
     * @param string $name
     * @param callable $callback
     */
    public static function addHandler($name, $callback)
    {
        self::$handlers[$name] = $callback;
    }

    /**
     * Remove um manipulador
     * @param string $name
     */
    public static function removeHandler($name)
    {
        unset(self::$handlers[$name]);
    }

    /**
     * Adiciona contexto global para todos os logs
     * @param string $key
     * @param mixed $value
     */
    public static function addContext($key, $value)
    {
        self::$context[$key] = $value;
    }

    /**
     * Limpa o contexto global
     */
    public static function clearContext()
    {
        self::$context = [];
    }

    /**
     * Realiza a rotação dos arquivos de log se excederem o tamanho máximo
     */
    private static function rotateLogFiles()
    {
        if (!file_exists(self::$logFile)) {
            return;
        }

        if (filesize(self::$logFile) > self::$maxFileSize) {
            $rotatedFile = self::$logPath . '/' . date('Y-m-d_H-i-s') . '.log';
            rename(self::$logFile, $rotatedFile);

            // Limpa logs antigos (mais de 30 dias)
            self::cleanOldLogs(30);
        }
    }

    /**
     * Limpa arquivos de log antigos
     * @param int $days
     */
    private static function cleanOldLogs($days = 30)
    {
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $files = glob(self::$logPath . '/*.log');

        if (empty($files)) return;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }

    /**
     * Obtém todos os logs de hoje
     * @return array
     */
    public static function getTodayLogs()
    {
        $file = self::$logPath . '/' . date('Y-m-d') . '.log';
        if (!file_exists($file)) return [];

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_reverse($lines);
    }

    /**
     * Obtém as estatísticas do logger
     * @return array
     */
    public static function getStats()
    {
        $logs = self::getTodayLogs();
        $stats = [
            'total' => count($logs),
            'errors' => 0,
            'warnings' => 0,
            'info' => 0,
            'debug' => 0,
        ];

        foreach ($logs as $log) {
            if (strpos($log, '[ERROR]') !== false || strpos($log, '[CRITICAL]') !== false) {
                $stats['errors']++;
            } elseif (strpos($log, '[WARNING]') !== false) {
                $stats['warnings']++;
            } elseif (strpos($log, '[INFO]') !== false) {
                $stats['info']++;
            } elseif (strpos($log, '[DEBUG]') !== false) {
                $stats['debug']++;
            }
        }

        return $stats;
    }
}
