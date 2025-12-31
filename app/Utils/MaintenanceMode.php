<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Utils
 */

namespace App\Utils;

/**
 * Modo de Manutenção - Gerencia o estado de disponibilidade da aplicação
 */
class MaintenanceMode
{
    /**
     * Caminho do arquivo de trava
     * @var string
     */
    private static $lockFile;

    /**
     * Mensagem padrão de manutenção
     * @var string
     */
    private static $message = 'Estamos realizando uma manutenção programada para melhorar sua experiência. Voltamos em instantes.';

    /**
     * Tempo de retorno estimado (em segundos)
     * @var int
     */
    private static $retryAfter = 3600;

    /**
     * Método responsável por inicializar o utilitário
     */
    public static function init()
    {
        self::$lockFile = __DIR__ . '/../../storage/maintenance.lock';
    }

    /**
     * Habilita o modo de manutenção
     * @param string $message
     * @param int $retryAfter
     */
    public static function enable($message = null, $retryAfter = 3600)
    {
        if (!self::$lockFile) self::init();

        $data = [
            'enabled' => true,
            'message' => $message ?? self::$message,
            'retryAfter' => $retryAfter,
            'timestamp' => time(),
            'by' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        file_put_contents(self::$lockFile, json_encode($data), LOCK_EX);
        chmod(self::$lockFile, 0644);

        Logger::info('Modo de manutenção habilitado', ['message' => $message]);
    }

    /**
     * Desabilita o modo de manutenção
     */
    public static function disable()
    {
        if (!self::$lockFile) self::init();

        if (file_exists(self::$lockFile)) {
            unlink(self::$lockFile);
            Logger::info('Modo de manutenção desabilitado');
        }
    }

    /**
     * Verifica se o modo de manutenção está ativo
     * @return bool
     */
    public static function isEnabled()
    {
        if (!self::$lockFile) self::init();
        return file_exists(self::$lockFile);
    }

    /**
     * Obtém os dados do estado de manutenção
     * @return array|null
     */
    public static function getData()
    {
        if (!self::$lockFile) self::init();
        if (!file_exists(self::$lockFile)) return null;

        $data = json_decode(file_get_contents(self::$lockFile), true);
        return $data ?? [];
    }

    /**
     * Retorna o tempo restante em segundos
     * @return int|null
     */
    public static function getRemainingTime()
    {
        $data = self::getData();
        if (!$data) return null;

        $elapsed = time() - $data['timestamp'];
        $remaining = $data['retryAfter'] - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Renderiza a página de manutenção com design Premium
     */
    public static function render()
    {
        $data = self::getData();
        $message = $data['message'] ?? self::$message;
        $remaining = self::getRemainingTime() ?? 3600;

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manutenção - Infinity Framework</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --bg: #0f172a;
            --text: #f8fafc;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(79, 70, 229, 0.15) 0%, transparent 40%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            padding: 60px;
            border-radius: 32px;
            max-width: 650px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            font-size: 3rem;
            font-weight: 600;
            margin-bottom: 24px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p {
            color: #94a3b8;
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 40px;
        }

        .timer-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .timer-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            padding: 20px;
            border-radius: 16px;
        }

        .timer-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary);
            display: block;
        }

        .timer-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        .footer {
            border-top: 1px solid var(--glass-border);
            padding-top: 30px;
            font-size: 0.9rem;
            color: #475569;
        }

        .footer span { color: var(--primary); font-weight: 600; }

        @media (max-width: 480px) {
            .container { padding: 40px 20px; }
            h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema em Manutenção</h1>
        <p>$message</p>
        
        <div class="timer-container">
            <div class="timer-box">
                <span class="timer-value" id="hours">00</span>
                <span class="timer-label">Horas</span>
            </div>
            <div class="timer-box">
                <span class="timer-value" id="minutes">00</span>
                <span class="timer-label">Minutos</span>
            </div>
            <div class="timer-box">
                <span class="timer-value" id="seconds">00</span>
                <span class="timer-label">Segundos</span>
            </div>
        </div>

        <div class="footer">
            Infinity Framework • <span>Modo de Manutenção</span>
        </div>
    </div>

    <script>
        let timeLeft = $remaining;

        function updateTimer() {
            if (timeLeft <= 0) {
                location.reload();
                return;
            }

            const h = Math.floor(timeLeft / 3600);
            const m = Math.floor((timeLeft % 3600) / 60);
            const s = timeLeft % 60;

            document.getElementById('hours').textContent = String(h).padStart(2, '0');
            document.getElementById('minutes').textContent = String(m).padStart(2, '0');
            document.getElementById('seconds').textContent = String(s).padStart(2, '0');
            
            timeLeft--;
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Verificar disponibilidade a cada 30 segundos
        setInterval(() => {
            fetch(window.location.href, { method: 'HEAD' })
                .then(res => { if(res.status !== 503) location.reload(); })
                .catch(() => {});
        }, 30000);
    </script>
</body>
</html>
HTML;
    }
}
