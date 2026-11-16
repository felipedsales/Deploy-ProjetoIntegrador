<?php

namespace App\Models;

/**
 * RF25 — ranqueamento de currículos e sugestão de vagas **por regras**, sem IA.
 *
 * RN13: o critério é objetivo e não discriminatório. A única coisa comparada é
 * a lista de requisitos da vaga com o texto do currículo e da carta de
 * apresentação. Nome, idade, endereço, CPF e qualquer outro dado pessoal do
 * candidato não entram na conta.
 *
 * Regra:
 *   1. Os requisitos da vaga (`vagas.requisitos`) são separados em itens (vírgula,
 *      ponto e vírgula, quebra de linha, ponto final ou " e ").
 *   2. Um item é atendido quando todas as suas palavras-chave aparecem no texto.
 *   3. Item encontrado no currículo vale 1 ponto; encontrado só na carta de
 *      apresentação vale meio ponto.
 *   4. Aderência = pontos ÷ total de itens, de 0 a 100.
 *
 * O ranqueamento só ordena e explica: nenhuma candidatura muda de status por ele.
 */
class Ranqueamento
{
    /** Quantos arquivos ainda sem texto são lidos por requisição, para não travar a página. */
    private const EXTRACOES_POR_REQUISICAO = 20;

    /** Palavras que não ajudam a distinguir um requisito. */
    private const PALAVRAS_IGNORADAS = [
        'a', 'ao', 'aos', 'as', 'com', 'como', 'da', 'das', 'de', 'do', 'dos', 'e', 'em', 'na', 'nas',
        'no', 'nos', 'o', 'os', 'ou', 'para', 'por', 'que', 'se', 'sem', 'ser', 'ter', 'um', 'uma',
        'conhecimento', 'conhecimentos', 'desejavel', 'diferencial', 'experiencia', 'necessario',
        'necessaria', 'nocoes', 'minimo', 'minima', 'boa', 'bom', 'boas', 'bons', 'area', 'etc',
    ];

    private $db;
    private $extracoes = 0;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Itens de requisito da vaga, com as palavras-chave de cada um.
     *
     * @return array lista de ['rotulo' => texto original, 'palavras' => [...]]
     */
    public function itensDaVaga($vaga)
    {
        $itens = [];
        foreach (preg_split('/[,;\n\r•·|]+|\.(?=\s|$)|\s[-–]\s|\se\s/ui', (string) ($vaga['requisitos'] ?? '')) as $trecho) {
            $rotulo = trim($trecho, " \t-–*");
            $palavras = array_values(array_diff($this->palavras($rotulo), self::PALAVRAS_IGNORADAS));
            if ($rotulo === '' || !$palavras) {
                continue;
            }
            $itens[implode(' ', $palavras)] = ['rotulo' => $rotulo, 'palavras' => $palavras];
        }

        return array_values($itens);
    }

    /**
     * Compara os itens de requisito com os textos do candidato.
     *
     * @return array ['pontuacao' => 0..100, 'atendidos' => [...], 'pela_carta' => [...], 'nao_encontrados' => [...]]
     */
    public function pontuar($itens, $textoCurriculo, $textoCarta = '')
    {
        $noCurriculo = array_flip($this->palavras($textoCurriculo));
        $naCarta = array_flip($this->palavras($textoCarta));
        $resultado = ['pontuacao' => 0, 'atendidos' => [], 'pela_carta' => [], 'nao_encontrados' => []];
        $pontos = 0;

        foreach ($itens as $item) {
            if ($this->contemTodas($item['palavras'], $noCurriculo)) {
                $resultado['atendidos'][] = $item['rotulo'];
                $pontos += 1;
            } elseif ($this->contemTodas($item['palavras'], $noCurriculo + $naCarta)) {
                $resultado['pela_carta'][] = $item['rotulo'];
                $pontos += 0.5;
            } else {
                $resultado['nao_encontrados'][] = $item['rotulo'];
            }
        }

        $resultado['pontuacao'] = $itens ? (int) round(100 * $pontos / count($itens)) : 0;

        return $resultado;
    }

    /**
     * Aderência de cada candidato da vaga (RN21: só da empresa dona da vaga).
     *
     * @return array|null null quando a vaga não tem requisitos que possam ser
     *                    comparados; senão, mapa candidato_id → resultado de
     *                    pontuar(), acrescido de 'situacao': 'calculada',
     *                    'sem_texto' (arquivo que não pôde ser lido) ou
     *                    'pendente' (texto ainda não extraído nesta requisição)
     */
    public function ranquearCandidatos($vaga, $empresaId)
    {
        $itens = $this->itensDaVaga($vaga);
        if (!$itens) {
            return null;
        }

        $sql = "SELECT cv.candidato_id, cu.id AS curriculo_id, cu.texto AS texto_curriculo,
                       (SELECT ca.id FROM cartas_apresentacao ca
                         WHERE ca.candidato_id = cv.candidato_id AND (ca.vaga_id = cv.vaga_id OR ca.vaga_id IS NULL)
                         ORDER BY ca.vaga_id IS NULL, ca.id DESC LIMIT 1) AS carta_id
                FROM candidaturas cv
                JOIN vagas v ON v.id = cv.vaga_id
                LEFT JOIN curriculos cu ON cu.candidato_id = cv.candidato_id
                WHERE cv.vaga_id = ? AND v.empresa_id = ?";

        $ranqueamento = [];
        foreach ($this->db->fetchAll($sql, [$vaga['id'], $empresaId]) as $linha) {
            $textoCurriculo = $linha['curriculo_id']
                ? $this->texto('curriculos', $linha['curriculo_id'], $linha['texto_curriculo'])
                : '';
            $textoCarta = $linha['carta_id'] ? $this->texto('cartas_apresentacao', $linha['carta_id']) : '';

            if ($textoCurriculo === null) {
                $ranqueamento[$linha['candidato_id']] = ['situacao' => 'pendente'];
            } elseif ($textoCurriculo === '' && (string) $textoCarta === '') {
                $ranqueamento[$linha['candidato_id']] = ['situacao' => 'sem_texto'];
            } else {
                $ranqueamento[$linha['candidato_id']] = $this->pontuar($itens, $textoCurriculo, (string) $textoCarta)
                    + ['situacao' => 'calculada'];
            }
        }

        return $ranqueamento;
    }

