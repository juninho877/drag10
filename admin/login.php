<?php ob_start(); ?>
<?php
session_start();

// Incluir as classes necessárias
require_once 'config/database.php';
require_once 'classes/User.php';

// Variáveis para o registro
$registerError = "";
$registerSuccess = "";

// Inicializar banco de dados (criar tabelas se não existirem)
try {
    $db = Database::getInstance();
    $db->createTables();
} catch (Exception $e) {
    $erro = "Erro de conexão com o banco de dados. Verifique as configurações.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    try {
        $user = new User();
        $result = $user->authenticate($username, $password);
        
        if ($result['success']) {
            $_SESSION["usuario"] = $result['user']['username'];
            $_SESSION["user_id"] = $result['user']['id'];
            $_SESSION["role"] = $result['user']['role'];
            header("Location: index.php");
            exit();
        } else {
            // Verificar se a conta está expirada
            if ($result['message'] === 'Conta expirada') {
                // Buscar o usuário pelo nome de usuário para obter o ID
                $stmt = $db->getConnection()->prepare("SELECT id FROM usuarios WHERE username = ?");
                $stmt->execute([$username]);
                $userId = $stmt->fetchColumn();
                
                if ($userId) {
                    // Armazenar o ID do usuário na sessão temporariamente para o pagamento
                    $_SESSION["temp_user_id"] = $userId;
                    $_SESSION["temp_username"] = $username;
                    
                    // Redirecionar para a página de pagamento com parâmetro de conta expirada
                    header("Location: payment.php?expired=true");
                    exit();
                }
            }
            $erro = $result['message'];
        }
    } catch (Exception $e) {
        $erro = "Erro interno do sistema. Tente novamente.";
    }
}

// Processar formulário de registro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register_action"])) {
    $newUsername = trim($_POST["new_username"]);
    $newEmail = trim($_POST["new_email"]);
    $newPassword = trim($_POST["new_password"]);
    $confirmNewPassword = trim($_POST["confirm_new_password"]);

    if (empty($newUsername) || empty($newEmail) || empty($newPassword) || empty($confirmNewPassword)) {
        $_SESSION['register_error'] = "Todos os campos são obrigatórios.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Formato de e-mail inválido.";
    } elseif (strlen($newPassword) < 6) {
        $_SESSION['register_error'] = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($newPassword !== $confirmNewPassword) {
        $_SESSION['register_error'] = "As senhas não coincidem.";
    } else {
        $userData = [
            'username' => $newUsername,
            'email' => $newEmail,
            'password' => $newPassword,
            'role' => 'user', // Novo usuário será do tipo 'user'
            'status' => 'active', // Ativo para o período de teste
            'expires_at' => date('Y-m-d', strtotime('+2 days')) // Teste grátis de 2 dias
        ];

        try {
            $user = new User();
            $result = $user->createUser($userData);
            if ($result['success']) {
                $_SESSION['register_success'] = "Sua conta foi criada com sucesso! Você tem um teste grátis de 2 dias. Faça login para começar.";
            } else {
                $_SESSION['register_error'] = $result['message'];
            }
        } catch (Exception $e) {
            $_SESSION['register_error'] = "Erro ao criar usuário: " . $e->getMessage();
        }
    }
    
    // Redirecionar após o processamento para evitar reenvio do formulário
    header("Location: login.php");
    exit();
}

// Verificar se existe uma mensagem de sucesso de login após renovação
$loginSuccess = isset($_SESSION['login_success']) && $_SESSION['login_success'];
$loginMessage = isset($_SESSION['login_message']) ? $_SESSION['login_message'] : '';

// Limpar mensagens da sessão após uso
if (isset($_SESSION['login_success'])) {
    unset($_SESSION['login_success']);
    unset($_SESSION['login_message']);
}

