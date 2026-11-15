<?php

namespace App\Models;

/**
 * Envio de e-mail transacional pela API HTTP do Brevo (RN22, RF05, RNF09).
 *
 * A chave e o remetente vêm só de variável de ambiente (BREVO_API_KEY,
 * BREVO_REMETENTE_EMAIL, BREVO_REMETENTE_NOME). Sem elas o envio fica
 * desligado e quem chama decide o que fazer (ver App\Models\Acesso).
 */
class Email
{
    private const URL_API = 'https://api.brevo.com/v3/smtp/email';

    /** Há serviço de e-mail configurado neste ambiente? */
    public static function configurado()
    {
        if (self::arquivoDeTeste()) {
            return true;
        }

        return (string) ferraz_env('BREVO_API_KEY', '') !== ''
            && Validador::email(ferraz_env('BREVO_REMETENTE_EMAIL', ''));
    }

    /**
     * Envia uma mensagem. Nunca lança exceção: falha de envio vira false e
     * uma linha no log, sem o conteúdo da mensagem nem a chave.
     *
     * @return bool
     */
    public static function enviar($paraEmail, $paraNome, $assunto, $html)
    {
        if (!self::configurado()) {
            return false;
        }

        if ($arquivo = self::arquivoDeTeste()) {
            $linha = json_encode(['para' => $paraEmail, 'assunto' => $assunto, 'html' => $html], JSON_UNESCAPED_UNICODE);
            return file_put_contents($arquivo, $linha . "\n", FILE_APPEND) !== false;
        }

        $corpo = json_encode([
            'sender' => [
                'email' => trim(ferraz_env('BREVO_REMETENTE_EMAIL')),
                'name' => (string) ferraz_env('BREVO_REMETENTE_NOME', 'Ferraz Conecta'),
            ],
            'to' => [['email' => $paraEmail, 'name' => $paraNome ?: $paraEmail]],
            'subject' => $assunto,
            'htmlContent' => $html,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::URL_API);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $corpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'api-key: ' . ferraz_env('BREVO_API_KEY'),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $resposta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return true;
        }

        $motivo = $erro ?: (json_decode((string) $resposta, true)['message'] ?? 'sem detalhe');
        error_log("[Ferraz Conecta] envio de e-mail falhou (HTTP {$status}): {$motivo}");

        return false;
    }

    /** Endereço público da aplicação, para montar os links dos e-mails. */
    public static function urlBase()
    {
        $url = rtrim((string) ferraz_env('APP_URL', ''), '/');
        if ($url !== '') {
            return $url;
        }

        $https = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || !empty($_SERVER['HTTPS']);

        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    /** Só em desenvolvimento: arquivo onde os e-mails são gravados em vez de enviados. */
    private static function arquivoDeTeste()
    {
        $arquivo = (string) ferraz_env('EMAIL_ARQUIVO_TESTE', '');

        return $arquivo !== '' && ferraz_ambiente_desenvolvimento() ? $arquivo : null;
    }
}
