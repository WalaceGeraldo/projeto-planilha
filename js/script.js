let cacheGlobal = {};
let abaAtiva = "";

async function carregarPorId(id) {
    document.getElementById('loading').style.display = 'block';

    try {
        const response = await fetch(`process.php?action=read&id=${id}`, { method: 'GET' });
        if (!response.ok) {
            let errorMsg = "Erro desconhecido.";
            try {
                const errBody = await response.json();
                errorMsg = errBody.error || errorMsg;
            } catch {
                errorMsg = await response.text();
            }
            throw new Error(errorMsg);
        }

        stateColumnWidths = {};

        const res = await response.json();

        if (!res.dados || Array.isArray(res.dados) && res.dados.length === 0) {
            cacheGlobal = { 'Planilha1': {} };
            renderizarAbas(['Planilha1']);
            abrirAba('Planilha1');
        } else {
            cacheGlobal = res.dados;
            if (Array.isArray(cacheGlobal) && cacheGlobal.length === 0) cacheGlobal = {};

            const abas = Object.keys(cacheGlobal);
            if (abas.length === 0) {
                cacheGlobal['Planilha1'] = {};
                abas.push('Planilha1');
            }
            renderizarAbas(abas);
            abrirAba(abas[0]);
        }

        document.getElementById('acoes').style.display = 'block';

    } catch (err) {
        document.getElementById('areaEdicao').innerHTML = `<p class="text-danger text-center p-5">${err.message}</p>`;
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
}

function renderizarAbas(nomes) {
    const container = document.getElementById('abasPlanilha');
    let html = nomes.map(n => `
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="abrirAba('${n}')" ondblclick="renomearAba('${n}')" title="Duplo clique para renomear">${n}</a>
        </li>
    `).join('');

    if (typeof USER_ROLE !== 'undefined' && USER_ROLE !== 'viewer') {
        html += `
            <li class="nav-item">
                <a class="nav-link text-success fw-bold" href="#" onclick="adicionarAba()" title="Nova Aba">+</a>
            </li>
        `;
    }

    container.innerHTML = html;
}

function renomearAba(nomeAntigo) {
    if (typeof USER_ROLE !== 'undefined' && USER_ROLE === 'viewer') return;

    const modal = new bootstrap.Modal(document.getElementById('modalRenomearAba'));
    document.getElementById('nomeAbaAntigo').value = nomeAntigo;
    document.getElementById('novoNomeAba').value = nomeAntigo;
    modal.show();
    setTimeout(() => {
        const input = document.getElementById('novoNomeAba');
        input.focus();
        input.select();
    }, 500);
}

function confirmarRenomearAba() {
    const nomeAntigo = document.getElementById('nomeAbaAntigo').value;
    const novoNome = document.getElementById('novoNomeAba').value.trim();

    if (!novoNome || novoNome === nomeAntigo) return;

    const abas = Object.keys(cacheGlobal);
    if (abas.includes(novoNome)) {
        alert("Já existe uma aba com este nome.");
        return;
    }

    const modalEl = document.getElementById('modalRenomearAba');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();

    cacheGlobal[novoNome] = cacheGlobal[nomeAntigo];
    delete cacheGlobal[nomeAntigo];

    if (stateColumnWidths[nomeAntigo]) {
        stateColumnWidths[novoNome] = stateColumnWidths[nomeAntigo];
        delete stateColumnWidths[nomeAntigo];
    }

    renderizarAbas(Object.keys(cacheGlobal));
    abrirAba(novoNome);
}

document.addEventListener('DOMContentLoaded', () => {
    const inputRenomear = document.getElementById('novoNomeAba');
    if (inputRenomear) {
        inputRenomear.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') confirmarRenomearAba();
        });
    }
});

function adicionarAba() {
    const modal = new bootstrap.Modal(document.getElementById('modalNovaAba'));
    document.getElementById('nomeNovaAba').value = "";
    modal.show();
    setTimeout(() => document.getElementById('nomeNovaAba').focus(), 500);
}