    /** Coloca primeiro os candidatos de maior aderência; quem não tem pontuação fica no fim. */
    public function ordenarPorAderencia($candidatos, $ranqueamento)
    {
        usort($candidatos, function ($a, $b) use ($ranqueamento) {
            return ($ranqueamento[$b['id']]['pontuacao'] ?? -1) <=> ($ranqueamento[$a['id']]['pontuacao'] ?? -1);
        });

        return $candidatos;
    }

    /**
     * Vagas abertas mais aderentes ao currículo do candidato (sugestão de vagas).
     *
     * @return array|null null quando o candidato não tem currículo com texto
     *                    legível; senão, as vagas com 'aderencia' (resultado de pontuar())
     */
    public function sugerirVagas($candidatoId, $limite = 10)
    {
        $curriculo = $this->db->fetch("SELECT id, texto FROM curriculos WHERE candidato_id = ?", [$candidatoId]);
        $texto = $curriculo ? $this->texto('curriculos', $curriculo['id'], $curriculo['texto']) : '';
        if ((string) $texto === '') {
            return null;
        }

        // RN14: só vagas ativas e dentro do prazo; fora as que o candidato já disputou.
        $sql = "SELECT v.*, e.nome AS empresa_nome
                FROM vagas v
                JOIN empresas e ON e.id = v.empresa_id
                WHERE v.status = 'Ativa' AND v.data_expiracao >= CURRENT_DATE AND e.status = 'Ativa'
                  AND NOT EXISTS (SELECT 1 FROM candidaturas cv WHERE cv.vaga_id = v.id AND cv.candidato_id = ?)
                ORDER BY v.created_at DESC
                LIMIT 300";

        $sugestoes = [];
        foreach ($this->db->fetchAll($sql, [$candidatoId]) as $vaga) {
            $itens = $this->itensDaVaga($vaga);
            $aderencia = $itens ? $this->pontuar($itens, $texto) : null;
            if ($aderencia && $aderencia['pontuacao'] > 0) {
                $sugestoes[] = $vaga + ['aderencia' => $aderencia];
            }
        }
        usort($sugestoes, function ($a, $b) {
            return $b['aderencia']['pontuacao'] <=> $a['aderencia']['pontuacao'];
        });

        return array_slice($sugestoes, 0, $limite);
    }

    /**
     * Texto já extraído do arquivo. Arquivo enviado antes do RF25 ainda não
     * tem texto (coluna nula): é lido agora, uma vez, e o resultado fica gravado.
     *
     * @return string|null null quando o limite de extrações da requisição acabou
     */
    private function texto($tabela, $id, $textoConhecido = false)
    {
        if ($textoConhecido === false) {
            $textoConhecido = $this->db->fetch("SELECT texto FROM {$tabela} WHERE id = ?", [$id])['texto'] ?? null;
        }
        if ($textoConhecido !== null) {
            return $textoConhecido;
        }
        if ($this->extracoes >= self::EXTRACOES_POR_REQUISICAO) {
            return null;
        }
        $this->extracoes++;

        $arquivo = $this->db->fetch("SELECT nome_arquivo, conteudo FROM {$tabela} WHERE id = ?", [$id]);
        $texto = $arquivo ? ExtratorTexto::extrair($arquivo['conteudo'], $arquivo['nome_arquivo']) : '';
        $this->db->query("UPDATE {$tabela} SET texto = ?, data_envio = data_envio WHERE id = ?", [$texto, $id]);

        return $texto;
    }

    /** Palavras do texto em minúsculas e sem acento; mantém +, # e . de termos como C++, C# e .NET. */
    private function palavras($texto)
    {
        $texto = mb_strtolower((string) $texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e',
            'í' => 'i', 'ì' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ú' => 'u', 'ü' => 'u',
            'ù' => 'u', 'ç' => 'c', 'ñ' => 'n',
        ]);
        preg_match_all('/[a-z0-9][a-z0-9+#]*(?:\.[a-z0-9+#]+)*|\.[a-z]+/', $texto, $encontradas);

        return array_values(array_unique(array_filter($encontradas[0], function ($palavra) {
            return strlen($palavra) >= 2 || !ctype_alpha($palavra);
        })));
    }

    private function contemTodas($palavras, $conjunto)
    {
        foreach ($palavras as $palavra) {
            if (!isset($conjunto[$palavra])) {
                return false;
            }
        }

        return true;
    }
}
