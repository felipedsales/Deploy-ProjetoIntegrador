<?php

namespace App\Models;

abstract class Model
{
    protected $db;
    protected $table;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll()
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->fetchAll($sql);
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function create($data)
    {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $fieldList = implode(', ', $fields);
        
        $sql = "INSERT INTO {$this->table} ({$fieldList}) VALUES ({$placeholders})";
        
        $this->db->query($sql, $data);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        return $this->db->query($sql, $values);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    public function where($conditions, $params = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * RNF03: troca a senha em texto puro pelo hash antes de gravar.
     * Usado pelos Models que têm a coluna `senha`.
     */
    protected function protegerSenha($data)
    {
        if (isset($data['senha']) && $data['senha'] !== '') {
            $data['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        }

        return $data;
    }

    /**
     * RNF03: autentica por e-mail conferindo a senha com password_verify().
     * A senha nunca é comparada em texto puro nem enviada na consulta.
     *
     * @return array|false a conta, sem a coluna `senha`, ou false
     */
    protected function autenticarPorEmail($email, $senha)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $conta = $this->db->fetch($sql, [$email]);

        if (!$conta || !password_verify((string) $senha, (string) $conta['senha'])) {
            return false;
        }

        unset($conta['senha']);
        return $conta;
    }

    /** RN18: conta suspensa pelo administrador não entra no sistema. */
    public function contaSuspensa($conta)
    {
        return ($conta['status'] ?? 'Ativa') === 'Suspensa';
    }

    public function count()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['total'];
    }
} 