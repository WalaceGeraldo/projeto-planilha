<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Editor de Planilhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="card shadow p-4 auth-card">
        <h3 class="text-center mb-4">Login</h3>
        <div id="alert" class="alert alert-danger d-none"></div>
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Usuário</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="text-center mt-3">
            <a href="register.php">Criar conta</a>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                
                if (res.ok) {
                    window.location.href = 'index.php';
                } else {
                    const alert = document.getElementById('alert');
                    alert.textContent = json.error || 'Erro ao logar';
                    alert.classList.remove('d-none');
                }
            } catch (err) {
                console.error(err);
            }
        });
    </script>
</body>
</html>
