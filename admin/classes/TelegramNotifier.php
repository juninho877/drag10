<?php
date_default_timezone_set('America/Sao_Paulo');

/**
 * Classe para enviar notificações via Telegram para o administrador
 */
class TelegramNotifier {
    /**
     * Envia uma notificação sobre um novo cadastro para o administrador
     * 
     * @param string $username Nome de usuário do novo cadastro
     * @param string $email Email do novo cadastro
     * @return bool Sucesso ou falha no envio
     */
    public static function sendNewRegistrationNotification($username, $email) {
        try {
            require_once __DIR__ . '/TelegramSettings.php';
            
            // Buscar configurações do administrador (ID 1)
            $telegramSettings = new TelegramSettings();
            $adminSettings = $telegramSettings->getSettings(1);
            
            // Verificar se as configurações existem e se há um chat ID de notificação
            if (!$adminSettings || empty($adminSettings['bot_token']) || empty($adminSettings['notification_chat_id'])) {
                error_log("Erro ao enviar notificação Telegram: Configurações do admin não encontradas ou incompletas");
                return false;
            }
            
            $botToken = $adminSettings['bot_token'];
            $chatId = $adminSettings['notification_chat_id'];
            
            // Construir a mensagem
            $message = "🔔 *NOVO CADASTRO NO SISTEMA*\n\n";
            $message .= "👤 *Usuário:* " . $username . "\n";
            $message .= "📧 *Email:* " . $email . "\n";
            $message .= "🕒 *Data:* " . date('d/m/Y H:i:s') . "\n\n";
            $message .= "Este usuário foi cadastrado através do formulário de registro.";
            
            // Enviar a mensagem via API do Telegram
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $data = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ];
            
            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === false) {
                error_log("Erro ao enviar notificação Telegram: Falha na requisição HTTP");
                return false;
            }
            
            $response = json_decode($result, true);
            if (!isset($response['ok']) || $response['ok'] !== true) {
                error_log("Erro ao enviar notificação Telegram: " . ($response['description'] ?? 'Erro desconhecido'));
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Exceção ao enviar notificação Telegram: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia uma notificação de teste para o chat especificado
     * 
     * @param string $botToken Token do bot do Telegram
     * @param string $chatId ID do chat para enviar a notificação
     * @return bool Sucesso ou falha no envio
     */
    public static function sendTestNotification($botToken, $chatId) {
        try {
            // Construir a mensagem
            $message = "🔔 *TESTE DE NOTIFICAÇÃO DE CADASTRO*\n\n";
            $message .= "👤 *Usuário:* Usuário Teste\n";
            $message .= "📧 *Email:* usuario.teste@exemplo.com\n";
            $message .= "🕒 *Data:* " . date('d/m/Y H:i:s') . "\n\n";
            $message .= "Esta é apenas uma mensagem de teste para verificar a configuração de notificações.";
            
            // Enviar a mensagem via API do Telegram
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $data = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ];
            
            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === false) {
                error_log("Erro ao enviar notificação de teste: Falha na requisição HTTP");
                return false;
            }
            
            $response = json_decode($result, true);
            if (!isset($response['ok']) || $response['ok'] !== true) {
                error_log("Erro ao enviar notificação de teste: " . ($response['description'] ?? 'Erro desconhecido'));
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Exceção ao enviar notificação de teste: " . $e->getMessage());
            return false;
        }
    }
}