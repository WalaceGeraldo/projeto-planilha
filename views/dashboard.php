<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Editor de Planilhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 dashboard-header">
        <h1>Painel de Controle</h1>
        <div class="dashboard-actions">
            <?php if (($_SESSION['role'] ?? 'viewer') === 'admin'): ?>
                <button onclick="exportarSelecionados()" class="btn btn-outline-success me-2">Exportar</button>
                <button onclick="abrirHistoricoGlobal()" class="btn btn-info me-2 text-white">Histórico</button>
                <button onclick="abrirGestaoUsuarios()" class="btn btn-warning me-2">Usuários</button>
            <?php endif; ?>
            <button onclick="logout()" class="btn btn-danger">Sair</button>
        </div>
    </div>

    <div class="modal fade" id="usersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gerenciar Usuários</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 border p-3 rounded bg-light">
                        <h6>Novo / Editar Usuário</h6>
                        <input type="hidden" id="userId">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" id="userName" class="form-control" placeholder="Usuário">
                            </div>
                            <div class="col-md-4">
                                <input type="password" id="userPass" class="form-control" placeholder="Senha (vazio mantém)">
                            </div>
                            <div class="col-md-2">
                                <select id="userRole" class="form-select">
                                    <option value="viewer">Visualizador</option>
                                    <option value="editor">Editor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button onclick="salvarUsuario()" class="btn btn-success w-100">Salvar</button>
                            </div>
                        </div>
                        <small class="text-muted edit-mode-msg" id="editModeMsg">Editando usuário... <a href="#" onclick="limparFormUser()">Cancelar</a></small>
                    </div>

                    <table class="table table-striped">
                        <thead><tr><th>Usuário</th><th>Função</th><th>Ações</th></tr></thead>
                        <tbody id="listaUsuarios"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (($_SESSION['role'] ?? 'viewer') !== 'viewer'): ?>
    <div class="card p-4 shadow-sm mb-4">
        <h5>Nova Planilha</h5>
        <div class="input-group mb-3">
            <input type="text" id="novoNome" class="form-control" placeholder="Nome da planilha...">
            <button onclick="criarPlanilha()" class="btn btn-success">Criar em Branco</button>
        </div>
        
        <label class="form-label">Ou importar arquivo local (.xlsx):</label>
        <div class="input-group">
            <input type="file" id="arquivoImportar" class="form-control" accept=".xlsx">
            <button onclick="importarPlanilha()" class="btn btn-outline-primary">Importar</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="mb-3">
        <input type="text" id="pesquisaPlanilha" class="form-control search-input" placeholder="Pesquisar planilha..." onkeyup="filtrarPlanilhas()">
    </div>

    <div class="list-group shadow-sm" id="listaPlanilhas">
        <?php if (empty($spreadsheets)): ?>
            <div class="list-group-item text-center text-muted p-5">Nenhuma planilha encontrada.</div>
        <?php else: ?>
            <?php foreach ($spreadsheets as $s): ?>
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center sheet-item">
                    <?php if (($_SESSION['role'] ?? 'viewer') === 'admin'): ?>
                        <input type="checkbox" class="form-check-input me-3 sheet-select" value="<?php echo $s['id']; ?>">
                    <?php endif; ?>
                    
                    <a href="editor.php?id=<?php echo $s['id']; ?>" class="d-block text-decoration-none text-dark flex-grow-1">
                        <h5 class="mb-1"><?php echo htmlspecialchars($s['name']); ?></h5>
                        <small class="text-muted">
                            Por: <strong><?php echo htmlspecialchars(get_username_by_id($s['owner_id'])); ?></strong> | 
                            Criado em: <?php echo $s['created_at']; ?>
                        </small>
                    </a>
                    
                    <?php if (($_SESSION['role'] ?? 'viewer') !== 'viewer'): ?>
                    <div class="btn-group">
                        <button onclick="renomear('<?php echo $s['id']; ?>', '<?php echo htmlspecialchars($s['name']); ?>')" class="btn btn-sm btn-outline-secondary">Renomear</button>
                        
                        <?php if (($_SESSION['role'] ?? 'viewer') === 'admin'): ?>
                        <button onclick="excluir('<?php echo $s['id']; ?>')" class="btn btn-sm btn-outline-danger">Excluir</button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <a href="editor.php?id=<?php echo $s['id']; ?>" class="btn btn-primary btn-sm rounded-pill">Abrir</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="modalImportSelection" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecionar Abas para Importar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Selecione quais abas da planilha deseja importar:</p>
                    <form id="formImportSelection">
                        <input type="hidden" id="importTempFile">
                        <input type="hidden" id="importOriginalName">
                        <div id="importSheetsList" class="list-group">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarImportacao()">Importar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGlobalHistory" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Histórico de Atividades</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul id="globalHistoryList" class="list-group">
                        <li class="list-group-item text-center">Carregando...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const swalConfig = {
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Sim',
            cancelButtonText: 'Cancelar'
        };

        async function renomear(id, nomeAtual) {
            const { value: novoNome } = await Swal.fire({
                title: 'Renomear Planilha',
                input: 'text',
                inputValue: nomeAtual,
                showCancelButton: true,
                inputValidator: (value) => {
                    if (!value) return 'Você precisa digitar um nome!'
                },
                ...swalConfig
            });

            if (novoNome && novoNome !== nomeAtual) {
                try {
                    const res = await fetch('process.php?action=rename', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, name: novoNome })
                    });
                    const json = await res.json();
                    if (res.ok) {
                        await Swal.fire('Sucesso!', 'Planilha renomeada.', 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('Erro', json.error || 'Falha ao renomear', 'error');
                    }
                } catch (e) { Swal.fire('Erro', 'Erro de conexão', 'error'); }
            }
        }

        async function excluir(id) {
            const result = await Swal.fire({
                title: 'Tem certeza?',
                text: "Isso apagará a planilha para TODOS os usuários e não pode ser desfeito.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            });

            if (result.isConfirmed) {
                try {
                    const res = await fetch('process.php?action=delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const json = await res.json();
                    if (res.ok) {
                        await Swal.fire('Excluído!', 'A planilha foi removida.', 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('Erro', json.error || 'Falha ao excluir', 'error');
                    }
                } catch (e) { Swal.fire('Erro', 'Erro de conexão', 'error'); }
            }
        }

        async function importarPlanilha() {
            const fileInput = document.getElementById('arquivoImportar');
            const file = fileInput.files[0];
            
            if (!file) return Swal.fire('Atenção', 'Por favor, selecione um arquivo primeiro.', 'warning');
            
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const res = await fetch('process.php?action=preview_import', {
                    method: 'POST',
                    body: formData
                });
                    
                if (!res.ok) throw new Error(await res.text());
                
                const data = await res.json();
                if (!data.success) throw new Error(data.error);
                
                const modalEl = document.getElementById('modalImportSelection');
                const modal = new bootstrap.Modal(modalEl);
                
                document.getElementById('importTempFile').value = data.temp_file;
                document.getElementById('importOriginalName').value = data.original_name;
                
                const list = document.getElementById('importSheetsList');
                list.innerHTML = '';
                
                data.sheets.forEach(sheet => {
                    const div = document.createElement('div');
                    div.className = 'list-group-item';
                    div.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input sheet-check" type="checkbox" value="${sheet}" id="sheet_${sheet}" checked>
                            <label class="form-check-label w-100" for="sheet_${sheet}">${sheet}</label>
                        </div>
                    `;
                    list.appendChild(div);
                });
                
                modal.show();
                fileInput.value = ''; 
                
            } catch (err) {
                Swal.fire('Erro', 'Erro ao carregar planilha: ' + err.message, 'error');
            }
        }
        
        async function confirmarImportacao() {
            const tempFile = document.getElementById('importTempFile').value;
            const originalName = document.getElementById('importOriginalName').value;
            
            const checked = document.querySelectorAll('.sheet-check:checked');
            const selectedSheets = Array.from(checked).map(c => c.value);
            
            if (selectedSheets.length === 0) return Swal.fire('Atenção', "Selecione pelo menos uma aba.", 'warning');
            
            try {
                const res = await fetch('process.php?action=confirm_import', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        temp_file: tempFile,
                        original_name: originalName,
                        sheets: selectedSheets
                    })
                });
                
                if (!res.ok) throw new Error(await res.text());
                const data = await res.json();
                
                if (data.success) {
                    await Swal.fire('Sucesso!', 'Planilha importada.', 'success');
                    location.reload();
                } else {
                    throw new Error(data.error || "Erro ao importar.");
                }
            } catch (err) {
                 Swal.fire('Erro', 'Erro ao confirmar importação: ' + err.message, 'error');
            }
        }

        async function criarPlanilha() {
            const nome = document.getElementById('novoNome').value;
            if (!nome) return Swal.fire('Atenção', 'Digite um nome para a planilha.', 'warning');

            try {
                const res = await fetch('process.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: nome })
                });
                const json = await res.json();
                
                if (res.ok) {
                    window.location.href = `editor.php?id=${json.id}`;
                } else {
                    Swal.fire('Erro', json.error || 'Erro desconhecido', 'error');
                }
            } catch (e) {
                Swal.fire('Erro', 'Erro de conexão', 'error');
            }
        }

        const usersModal = new bootstrap.Modal(document.getElementById('usersModal'));
        
        async function abrirGestaoUsuarios() {
            usersModal.show();
            carregarUsuarios();
            limparFormUser();
        }

        async function carregarUsuarios() {
            const tbody = document.getElementById('listaUsuarios');
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">Carregando...</td></tr>';
            
            try {
                const res = await fetch('auth.php?action=list_users');
                const data = await res.json();
                
                if (data.users) {
                    tbody.innerHTML = data.users.map(u => `
                        <tr>
                            <td>${u.username}</td>
                            <td><span class="badge bg-secondary">${u.role}</span></td>
                            <td>
                                <button onclick="editarUsuario('${u.id}', '${u.username}', '${u.role}')" class="btn btn-sm btn-info text-white me-1">Editar</button>
                                <button onclick="excluirUsuario('${u.id}')" class="btn btn-sm btn-danger">Excluir</button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    Swal.fire('Erro', "Erro ao carregar usuários: " + (data.error || ""), 'error');
                }
            } catch (e) { console.error(e); }
        }

        function editarUsuario(id, nome, role) {
            document.getElementById('userId').value = id;
            document.getElementById('userName').value = nome;
            document.getElementById('userRole').value = role;
            document.getElementById('userPass').value = '';
            document.getElementById('editModeMsg').style.display = 'block';
        }

        function limparFormUser() {
            document.getElementById('userId').value = '';
            document.getElementById('userName').value = '';
            document.getElementById('userRole').value = 'editor';
            document.getElementById('userPass').value = '';
            document.getElementById('editModeMsg').style.display = 'none';
        }

        async function salvarUsuario() {
            const id = document.getElementById('userId').value;
            const user = document.getElementById('userName').value;
            const pass = document.getElementById('userPass').value;
            const role = document.getElementById('userRole').value;
            
            if (!user) return Swal.fire('Atenção', "Preencha o usuário.", 'warning');
            if (!id && !pass) return Swal.fire('Atenção', "Senha é obrigatória para novos usuários.", 'warning');

            const action = id ? 'update_user' : 'create_user';
            
            try {
                const res = await fetch(`auth.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, username: user, password: pass, role })
                });
                const json = await res.json();
                
                if (json.success) {
                    Swal.fire('Sucesso!', "Usuário salvo com sucesso!", 'success');
                    limparFormUser();
                    carregarUsuarios();
                } else {
                    Swal.fire('Erro', json.error, 'error');
                }
            } catch (e) { Swal.fire('Erro', 'Erro de conexão', 'error'); }
        }

        async function excluirUsuario(id) {
            const result = await Swal.fire({
                title: 'Excluir Usuário?',
                text: "Essa ação não pode ser desfeita.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            });

            if (!result.isConfirmed) return;
            
            try {
                const res = await fetch(`auth.php?action=delete_user`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const json = await res.json();
                
                if (json.success) {
                    Swal.fire('Deletado!', 'Usuário removido.', 'success');
                    carregarUsuarios();
                }
                else Swal.fire('Erro', json.error, 'error');
            } catch (e) { Swal.fire('Erro', 'Erro de conexão', 'error'); }
        }

        async function exportarSelecionados() {
            const checked = document.querySelectorAll('.sheet-select:checked');
            if (checked.length === 0) return Swal.fire('Atenção', "Selecione pelo menos uma planilha.", 'info');
            
            const ids = Array.from(checked).map(el => el.value);
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process.php?action=export_bulk';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids';
            input.value = JSON.stringify(ids);
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        async function logout() {
            await fetch('auth.php?action=logout');
            window.location.href = 'login.php';
        }
        
        async function abrirHistoricoGlobal() {
            const modalEl = document.getElementById('modalGlobalHistory');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            
            const list = document.getElementById('globalHistoryList');
            list.innerHTML = '<li class="list-group-item text-center">Carregando...</li>';
            
            try {
                const res = await fetch('process.php?action=history');
                const data = await res.json();
                
                if (data.history && data.history.length > 0) {
                    list.innerHTML = '';
                    data.history.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';
                        
                        li.innerHTML = `
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>${item.user}</strong>: ${item.action} 
                                    ${item.spreadsheet ? `<span class="badge bg-secondary ms-1">${item.spreadsheet}</span>` : ''}
                                </div>
                                <small class="text-muted">${item.timestamp}</small>
                            </div>
                        `;
                        list.appendChild(li);
                    });
                } else {
                    list.innerHTML = '<li class="list-group-item text-center text-muted">Nenhuma atividade recente.</li>';
                }
            } catch (err) {
                list.innerHTML = '<li class="list-group-item text-danger">Erro ao carregar histórico.</li>';
            }
        }
        
        function filtrarPlanilhas() {
            const termo = document.getElementById('pesquisaPlanilha').value.toLowerCase();
            const itens = document.querySelectorAll('.sheet-item');
            
            itens.forEach(item => {
                const texto = item.innerText.toLowerCase();
                if (texto.includes(termo)) {
                    item.classList.remove('d-none');
                    item.classList.add('d-flex');
                } else {
                    item.classList.remove('d-flex');
                    item.classList.add('d-none');
                }
            });
        }
    </script>
</body>
</html>
