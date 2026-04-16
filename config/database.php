<?php

return [
    'host' => getenv('DB_HOST') ?? 'localhost',
    'username' => getenv('DB_USERNAME') ?? 'root',
    'password' => getenv('DB_PASSWORD') ?? '',
    'database' => getenv('DB_DATABASE') ?? 'ferraz_conecta',
    'charset' => 'utf8',
    'port' => getenv('DB_PORT') ?? 5432,
    'type' => getenv('DB_TYPE') ?? 'pgsql'
];
