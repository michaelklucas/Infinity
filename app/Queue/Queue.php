<?php

namespace App\Queue;

use App\Config\src\Database;

class Queue
{
    /**
     * Adiciona um Job na fila
     * @param string $jobClass Nome da classe do Job (deve ter método handle)
     * @param array $data Dados para processamento
     * @return int ID do job criado
     */
    public static function push($jobClass, $data = [])
    {
        $db = new Database('jobs');
        return $db->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'job' => $jobClass,
                'data' => $data
            ]),
            'attempts' => 0,
            'available_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Processa o próximo job da fila
     * @return bool Se processou algo
     */
    public static function work()
    {
        $db = new Database('jobs');
        
        // Busca job pendente e "trava" (simulado com verificação simples)
        // Em produção idealmente usaríamos transações ou SELECT FOR UPDATE
        $job = $db->select("reserved = 0 AND available_at <= '" . date('Y-m-d H:i:s') . "'", 'id ASC', '1')->fetch(\PDO::FETCH_ASSOC);

        if (!$job) {
            return false;
        }

        // Marca como reservado
        $db->update('id = ' . $job['id'], ['reserved' => 1, 'reserved_at' => date('Y-m-d H:i:s')]);

        try {
            $payload = json_decode($job['payload'], true);
            $jobClass = $payload['job'];
            $data = $payload['data'];

            if (class_exists($jobClass)) {
                $instance = new $jobClass();
                if (method_exists($instance, 'handle')) {
                    // Executa o Job
                    echo "[\033[32mProcessando\033[0m] Job #{$job['id']} {$jobClass}...\n";
                    $instance->handle($data);
                    echo "[\033[32mSucesso\033[0m] Job #{$job['id']} finalizado.\n";
                    
                    // Remove da fila
                    $db->delete('id = ' . $job['id']);
                    return true;
                }
            }
            
            throw new \Exception("Classe ou método handle não encontrado.");

        } catch (\Throwable $e) {
            echo "[\033[31mFalha\033[0m] Job #{$job['id']}: " . $e->getMessage() . "\n";
            
            // Devolve para fila com delay e incrementa tentativas
            $attempts = $job['attempts'] + 1;
            if ($attempts >= 3) {
                // Falhou max vezes, remove ou move para failed_jobs (aqui apenas removemos)
                $db->delete('id = ' . $job['id']);
                echo "[\033[31mRemovido\033[0m] Job #{$job['id']} atingiu max tentativas.\n";
            } else {
                $db->update('id = ' . $job['id'], [
                    'reserved' => 0,
                    'attempts' => $attempts,
                    'available_at' => date('Y-m-d H:i:s', strtotime('+5 seconds')) // Retry delay
                ]);
            }
            return true;
        }
    }
}
