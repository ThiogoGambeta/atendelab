<?php

class AtendimentosController
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

        $sql = 'SELECT
                    a.id,
                    CONCAT("ATD-", LPAD(a.id, 4, "0")) AS protocolo,
                    a.pessoa_id,
                    p.nome AS pessoa_nome,
                    a.tipo_atendimento_id,
                    t.nome AS tipo_nome,
                    a.usuario_id,
                    u.nome AS usuario_nome,
                    a.descricao,
                    a.status,
                    a.data_atendimento,
                    a.horario_atendimento,
                    a.observacao_final,
                    a.criado_em,
                    a.atualizado_em
                FROM atendimentos a
                INNER JOIN pessoas p ON p.id = a.pessoa_id
                INNER JOIN tipos_atendimentos t ON t.id = a.tipo_atendimento_id
                INNER JOIN usuarios u ON u.id = a.usuario_id
                ORDER BY a.id DESC';

        $stmt = $this->pdo->query($sql);
        $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($atendimentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function buscar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $this->responder(['erro' => 'ID inválido.'], 400);
            return;
        }

        $sql = 'SELECT
                    a.id,
                    CONCAT("ATD-", LPAD(a.id, 4, "0")) AS protocolo,
                    a.pessoa_id,
                    p.nome AS pessoa_nome,
                    a.tipo_atendimento_id,
                    t.nome AS tipo_nome,
                    a.usuario_id,
                    u.nome AS usuario_nome,
                    a.descricao,
                    a.status,
                    a.data_atendimento,
                    a.horario_atendimento,
                    a.observacao_final,
                    a.criado_em,
                    a.atualizado_em
                FROM atendimentos a
                INNER JOIN pessoas p ON p.id = a.pessoa_id
                INNER JOIN tipos_atendimentos t ON t.id = a.tipo_atendimento_id
                INNER JOIN usuarios u ON u.id = a.usuario_id
                WHERE a.id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$atendimento) {
            $this->responder(['erro' => 'Atendimento não encontrado.'], 404);
            return;
        }

        $this->responder($atendimento);
    }

    public function criar(): void
    {
        $pessoaId = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
        $tipoAtendimentoId = filter_input(INPUT_POST, 'tipo_atendimento_id', FILTER_VALIDATE_INT);
        $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
        $descricao = trim($_POST['descricao'] ?? '');
        $dataAtendimento = trim($_POST['data_atendimento'] ?? '');
        $horarioAtendimento = trim($_POST['horario_atendimento'] ?? '');
        $status = $_POST['status'] ?? 'aberto';

        if (!$pessoaId || !$tipoAtendimentoId || !$usuarioId || $descricao === '' || $dataAtendimento === '' || $horarioAtendimento === '') {
            $this->responder(['erro' => 'Pessoa, tipo, usuário, descrição, data e horário são obrigatórios.'], 400);
            return;
        }

        if (!in_array($status, ['aberto', 'em_andamento', 'concluido'], true)) {
            $this->responder(['erro' => 'Status inválido.'], 400);
            return;
        }

        if ($status !== 'aberto') {
            $this->responder(['erro' => 'Novo atendimento deve iniciar como aberto.'], 400);
            return;
        }

        if (!$this->existeRegistroAtivo('pessoas', $pessoaId)) {
            $this->responder(['erro' => 'Pessoa inexistente ou inativa.'], 400);
            return;
        }

        if (!$this->existeRegistroAtivo('tipos_atendimentos', $tipoAtendimentoId)) {
            $this->responder(['erro' => 'Tipo de atendimento inexistente ou inativo.'], 400);
            return;
        }

        if (!$this->existeRegistroAtivo('usuarios', $usuarioId)) {
            $this->responder(['erro' => 'Usuário inexistente ou inativo.'], 400);
            return;
        }

        try {
            $sql = 'INSERT INTO atendimentos
                    (pessoa_id, tipo_atendimento_id, usuario_id, descricao, data_atendimento, horario_atendimento, status)
                    VALUES
                    (:pessoa_id, :tipo_atendimento_id, :usuario_id, :descricao, :data_atendimento, :horario_atendimento, :status)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_atendimento_id', $tipoAtendimentoId, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':data_atendimento', $dataAtendimento);
            $stmt->bindValue(':horario_atendimento', $horarioAtendimento);
            $stmt->bindValue(':status', $status);
            $stmt->execute();

            $id = $this->pdo->lastInsertId();

            $this->responder([
                'mensagem' => 'Atendimento cadastrado com sucesso.',
                'id' => $id,
                'protocolo' => 'ATD-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            ], 201);
        } catch (PDOException $e) {
            $this->responder(['erro' => 'Erro ao cadastrar atendimento.'], 500);
        }
    }

    public function alterarStatus(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $observacaoFinal = trim($_POST['observacao_final'] ?? '');

        if (!$id) {
            $this->responder(['erro' => 'ID inválido.'], 400);
            return;
        }

        if (!in_array($status, ['aberto', 'em_andamento', 'concluido'], true)) {
            $this->responder(['erro' => 'Status inválido.'], 400);
            return;
        }

        if ($status === 'concluido' && $observacaoFinal === '') {
            $this->responder(['erro' => 'Observação final é obrigatória para concluir o atendimento.'], 400);
            return;
        }

        $sqlBusca = 'SELECT id FROM atendimentos WHERE id = :id';
        $stmtBusca = $this->pdo->prepare($sqlBusca);
        $stmtBusca->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtBusca->execute();

        if (!$stmtBusca->fetch(PDO::FETCH_ASSOC)) {
            $this->responder(['erro' => 'Atendimento não encontrado.'], 404);
            return;
        }

        try {
            $sql = 'UPDATE atendimentos
                    SET status = :status,
                        observacao_final = :observacao_final
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':observacao_final', $observacaoFinal !== '' ? $observacaoFinal : null);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->responder(['mensagem' => 'Status do atendimento atualizado com sucesso.']);
        } catch (PDOException $e) {
            $this->responder(['erro' => 'Erro ao atualizar status do atendimento.'], 500);
        }
    }

    private function existeRegistroAtivo(string $tabela, int $id): bool
    {
        $tabelasPermitidas = ['pessoas', 'tipos_atendimentos', 'usuarios'];

        if (!in_array($tabela, $tabelasPermitidas, true)) {
            return false;
        }

        $sql = "SELECT id FROM {$tabela} WHERE id = :id AND status = 'ativo' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
