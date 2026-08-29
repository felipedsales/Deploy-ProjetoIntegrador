<?php

/**
 * Funções utilitárias de infraestrutura do Ferraz Conecta.
 * Carregado uma única vez pelo app/bootstrap.php.
 */

if (!function_exists('ferraz_env')) {
    /**
     * Lê uma variável de ambiente procurando, nesta ordem, em $_ENV, $_SERVER
     * e getenv(). Assim funciona tanto com o .env local (carregado pelo
     * phpdotenv) quanto com as variáveis reais do container no Railway, onde
     * $_ENV pode não estar populado dependendo do variables_order.
     *
     * Os literais "true", "false" e "null" são convertidos para os tipos do PHP.
     * Retorna $padrao quando a variável não existe.
     *
     * @param  string $chave
     * @param  mixed  $padrao
     * @return mixed
     */
    function ferraz_env($chave, $padrao = null)
    {
        $valor = $_ENV[$chave] ?? $_SERVER[$chave] ?? getenv($chave);

        if ($valor === false || $valor === null) {
            return $padrao;
        }

        switch (strtolower(trim((string) $valor))) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
        }

        return $valor;
    }
}

if (!function_exists('ferraz_ambiente_desenvolvimento')) {
    /**
     * Indica se a aplicação está em ambiente de desenvolvimento, para decidir
     * o nível de detalhe das mensagens de erro. Na dúvida (nada configurado),
     * assume PRODUÇÃO — o lado mais seguro.
     *
     * @return bool
     */
    function ferraz_ambiente_desenvolvimento()
    {
        $ambiente = strtolower((string) ferraz_env('APP_ENV', ''));

        if (in_array($ambiente, ['local', 'dev', 'development', 'testing'], true)) {
            return true;
        }

        return ferraz_env('APP_DEBUG', false) === true;
    }
}
