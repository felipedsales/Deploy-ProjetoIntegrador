<?php
// Arquivo de debug para verificar configurações
echo "<h1>Debug - Configurações do Sistema</h1>";

echo "<h2>Variáveis de Ambiente:</h2>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h2>Variáveis do Servidor:</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>Configuração do Banco:</h2>";
$config = require __DIR__ . '/../config/database.php';
echo "<pre>";
print_r($config);
echo "</pre>";

echo "<h2>Teste de Conexão:</h2>";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $db = \App\Models\Database::getInstance();
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida com sucesso!</p>";
    
    // Testar uma query simples
    $result = $db->fetch("SELECT 1 as test");
    echo "<p>Query de teste: " . print_r($result, true) . "</p>";
    
    // Verificar versão do MySQL
    $version = $db->fetch("SELECT VERSION() as version");
    echo "<p>Versão do MySQL: " . $version['version'] . "</p>";
    
    // Verificar charset
    $charset = $db->fetch("SELECT @@character_set_database as charset");
    echo "<p>Charset do banco: " . $charset['charset'] . "</p>";
    
    // Verificar tabelas existentes
    $tables = $db->fetchAll("SHOW TABLES");
    echo "<h3>Tabelas no banco:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li>" . $tableName . "</li>";
    }
    echo "</ul>";
    
    // Verificar se as tabelas principais existem
    $mainTables = ['candidatos', 'empresas', 'vagas', 'candidaturas'];
    echo "<h3>Status das tabelas principais:</h3>";
    foreach ($mainTables as $table) {
        $exists = $db->fetch("SHOW TABLES LIKE '$table'");
        if ($exists) {
            echo "<p style='color: green;'>✅ Tabela '$table' existe</p>";
        } else {
            echo "<p style='color: red;'>❌ Tabela '$table' não existe</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

echo "<h2>Extensões PHP Instaladas:</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

echo "<h2>Extensões MySQL:</h2>";
if (extension_loaded('pdo_mysql')) {
    echo "<p style='color: green;'>✅ PDO MySQL está instalado</p>";
} else {
    echo "<p style='color: red;'>❌ PDO MySQL não está instalado</p>";
}

if (extension_loaded('mysqli')) {
    echo "<p style='color: green;'>✅ MySQLi está instalado</p>";
} else {
    echo "<p style='color: red;'>❌ MySQLi não está instalado</p>";
}

echo "<h2>Informações do PHP:</h2>";
echo "<p>Versão do PHP: " . PHP_VERSION . "</p>";
echo "<p>SAPI: " . php_sapi_name() . "</p>";
echo "<p>Timezone: " . date_default_timezone_get() . "</p>";
?> 