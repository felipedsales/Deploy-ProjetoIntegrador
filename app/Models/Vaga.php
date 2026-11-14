<?php

namespace App\Models;

class Vaga extends Model
{
    protected $table = 'vagas';

    /** Domínios dos ENUMs de vagas, usados nos formulários e nos filtros da busca. */
    public const TIPOS_CONTRATO = ['CLT', 'PJ', 'Freelance', 'Estágio'];
    public const MODALIDADES = ['Presencial', 'Remoto', 'Híbrido'];
    public const NIVEIS_EXPERIENCIA = ['Sem experiência', 'Júnior', 'Pleno', 'Sênior'];

    /** RF11: campos acompanhados no histórico de alterações e o nome exibido de cada um. */
    public const CAMPOS_HISTORICO = [
        'titulo' => 'Título', 'descricao' => 'Descrição', 'requisitos' => 'Requisitos', 'salario' => 'Salário',
        'tipo_contrato' => 'Tipo de contrato', 'modalidade' => 'Modalidade', 'localizacao' => 'Localização',
        'categoria_id' => 'Categoria', 'nivel_experiencia' => 'Nível de experiência', 'beneficios' => 'Benefícios',
        'status' => 'Status', 'data_expiracao' => 'Candidaturas até',
    ];

    /** RN14: validade mínima, em dias, do prazo de candidatura de uma vaga. */
    public const VALIDADE_MINIMA_DIAS = 7;

    /**
     * RN14: condição SQL das vagas que podem ser exibidas ao público —
     * status 'Ativa' e prazo de candidatura ainda válido. Espera o alias "v".
     */
    private const CONDICAO_ATIVA = "v.status = 'Ativa' AND v.data_expiracao >= CURRENT_DATE";

    /**
     * RN14 (Diagrama de Classe: Vaga.estaAtiva): a vaga só pode ser exibida e
     * receber candidaturas se estiver com status 'Ativa' e dentro do prazo.
     */
    public function estaAtiva($vaga)
    {
        if (!$vaga || ($vaga['status'] ?? null) !== 'Ativa' || empty($vaga['data_expiracao'])) {
            return false;
        }

        return $vaga['data_expiracao'] >= date('Y-m-d');
    }

    /**
     * Menor data de expiração aceita (RN14): VALIDADE_MINIMA_DIAS dias depois
     * da publicação. Para vaga nova, a publicação é hoje.
     */
    public function dataExpiracaoMinima($dataPublicacao = null)
    {
        $base = $dataPublicacao ? substr($dataPublicacao, 0, 10) : date('Y-m-d');

        return date('Y-m-d', strtotime($base . ' +' . self::VALIDADE_MINIMA_DIAS . ' days'));
    }

    /**
     * Valida os dados de uma vaga antes de gravar (UC-E05 Manter Vagas).
     *  - RN03: título, descrição, requisitos e localização são obrigatórios.
     *  - RN14: data de expiração obrigatória, com validade mínima de 7 dias
     *    contados da publicação.
     *
     * @param array       $dados          campos enviados pelo formulário
     * @param string|null $dataPublicacao created_at da vaga, ao editar
     * @return array mapa campo → mensagem; vazio quando está tudo certo
     */
    public function validar($dados, $dataPublicacao = null)
    {
        $erros = [];

        $obrigatorios = [
            'titulo' => 'Informe o título da vaga.',
            'descricao' => 'Informe a descrição da vaga.',
            'requisitos' => 'Informe os requisitos da vaga.',
            'localizacao' => 'Informe a localização da vaga.',
        ];
        foreach ($obrigatorios as $campo => $mensagem) {
            if (trim((string) ($dados[$campo] ?? '')) === '') {
                $erros[$campo] = $mensagem;
            }
        }

        $dataExpiracao = trim((string) ($dados['data_expiracao'] ?? ''));
        $data = \DateTime::createFromFormat('!Y-m-d', $dataExpiracao);
        if ($dataExpiracao === '') {
            $erros['data_expiracao'] = 'Informe até quando a vaga recebe candidaturas.';
        } elseif (!$data || $data->format('Y-m-d') !== $dataExpiracao) {
            $erros['data_expiracao'] = 'Data de expiração inválida.';
        } else {
            $minima = $this->dataExpiracaoMinima($dataPublicacao);
            if ($dataExpiracao < $minima) {
                $erros['data_expiracao'] = 'A vaga deve ficar aberta por no mínimo '
                    . self::VALIDADE_MINIMA_DIAS . ' dias: escolha uma data a partir de '
                    . date('d/m/Y', strtotime($minima)) . '.';
            }
        }


        // RF12: categoria e nível de experiência são opcionais, mas têm de existir.
        if (!empty($dados['categoria_id']) && !(new Categoria())->findById($dados['categoria_id'])) {
            $erros['categoria_id'] = 'Categoria inválida.';
        }
        if (!empty($dados['nivel_experiencia']) && !in_array($dados['nivel_experiencia'], self::NIVEIS_EXPERIENCIA, true)) {
            $erros['nivel_experiencia'] = 'Nível de experiência inválido.';
        }

        return $erros;
    }

