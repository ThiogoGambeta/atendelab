<?php

class TiposAtendimentosController
{
    private PDO $pdo;

    public function __construct()
    {
        require __DIR__ . '/../../config/database.php';
        $this->pdo = $pdo;
    }

    private function responder(array $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function listar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $sql = 'SELECT id, nome, descricao, status, criado_em, atualizado_em
                FROM tipos_atendimentos
                ORDER BY id DESC';

        $stmt = $this->pdo->query($sql);
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($tipos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function buscar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $this->responder(['erro' => 'ID inválido.'], 400);
            return;
        }

        $sql = 'SELECT id, nome, descricao, status, criado_em, atualizado_em
                FROM tipos_atendimentos
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tipo) {
            $this->responder(['erro' => 'Tipo de atendimento não encontrado.'], 404);
            return;
        }

        $this->responder($tipo);
    }

    public function criar(): void
    {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if ($nome === '') {
            $this->responder(['erro' => 'Nome é obrigatório.'], 400);
            return;
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $this->responder(['erro' => 'Status inválido.'], 400);
            return;
        }

        try {
            $sql = 'INSERT INTO tipos_atendimentos (nome, descricao, status)
                    VALUES (:nome, :descricao, :status)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':descricao', $descricao !== '' ? $descricao : null);
            $stmt->bindValue(':status', $status);
            $stmt->execute();

            $this->responder([
                'mensagem' => 'Tipo de atendimento cadastrado com sucesso.',
                'id' => $this->pdo->lastInsertId()
            ], 201);
        } catch (PDOException $e) {
            $this->responder(['erro' => 'Erro ao cadastrar tipo de atendimento.'], 500);
        }
    }

    public function atualizar(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? 'ativo';

        if (!$id || $nome === '') {
            $this->responder(['erro' => 'ID e nome são obrigatórios.'], 400);
            return;
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $this->responder(['erro' => 'Status inválido.'], 400);
            return;
        }

        try {
            $sql = 'UPDATE tipos_atendimentos
                    SET nome = :nome,
                        descricao = :descricao,
                        status = :status
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':descricao', $descricao !== '' ? $descricao : null);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->responder(['mensagem' => 'Tipo de atendimento atualizado com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(['erro' => 'Erro ao atualizar tipo de atendimento.'], 500);
        }
    }

    public function inativar(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $this->responder(['erro' => 'ID inválido.'], 400);
            return;
        }

        $sql = 'UPDATE tipos_atendimentos SET status = :status WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', 'inativo');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $this->responder(['mensagem' => 'Tipo de atendimento inativado com sucesso.']);
    }
}
