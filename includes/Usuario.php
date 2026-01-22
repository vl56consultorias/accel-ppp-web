<?php
/**
 * Classe de Gerenciamento de Usuários
 */

require_once __DIR__ . '/../database/database.php';

class Usuario {
    private $db;
    private $pdo;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }
    
    /**
     * Criar novo usuário
     */
    public function criar($dados) {
        try {
            // Validar dados obrigatórios
            if (empty($dados['nome']) || empty($dados['email']) || empty($dados['login']) || empty($dados['senha'])) {
                throw new Exception('Nome, email, login e senha são obrigatórios');
            }
            
            // Validar email
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            // Verificar se email já existe
            if ($this->emailExiste($dados['email'])) {
                throw new Exception('Email já cadastrado');
            }
            
            // Verificar se login já existe
            if ($this->loginExiste($dados['login'])) {
                throw new Exception('Login já está em uso');
            }
            
            // Verificar se CPF já existe (se fornecido)
            if (!empty($dados['cpf']) && $this->cpfExiste($dados['cpf'])) {
                throw new Exception('CPF já cadastrado');
            }
            
            // Hash da senha
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            // Validar e definir role
            $role = $dados['role'] ?? '001'; // Padrão: leitura
            if (!in_array($role, ['111', '010', '001'])) {
                $role = '001';
            }
            
            $sql = "INSERT INTO usuarios (
                        nome, email, login, senha_hash, role, telefone, cpf, data_nascimento, 
                        genero, endereco, cidade, estado, cep, foto, ativo
                    ) VALUES (
                        :nome, :email, :login, :senha_hash, :role, :telefone, :cpf, :data_nascimento,
                        :genero, :endereco, :cidade, :estado, :cep, :foto, 1
                    )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':login' => $dados['login'],
                ':senha_hash' => $senhaHash,
                ':role' => $role,
                ':telefone' => $dados['telefone'] ?? null,
                ':cpf' => $dados['cpf'] ?? null,
                ':data_nascimento' => $dados['data_nascimento'] ?? null,
                ':genero' => $dados['genero'] ?? null,
                ':endereco' => $dados['endereco'] ?? null,
                ':cidade' => $dados['cidade'] ?? null,
                ':estado' => $dados['estado'] ?? null,
                ':cep' => $dados['cep'] ?? null,
                ':foto' => $dados['foto'] ?? null
            ]);
            
            $id = $this->pdo->lastInsertId();
            
            // Registrar log
            $this->db->registrarLog($id, 'CRIAR', "Usuário {$dados['nome']} criado");
            
            return ['success' => true, 'id' => $id, 'message' => 'Usuário criado com sucesso'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Listar todos os usuários
     */
    public function listar($filtros = []) {
        try {
            $sql = "SELECT * FROM usuarios WHERE 1=1";
            $params = [];
            
            // Filtro por status
            if (isset($filtros['ativo'])) {
                $sql .= " AND ativo = :ativo";
                $params[':ativo'] = $filtros['ativo'];
            }
            
            // Filtro por busca
            if (!empty($filtros['busca'])) {
                $sql .= " AND (nome LIKE :busca OR email LIKE :busca OR cpf LIKE :busca)";
                $params[':busca'] = '%' . $filtros['busca'] . '%';
            }
            
            $sql .= " ORDER BY criado_em DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Buscar usuário por ID
     */
    public function buscarPorId($id) {
        try {
            $sql = "SELECT * FROM usuarios WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar usuário
     */
    public function atualizar($id, $dados) {
        try {
            // Verificar se usuário existe
            $usuario = $this->buscarPorId($id);
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }
            
            // Validar email
            if (!empty($dados['email']) && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            // Verificar se email já existe (exceto o próprio usuário)
            if (!empty($dados['email']) && $dados['email'] !== $usuario['email']) {
                if ($this->emailExiste($dados['email'])) {
                    throw new Exception('Email já cadastrado');
                }
            }
            
            // Verificar se CPF já existe (exceto o próprio usuário)
            if (!empty($dados['cpf']) && $dados['cpf'] !== $usuario['cpf']) {
                if ($this->cpfExiste($dados['cpf'])) {
                    throw new Exception('CPF já cadastrado');
                }
            }
            
            // Validar e definir role se fornecido
            $role = $dados['role'] ?? $usuario['role'];
            if (!in_array($role, ['111', '010', '001'])) {
                $role = $usuario['role'];
            }
            
            $sql = "UPDATE usuarios SET 
                        nome = :nome,
                        email = :email,
                        role = :role,
                        telefone = :telefone,
                        cpf = :cpf,
                        data_nascimento = :data_nascimento,
                        genero = :genero,
                        endereco = :endereco,
                        cidade = :cidade,
                        estado = :estado,
                        cep = :cep,
                        foto = :foto,
                        atualizado_em = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':nome' => $dados['nome'] ?? $usuario['nome'],
                ':email' => $dados['email'] ?? $usuario['email'],
                ':role' => $role,
                ':telefone' => $dados['telefone'] ?? $usuario['telefone'],
                ':cpf' => $dados['cpf'] ?? $usuario['cpf'],
                ':data_nascimento' => $dados['data_nascimento'] ?? $usuario['data_nascimento'],
                ':genero' => $dados['genero'] ?? $usuario['genero'],
                ':endereco' => $dados['endereco'] ?? $usuario['endereco'],
                ':cidade' => $dados['cidade'] ?? $usuario['cidade'],
                ':estado' => $dados['estado'] ?? $usuario['estado'],
                ':cep' => $dados['cep'] ?? $usuario['cep'],
                ':foto' => $dados['foto'] ?? $usuario['foto']
            ]);
            
            // Registrar log
            $this->db->registrarLog($id, 'ATUALIZAR', "Usuário {$dados['nome']} atualizado");
            
            return ['success' => true, 'message' => 'Usuário atualizado com sucesso'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Deletar usuário (exclusão física)
     */
    public function deletar($id) {
        try {
            $usuario = $this->buscarPorId($id);
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }
            
            // Registrar log antes de deletar
            $this->db->registrarLog($id, 'DELETAR', "Usuário {$usuario['nome']} deletado");
            
            $sql = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return ['success' => true, 'message' => 'Usuário deletado com sucesso'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Ativar/Desativar usuário (exclusão lógica)
     */
    public function alterarStatus($id, $ativo) {
        try {
            $usuario = $this->buscarPorId($id);
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }
            
            $sql = "UPDATE usuarios SET ativo = :ativo, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':ativo' => $ativo ? 1 : 0
            ]);
            
            $status = $ativo ? 'ativado' : 'desativado';
            $this->db->registrarLog($id, 'STATUS', "Usuário {$usuario['nome']} {$status}");
            
            return ['success' => true, 'message' => "Usuário {$status} com sucesso"];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar se email já existe
     */
    private function emailExiste($email, $excluirId = null) {
        $sql = "SELECT id FROM usuarios WHERE email = :email";
        if ($excluirId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $params = [':email' => $email];
        if ($excluirId) {
            $params[':id'] = $excluirId;
        }
        
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Verificar se CPF já existe
     */
    private function cpfExiste($cpf, $excluirId = null) {
        $sql = "SELECT id FROM usuarios WHERE cpf = :cpf";
        if ($excluirId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $params = [':cpf' => $cpf];
        if ($excluirId) {
            $params[':id'] = $excluirId;
        }
        
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Verificar se login já existe
     */
    private function loginExiste($login, $excluirId = null) {
        $sql = "SELECT id FROM usuarios WHERE login = :login";
        if ($excluirId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $params = [':login' => $login];
        if ($excluirId) {
            $params[':id'] = $excluirId;
        }
        
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Autenticar usuário
     */
    public function autenticar($login, $senha) {
        try {
            $sql = "SELECT * FROM usuarios WHERE login = :login AND ativo = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':login' => $login]);
            
            $usuario = $stmt->fetch();
            
            if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
                return ['success' => false, 'message' => 'Login ou senha incorretos'];
            }
            
            // Atualizar último acesso
            $sqlUpdate = "UPDATE usuarios SET ultimo_acesso = CURRENT_TIMESTAMP WHERE id = :id";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([':id' => $usuario['id']]);
            
            // Remover senha do retorno
            unset($usuario['senha_hash']);
            
            // Registrar log
            $this->db->registrarLog($usuario['id'], 'LOGIN', "Usuário {$usuario['nome']} realizou login");
            
            return ['success' => true, 'usuario' => $usuario];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Estatísticas
     */
    public function estatisticas() {
        $stats = [];
        
        // Total de usuários
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $stats['total'] = $stmt->fetch()['total'];
        
        // Usuários ativos
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        $stats['ativos'] = $stmt->fetch()['total'];
        
        // Usuários inativos
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 0");
        $stats['inativos'] = $stmt->fetch()['total'];
        
        // Cadastros hoje
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE DATE(criado_em) = DATE('now')");
        $stats['hoje'] = $stmt->fetch()['total'];
        
        return $stats;
    }
    
    /**
     * Verificar se usuário tem permissão de admin (111)
     */
    public function isAdmin($userId) {
        $usuario = $this->buscarPorId($userId);
        return $usuario && $usuario['role'] === '111';
    }
    
    /**
     * Verificar se usuário tem permissão de escrita (010 ou 111)
     */
    public function temPermissaoEscrita($userId) {
        $usuario = $this->buscarPorId($userId);
        return $usuario && ($usuario['role'] === '010' || $usuario['role'] === '111');
    }
    
    /**
     * Verificar se usuário tem permissão de leitura (001, 010 ou 111)
     */
    public function temPermissaoLeitura($userId) {
        $usuario = $this->buscarPorId($userId);
        return $usuario && in_array($usuario['role'], ['001', '010', '111']);
    }
    
    /**
     * Obter nome do role em português
     */
    public function getNomeRole($role) {
        $roles = [
            '111' => 'Administrador',
            '010' => 'Escrita',
            '001' => 'Leitura'
        ];
        return $roles[$role] ?? 'Desconhecido';
    }
}
?>