    /**
     * RF11 — grava a edição da vaga e, na mesma transação, uma linha no
     * histórico com os campos que mudaram. Edição sem mudança não gera histórico.
     */
    public function atualizarComHistorico($vaga, $dados)
    {
        $alteracoes = [];
        foreach (array_keys(self::CAMPOS_HISTORICO) as $campo) {
            if (!array_key_exists($campo, $dados)) {
                continue;
            }
            $de = $this->normalizar($campo, $vaga[$campo] ?? null);
            $para = $this->normalizar($campo, $dados[$campo]);
            if ($de !== $para) {
                $alteracoes[$campo] = ['de' => $de, 'para' => $para];
            }
        }

        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $this->update($vaga['id'], $dados);
            if ($alteracoes) {
                $this->db->query(
                    "INSERT INTO vagas_historico (vaga_id, alteracoes) VALUES (?, ?)",
                    [$vaga['id'], json_encode($alteracoes, JSON_UNESCAPED_UNICODE)]
                );
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return count($alteracoes);
    }

    /** Deixa o valor antigo e o novo comparáveis (texto vazio = nulo; salário como número). */
    private function normalizar($campo, $valor)
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }
        if ($campo === 'salario') {
            return number_format((float) $valor, 2, '.', '');
        }
        if ($campo === 'categoria_id') {
            return (string) (int) $valor;
        }

