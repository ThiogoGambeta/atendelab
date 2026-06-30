<?php
$tituloPagina = 'Pessoas';
require __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Pessoas atendidas</h1>
        <p class="text-secondary mb-0">Cadastro de pessoas atendidas pelo sistema.</p>
    </div>

    <button class="btn btn-success" onclick="novaPessoa()">Nova pessoa</button>
</div>

<div id="alerta"></div>

<div class="card border-0 shadow-sm mb-4 d-none" id="cardFormulario">
    <div class="card-body">
        <h2 class="h5 mb-3" id="tituloFormulario">Nova pessoa</h2>

        <form id="formPessoa">
            <input type="hidden" name="id" id="pessoaId">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input class="form-control" name="nome" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Documento *</label>
                    <input class="form-control" name="documento" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input class="form-control" name="telefone">
                </div>

                <div class="col-md-6">
                    <label class="form-label">E-mail *</label>
                    <input class="form-control" type="email" name="email" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Curso</label>
                    <input class="form-control" name="curso">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <input class="form-control" name="periodo">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="3"></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-success" type="submit">Salvar</button>
                <button class="btn btn-outline-secondary" type="button" onclick="fecharFormulario()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Documento</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody id="tabelaPessoas">
                <tr>
                    <td colspan="6" class="text-center py-4">Carregando...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const formPessoa = document.getElementById('formPessoa');
const cardFormulario = document.getElementById('cardFormulario');

function novaPessoa() {
    formPessoa.reset();
    document.getElementById('pessoaId').value = '';
    document.getElementById('tituloFormulario').textContent = 'Nova pessoa';
    cardFormulario.classList.remove('d-none');
}

function fecharFormulario() {
    formPessoa.reset();
    cardFormulario.classList.add('d-none');
}

async function carregarPessoas() {
    try {
        const resposta = await AtendeLabApi.get('pessoas', 'listar');
        const pessoas = AtendeLabApi.toList(resposta);
        const tbody = document.getElementById('tabelaPessoas');

        if (!pessoas.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Nenhuma pessoa cadastrada.</td></tr>';
            return;
        }

        tbody.innerHTML = pessoas.map(pessoa => `
            <tr>
                <td>${AtendeLabApi.escape(pessoa.id)}</td>
                <td>${AtendeLabApi.escape(pessoa.nome)}</td>
                <td>${AtendeLabApi.escape(pessoa.documento)}</td>
                <td>${AtendeLabApi.escape(pessoa.email)}</td>
                <td>
                    <span class="badge ${pessoa.status === 'ativo' ? 'text-bg-success' : 'text-bg-secondary'}">
                        ${AtendeLabApi.escape(pessoa.status)}
                    </span>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarPessoa(${Number(pessoa.id)})">Editar</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="inativarPessoa(${Number(pessoa.id)})">Inativar</button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
}

async function editarPessoa(id) {
    try {
        const resposta = await AtendeLabApi.get('pessoas', 'buscar', { id });
        const pessoa = AtendeLabApi.toObject(resposta);

        document.getElementById('pessoaId').value = pessoa.id ?? '';
        formPessoa.nome.value = pessoa.nome ?? '';
        formPessoa.documento.value = pessoa.documento ?? '';
        formPessoa.telefone.value = pessoa.telefone ?? '';
        formPessoa.email.value = pessoa.email ?? '';
        formPessoa.curso.value = pessoa.curso ?? '';
        formPessoa.periodo.value = pessoa.periodo ?? '';
        formPessoa.status.value = pessoa.status ?? 'ativo';
        formPessoa.observacoes.value = pessoa.observacoes ?? '';

        document.getElementById('tituloFormulario').textContent = 'Editar pessoa';
        cardFormulario.classList.remove('d-none');
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
}

async function inativarPessoa(id) {
    if (!confirm('Deseja inativar esta pessoa?')) return;

    try {
        await AtendeLabApi.post('pessoas', 'inativar', { id });
        AtendeLabApi.showAlert('alerta', 'Pessoa inativada com sucesso.');
        await carregarPessoas();
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
}

formPessoa.addEventListener('submit', async event => {
    event.preventDefault();

    try {
        const id = document.getElementById('pessoaId').value;
        const action = id ? 'atualizar' : 'criar';

        await AtendeLabApi.post('pessoas', action, new FormData(formPessoa));

        AtendeLabApi.showAlert('alerta', 'Pessoa salva com sucesso.');
        fecharFormulario();
        await carregarPessoas();
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
});

document.addEventListener('DOMContentLoaded', carregarPessoas);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>