// Recuperar mensagens de cadastro da sessão
if (isset($_SESSION['register_success'])) {
    $registerSuccess = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if (isset($_SESSION['register_error'])) {
    $registerError = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gera Top</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Theme */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Brand Colors */
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-500: #06090e;
            --primary-600: #06090e;
            --primary-700: #02050e;
            --primary-900: #1e3a8a;
            
            /* Status Colors */
            --success-50: #f0fdf4;
            --success-500: #22c55e;
            --success-600: #16a34a;
            --danger-50: #fef2f2;
            --danger-500: #ef4444;
            --danger-600: #dc2626;
            --warning-50: #fffbeb;
            --warning-500: #f59e0b;
            --warning-600: #d97706;
            
            /* Layout */
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Dark Theme */
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: #0a125d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-700) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            transition: var(--transition);
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        .login-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 380px; /* Reduzido de 420px para 380px */
            background: var(--bg-primary);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: 1px solid var(--border-color);
            animation: slideIn 0.6s ease-out;
            margin-bottom: 1rem;
        }

        .login-header {
            background: linear-gradient(180deg, var(--primary-500), var(--primary-600));
            padding: 2rem 1.5rem 1.5rem; /* Reduzido o padding */
            text-align: center;
            color: white;
            position: relative;
        }

        .theme-toggle {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .logo {
            width: 100px; /* Reduzido de 130px para 100px */
            height: 100px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem; /* Reduzido o margin-bottom */
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            animation: pulse 2s infinite;
            padding: 6px;
            overflow: hidden;
        }

        .login-title {
            font-size: 1.75rem; /* Reduzido de 2rem */
            font-weight: 700;
            margin-bottom: 0.25rem; /* Reduzido */
            letter-spacing: -0.025em;
        }

        .login-subtitle {
            opacity: 0.9;
            font-size: 0.875rem; /* Reduzido de 1rem */
            font-weight: 400;
        }

        .login-form {
            padding: 2rem 1.5rem; /* Reduzido o padding */
        }

        .form-group {
            margin-bottom: 1.25rem; /* Reduzido de 1.5rem */
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem; /* Reduzido de 0.75rem */
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .input-wrapper {
            display: flex;
            align-items: center;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-secondary);
            transition: var(--transition);
            position: relative;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary-500);
            background: var(--bg-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input {
            width: 100%;
            flex-grow: 1;
            padding: 0.875rem; /* Reduzido de 1rem */
            border: none;
            background: transparent;
            outline: none;
            color: var(--text-primary);
            font-size: 0.9375rem; /* Reduzido de 1rem */
            font-weight: 500;
            transition: var(--transition);
        }
        
        .input-icon-left {
            padding-left: 0.875rem; /* Reduzido de 1rem */
            padding-right: 0.625rem; /* Reduzido de 0.75rem */
            color: var(--text-muted);
            transition: var(--transition);
        }

        .input-wrapper:focus-within .input-icon-left {
            color: var(--primary-500);
        }

        .password-toggle-icon {
            padding: 0 0.875rem; /* Reduzido de 1rem */
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
        }
        .password-toggle-icon:hover {
            color: var(--primary-500);
        }

        .submit-btn {
            width: 100%;
            padding: 0.875rem; /* Reduzido de 1rem */
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 0.9375rem; /* Reduzido de 1rem */
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: var(--danger-50);
            color: var(--danger-600);
            padding: 0.875rem; /* Reduzido de 1rem */
            border-radius: var(--border-radius);
            margin-top: 1.25rem; /* Reduzido de 1.5rem */
            font-size: 0.8125rem; /* Reduzido de 0.875rem */
            border: 1px solid rgba(239, 68, 68, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }

        [data-theme="dark"] .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-500);
        }

        .success-message {
            background: var(--success-50);
            color: var(--success-600);
            padding: 0.875rem; /* Reduzido de 1rem */
            border-radius: var(--border-radius);
            margin-bottom: 1.25rem; /* Reduzido de 1.5rem */
            font-size: 0.8125rem; /* Reduzido de 0.875rem */
            border: 1px solid rgba(34, 197, 94, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        [data-theme="dark"] .success-message {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success-400);
        }

        .welcome-text {
            text-align: center;
            margin-bottom: 1.25rem; /* Reduzido de 1.5rem */
            padding: 0.875rem; /* Reduzido de 1rem */
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }

        .welcome-text h3 {
            color: var(--text-primary);
            font-size: 1rem; /* Reduzido de 1.125rem */
            font-weight: 600;
            margin-bottom: 0.375rem; /* Reduzido de 0.5rem */
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 0.8125rem; /* Reduzido de 0.875rem */
        }

        .footer {
            text-align: center;
            color: white;
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            opacity: 0.8;
            transition: var(--transition);
            width: 100%;
            max-width: 380px;
        }

        [data-theme="dark"] .footer {
            color: var(--text-secondary);
        }

        /* Loading state */
        .submit-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .submit-btn.loading::after {
            content: '';
            width: 18px; /* Reduzido de 20px */
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
        }
        
        .toggle-link {
            color: var(--primary-500);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .toggle-link:hover {
            color: var(--primary-600);
            text-decoration: underline;
        }
        
        /* Dark theme adjustment for toggle link */
        [data-theme="dark"] .toggle-link {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .toggle-link:hover {
            color: var(--primary-400);
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-4 {
            margin-top: 1rem;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header,
            .login-form {
                padding: 1.5rem 1rem; /* Ajuste para telas pequenas */
            }
            
            .login-title {
                font-size: 1.5rem; /* Ajuste para telas pequenas */
            }
            
            .logo {
                width: 80px; /* Ajuste para telas pequenas */
                height: 80px;
            }

            .login-container {
                max-width: 340px; /* Ajuste para telas pequenas */
            }

            .footer {
                max-width: 340px; /* Ajuste para telas pequenas */
            }
        }

        @media (max-width: 360px) {
            .login-container {
                max-width: 320px; /* Ajuste para telas muito pequenas */
            }

            .footer {
                max-width: 320px; /* Ajuste para telas muito pequenas */
            }
        }

        /* Focus states for accessibility */
        .theme-toggle:focus,
        .submit-btn:focus {
            outline: 2px solid rgba(255, 255, 255, 0.5);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                
                <div class="logo">
                    <img src="https://i.postimg.cc/5yxSK8Ws/logo-geratop.png" alt="Logo Gera Top" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h1 class="login-title">Gera Top Banners</h1>
                <p class="login-subtitle">Jogos Filmes e Series</p>
            </div>

            <div class="login-form" id="loginFormContainer">
                <?php if (isset($registerSuccess) && !empty($registerSuccess)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $registerSuccess; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($loginSuccess): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $loginMessage; ?>
                </div>
                <?php endif; ?>
                
                <div class="welcome-text">
                    <h3>Bem-vindo de volta!</h3>
                    <p>Faça login para acessar o painel administrativo</p>
                </div>

                <form method="POST" action="login.php" id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>
                            Usuário
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon-left"></i>
                            <input type="text" id="username" name="username" class="form-input" placeholder="Digite seu usuário" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Senha
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon-left"></i>
                            <input type="password" id="password" name="password" class="form-input" placeholder="Digite sua senha" required autocomplete="current-password">
                            <i class="fas fa-eye password-toggle-icon" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Entrar no Sistema
                    </button>

                    <?php if (isset($erro)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $erro; ?>
                        </div>
                    <?php endif; ?>
                </form>
                <div class="text-center mt-4">
                    <a href="#" id="showRegisterForm" class="toggle-link">Não tem uma conta? Cadastre-se!</a>
                </div>
            </div>
            
            <div class="login-form" id="registerFormContainer" style="display: none;">
                <div class="welcome-text">
                    <h3>Crie sua conta grátis!</h3>
                    <p>Teste o sistema por 2 dias sem compromisso.</p>
                </div>

                <form method="POST" action="login.php" id="registerForm">
                    <input type="hidden" name="register_action" value="1">

                    <div class="form-group">
                        <label for="new_username" class="form-label">
                            <i class="fas fa-user"></i>
                            Nome de Usuário
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon-left"></i>
                            <input type="text" id="new_username" name="new_username" class="form-input" placeholder="Escolha um nome de usuário" required autocomplete="new-username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon-left"></i>
                            <input type="email" id="new_email" name="new_email" class="form-input" placeholder="Seu melhor e-mail" required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Senha
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon-left"></i>
                            <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Mínimo de 6 caracteres" required autocomplete="new-password">
                            <i class="fas fa-eye password-toggle-icon" id="toggleNewPassword"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_new_password" class="form-label">
                            <i class="fas fa-check"></i>
                            Confirmar Senha
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-check input-icon-left"></i>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-input" placeholder="Repita a senha" required autocomplete="new-password">
                            <i class="fas fa-eye password-toggle-icon" id="toggleConfirmNewPassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="registerSubmitBtn">
                        <i class="fas fa-user-plus"></i>
                        Cadastrar
                    </button>

                    <?php if (isset($registerError) && !empty($registerError)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $registerError; ?>
                        </div>
                    <?php endif; ?>
                </form>
                <div class="text-center mt-4">
                    <a href="#" id="showLoginForm" class="toggle-link">Já tem uma conta? Faça login!</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> Todos os direitos reservados DragonApps
        </div>
    </div>

    <script>
        // Theme Management
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const themeIcon = themeToggle.querySelector('i');

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        body.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Form enhancements
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const inputs = document.querySelectorAll('.form-input');

        // Input focus animations
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                // Animação agora é controlada pelo :focus-within no CSS
            });
            
            input.addEventListener('blur', function() {
                // Animação agora é controlada pelo CSS
            });
        });

        // Form submission with loading state
        loginForm.addEventListener('submit', function(e) {
            submitBtn.classList.add('loading');
            
            const btnText = submitBtn.querySelector('span');
            if(!btnText) {
                submitBtn.innerHTML = `<i class="fas fa-sign-in-alt"></i><span>${submitBtn.textContent.trim()}</span>`
            }
            submitBtn.querySelector('span').textContent = ' Entrando...';
        });

        // Form Toggling
        const loginFormContainer = document.getElementById('loginFormContainer');
        const registerFormContainer = document.getElementById('registerFormContainer');
        const showRegisterFormBtn = document.getElementById('showRegisterForm');
        const showLoginFormBtn = document.getElementById('showLoginForm');

        if (showRegisterFormBtn) {
            showRegisterFormBtn.addEventListener('click', function(e) {
                e.preventDefault();
                loginFormContainer.style.display = 'none';
                registerFormContainer.style.display = 'block';
                // Clear login form errors when switching
                const loginErrorDiv = loginFormContainer.querySelector('.error-message');
                if (loginErrorDiv) loginErrorDiv.style.display = 'none';
            });
        }

        if (showLoginFormBtn) {
            showLoginFormBtn.addEventListener('click', function(e) {
                e.preventDefault();
                registerFormContainer.style.display = 'none';
                loginFormContainer.style.display = 'block';
                // Clear register form errors when switching
                const registerErrorDiv = registerFormContainer.querySelector('.error-message');
                if (registerErrorDiv) registerErrorDiv.style.display = 'none';
            });
        }

        // Password toggle for new registration fields
        const toggleNewPassword = document.getElementById('toggleNewPassword');
        const newPasswordInput = document.getElementById('new_password');
        const toggleConfirmNewPassword = document.getElementById('toggleConfirmNewPassword');
        const confirmNewPasswordInput = document.getElementById('confirm_new_password');

        function setupPasswordToggle(toggleBtn, passwordInput) {
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        }

        setupPasswordToggle(toggleNewPassword, newPasswordInput);
        setupPasswordToggle(toggleConfirmNewPassword, confirmNewPasswordInput);

        // Handle initial display based on PHP messages (e.g., if registration failed, stay on register form)
        <?php if (isset($registerError) && !empty($registerError)): ?>
            loginFormContainer.style.display = 'none';
            registerFormContainer.style.display = 'block';
        <?php elseif (isset($registerSuccess) && !empty($registerSuccess)): ?>
            loginFormContainer.style.display = 'block';
            registerFormContainer.style.display = 'none';
        <?php endif; ?>

        // Form submission loading state for register form
        const registerForm = document.getElementById('registerForm');
        const registerSubmitBtn = document.getElementById('registerSubmitBtn');

        if (registerForm && registerSubmitBtn) {
            registerForm.addEventListener('submit', function(e) {
                registerSubmitBtn.classList.add('loading');
                const btnTextSpan = registerSubmitBtn.querySelector('span');
                if (btnTextSpan) {
                    btnTextSpan.textContent = ' Cadastrando...';
                } else {
                    registerSubmitBtn.innerHTML = `<i class="fas fa-user-plus"></i><span> Cadastrando...</span>`;
                }
            });
        }

        // Lógica para mostrar/esconder senha
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }

            // Auto-focus no campo de usuário
            const usernameInput = document.getElementById('username');
            if (usernameInput) {
                setTimeout(() => usernameInput.focus(), 100);
            }

            // Ajuste para o texto do botão no loading state
            const btnTextSpan = document.createElement('span');
            btnTextSpan.textContent = ' Entrar no Sistema';
            submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i>';
            submitBtn.appendChild(btnTextSpan);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + T for theme toggle
            if (e.altKey && e.key === 't') {
                e.preventDefault();
                themeToggle.click();
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>