        return trim(str_replace("\r\n", "\n", (string) $valor));
    }

    /** RF11 — histórico de alterações da vaga, do mais recente para o mais antigo. */
    public function getHistorico($vagaId)
    {
        $linhas = $this->db->fetchAll(
            "SELECT alteracoes, created_at FROM vagas_historico WHERE vaga_id = ? ORDER BY id DESC",
            [$vagaId]
        );
        foreach ($linhas as &$linha) {
            $linha['alteracoes'] = json_decode($linha['alteracoes'], true) ?: [];
        }

        return $linhas;
    }

    /** RN19 — conta uma visualização, sem mexer na data de atualização da vaga. */
    public function registrarVisualizacao($vagaId)
    {
        $sql = "UPDATE {$this->table} SET visualizacoes = visualizacoes + 1, updated_at = updated_at WHERE id = ?";
        return $this->db->query($sql, [$vagaId]);
    }

    public function getVagasDestaque($limit = 8)
    {
        // RN14: só vagas ativas e dentro do prazo.
        $sql = "SELECT v.*, e.nome AS empresa_nome FROM {$this->table} v
                LEFT JOIN empresas e ON e.id = v.empresa_id
                WHERE " . self::CONDICAO_ATIVA . "
                ORDER BY v.id DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * RF12 / UC-C03 Pesquisar Vagas — busca com filtros combináveis (RN05):
     * palavra-chave, localidade, área (categoria), nível de experiência, tipo de
     * contrato e modalidade. Só devolve vagas
     * ativas e dentro do prazo (RN14). Filtro vazio ou com valor fora do
     * domínio é ignorado.
     */
    public function buscarVagas($filtros)
    {
        $condicoes = [self::CONDICAO_ATIVA];
        $parametros = [];

        $termo = trim((string) ($filtros['busca'] ?? ''));
        if ($termo !== '') {
            $condicoes[] = '(v.titulo LIKE ? OR v.descricao LIKE ? OR v.requisitos LIKE ?)';
            array_push($parametros, "%{$termo}%", "%{$termo}%", "%{$termo}%");
        }

        $localizacao = trim((string) ($filtros['localizacao'] ?? ''));
        if ($localizacao !== '') {
            $condicoes[] = 'v.localizacao LIKE ?';
            $parametros[] = "%{$localizacao}%";
        }

        if (in_array($filtros['tipo_contrato'] ?? '', self::TIPOS_CONTRATO, true)) {
            $condicoes[] = 'v.tipo_contrato = ?';
            $parametros[] = $filtros['tipo_contrato'];
        }

        if (in_array($filtros['modalidade'] ?? '', self::MODALIDADES, true)) {
            $condicoes[] = 'v.modalidade = ?';
            $parametros[] = $filtros['modalidade'];
        }

        // Área: categoria da vaga (UC13).
        if ((int) ($filtros['categoria_id'] ?? 0) > 0) {
            $condicoes[] = 'v.categoria_id = ?';
            $parametros[] = (int) $filtros['categoria_id'];
        }

        if (in_array($filtros['nivel_experiencia'] ?? '', self::NIVEIS_EXPERIENCIA, true)) {
            $condicoes[] = 'v.nivel_experiencia = ?';
            $parametros[] = $filtros['nivel_experiencia'];
        }

        $sql = "SELECT v.*, e.nome as empresa_nome, c.nome AS categoria_nome
                FROM {$this->table} v
                JOIN empresas e ON v.empresa_id = e.id
                LEFT JOIN categorias c ON c.id = v.categoria_id
                WHERE " . implode(' AND ', $condicoes) . "
                ORDER BY v.created_at DESC";
        return $this->db->fetchAll($sql, $parametros);
    }

    public function getVagasPorEmpresa($empresaId)
    {
        $sql = "SELECT v.*, e.nome as empresa_nome 
                FROM {$this->table} v 
                JOIN empresas e ON v.empresa_id = e.id 
                WHERE v.empresa_id = ? 
                ORDER BY v.created_at DESC";
        return $this->db->fetchAll($sql, [$empresaId]);
    }

    public function getCandidatosVaga($vagaId)
    {
        $sql = "SELECT c.*, cv.data_candidatura, cv.status 
                FROM candidaturas cv 
                JOIN candidatos c ON cv.candidato_id = c.id 
                WHERE cv.vaga_id = ? 
                ORDER BY cv.data_candidatura DESC";
        return $this->db->fetchAll($sql, [$vagaId]);
    }

    public function getEstatisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_vagas,
                    COUNT(CASE WHEN status = 'Ativa' THEN 1 END) as vagas_ativas,
                    COUNT(CASE WHEN status = 'Inativa' THEN 1 END) as vagas_fechadas
                FROM {$this->table}";
        return $this->db->fetch($sql);
    }

    /** Lista pública de vagas — RN14: só ativas e dentro do prazo. */
    public function findAll()
    {
        $sql = "SELECT v.*, e.nome as empresa_nome 
                FROM {$this->table} v 
                JOIN empresas e ON v.empresa_id = e.id 
                WHERE " . self::CONDICAO_ATIVA . "
                ORDER BY v.created_at DESC";
        return $this->db->fetchAll($sql);
    }

    // Métodos para denúncias
    public function denunciarVaga($candidatoId, $vagaId, $motivo, $descricao = null)
    {
        $sql = "INSERT INTO denuncias (candidato_id, vaga_id, motivo, descricao) 
                VALUES (?, ?, ?, ?)";
        return $this->db->query($sql, [$candidatoId, $vagaId, $motivo, $descricao]);
    }

    public function verificarDenunciaExistente($candidatoId, $vagaId)
    {
        $sql = "SELECT id FROM denuncias WHERE candidato_id = ? AND vaga_id = ?";
        return $this->db->fetch($sql, [$candidatoId, $vagaId]);
    }

    public function getDenunciasVaga($vagaId)
    {
        $sql = "SELECT dv.*, c.nome as candidato_nome 
                FROM denuncias dv 
                JOIN candidatos c ON dv.candidato_id = c.id 
                WHERE dv.vaga_id = ? 
                ORDER BY dv.created_at DESC";
        return $this->db->fetchAll($sql, [$vagaId]);
    }

    public function getTodasDenuncias()
    {
        $sql = "SELECT dv.*, c.nome as candidato_nome, v.titulo as vaga_titulo, e.nome as empresa_nome
                FROM denuncias dv 
                JOIN candidatos c ON dv.candidato_id = c.id 
                JOIN vagas v ON dv.vaga_id = v.id 
                JOIN empresas e ON v.empresa_id = e.id 
                ORDER BY dv.created_at DESC";
        return $this->db->fetchAll($sql);
    }
}
