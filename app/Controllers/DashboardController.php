<?php

require_once __DIR__ . '/../../config/database.php';

class DashboardController
{
    public function resumo()
    {
        global $pdo;

        header('Content-Type: application/json; charset=utf-8');

        $totalPessoas = (int) $pdo
            ->query("SELECT COUNT(*) FROM pessoas")
            ->fetchColumn();

        $totalTipos = (int) $pdo
            ->query("SELECT COUNT(*) FROM tipos_atendimentos")
            ->fetchColumn();

        $totalAtendimentos = (int) $pdo
            ->query("SELECT COUNT(*) FROM atendimentos")
            ->fetchColumn();

        echo json_encode([
            'indicadores' => [
                'total_pessoas' => $totalPessoas,
                'total_tipos' => $totalTipos,
                'total_atendimentos' => $totalAtendimentos
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
}