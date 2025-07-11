<?php
require_once 'config/database.php';

class AdminSettings {
    private $db;
    
    /**
     * Construtor da classe
     * Inicializa a conexão com o banco de dados
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->createSettingsTable();
    }
    
    /**
     * Criar tabela de configurações se não existir
     */
    private function createSettingsTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS admin_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_name VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ";
        
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Erro ao criar tabela de configurações: " . $e->getMessage());
        }
    }
    
    /**
     * Obter o valor de uma configuração
     * 
     * @param string $name Nome da configuração
     * @param mixed $default Valor padrão se a configuração não existir
     * @return mixed Valor da configuração ou valor padrão
     */
    public function getSetting($name, $default = null) {
        try {
            $stmt = $this->db->prepare("
                SELECT setting_value 
                FROM admin_settings 
                WHERE setting_name = ?
            ");
            $stmt->execute([$name]);
            $result = $stmt->fetch();
            
            return $result ? $result['setting_value'] : $default;
        } catch (PDOException $e) {
            error_log("Erro ao obter configuração: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Definir ou atualizar o valor de uma configuração
     * 
     * @param string $name Nome da configuração
     * @param mixed $value Valor da configuração
     * @return bool Sucesso da operação
     */
    public function setSetting($name, $value) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO admin_settings (setting_name, setting_value, created_at) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$name, $value]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao definir configuração: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter todas as configurações
     * 
     * @return array Array associativo com todas as configurações
     */
    public function getAllSettings() {
        try {
            $stmt = $this->db->prepare("
                SELECT setting_name, setting_value 
                FROM admin_settings
            ");
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_name']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (PDOException $e) {
            error_log("Erro ao obter todas as configurações: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Excluir uma configuração
     * 
     * @param string $name Nome da configuração
     * @return bool Sucesso da operação
     */
    public function deleteSetting($name) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM admin_settings 
                WHERE setting_name = ?
            ");
            $stmt->execute([$name]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao excluir configuração: " . $e->getMessage());
            return false;
        }
    }
}
?>