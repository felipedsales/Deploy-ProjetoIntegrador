<?php

namespace App\Models;

/**
 * Exportação de dados pelo próprio usuário (RF23, RN20 — LGPD): reúne tudo o
 * que o sistema guarda sobre o candidato ou a empresa. A senha (hash) e o
 * conteúdo dos arquivos não entram; dos arquivos vão nome, tamanho e data, e
 * eles podem ser baixados nas telas de perfil.
 */
class ExportacaoDados extends Model
{
    protected $table = 'candidatos';

    public function gerar($perfil, $usuarioId)
    {
        $dados = $perfil === 'empresa' ? $this->daEmpresa($usuarioId) : $this->doCandidato($usuarioId);

        return [
            'sistema' => 'Ferraz Conecta',
            'gerado_em' => date('c'),
            'perfil' => $perfil,
        ] + $dados + [
            'notificacoes' => $this->db->fetchAll(
                "SELECT titulo, mensagem, lida, created_at FROM notificacoes WHERE tipo_usuario = ? AND usuario_id = ? ORDER BY id",
                [$perfil, $usuarioId]
            ),
            'pedidos_de_suporte' => $this->db->fetchAll(
                "SELECT assunto, mensagem, status, resposta, respondido_em, created_at
                 FROM tickets_suporte WHERE tipo_usuario = ? AND usuario_id = ? ORDER BY id",
                [$perfil, $usuarioId]
            ),
        ];
    }

    private function doCandidato($id)
    {
        $lista = function ($sql) use ($id) {
            return $this->db->fetchAll($sql, [$id]);
        };

        return [
            'cadastro' => $this->semSenha($this->db->fetch("SELECT * FROM candidatos WHERE id = ?", [$id])),
            'curriculo' => $this->db->fetch("SELECT nome_arquivo, tamanho, data_envio FROM curriculos WHERE candidato_id = ?", [$id]) ?: null,
            'cartas_de_apresentacao' => $lista(
                "SELECT ca.nome_arquivo, ca.tamanho, ca.data_envio, v.titulo AS vaga
                 FROM cartas_apresentacao ca LEFT JOIN vagas v ON v.id = ca.vaga_id WHERE ca.candidato_id = ? ORDER BY ca.id"
            ),
            'candidaturas' => $lista(
                "SELECT v.titulo AS vaga, e.nome AS empresa, cv.status, cv.data_candidatura
                 FROM candidaturas cv JOIN vagas v ON v.id = cv.vaga_id JOIN empresas e ON e.id = v.empresa_id
                 WHERE cv.candidato_id = ? ORDER BY cv.id"
            ),
            'entrevistas' => $lista(
                "SELECT v.titulo AS vaga, en.data_hora, en.local, en.status
                 FROM entrevistas en JOIN vagas v ON v.id = en.vaga_id WHERE en.candidato_id = ? ORDER BY en.id"
            ),
            'vagas_favoritas' => $lista(
                "SELECT v.titulo AS vaga, f.created_at FROM favoritos f JOIN vagas v ON v.id = f.vaga_id WHERE f.candidato_id = ? ORDER BY f.id"
            ),
            'denuncias_feitas' => $lista(
                "SELECT v.titulo AS vaga, d.motivo, d.descricao, d.status, d.created_at
                 FROM denuncias d LEFT JOIN vagas v ON v.id = d.vaga_id WHERE d.candidato_id = ? ORDER BY d.id"
            ),
            'mensagens' => $lista(
                "SELECT v.titulo AS vaga, m.remetente, m.texto, m.created_at
                 FROM mensagens m JOIN candidaturas cv ON cv.id = m.candidatura_id JOIN vagas v ON v.id = cv.vaga_id
                 WHERE cv.candidato_id = ? ORDER BY m.id"
            ),
            'avaliacoes' => $lista(
                "SELECT v.titulo AS vaga, a.autor, a.nota, a.comentario, a.created_at
                 FROM avaliacoes a JOIN candidaturas cv ON cv.id = a.candidatura_id JOIN vagas v ON v.id = cv.vaga_id
                 WHERE cv.candidato_id = ? ORDER BY a.id"
            ),
        ];
    }

    private function daEmpresa($id)
    {
        $lista = function ($sql) use ($id) {
            return $this->db->fetchAll($sql, [$id]);
        };

        return [
            'cadastro' => $this->semSenha($this->db->fetch("SELECT * FROM empresas WHERE id = ?", [$id])),
            'logo' => $this->db->fetch("SELECT tipo, tamanho, data_envio FROM logos_empresas WHERE empresa_id = ?", [$id]) ?: null,
            'vagas' => $lista(
                "SELECT titulo, descricao, requisitos, salario, tipo_contrato, modalidade, localizacao, nivel_experiencia,
                        beneficios, status, data_expiracao, visualizacoes, created_at
                 FROM vagas WHERE empresa_id = ? ORDER BY id"
            ),
            'candidaturas_recebidas' => $lista(
                "SELECT v.titulo AS vaga, cv.status, cv.data_candidatura
                 FROM candidaturas cv JOIN vagas v ON v.id = cv.vaga_id WHERE v.empresa_id = ? ORDER BY cv.id"
            ),
            'entrevistas' => $lista(
                "SELECT v.titulo AS vaga, en.data_hora, en.local, en.status
                 FROM entrevistas en JOIN vagas v ON v.id = en.vaga_id WHERE v.empresa_id = ? ORDER BY en.id"
            ),
            'mensagens_enviadas' => $lista(
                "SELECT v.titulo AS vaga, m.texto, m.created_at
                 FROM mensagens m JOIN candidaturas cv ON cv.id = m.candidatura_id JOIN vagas v ON v.id = cv.vaga_id
                 WHERE v.empresa_id = ? AND m.remetente = 'empresa' ORDER BY m.id"
            ),
            'avaliacoes' => $lista(
                "SELECT v.titulo AS vaga, a.autor, a.nota, a.comentario, a.created_at
                 FROM avaliacoes a JOIN candidaturas cv ON cv.id = a.candidatura_id JOIN vagas v ON v.id = cv.vaga_id
                 WHERE v.empresa_id = ? ORDER BY a.id"
            ),
        ];
    }

    private function semSenha($cadastro)
    {
        unset($cadastro['senha']);

        return $cadastro;
    }
}
