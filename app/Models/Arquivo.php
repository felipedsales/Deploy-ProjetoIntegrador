<?php

namespace App\Models;

/**
 * Arquivos enviados pelos usuários (RF08 currículo, RF07 logo).
 *
 * Ficam em uploads/, fora do DocumentRoot (public/): nenhum arquivo é servido
 * direto pelo Apache, só por rotas que conferem a permissão de quem pede.
 */
class Arquivo
{
    public const CURRICULO = [
        'pasta' => 'curriculos',
        'tamanho_maximo' => 2 * 1024 * 1024,
        'descricao' => 'PDF, DOC ou DOCX de até 2 MB',
        // extensão => tipos de conteúdo aceitos (detectados no arquivo, não informados pelo navegador)
        'tipos' => [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/octet-stream',
            ],
        ],
    ];

    public const LOGO = [
        'pasta' => 'logos',
        'tamanho_maximo' => 1024 * 1024,
        'descricao' => 'imagem PNG, JPG ou WEBP de até 1 MB',
        'tipos' => [
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'],
        ],
    ];

    private static function raiz()
    {
        return dirname(__DIR__, 2) . '/uploads';
    }

    /**
     * Valida e grava um arquivo recebido em $_FILES.
     *
     * @param array|null $arquivo entrada de $_FILES
     * @param array      $regras  self::CURRICULO ou self::LOGO
     * @param string     $prefixo identifica o dono no nome do arquivo (ex.: id do candidato)
     * @return array ['caminho' => relativo a uploads/] ou ['erro' => mensagem]
     */
    public static function salvar($arquivo, $regras, $prefixo)
    {
        $erroEnvio = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($erroEnvio === UPLOAD_ERR_NO_FILE) {
            return ['erro' => 'Selecione um arquivo.'];
        }
        if ($erroEnvio === UPLOAD_ERR_INI_SIZE || $erroEnvio === UPLOAD_ERR_FORM_SIZE
            || ($arquivo['size'] ?? 0) > $regras['tamanho_maximo']) {
            return ['erro' => "Arquivo grande demais. Envie {$regras['descricao']}."];
        }
        if ($erroEnvio !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
            return ['erro' => 'Não foi possível receber o arquivo. Tente novamente.'];
        }

        $extensao = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

        if (!isset($regras['tipos'][$extensao]) || !in_array($tipo, $regras['tipos'][$extensao], true)) {
            return ['erro' => "Tipo de arquivo não aceito. Envie {$regras['descricao']}."];
        }

        $pasta = self::raiz() . '/' . $regras['pasta'];
        if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
            return ['erro' => 'Não foi possível gravar o arquivo. Tente novamente.'];
        }

        // Nome gerado pelo sistema: o nome original não entra no caminho.
        $caminho = $regras['pasta'] . '/' . $prefixo . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;
        if (!move_uploaded_file($arquivo['tmp_name'], self::raiz() . '/' . $caminho)) {
            return ['erro' => 'Não foi possível gravar o arquivo. Tente novamente.'];
        }

        return ['caminho' => $caminho];
    }

    /** Caminho absoluto de um arquivo gravado, ou null se não existir dentro de uploads/. */
    public static function localizar($caminho)
    {
        if (!$caminho) {
            return null;
        }

        $raiz = realpath(self::raiz());
        $absoluto = realpath(self::raiz() . '/' . $caminho);

        if ($raiz === false || $absoluto === false || strpos($absoluto, $raiz . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return is_file($absoluto) ? $absoluto : null;
    }

    public static function remover($caminho)
    {
        $absoluto = self::localizar($caminho);
        if ($absoluto !== null) {
            unlink($absoluto);
        }
    }
}