function confirmarNovaAba() {
    const nome = document.getElementById('nomeNovaAba').value.trim();
    if (!nome) return alert("Digite um nome para a aba.");

    const abasExistentes = Object.keys(cacheGlobal);
    if (abasExistentes.includes(nome)) {
        alert("Já existe uma aba com este nome.");
        return;
    }

    const modalEl = document.getElementById('modalNovaAba');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();

    cacheGlobal[nome] = gerarGradeVazia();
    renderizarAbas([...abasExistentes, nome]);
    abrirAba(nome);
}

document.addEventListener('DOMContentLoaded', () => {
    const inputNovaAba = document.getElementById('nomeNovaAba');
    if (inputNovaAba) {
        inputNovaAba.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') confirmarNovaAba();
        });
    }
});

function gerarGradeVazia() {
    const dados = {};
    dados[1] = { A: 'A', B: 'B', C: 'C', D: 'D', E: 'E' };

    for (let i = 2; i <= 20; i++) {
        dados[i] = { A: '', B: '', C: '', D: '', E: '' };
    }
    return dados;
}

let stateColumnWidths = {};

function abrirAba(nome) {
    abaAtiva = nome;
    document.querySelectorAll('.nav-link').forEach(el => el.classList.toggle('active', el.innerText === nome));

    let dados = cacheGlobal[nome];

    if (!dados || (Array.isArray(dados) && dados.length === 0) || Object.keys(dados).length === 0) {
        dados = gerarGradeVazia();
        cacheGlobal[nome] = dados;
    }

    const rows = Object.keys(dados);

    if (rows.length === 0) {
        document.getElementById('areaEdicao').innerHTML = '<p class="text-center p-5">Aba vazia.</p>';
        return;
    }

    const firstRowIdx = rows[0];
    const headerCols = Object.keys(dados[firstRowIdx]);

    if (!stateColumnWidths[nome]) {
        stateColumnWidths[nome] = {};

        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");
        const fontStyle = "15px system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif";

        headerCols.forEach(col => {
            context.font = "bold " + fontStyle;
            let maxW = context.measureText((dados[firstRowIdx][col] || "").toString().trim()).width;

            context.font = fontStyle;
            for (let i = 1; i < Math.min(rows.length, 300); i++) {
                const val = (dados[rows[i]][col] || "").toString().trim();
                if (val.length > 0) {
                    const w = context.measureText(val).width;
                    if (w > maxW) maxW = w;
                }
            }
            stateColumnWidths[nome][col] = Math.max(50, Math.ceil(maxW + 12));
        });
    }

    const isViewer = (typeof USER_ROLE !== 'undefined' && USER_ROLE === 'viewer');
    const disabledAttr = isViewer ? 'disabled' : '';

    let html = '<table id="tabelaDados" class="table table-bordered table-sm mb-0"><thead><tr><th class="text-center bg-light">#</th>';
    headerCols.forEach(colKey => {
        const w = stateColumnWidths[nome][colKey];
        html += `<th data-col="${colKey}" class="text-center position-relative bg-light" style="min-width: ${w}px; width: ${w}px;">
                    <div style="position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content: center;">
                        <span class="fw-bold">${colKey}</span>
                        <div class="resizer" style="z-index: 100;"></div>
                    </div>
                 </th>`;
    });
    html += '</tr></thead><tbody>';

    for (let i = 1; i <= rows.length; i++) {
        let rowIdx = rows[i - 1];
        if (!rowIdx) break;

        html += `<tr><td class="text-center bg-light fw-bold" style="width: 40px;">${rowIdx}</td>`;

        headerCols.forEach(colKey => {
            let cellData = dados[rowIdx] && dados[rowIdx][colKey] ? dados[rowIdx][colKey] : null;

            if (cellData && cellData.merged) {
                return;
            }

            let val = '';
            let style = '';
            let colspan = '';
            let rowspan = '';
            let isMaster = false;

            if (typeof cellData === 'object' && cellData !== null) {
                val = cellData.value || '';

                if (cellData.style) {
                    if (cellData.style.bold) style += 'font-weight: bold;';
                    if (cellData.style.italic) style += 'font-style: italic;';
                    if (cellData.style.underline) style += 'text-decoration: underline;';
                    if (cellData.style.color) style += `color: ${cellData.style.color};`;
                    if (cellData.style.background) style += `background-color: ${cellData.style.background};`;
                }

                if (cellData.merge) {
                    isMaster = true;
                    if (cellData.merge.c > 1) colspan = `colspan="${cellData.merge.c}"`;
                    if (cellData.merge.r > 1) rowspan = `rowspan="${cellData.merge.r}"`;
                }
            } else {
                val = cellData || '';
            }

            html += `<td ${colspan} ${rowspan} class="${isMaster ? 'text-center align-middle' : ''}">
                        <input type="text" id="cell-${rowIdx}-${colKey}" class="form-control border-0 cell-input" 
                     value="${val}" 
                     style="${style}; height: 100%;"
                     readonly
                     onmousedown="startSelection(event, '${rowIdx}', '${colKey}')"
                     onmouseover="moveSelection('${rowIdx}', '${colKey}')"
                     ondblclick="startEditing(this)"
                     onblur="stopEditing(this)"
                     onchange="updateCache('${rowIdx}', '${colKey}', this.value)" ${disabledAttr}></td>`;
        });
        html += '</tr>';
    }
    html += '</tbody></table>';
    const area = document.getElementById('areaEdicao');
    area.innerHTML = html;

    aplicarZoom();

    const searchInput = document.getElementById('termoPesquisa');
    if (searchInput) {
        searchInput.value = "";
        searchInput.focus();
    }

    const btnSalvar = document.getElementById('acoes');
    if (btnSalvar) {
        btnSalvar.style.display = isViewer ? 'none' : 'block';
    }

    initResize(document.getElementById('tabelaDados'));
}

