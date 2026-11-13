<?php

namespace App\Models;

/**
 * Categoria de vaga (RF12 — filtro por área; UC13 Gerenciar Categorias de Vagas).
 * A leitura fica aqui; criar, editar e excluir são ações do administrador e
 * ficam em Administrador, junto com o registro em log (RN07).
 */
class Categoria extends Model
{
    protected $table = 'categorias';

    /** Categorias em ordem alfabética, para os formulários e filtros. */
    public function listar()
    {
        return $this->db->fetchAll("SELECT id, nome FROM {$this->table} ORDER BY nome");
    }

    /** Categorias com a quantidade de vagas que usam cada uma (tela do administrador). */
    public function listarComUso()
    {
        $sql = "SELECT c.id, c.nome, COUNT(v.id) AS total_vagas
                FROM {$this->table} c
                LEFT JOIN vagas v ON v.categoria_id = c.id
                GROUP BY c.id, c.nome
                ORDER BY c.nome";
        return $this->db->fetchAll($sql);
    }
}
