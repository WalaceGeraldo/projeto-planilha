<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro - Editor de Planilhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="card shadow p-4 auth-card">
        <h3 class="text-center mb-4">Criar Conta</h3>
        <div id="alert" class="alert alert-danger d-none"></div>
        <div id="success" class="alert alert-success d-none">Conta criada! Redirecionando...</div>
        <form id="registerForm">
            <div class="mb-3">
                <label class="form-label">Usuário</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Registrar</button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php">Voltar para Login</a>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const alert = document.getElementById('alert');
            const success = document.getElementById('success');

            alert.classList.add('d-none');

            try {
                const res = await fetch('auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                
                if (res.ok) {
                    success.classList.remove('d-none');
                    setTimeout(() => window.location.href = 'login.php', 1500);
                } else {
                    alert.textContent = json.error || 'Erro ao registrar';
                    alert.classList.remove('d-none');
                }
            } catch (err) {
                console.error(err);
            }
        });
    </script>
</body>
</html>
