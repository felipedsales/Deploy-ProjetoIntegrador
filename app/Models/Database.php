<?php

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        // Carregar variáveis de ambiente se não estiverem carregadas
        if (!function_exists('getenv') || empty($_ENV)) {
            $this->loadEnv();
        }
        
        $config = require __DIR__ . '/../../config/database.php';
        
        try {
            // Detectar se é PostgreSQL ou MySQL baseado na variável de ambiente
            $dbType = $_ENV['DB_TYPE'] ?? 'mysql';
            
            if ($dbType === 'postgresql') {
                $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
            } else {
                // MySQL (padrão)
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    PDO::MYSQL_ATTR_SSL_CA => false,
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true,
                    PDO::ATTR_PERSISTENT => false,
                ];
            }
            
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
            
        } catch (PDOException $e) {
            throw new \Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }

    private function loadEnv()
    {
        // Carregar variáveis de ambiente do arquivo .env se existir
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollback()
    {
        return $this->connection->rollback();
    }

    public function getDbType()
    {
        return $_ENV['DB_TYPE'] ?? 'mysql';
    }
} 