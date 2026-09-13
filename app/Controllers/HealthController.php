<?php

namespace App\Controllers;

use App\Models\Database;
use Throwable;

/**
 * Endpoint de operação usado pelo healthcheck do Railway (ver railway.json).
 * Não corresponde a nenhum RF/RN do catálogo em docs/REQUISITOS.md: é
 * infraestrutura de deploy, não regra de negócio da aplicação.
 */
class HealthController extends Controller
{
    public function index()
    {
        header('Content-Type: application/json');

        $banco = 'conectado';

        try {
            Database::getInstance()->getConnection()->query('SELECT 1');
        } catch (Throwable $e) {
            $banco = 'indisponivel';
            error_log('[Ferraz Conecta] Healthcheck: falha ao conectar no banco: ' . $e->getMessage());
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => 'ok',
                'banco' => $banco,
            ],
            'message' => 'Serviço operando normalmente.',
        ]);

        return null;
    }
}
