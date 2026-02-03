<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="data:,"> <title><?php echo htmlspecialchars($meta['name']); ?> - Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script>
        const USER_ROLE = "<?php echo $_SESSION['role'] ?? 'viewer'; ?>";
        const SPREADSHEET_ID = "<?php echo $id; ?>";
    </script>
</head>
<body class="bg-light container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><?php echo htmlspecialchars($meta['name']); ?></h3>
        <div>
            <?php if (($_SESSION['role'] ?? 'viewer') === 'admin'): ?>
                <button onclick="verHistorico()" class="btn btn-warning btn-sm me-2"> Histórico</button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary btn-sm me-2">Voltar</a>
            <span class="badge bg-info text-dark"><?php echo $_SESSION['role']; ?></span>
        </div>
    </div>

    <!-- Modal Histórico -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Histórico de Alterações</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul id="listaHistorico" class="list-group list-group-flush">
                        <li class="list-group-item text-center">Carregando...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Removido, agora carrega via ID -->
    <div id="loading" class="mt-2 text-center">Carregando dados...</div>

    <div class="my-3 d-flex gap-2 align-items-center">
        <!-- Barra de Ferramentas -->
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary fw-bold" onclick="aplicarEstilo('bold')" title="Negrito">B</button>
            <button type="button" class="btn btn-outline-secondary fst-italic" onclick="aplicarEstilo('italic')" title="Itálico">I</button>
            <button type="button" class="btn btn-outline-secondary text-decoration-underline" onclick="aplicarEstilo('underline')" title="Sublinhado">U</button>
            <button type="button" class="btn btn-outline-secondary" onclick="mesclarCelulas()" title="Mesclar Células">🔗</button>
        </div>
        
        <div class="vr mx-2"></div>

        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary" onclick="desfazer()" title="Desfazer">↩️</button>
            <button type="button" class="btn btn-outline-secondary" onclick="refazer()" title="Refazer">↪️</button>
        </div>
        
        <div class="d-flex align-items-center border rounded px-2">
            <span class="me-2 text-muted small">Cor:</span>
            <input type="color" id="corTexto" class="form-control form-control-color border-0 p-0" value="#000000" onchange="aplicarEstilo('color', this.value)" title="Cor do Texto">
        </div>

        <div class="d-flex align-items-center border rounded px-2">
            <span class="me-2 text-muted small">Fundo:</span>
            <input type="color" id="corFundo" class="form-control form-control-color border-0 p-0" value="#ffffff" onchange="aplicarEstilo('background', this.value)" title="Cor de Fundo">
        </div>

        <div class="vr mx-2"></div>

        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary" onclick="alterarZoom(-0.1)" title="Diminuir Zoom">➖</button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetarZoom()" title="Resetar Zoom">🔍</button>
            <button type="button" class="btn btn-outline-secondary" onclick="alterarZoom(0.1)" title="Aumentar Zoom">➕</button>
        </div>

        <div class="vr mx-2"></div>

        <input type="text" id="termoPesquisa" class="form-control search-input-small" placeholder="Pesquisar nesta aba..." onkeyup="pesquisar()">
    </div>

    <ul class="nav nav-tabs" id="abasPlanilha" role="tablist"></ul>
    
    <div id="areaEdicao" class="table-container p-2">
        <!-- Tabela será renderizada aqui -->
    </div>

    <div id="acoes" class="actions-container">
        <button onclick="salvar()" class="btn btn-primary btn-lg me-2">Salvar Alterações</button>
    </div>

    <script src="js/script.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            carregarPorId(SPREADSHEET_ID);
        });

        async function verHistorico() {
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();
            
            const lista = document.getElementById('listaHistorico');
            lista.innerHTML = '<li class="list-group-item text-center">Carregando...</li>';

            try {
                const res = await fetch(`process.php?action=history&id=${SPREADSHEET_ID}`);
                const data = await res.json();
                
                if (data.history && data.history.length > 0) {
                    lista.innerHTML = data.history.map(h => `
                        <li class="list-group-item">
                            <small class="text-muted">${h.timestamp}</small><br>
                            <strong>${h.user}</strong>: ${h.action}
                        </li>
                    `).join('');
                } else {
                    lista.innerHTML = '<li class="list-group-item text-center text-muted">Nenhum registro encontrado.</li>';
                }
            } catch (e) {
                lista.innerHTML = '<li class="list-group-item text-danger">Erro ao carregar histórico.</li>';
            }
        }
    </script>
    
    <!-- Modal Salvar -->
    <div class="modal fade" id="modalSalvar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Salvar Alterações</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Descreva o que foi alterado para o histórico:</p>
                    <textarea id="saveMessage" class="form-control" rows="3" placeholder="Ex: Atualizei os valores de Janeiro..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="executarSalvar()">Confirmar Salvar</button>
                </div>
            </div>
        </div>
        </div>
    </div>


</body>
</html>
