<?php

namespace App\Models;

/**
 * Arquivos enviados pelos usuários (RF08 currículo, RF07 logo).
 *
 * Esta classe só valida o envio. Os arquivos são gravados no banco (tabelas
 * curriculos e logos_empresas), e não no disco do servidor, para não se
 * perderem quando a aplicação é reimplantada.
 */
class Arquivo
{
    public const CURRICULO = [
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

    /** RF09 — carta de apresentação: mesmos formatos e limite do currículo. */
    public const CARTA = self::CURRICULO;

    public const LOGO = [
        'tamanho_maximo' => 1024 * 1024,
        'descricao' => 'imagem PNG, JPG ou WEBP de até 1 MB',
        'tipos' => [
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'],
        ],
    ];

    /**
     * Valida um arquivo recebido em $_FILES: envio, tamanho e tipo. O tipo é
     * conferido pelo conteúdo do arquivo, não pelo que o navegador informa.
     *
     * @param array|null $arquivo entrada de $_FILES
     * @param array      $regras  self::CURRICULO ou self::LOGO
     * @return array ['temporario', 'extensao', 'tipo', 'nome', 'tamanho'] ou ['erro' => mensagem]
     */
    public static function validar($arquivo, $regras)
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

        return [
            'temporario' => $arquivo['tmp_name'],
            'extensao' => $extensao,
            // Tipo oficial da extensão (o primeiro da lista), usado ao devolver o arquivo.
            'tipo' => $regras['tipos'][$extensao][0],
            'nome' => mb_substr(basename((string) $arquivo['name']), -255),
            'tamanho' => filesize($arquivo['tmp_name']),
        ];
    }
}
