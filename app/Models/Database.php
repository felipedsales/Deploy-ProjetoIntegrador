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
        // As variáveis de ambiente já foram carregadas uma única vez em
        // app/bootstrap.php (via vlucas/phpdotenv). Aqui só lemos a config.
        $config = require __DIR__ . '/../../config/database.php';

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::ATTR_PERSISTENT => false,
            ];

            // RNF03 exige comunicação criptografada com o banco. Em produção
            // (Railway) isso é atendido pela rede privada entre os serviços;
            // se o MySQL de destino exigir TLS explícito, aponte DB_SSL_CA
            // para o certificado da CA. Sem essa variável — caso do MySQL
            // local — conecta sem TLS: ativar MYSQL_ATTR_SSL_CA sem um
            // certificado válido faz o servidor derrubar a conexão (era a
            // causa do "SQLSTATE[HY000] [2006] MySQL server has gone away").
            $sslCa = $config['ssl_ca'] ?? null;
            if ($sslCa !== null && $sslCa !== '') {
                if (is_file($sslCa)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
                } else {
                    error_log("[Ferraz Conecta] DB_SSL_CA definida mas o arquivo nao existe: {$sslCa}. Conectando sem TLS.");
                }
            }

            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);

        } catch (PDOException $e) {
            throw new \Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
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
}
