<?php

require __DIR__ . '/../includes/app.php';

use App\Utils\MaintenanceMode;

MaintenanceMode::init();

$action = $argv[1] ?? 'status';
$message = $argv[2] ?? null;

switch ($action) {
    case 'on':
    case 'enable':
        MaintenanceMode::enable($message);
        echo "✅ Maintenance mode ATIVADO\n";
        if ($message) {
            echo "Mensagem: $message\n";
        }
        break;

    case 'off':
    case 'disable':
        MaintenanceMode::disable();
        echo "✅ Maintenance mode DESATIVADO\n";
        break;

    case 'status':
        if (MaintenanceMode::isEnabled()) {
            $data = MaintenanceMode::getData();
            echo "🔧 Maintenance Mode: ATIVO\n";
            echo "Mensagem: {$data['message']}\n";
            echo "Tempo Restante: \n";
            echo "Ativado em: " . date('Y-m-d H:i:s', $data['timestamp']) . "\n";
            echo "Ativado por: {$data['by']}\n";
        } else {
            echo "✅ Maintenance Mode: INATIVO\n";
        }
        break;

    default:
        echo "Uso: php bin/maintenance.php <action> [message]\n";
        echo "\nAções disponíveis:\n";
        echo "  on|enable      - Ativar maintenance mode\n";
        echo "  off|disable    - Desativar maintenance mode\n";
        echo "  status         - Ver status atual\n";
        echo "\nExemplos:\n";
        echo "  php bin/maintenance.php on 'Atualizando banco de dados...'\n";
        echo "  php bin/maintenance.php off\n";
        echo "  php bin/maintenance.php status\n";
}
