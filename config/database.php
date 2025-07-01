<?php

return [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'database' => $_ENV['DB_DATABASE'] ?? 'ferraz_conecta',
    'charset' => 'utf8mb4',
    'port' => $_ENV['DB_PORT'] ?? 3306,
    'type' => $_ENV['DB_TYPE'] ?? 'mysql'
]; 