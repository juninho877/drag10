<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'classes/User.php';
require_once 'classes/AdminSettings.php';

$user = new User();
$adminSettings = new AdminSettings();
$mensagem = "";
$tipoMensagem = "";

// Buscar dados atuais do usuário
$userId = $_SESSION['user_id'];
$currentUserData = null;

// Carregar configurações do popup (apenas para admin)
$popupEnabled = false;
$popupMessage = '';
$popupButtonText = '';
$popupButtonUrl = '';

if ($_SESSION["role"] === 'admin') {
    $popupEnabled = $adminSettings->getSetting('popup_enabled', '0') === '1';
    $popupMessage = $adminSettings->getSetting('popup_message', '');
    $popupButtonText = $adminSettings->getSetting('popup_button_text', '');
    $popupButtonUrl = $adminSettings->getSetting('popup_button_url', '');
}

try {
    $currentUserData = $user->getUserById($userId);
    if (!$currentUserData) {
        $mensagem = "Erro ao carregar dados do usuário!";
        $tipoMensagem = "error";
    }
} catch (Exception $e) {
    $mensagem = "Erro de conexão com o banco de dados: " . $e->getMessage();
    $tipoMensagem = "error";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $currentUserData) {
    // Processar configurações do popup (apenas para admin)
    if ($_SESSION["role"] === 'admin' && isset($_POST['save_popup_settings'])) {
        $popupEnabled = isset($_POST['popup_enabled']) ? true : false;
        $popupMessage = trim($_POST['popup_message']);
        $popupButtonText = trim($_POST['popup_button_text']);
        $popupButtonUrl = trim($_POST['popup_button_url']);
        
        // Salvar configurações
        $adminSettings->setSetting('popup_enabled', $popupEnabled ? '1' : '0');
        $adminSettings->setSetting('popup_message', $popupMessage);
        $adminSettings->setSetting('popup_button_text', $popupButtonText);
        $adminSettings->setSetting('popup_button_url', $popupButtonUrl);
        
        $mensagem = "Configurações do popup atualizadas com sucesso!";
        $tipoMensagem = "success";
        
        // Atualizar variáveis locais para refletir as novas configurações
        $popupEnabled = $adminSettings->getSetting('popup_enabled', '0') === '1';
        $popupMessage = $adminSettings->getSetting('popup_message', '');
        $popupButtonText = $adminSettings->getSetting('popup_button_text', '');
        $popupButtonUrl = $adminSettings->getSetting('popup_button_url', '');
    }
    // Processar alterações de usuário/senha
    elseif (isset($_POST['update_user_settings'])) {
    $novo_usuario = trim($_POST["novo_usuario"]);
    $senha_atual = trim($_POST["senha_atual"]);
    $nova_senha = trim($_POST["nova_senha"]);
    $confirmar_senha = trim($_POST["confirmar_senha"]);
    
    // Validações básicas
    if (empty($novo_usuario)) {
        $mensagem = "O nome de usuário não pode estar vazio!";
        $tipoMensagem = "error";
    } elseif (empty($senha_atual)) {
        $mensagem = "A senha atual é obrigatória para confirmar as alterações!";
        $tipoMensagem = "error";
    } elseif (empty($nova_senha)) {
        $mensagem = "A nova senha é obrigatória!";
        $tipoMensagem = "error";
    } elseif (strlen($nova_senha) < 6) {
        $mensagem = "A nova senha deve ter pelo menos 6 caracteres!";
        $tipoMensagem = "error";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas senhas não coincidem!";
        $tipoMensagem = "error";
    } else {
        // Tentar autenticar o usuário com a senha atual usando a classe User
        try {
            $authResult = $user->authenticate($currentUserData['username'], $senha_atual);
            
            if (!$authResult['success']) {
                $mensagem = "Senha atual incorreta! Verifique se digitou corretamente.";
                $tipoMensagem = "error";
            } else {
                // Preparar dados para atualização
                $updateData = [
                    'username' => $novo_usuario,
                    'email' => $currentUserData['email'], // Manter email atual
                    'role' => $currentUserData['role'], // Manter role atual
                    'status' => $currentUserData['status'], // Manter status atual
                    'expires_at' => $currentUserData['expires_at'], // Manter data de expiração atual
                    'password' => $nova_senha // Nova senha
                ];
                
                $result = $user->updateUser($userId, $updateData);
                
                if ($result['success']) {
                    $_SESSION["usuario"] = $novo_usuario;
                    $mensagem = "Usuário e senha alterados com sucesso!";
                    $tipoMensagem = "success";
                    
                    // Recarregar dados do usuário
                    $currentUserData = $user->getUserById($userId);
                } else {
                    $mensagem = $result['message'];
                    $tipoMensagem = "error";
                }
            }
        } catch (Exception $e) {
            $mensagem = "Erro ao verificar senha: " . $e->getMessage();
            $tipoMensagem = "error";
        }
    }
    }
}

