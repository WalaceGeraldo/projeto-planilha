<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use App\Config\Database;

try {
    // Carregar .env
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();

    // Carregar config
    require_once __DIR__ . '/includes/config.php';

    // Conectar
    $db = Database::getInstance()->getConnection();

    echo "<h1>Instalação do Banco de Dados</h1>";

    // 1. Tabela Users
    $sqlUsers = "
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'viewer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";
    $db->exec($sqlUsers);
    echo "<p>✅ Tabela 'users' verificada/criada.</p>";

    // 2. Tabela Spreadsheets
    $sqlSheets = "
        CREATE TABLE IF NOT EXISTS spreadsheets (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            owner_id INTEGER REFERENCES users(id),
            data JSONB DEFAULT '{}',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";
    $db->exec($sqlSheets);
    echo "<p>✅ Tabela 'spreadsheets' verificada/criada.</p>";

    // 3. Tabela History
    $sqlHistory = "
        CREATE TABLE IF NOT EXISTS spreadsheet_history (
            id SERIAL PRIMARY KEY,
            spreadsheet_id VARCHAR(50) REFERENCES spreadsheets(id) ON DELETE CASCADE,
            user_id INTEGER REFERENCES users(id),
            action VARCHAR(50) NOT NULL,
            details TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";
    $db->exec($sqlHistory);
    echo "<p>✅ Tabela 'spreadsheet_history' verificada/criada.</p>";

    // 4. Criar Admin padrão se não existir
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $passHash = password_hash('admin', PASSWORD_DEFAULT);
        $stmtInsert = $db->prepare("INSERT INTO users (username, password, role) VALUES ('admin', :p, 'admin')");
        $stmtInsert->execute([':p' => $passHash]);
        echo "<p>✅ Usuário 'admin' criado (senha: admin).</p>";
    } else {
        echo "<p>ℹ️ Usuário 'admin' já existe.</p>";
    }

    echo "<h3>Instalação Concluída com Sucesso! 🚀</h3>";
    echo "<p><a href='login.php'>Ir para Login</a></p>";

} catch (Exception $e) {
    die("<h2 style='color:red'>Erro Fatal: " . $e->getMessage() . "</h2><p>Verifique se as variáveis de ambiente (DB_HOST, etc) estão corretas no Railway.</p>");
}