function initResize(table) {
    const cols = table.querySelectorAll('th');

    cols.forEach(col => {
        const resizer = col.querySelector('.resizer');
        if (!resizer) return;

        let startX, startW;

        const mouseMoveHandler = (e) => {
            const dx = e.clientX - startX;
            const newW = Math.max(50, startW + dx);
            col.style.width = `${newW}px`;
            col.style.minWidth = `${newW}px`;
        };

        const mouseUpHandler = () => {
            document.removeEventListener('mousemove', mouseMoveHandler);
            document.removeEventListener('mouseup', mouseUpHandler);
            resizer.classList.remove('resizing');

            const colKey = col.getAttribute('data-col');
            if (stateColumnWidths[abaAtiva]) {
                stateColumnWidths[abaAtiva][colKey] = parseInt(col.style.width);
            }
        };

        resizer.addEventListener('mousedown', (e) => {
            e.preventDefault();
            startX = e.clientX;
            startW = col.getBoundingClientRect().width;

            resizer.classList.add('resizing');
            document.addEventListener('mousemove', mouseMoveHandler);
            document.addEventListener('mouseup', mouseUpHandler);
        });
    });
}

function pesquisar() {
    const termo = document.getElementById('termoPesquisa').value.toLowerCase();
    const linhas = document.querySelectorAll('#areaEdicao tbody tr');

    linhas.forEach(linha => {
        const textoLinha = Array.from(linha.querySelectorAll('input')).map(input => input.value).join(' ').toLowerCase();
        linha.style.display = textoLinha.includes(termo) ? '' : 'none';
    });
}

let selectionStart = null;
let selectionEnd = null;
let isDragging = false;

function startSelection(e, r, c) {
    if (e.target.disabled) return;
    if (!e.target.readOnly) return;

    if (e) e.preventDefault();

    if (document.activeElement && document.activeElement !== e.target) {
        document.activeElement.blur();
    }

    selectionStart = { r, c };
    selectionEnd = { r, c };
    isDragging = true;
    updateSelectionVisuals();
}

function moveSelection(r, c) {
    if (isDragging) {
        selectionEnd = { r, c };
        updateSelectionVisuals();
    }
}

document.addEventListener('mouseup', () => {
    isDragging = false;
});