$pageTitle = "Configurações da Conta";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-cog text-primary-500 mr-3"></i>
        Configurações da Conta
    </h1>
    <p class="page-subtitle">Gerencie suas informações de acesso e preferências do sistema</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Main Settings Form -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informações da Conta</h3>
                <p class="card-subtitle">Atualize seu nome de usuário e senha</p>
            </div>
            <div class="card-body">
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipoMensagem; ?> mb-6">
                        <i class="fas fa-<?php echo $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <?php if ($currentUserData): ?>
                <form method="POST" action="" id="settingsForm">
                    <input type="hidden" name="update_user_settings" value="1">
                    <div class="form-group">
                        <label for="novo_usuario" class="form-label">
                            <i class="fas fa-user mr-2"></i>
                            Nome de Usuário
                        </label>
                        <input type="text" id="novo_usuario" name="novo_usuario" class="form-input" 
                               value="<?php echo htmlspecialchars($currentUserData['username']); ?>" required>
                        <p class="text-xs text-muted mt-1">Este será seu nome de login no sistema</p>
                    </div>

                    <div class="border-t border-gray-200 my-6 pt-6">
                        <h4 class="text-lg font-semibold mb-4">Alterar Senha</h4>
                        
                        <div class="form-group">
                            <label for="senha_atual" class="form-label">
                                <i class="fas fa-lock mr-2"></i>
                                Senha Atual
                            </label>
                            <div class="relative">
                                <input type="password" id="senha_atual" name="senha_atual" class="form-input pr-10" 
                                       placeholder="Digite sua senha atual para confirmar" required autocomplete="current-password">
                                <button type="button" class="password-toggle" data-target="senha_atual">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="text-xs text-muted mt-1">Use a mesma senha que você usa para fazer login</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="nova_senha" class="form-label">
                                    <i class="fas fa-key mr-2"></i>
                                    Nova Senha
                                </label>
                                <div class="relative">
                                    <input type="password" id="nova_senha" name="nova_senha" class="form-input pr-10" 
                                           placeholder="Mínimo de 6 caracteres" required autocomplete="new-password">
                                    <button type="button" class="password-toggle" data-target="nova_senha">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2" id="passwordStrength" style="display: none;">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <p class="strength-text" id="strengthText"></p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmar_senha" class="form-label">
                                    <i class="fas fa-check mr-2"></i>
                                    Confirmar Nova Senha
                                </label>
                                <div class="relative">
                                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-input pr-10" 
                                           placeholder="Repita a nova senha" required autocomplete="new-password">
                                    <button type="button" class="password-toggle" data-target="confirmar_senha">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-match mt-2" id="passwordMatch" style="display: none;">
                                    <p class="match-text" id="matchText"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i>
                            Salvar Alterações
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i>
                            Cancelar
                        </button>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        Não foi possível carregar os dados do usuário. Tente fazer login novamente.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($_SESSION["role"] === 'admin'): ?>
    <!-- Configurações do Popup (apenas para admin) -->
    <div class="lg:col-span-2">
        <div class="card mt-6">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bell text-primary-500 mr-2"></i>
                    Configurações do Popup Pós-Login
                </h3>
                <p class="card-subtitle">Configure o popup que será exibido para todos os usuários após o login</p>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="popupSettingsForm">
                    <input type="hidden" name="save_popup_settings" value="1">
                    
                    <div class="form-group">
                        <div class="flex items-center">
                            <input type="checkbox" id="popup_enabled" name="popup_enabled" class="form-checkbox" 
                                  <?php echo $popupEnabled ? 'checked' : ''; ?>>
                            <label for="popup_enabled" class="ml-2 font-medium">
                                Ativar popup após o login
                            </label>
                        </div>
                        <p class="text-xs text-muted mt-1">
                            Quando ativado, todos os usuários verão um popup após fazer login no sistema
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup_message" class="form-label">
                            <i class="fas fa-comment-alt mr-2"></i>
                            Mensagem do Popup
                        </label>
                        <textarea id="popup_message" name="popup_message" class="form-input" rows="6" 
                                  placeholder="Digite a mensagem que será exibida no popup"><?php echo htmlspecialchars($popupMessage); ?></textarea>
                        <p class="text-xs text-muted mt-1">
                            Você pode usar HTML básico para formatação (negrito, itálico, links, etc.)
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="popup_button_text" class="form-label">
                                <i class="fas fa-mouse-pointer mr-2"></i>
                                Texto do Botão
                            </label>
                            <input type="text" id="popup_button_text" name="popup_button_text" class="form-input" 
                                   value="<?php echo htmlspecialchars($popupButtonText); ?>" 
                                   placeholder="Ex: Saiba mais">
                            <p class="text-xs text-muted mt-1">
                                Deixe em branco para não exibir um botão
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label for="popup_button_url" class="form-label">
                                <i class="fas fa-link mr-2"></i>
                                URL do Botão
                            </label>
                            <input type="text" id="popup_button_url" name="popup_button_url" class="form-input" 
                                   value="<?php echo htmlspecialchars($popupButtonUrl); ?>" 
                                   placeholder="Ex: https://exemplo.com">
                            <p class="text-xs text-muted mt-1">
                                URL para onde o botão irá redirecionar
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="button" class="btn btn-secondary" id="previewPopupBtn">
                            <i class="fas fa-eye"></i>
                            Pré-visualizar Popup
                        </button>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" name="save_popup_settings" value="1">
                            <i class="fas fa-save"></i>
                            Salvar Configurações do Popup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sidebar Info -->
    <div class="space-y-6">
        <!-- Account Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informações da Conta</h3>
            </div>
            <div class="card-body">
                <?php if ($currentUserData): ?>
                <div class="flex items-center gap-3 mb-4">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUserData['username'], 0, 2)); ?>
                    </div>
                    <div>
                        <h4 class="font-semibold"><?php echo htmlspecialchars($currentUserData['username']); ?></h4>
                        <p class="text-sm text-muted">
                            <?php echo $currentUserData['role'] === 'admin' ? 'Administrador' : 'Usuário'; ?>
                        </p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted">ID:</span>
                        <span><?php echo $currentUserData['id']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Último acesso:</span>
                        <span>
                            <?php 
                            if ($currentUserData['last_login']) {
                                echo date('d/m/Y H:i', strtotime($currentUserData['last_login']));
                            } else {
                                echo 'Nunca';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Status:</span>
                        <span class="<?php echo $currentUserData['status'] === 'active' ? 'text-success-600' : 'text-danger-600'; ?> font-medium">
                            <?php echo $currentUserData['status'] === 'active' ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </div>
                    <?php if ($currentUserData['expires_at']): ?>
                    <div class="flex justify-between">
                        <span class="text-muted">Expira em:</span>
                        <span><?php echo date('d/m/Y', strtotime($currentUserData['expires_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-muted">Criado em:</span>
                        <span><?php echo date('d/m/Y', strtotime($currentUserData['created_at'])); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Security Tips -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔒 Dicas de Segurança</h3>
            </div>
            <div class="card-body">
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-shield-alt text-success-500 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Use senhas fortes</p>
                            <p class="text-muted">Combine letras, números e símbolos</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-clock text-warning-500 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Altere regularmente</p>
                            <p class="text-muted">Recomendamos trocar a cada 3 meses</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-secret text-primary-500 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Mantenha em segredo</p>
                            <p class="text-muted">Nunca compartilhe suas credenciais</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ações Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="space-y-2">
                    <a href="index.php" class="btn btn-secondary w-full text-sm">
                        <i class="fas fa-home"></i>
                        Voltar ao Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-danger w-full text-sm">
                        <i class="fas fa-sign-out-alt"></i>
                        Sair da Conta
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
	.relative {
    position: relative;
	}
    .alert {
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    }
    
    .alert-success {
        background: var(--success-50);
        color: var(--success-600);
        border: 1px solid rgba(34, 197, 94, 0.2);
    }
    
    .alert-error {
        background: var(--danger-50);
        color: var(--danger-600);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .password-toggle {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        transition: var(--transition);
    }
    
    .password-toggle:hover {
        color: var(--text-primary);
        background: var(--bg-tertiary);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .password-strength {
        margin-top: 0.5rem;
    }

    .strength-bar {
        width: 100%;
        height: 4px;
        background: var(--bg-tertiary);
        border-radius: 2px;
        overflow: hidden;
    }

    .strength-fill {
        height: 100%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }

    .strength-text {
        font-size: 0.75rem;
        margin-top: 0.25rem;
        font-weight: 500;
    }

    .password-match .match-text {
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    [data-theme="dark"] .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
    }
    
    [data-theme="dark"] .alert-error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-400);
    }
    
    [data-theme="dark"] .border-gray-200 {
        border-color: var(--border-color);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });

    // Password strength indicator
    const newPasswordInput = document.getElementById('nova_senha');
    const confirmPasswordInput = document.getElementById('confirmar_senha');
    const passwordStrength = document.getElementById('passwordStrength');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const passwordMatch = document.getElementById('passwordMatch');
    const matchText = document.getElementById('matchText');
    
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];
        
        if (password.length >= 6) strength += 1;
        if (password.length >= 8) strength += 1;
        if (/[a-z]/.test(password)) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        const colors = ['#ef4444', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#16a34a'];
        const texts = ['Muito fraca', 'Fraca', 'Regular', 'Boa', 'Forte', 'Muito forte'];
        
        strengthFill.style.width = `${(strength / 6) * 100}%`;
        strengthFill.style.backgroundColor = colors[strength - 1] || colors[0];
        strengthText.textContent = texts[strength - 1] || texts[0];
        strengthText.style.color = colors[strength - 1] || colors[0];
        
        return strength;
    }
    
    function checkPasswordMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword) {
            passwordMatch.style.display = 'block';
            if (newPassword === confirmPassword) {
                matchText.textContent = '✓ Senhas coincidem';
                matchText.style.color = 'var(--success-600)';
                confirmPasswordInput.setCustomValidity('');
            } else {
                matchText.textContent = '✗ Senhas não coincidem';
                matchText.style.color = 'var(--danger-600)';
                confirmPasswordInput.setCustomValidity('As senhas não coincidem');
            }
        } else {
            passwordMatch.style.display = 'none';
            confirmPasswordInput.setCustomValidity('');
        }
    }
    
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        if (password) {
            passwordStrength.style.display = 'block';
            checkPasswordStrength(password);
        } else {
            passwordStrength.style.display = 'none';
        }
        checkPasswordMatch();
    });
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    // Form submission with confirmation
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Confirmar Alterações',
            text: 'Tem certeza que deseja alterar suas informações de login?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, alterar',
            cancelButtonText: 'Cancelar',
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
});

