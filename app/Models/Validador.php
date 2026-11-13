<?php

namespace App\Models;

/**
 * RN16 — validação de formato na camada de negócio do back-end: CPF, CNPJ,
 * e-mail, telefone e senha. O JavaScript dos formulários repete essas regras
 * só por usabilidade; quem decide é este código.
 */
class Validador
{
    /** Senha: a mesma regra informada nas telas de cadastro. */
    public const REGRA_SENHA = 'A senha deve ter no mínimo 6 caracteres, com pelo menos uma letra e um número.';

    public static function email($email)
    {
        return filter_var(trim((string) $email), FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function senha($senha)
    {
        $senha = (string) $senha;

        return strlen($senha) >= 6 && preg_match('/[A-Za-z]/', $senha) && preg_match('/\d/', $senha);
    }

    /** Telefone brasileiro com DDD: 10 ou 11 dígitos. */
    public static function telefone($telefone)
    {
        $digitos = strlen(self::digitos($telefone));

        return $digitos === 10 || $digitos === 11;
    }

    public static function cpf($cpf)
    {
        $cpf = self::digitos($cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao <= 10; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * ($posicao + 1 - $i);
            }
            if ((int) $cpf[$posicao] !== (($soma * 10) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    public static function cnpj($cnpj)
    {
        $cnpj = self::digitos($cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($posicao = 12; $posicao <= 13; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cnpj[$i] * $pesos[$i + 13 - $posicao];
            }
            $resto = $soma % 11;
            if ((int) $cnpj[$posicao] !== ($resto < 2 ? 0 : 11 - $resto)) {
                return false;
            }
        }

        return true;
    }

    /** 000.000.000-00 — formato único de gravação, para a UNIQUE do banco valer. */
    public static function formatarCpf($cpf)
    {
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', self::digitos($cpf));
    }

    /** 00.000.000/0000-00 — formato único de gravação. */
    public static function formatarCnpj($cnpj)
    {
        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', self::digitos($cnpj));
    }

    public static function digitos($valor)
    {
        return preg_replace('/\D/', '', (string) $valor);
    }
}