function updateSelectionVisuals() {
    document.querySelectorAll('.selected-cell').forEach(el => el.classList.remove('selected-cell'));

    if (!selectionStart || !selectionEnd) return;

    if (!cacheGlobal[abaAtiva] || !stateColumnWidths[abaAtiva]) return;

    const rows = Object.keys(cacheGlobal[abaAtiva]).filter(k => k !== 'config');

    const r1 = parseInt(selectionStart.r);
    const r2 = parseInt(selectionEnd.r);
    const minR = Math.min(r1, r2);
    const maxR = Math.max(r1, r2);

    const cols = Object.keys(stateColumnWidths[abaAtiva]);
    const c1Idx = cols.indexOf(selectionStart.c);
    const c2Idx = cols.indexOf(selectionEnd.c);

    if (c1Idx === -1 || c2Idx === -1) return;

    const minCIdx = Math.min(c1Idx, c2Idx);
    const maxCIdx = Math.max(c1Idx, c2Idx);
    const selectedCols = cols.slice(minCIdx, maxCIdx + 1);

    for (let r = minR; r <= maxR; r++) {
        selectedCols.forEach(c => {
            const input = document.getElementById(`cell-${r}-${c}`);
            if (input) {
                input.classList.add('selected-cell');
            }
        });
    }
}

function aplicarEstilo(tipo, valor = null) {
    if (!selectionStart) return alert("Selecione células primeiro.");

    saveState();

    const r1 = parseInt(selectionStart.r);
    const r2 = parseInt(selectionEnd.r);
    const minR = Math.min(r1, r2);
    const maxR = Math.max(r1, r2);

    const cols = Object.keys(stateColumnWidths[abaAtiva]);
    const c1Idx = cols.indexOf(selectionStart.c);
    const c2Idx = cols.indexOf(selectionEnd.c);
    const minCIdx = Math.min(c1Idx, c2Idx);
    const maxCIdx = Math.max(c1Idx, c2Idx);
    const selectedCols = cols.slice(minCIdx, maxCIdx + 1);

    for (let r = minR; r <= maxR; r++) {
        if (!cacheGlobal[abaAtiva][r]) cacheGlobal[abaAtiva][r] = {};

        selectedCols.forEach(c => {
            let data = cacheGlobal[abaAtiva][r][c];

            if (typeof data !== 'object' || data === null) {
                data = { value: data || '', style: {} };
            }
            if (!data.style) data.style = {};

            if (tipo === 'bold') data.style.bold = !data.style.bold;
            if (tipo === 'italic') data.style.italic = !data.style.italic;
            if (tipo === 'underline') data.style.underline = !data.style.underline;
            if (tipo === 'color') data.style.color = valor;
            if (tipo === 'background') data.style.background = valor;

            cacheGlobal[abaAtiva][r][c] = data;
        });
    }

    abrirAba(abaAtiva);
}

function mesclarCelulas() {
    if (!selectionStart || !selectionEnd) return alert("Selecione células para mesclar.");

    saveState();

    const r1 = parseInt(selectionStart.r);
    const r2 = parseInt(selectionEnd.r);
    const minR = Math.min(r1, r2);
    const maxR = Math.max(r1, r2);

    const cols = Object.keys(stateColumnWidths[abaAtiva]);
    const c1Idx = cols.indexOf(selectionStart.c);
    const c2Idx = cols.indexOf(selectionEnd.c);
    if (c1Idx === -1 || c2Idx === -1) return;
    const minCIdx = Math.min(c1Idx, c2Idx);
    const maxCIdx = Math.max(c1Idx, c2Idx);

    const masterR = minR;
    const masterC = cols[minCIdx];

    if (!cacheGlobal[abaAtiva][masterR]) cacheGlobal[abaAtiva][masterR] = {};
    let masterData = cacheGlobal[abaAtiva][masterR][masterC];

    if (typeof masterData !== 'object' || masterData === null) {
        masterData = { value: masterData || '', style: {} };
    }

    if (masterData.merge && minR === maxR && minCIdx === maxCIdx) {
    }

    const rowSpan = maxR - minR + 1;
    const colSpan = maxCIdx - minCIdx + 1;

    if (rowSpan === 1 && colSpan === 1) {
        if (masterData.merge) {
            delete masterData.merge;
            cacheGlobal[abaAtiva][masterR][masterC] = masterData;
            alert("Para desmesclar, selecione a célula mesclada e clique novamente (Lógica WIP).");
            return;
        }
        return alert("Selecione mais de uma célula.");
    }

    masterData.merge = { r: rowSpan, c: colSpan };
    cacheGlobal[abaAtiva][masterR][masterC] = masterData;

    for (let r = minR; r <= maxR; r++) {
        if (!cacheGlobal[abaAtiva][r]) cacheGlobal[abaAtiva][r] = {};

        for (let cIdx = minCIdx; cIdx <= maxCIdx; cIdx++) {
            const c = cols[cIdx];
            if (r === masterR && c === masterC) continue;

            let cell = cacheGlobal[abaAtiva][r][c];
            if (typeof cell !== 'object' || cell === null) {
                cell = { value: cell || '', style: {} };
            }
            cell.merged = true;
            cacheGlobal[abaAtiva][r][c] = cell;
        }
    }

    selectionStart = null;
    selectionEnd = null;
    updateSelectionVisuals();
    abrirAba(abaAtiva);
}

