<?php

namespace App\Models;

class Endereco
{
    public const CAMPOS = ['cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf'];

    public static function campos($entrada)
    {
        $campos = [];
        foreach (self::CAMPOS as $nome) {
            $valor = $entrada[$nome] ?? '';
            $campos[$nome] = is_string($valor) ? trim($valor) : '';
        }
        $campos['uf'] = strtoupper($campos['uf']);
        return $campos;
    }

    public static function validar($campos)
    {
        if (!array_filter($campos, 'strlen')) {
            return [];
        }

        $erros = [];
        if (!preg_match('/^\d{5}-?\d{3}$/D', $campos['cep'])) {
            $erros['cep'] = 'Informe um CEP com oito dígitos.';
        }
        foreach (['logradouro' => 'a rua', 'numero' => 'o número ou S/N', 'cidade' => 'a cidade'] as $campo => $descricao) {
            if ($campos[$campo] === '') {
                $erros[$campo] = "Informe {$descricao}.";
            }
        }
        if (!preg_match('/^[A-Z]{2}$/D', $campos['uf'])) {
            $erros['uf'] = 'Informe a sigla do estado.';
        }
        return $erros;
    }

    public static function paraGravar($campos)
    {
        // Um perfil sem alteração de endereço mantém inclusive o texto de cadastros antigos.
        if (!array_filter($campos, 'strlen')) {
            return [];
        }
        $cep = str_replace('-', '', $campos['cep']);
        $campos['cep'] = substr($cep, 0, 5) . '-' . substr($cep, 5);
        $partes = [$campos['logradouro'] . ', ' . $campos['numero'], $campos['bairro'],
            $campos['cidade'] . '/' . $campos['uf'], 'CEP ' . $campos['cep']];
        return [
            'endereco' => implode(' — ', array_filter($partes, 'strlen')),
            'endereco_campos' => json_encode($campos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }
}
