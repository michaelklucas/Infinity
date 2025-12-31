<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Controller\Pages
 */

namespace App\Controller\Pages;

use App\Utils\View;
use App\Utils\Logger;
use App\Utils\DebugBar;
use App\Model\Entity\Test as TestModel;
use Exception;

/**
 * Controlador de Testes do DebugBar
 */
class TestDebug extends Page
{
    /**
     * Método responsável por gerar diversos tipos de logs e erros para testar o DebugBar
     * @param Request $request
     * @return string
     */
    public static function getTest($request)
    {
        // 1. Teste de Logs
        Logger::info('Iniciando teste de DebugBar');
        Logger::warning('Aviso de teste: Verificando comportamento das abas');
        Logger::error('Erro simulado apenas para registro no log');
        
        // 2. Teste de Timings (Medição de tempo)
        $timer = DebugBar::startTiming('Processamento Complexo');
        usleep(50000); // Simula delay de 50ms
        DebugBar::endTiming($timer);

        // 3. Teste de Query SQL (simula erro de banco)
        try {
            TestModel::getDatabaseError();
        } catch (Exception $e) {
            Logger::error('Erro de banco capturado: ' . $e->getMessage());
        }

        // 4. Teste de Exceção ou Warning via Query Params
        $queryParams = $request->getQueryParams();
        
        if (isset($queryParams['error']) && $queryParams['error'] == 'fatal') {
            throw new Exception("ERRO FATAL CRÍTICO: Teste de Exception do Infinity Framework!");
        }

        if (isset($queryParams['error']) && $queryParams['error'] == 'warning') {
            $x = 10 / 0; // Dispara um warning de divisão por zero
        }

        // Renderiza a view de teste
        $content = View::render('pages/home', [
            'name'        => 'Teste do DebugBar',
            'description' => 'Esta página gerou vários logs, queries e timings. Verifique a barra de ferramentas no rodapé!',
            'features'    => '
                <div class="card">
                    <h3>Simular Exception</h3>
                    <p>Clique abaixo para disparar uma exceção e ver a nova tela de erro Premium.</p>
                    <a href="/debug-test?error=fatal" class="btn">Gerar Exception</a>
                </div>
                <div class="card">
                    <h3>Simular Warning</h3>
                    <p>Gera um erro de divisão por zero que será capturado pelo DebugBar.</p>
                    <a href="/debug-test?error=warning" class="btn">Gerar Warning</a>
                </div>
            '
        ]);

        return parent::getPage('Infinity | Debug Test', $content);
    }
}
