<?php

/**
 * Configuração de autenticação social (Google / LinkedIn OAuth).
 *
 * client_id e client_secret vêm de variáveis de ambiente (RNF03) — nada fica
 * hardcoded aqui. redirect_uri é montado a partir de APP_URL, para acompanhar
 * o host de cada ambiente (local, Railway) sem editar este arquivo.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$appUrl = rtrim((string) ferraz_env('APP_URL', 'http://localhost:8000'), '/');

return [
    'google' => [
        'client_id' => ferraz_env('GOOGLE_CLIENT_ID'),
        'client_secret' => ferraz_env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => $appUrl . '/auth/google/callback',
        'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'scope' => 'email profile'
    ],

    'linkedin' => [
        'client_id' => ferraz_env('LINKEDIN_CLIENT_ID'),
        'client_secret' => ferraz_env('LINKEDIN_CLIENT_SECRET'),
        'redirect_uri' => $appUrl . '/auth/linkedin/callback',
        'auth_url' => 'https://www.linkedin.com/oauth/v2/authorization',
        'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
        'userinfo_url' => 'https://api.linkedin.com/v2/me',
        'email_url' => 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))',
        'scope' => 'r_liteprofile r_emailaddress'
    ]
];