function resetForm() {
    document.getElementById('settingsForm').reset();
    document.getElementById('passwordStrength').style.display = 'none';
    document.getElementById('passwordMatch').style.display = 'none';
}
</script>

<?php if ($_SESSION["role"] === 'admin'): ?>
<script>
// Script para pré-visualização do popup
document.addEventListener('DOMContentLoaded', function() {
    const previewPopupBtn = document.getElementById('previewPopupBtn');
    
    if (previewPopupBtn) {
        previewPopupBtn.addEventListener('click', function() {
            const popupEnabled = document.getElementById('popup_enabled').checked;
            const popupMessage = document.getElementById('popup_message').value;
            const popupButtonText = document.getElementById('popup_button_text').value;
            const popupButtonUrl = document.getElementById('popup_button_url').value;
            
            if (!popupEnabled || !popupMessage.trim()) {
                Swal.fire({
                    title: 'Aviso',
                    text: 'O popup está desativado ou não tem mensagem. Ative-o e adicione uma mensagem para visualizar.',
                    icon: 'warning',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
                return;
            }
            
            // Configurar botão (se houver)
            let buttonHtml = '';
            if (popupButtonText.trim() && popupButtonUrl.trim()) {
                buttonHtml = `
                    <a href="${popupButtonUrl}" class="swal2-confirm swal2-styled" style="display: inline-block; margin-top: 1rem;">
                        ${popupButtonText}
                    </a>
                `;
            }
            
            // Mostrar pré-visualização
            Swal.fire({
                title: 'Mensagem do Sistema',
                html: `
                    <div>${popupMessage}</div>
                    ${buttonHtml}
                `,
                showConfirmButton: !buttonHtml,
                confirmButtonText: 'Fechar',
                customClass: {
                    container: 'modern-popup',
                    popup: 'modern-popup-content',
                    title: 'modern-popup-title',
                    htmlContainer: 'modern-popup-body',
                    confirmButton: 'modern-popup-confirm',
                    backdrop: 'modern-popup-backdrop'
                },
                buttonsStyling: false
            });
        });
    }
});
</script>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
