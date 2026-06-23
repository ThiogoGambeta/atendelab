<?php

class PessoasController
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

        $sql = 'SELECT id, nome, documento, telefone, email, curso, periodo, status, observacoes, criado_em, atualizado_em
                FROM pessoas
                ORDER BY id DESC';

        $stmt = $this->pdo->query($sql);
        $pessoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($pessoas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function buscar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $this->responder(['erro' => 'ID inválido.'], 400);
            return;
        }

        $sql = 'SELECT id, nome, documento, telefone, email, curso, periodo, status, observacoes, criado_em, atualizado_em
                FROM pessoas
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pessoa) {
            $this->responder(['erro' => 'Pessoa não encontrada.'], 404);
            return;
        }

        $this->responder($pessoa);
    }

    public function criar(): void
    {
        $nome = trim($_POST['nome'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $curso = trim($_POST['curso'] ?? '');
        $periodo = trim($_POST['periodo'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($nome === '' || $documento === '' || $email === '') {
            $this->responder(['erro' => 'Nome, documento e e-mail são obrigatórios.'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(['erro' => 'E-mail inválido.'], 400);
            return;
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $this->responder(['erro' => 'Status inválido.'], 400);
            return;
        }

        try {
            $sql = 'INSERT INTO pessoas (nome, documento, telefone, email, curso, periodo, status, observacoes)
                    VALUES (:nome, :documento, :telefone, :email, :curso, :periodo, :status, :observacoes)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':documento', $documento);
            $stmt->bindValue(':telefone', $telefone);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':curso', $curso);
            $stmt->bindValue(':periodo', $periodo);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null);
            $stmt->execute();

            $this->responder([
                'mensagem' => 'Pessoa cadastrada com sucesso.',
                'id' => $this->pdo->lastInsertId()
            ], 201);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(['erro' => 'Documento já cadastrado.'], 409);
                return;
            }

            $this->responder(['erro' => 'Erro ao cadastrar pessoa.'], 500);
        }
    }

    public function atualizar(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $curso = trim($_POST['curso'] ?? '');
        $periodo = trim($_POST['periodo'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        $observacoes = trim($_POST['observacoes'] ?? '');

        if (!$id || $nome === '' || $documento === '' || $email === '') {
            $this->responder(['erro' => 'ID, nome, documento e e-mail são obrigatórios.'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(['erro' => 'E-mail inválido.'], 400);
            return;
        }

        if (!in_array($status, ['ativo', 'inativo'], true)) {
            $this->responder(['erro' => 'Status inválido.'], 400);
            return;
        }

        try {
            $sql = 'UPDATE pessoas
                    SET nome = :nome,
                        documento = :documento,
                        telefone = :telefone,
                        email = :email,
                        curso = :curso,
                        periodo = :periodo,
                        status = :status,
                        observacoes = :observacoes
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome);
            $stmt->bindValue(':documento', $documento);
            $stmt->bindValue(':telefone', $telefone);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':curso', $curso);
            $stmt->bindValue(':periodo', $periodo);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->responder(['mensagem' => 'Pessoa atualizada com sucesso.']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(['erro' => 'Documento já cadastrado para outra pessoa.'], 409);
                return;
            }

            $this->responder(['erro' => 'Erro ao atualizar pessoa.'], 500);
        }
    }

    public function inativar(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $this->responder(['erro' => 'ID inválido.'], 400);
            return;
        }

        $sql = 'UPDATE pessoas SET status = :status WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', 'inativo');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $this->responder(['mensagem' => 'Pessoa inativada com sucesso.']);
    }
}
