<?php
/**
 * Sistema CRUD - Gerenciamento de Banco de Dados
 * SQLite3 com PDO
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $dbPath;
    
    private function __construct() {
        $this->dbPath = __DIR__ . '/usuarios.db';
        $this->connect();
        $this->createTables();
    }
    
    /**
     * Singleton - Retorna sempre a mesma instância
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Conectar ao banco de dados
     */
    private function connect() {
        try {
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            die('Erro na conexão: ' . $e->getMessage());
        }
    }
    
    /**
     * Criar tabelas se não existirem
     */
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            login VARCHAR(50) NOT NULL UNIQUE,
            senha_hash VARCHAR(255) NOT NULL,
            role VARCHAR(3) DEFAULT '001',
            telefone VARCHAR(20),
            cpf VARCHAR(14) UNIQUE,
            data_nascimento DATE,
            genero VARCHAR(20),
            endereco TEXT,
            cidade VARCHAR(100),
            estado VARCHAR(2),
            cep VARCHAR(10),
            foto VARCHAR(255),
            ativo INTEGER DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso DATETIME
        );
        
        CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER,
            acao VARCHAR(50) NOT NULL,
            descricao TEXT,
            ip_address VARCHAR(45),
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        );
        
        CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);
        CREATE INDEX IF NOT EXISTS idx_usuarios_cpf ON usuarios(cpf);
        CREATE INDEX IF NOT EXISTS idx_usuarios_ativo ON usuarios(ativo);
        CREATE INDEX IF NOT EXISTS idx_logs_usuario ON logs(usuario_id);
        ";
        
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            die('Erro ao criar tabelas: ' . $e->getMessage());
        }
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Registrar log de ação
     */
    public function registrarLog($usuario_id, $acao, $descricao) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        $sql = "INSERT INTO logs (usuario_id, acao, descricao, ip_address) 
                VALUES (:usuario_id, :acao, :descricao, :ip)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':acao' => $acao,
            ':descricao' => $descricao,
            ':ip' => $ip
        ]);
    }
}
?>
