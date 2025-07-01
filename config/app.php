<?php

return [
    'name' => 'Ferraz Conecta',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    
    // Configurações de upload
    'upload_path' => __DIR__ . '/../uploads/',
    'max_upload_size' => 5 * 1024 * 1024, // 5MB
    
    // Configurações de sessão
    'session_lifetime' => 7200, // 2 horas
    
    // Configurações de segurança
    'csrf_token_name' => 'csrf_token',
    'password_min_length' => 6,
]; 