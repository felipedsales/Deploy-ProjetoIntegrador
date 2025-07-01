<?php

return [
    'host' => getenv['DB_HOST'] ?? 'localhost',
    'username' => getenv['DB_USERNAME'] ?? 'root',
    'password' => getenv['DB_PASSWORD'] ?? '',
    'database' => getenv['DB_DATABASE'] ?? 'ferraz_conecta',
    'charset' => 'utf8mb4',
    'port' => getenv['DB_PORT'] ?? 3306,
    'type' => getenv['DB_TYPE'] ?? 'mysql'
]; 
