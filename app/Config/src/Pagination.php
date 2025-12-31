<?php
/**
 * Infinity Framework
 * @author Infinity
 * @package App\Config\src
 */

namespace App\Config\src;

/**
 * Utilitário de Paginação - Calcula e gerencia links de páginas para listagens
 */
class Pagination
{
    /**
     * Número máximo de registros por página
     * @var int
     */
    private $limit;

    /**
     * Quantidade total de resultados no banco de dados
     * @var int
     */
    private $results;

    /**
     * Quantidade total de páginas calculadas
     * @var int
     */
    private $pages;

    /**
     * Índice da página atual
     * @var int
     */
    private $currentPage;

    /**
     * Número máximo de links de páginas a serem exibidos simultaneamente
     * @var int
     */
    private $maxPages;

    /**
     * Construtor da classe de Paginação
     * @param int $results Total de resultados
     * @param int $currentPage Página atual solicitada
     * @param int $limit Limite de registros por página
     * @param int $maxPages Máximo de links visíveis
     */
    public function __construct($results, $currentPage = 1, $limit = 10, $maxPages = 5)
    {
        $this->results     = $results;
        $this->limit       = $limit;
        $this->currentPage = (is_numeric($currentPage) && $currentPage > 0) ? (int)$currentPage : 1;
        $this->maxPages    = $maxPages;
        $this->calculate();
    }

    /**
     * Método responsável por calcular as variáveis da paginação
     */
    private function calculate()
    {
        // Calcula o total de páginas necessário
        $this->pages = $this->results > 0 ? ceil($this->results / $this->limit) : 1;

        // Garante que a página atual não ultrapasse o limite total de páginas
        if ($this->currentPage > $this->pages) {
            $this->currentPage = (int)$this->pages;
        }
    }

    /**
     * Retorna a cláusula LIMIT para ser utilizada em consultas SQL
     * @return string Ex: "0,10"
     */
    public function getLimit()
    {
        $offset = ($this->limit * ($this->currentPage - 1));
        return $offset . ',' . $this->limit;
    }

    /**
     * Retorna a estrutura de páginas (links) disponíveis para renderização
     * @return array
     */
    public function getPages()
    {
        // Se houver apenas uma página, não retorna nada
        if ($this->pages <= 1) return [];

        $pages = [];

        // Calcula o início e o fim da exibição dos links
        $startPage = max(1, $this->currentPage - floor($this->maxPages / 2));
        $endPage = min($this->pages, $startPage + $this->maxPages - 1);

        // Ajusta o início se o fim estiver no limite
        if ($endPage - $startPage + 1 < $this->maxPages) {
            $startPage = max(1, $endPage - $this->maxPages + 1);
        }

        // Adiciona seta para a página anterior caso não esteja no início
        if ($startPage > 1) {
            $pages[] = [
                'page'    => $startPage - 1,
                'current' => false,
                'arrow'   => 'prev',
            ];
        }

        // Gera os links das páginas
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pages[] = [
                'page'    => $i,
                'current' => $i == $this->currentPage,
                'arrow'   => null,
            ];
        }

        // Adiciona seta para a próxima página caso não esteja no fim
        if ($endPage < $this->pages) {
            $pages[] = [
                'page'    => $endPage + 1,
                'current' => false,
                'arrow'   => 'next',
            ];
        }

        return $pages;
    }
}
