<?php

namespace App\Controller\Pages;

use App\Utils\View;
use App\Http\SSE;

class TestSSE extends Page
{
    /**
     * Renderiza a página de demonstração do SSE
     * @return string
     */
    public static function getTestPage()
    {
        return parent::getPage('Teste SSE Real-Time', View::render('pages/test-sse'));
    }

    /**
     * Inicia o stream de dados
     */
    public static function stream()
    {
        // Inicia headers SSE
        SSE::start();

        // Simula um processo de 5 passos
        $steps = [
            'Iniciando conexão segura...',
            'Carregando módulos do sistema...',
            'Processando dados do usuário...',
            'Otimizando banco de dados...',
            'Finalizando operações...'
        ];

        foreach ($steps as $i => $step) {
            // Calcula progresso
            $progress = ($i + 1) * 20;
            
            // Envia evento de 'update'
            SSE::send([
                'progress' => $progress,
                'message' => $step,
                'timestamp' => date('H:i:s')
            ], 'update');

            // Simula tempo de processamento
            sleep(1);
        }

        // Envia evento final
        SSE::send(['message' => 'Processo Concluído!'], 'complete');
        
        // Finaliza (opcional, client pode fechar)
        exit;
    }
}
