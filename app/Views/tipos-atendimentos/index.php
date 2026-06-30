<?php
$tituloPagina = 'Tipos de Atendimento';
require __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Tipos de atendimento</h1>
        <p class="text-secondary mb-0">Categorias usadas para organizar os atendimentos.</p>
    </div>

    <button class="btn btn-success" onclick="novoTipo()">Novo tipo</button>
</div>

<div id="alerta"></div>

<div class="card border-0 shadow-sm mb-4 d-none" id="cardFormulario">
    <div class="card-body">
        <h2 class="h5 mb-3" id="tituloFormulario">Novo tipo</h2>

        <form id="formTipo">
            <input type="hidden" name="id" id="tipoId">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nome *</label>
                    <input class="form-control" name="nome" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="descricao" rows="3"></textarea>
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
                    <th>Descrição</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody id="tabelaTipos">
                <tr>
                    <td colspan="5" class="text-center py-4">Carregando...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const formTipo = document.getElementById('formTipo');
const cardFormulario = document.getElementById('cardFormulario');

function novoTipo() {
    formTipo.reset();
    document.getElementById('tipoId').value = '';
    document.getElementById('tituloFormulario').textContent = 'Novo tipo';
    cardFormulario.classList.remove('d-none');
}

function fecharFormulario() {
    formTipo.reset();
    cardFormulario.classList.add('d-none');
}

async function carregarTipos() {
    try {
        const resposta = await AtendeLabApi.get('tipos', 'listar');
        const tipos = AtendeLabApi.toList(resposta);
        const tbody = document.getElementById('tabelaTipos');

        if (!tipos.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Nenhum tipo cadastrado.</td></tr>';
            return;
        }

        tbody.innerHTML = tipos.map(tipo => `
            <tr>
                <td>${AtendeLabApi.escape(tipo.id)}</td>
                <td>${AtendeLabApi.escape(tipo.nome)}</td>
                <td>${AtendeLabApi.escape(tipo.descricao)}</td>
                <td>
                    <span class="badge ${tipo.status === 'ativo' ? 'text-bg-success' : 'text-bg-secondary'}">
                        ${AtendeLabApi.escape(tipo.status)}
                    </span>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarTipo(${Number(tipo.id)})">Editar</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="inativarTipo(${Number(tipo.id)})">Inativar</button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
}

async function editarTipo(id) {
    try {
        const resposta = await AtendeLabApi.get('tipos', 'buscar', { id });
        const tipo = AtendeLabApi.toObject(resposta);

        document.getElementById('tipoId').value = tipo.id ?? '';
        formTipo.nome.value = tipo.nome ?? '';
        formTipo.descricao.value = tipo.descricao ?? '';
        formTipo.status.value = tipo.status ?? 'ativo';

        document.getElementById('tituloFormulario').textContent = 'Editar tipo';
        cardFormulario.classList.remove('d-none');
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
}

async function inativarTipo(id) {
    if (!confirm('Deseja inativar este tipo de atendimento?')) return;

    try {
        await AtendeLabApi.post('tipos', 'inativar', { id });
        AtendeLabApi.showAlert('alerta', 'Tipo inativado com sucesso.');
        await carregarTipos();
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
}

formTipo.addEventListener('submit', async event => {
    event.preventDefault();

    try {
        const id = document.getElementById('tipoId').value;
        const action = id ? 'atualizar' : 'criar';

        await AtendeLabApi.post('tipos', action, new FormData(formTipo));

        AtendeLabApi.showAlert('alerta', 'Tipo salvo com sucesso.');
        fecharFormulario();
        await carregarTipos();
    } catch (error) {
        AtendeLabApi.showAlert('alerta', error.message, 'danger');
    }
});

document.addEventListener('DOMContentLoaded', carregarTipos);
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>