function startEditing(input) {
    if (input.disabled) return;
    input.readOnly = false;
    input.classList.add('editing');
    input.focus();
}

function stopEditing(input) {
    input.readOnly = true;
    input.classList.remove('editing');
}

let currentZoom = 1.0;

function alterarZoom(delta) {
    currentZoom += delta;
    if (currentZoom < 0.5) currentZoom = 0.5;
    if (currentZoom > 2.0) currentZoom = 2.0;

    aplicarZoom();
}

function resetarZoom() {
    currentZoom = 1.0;
    aplicarZoom();
}

function aplicarZoom() {
    const tabela = document.getElementById('tabelaDados');
    if (tabela) {
        tabela.style.zoom = currentZoom;
    }
}

function updateCache(r, c, v) {
    saveState();

    let current = cacheGlobal[abaAtiva][r][c];

    if (typeof current === 'object' && current !== null) {
        current.value = v;
        cacheGlobal[abaAtiva][r][c] = current;
    } else {
        cacheGlobal[abaAtiva][r][c] = v;
    }
}

let historyStack = [];
let futureStack = [];

function saveState() {
    const state = JSON.parse(JSON.stringify(cacheGlobal[abaAtiva]));
    historyStack.push(state);
    futureStack = [];

    if (historyStack.length > 50) historyStack.shift();
}

function desfazer() {
    if (historyStack.length === 0) return;

    const current = JSON.parse(JSON.stringify(cacheGlobal[abaAtiva]));
    futureStack.push(current);

    const previous = historyStack.pop();
    cacheGlobal[abaAtiva] = previous;
    abrirAba(abaAtiva);
}

function refazer() {
    if (futureStack.length === 0) return;

    const current = JSON.parse(JSON.stringify(cacheGlobal[abaAtiva]));
    historyStack.push(current);

    const next = futureStack.pop();
    cacheGlobal[abaAtiva] = next;
    abrirAba(abaAtiva);
}

function salvar() {
    if (typeof SPREADSHEET_ID === 'undefined') return alert("ID da planilha não definido.");

    const modal = new bootstrap.Modal(document.getElementById('modalSalvar'));
    document.getElementById('saveMessage').value = "Atualização de dados";
    modal.show();
}

async function executarSalvar() {
    const message = document.getElementById('saveMessage').value;

    const modalEl = document.getElementById('modalSalvar');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();

    try {
        const res = await fetch('process.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: SPREADSHEET_ID,
                dados: cacheGlobal,
                message: message || "Atualização de dados"
            })
        });
        const json = await res.json();
        if (json.success) {
            alert("Salvo com sucesso!");
        } else {
            alert('Erro ao salvar: ' + (json.error || "Desconhecido"));
        }
    } catch (err) {
        alert('Erro ao salvar: ' + err.message);
    }
}

async function logout() {
    await fetch('auth.php?action=logout');
    window.location.href = 'login.php';
}

const originalFetch = window.fetch;
window.fetch = async function (...args) {
    const response = await originalFetch(...args);
    if (response.status === 401) {
        window.location.href = 'login.php';
    }
    return response;
};