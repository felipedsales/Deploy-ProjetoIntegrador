<?php

namespace App\Models;

/**
 * Extrai o texto de um currículo ou de uma carta de apresentação (RF25), para
 * o ranqueamento por regras. PDF é lido com o utilitário `pdftotext`
 * (poppler-utils, instalado na imagem) e DOCX é lido direto do XML interno.
 *
 * Não há como ler o formato antigo .doc nem PDF que seja só imagem
 * (digitalizado): nesses casos o resultado é uma string vazia.
 */
class ExtratorTexto
{
    /** Limite do texto guardado por arquivo, em caracteres. */
    private const TAMANHO_MAXIMO = 100000;

    /**
     * @param string $conteudo    bytes do arquivo
     * @param string $nomeArquivo nome original, usado só para saber a extensão
     * @return string texto extraído; vazio quando não foi possível extrair
     */
    public static function extrair($conteudo, $nomeArquivo)
    {
        $extensao = strtolower(pathinfo((string) $nomeArquivo, PATHINFO_EXTENSION));

        try {
            if ($extensao === 'pdf') {
                $texto = self::dePdf($conteudo);
            } elseif ($extensao === 'docx') {
                $texto = self::deDocx($conteudo);
            } else {
                $texto = '';
            }
        } catch (\Throwable $e) {
            error_log('[Ferraz Conecta] extração de texto falhou: ' . $e->getMessage());
            $texto = '';
        }

        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
        }
        $texto = trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', preg_replace('/\R+/u', "\n", $texto)));

        return mb_substr($texto, 0, self::TAMANHO_MAXIMO);
    }

    private static function dePdf($conteudo)
    {
        $temporario = self::gravarTemporario($conteudo);
        try {
            // Sem shell: o comando é uma lista de argumentos e o caminho é gerado aqui.
            $processo = proc_open(
                ['timeout', '15', 'pdftotext', '-enc', 'UTF-8', '-q', $temporario, '-'],
                [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']],
                $canais
            );
            if (!is_resource($processo)) {
                return '';
            }
            $texto = stream_get_contents($canais[1]);
            fclose($canais[1]);
            proc_close($processo);

            return (string) $texto;
        } finally {
            @unlink($temporario);
        }
    }

    private static function deDocx($conteudo)
    {
        $temporario = self::gravarTemporario($conteudo);
        try {
            $zip = new \ZipArchive();
            if ($zip->open($temporario) !== true) {
                return '';
            }
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml === false) {
                return '';
            }

            // Fim de parágrafo, quebra e tabulação viram espaço em branco antes de tirar as marcações.
            $xml = preg_replace('#</w:p>|<w:br[^>]*/>|<w:tab[^>]*/>#', "\n", $xml);

            return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        } finally {
            @unlink($temporario);
        }
    }

    private static function gravarTemporario($conteudo)
    {
        $temporario = tempnam(sys_get_temp_dir(), 'fc_texto_');
        file_put_contents($temporario, $conteudo);

        return $temporario;
    }
}
