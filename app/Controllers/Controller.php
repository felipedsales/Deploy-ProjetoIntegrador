<?php

namespace App\Controllers;

abstract class Controller
{
    protected function render($view, $data = [])
    {
        try {
            // Extrai os dados para variáveis que podem ser usadas na view
            extract($data);
            
            // Inicia o buffer de saída
            ob_start();
            
            // Inclui a view
            $viewPath = __DIR__ . "/../Views/{$view}.php";
            
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                throw new \Exception("View não encontrada: {$viewPath}");
            }
            
            // Captura o conteúdo da view
            $content = ob_get_clean();
            
            if ($content === false) {
                throw new \Exception("Erro ao capturar conteúdo da view");
            }
            
            // Se não for uma view completa (sem HTML), usa o layout
            if (!str_contains($content, '<!DOCTYPE html>')) {
                return $this->renderWithLayout($content, $data);
            }
            
            return $content;
        } catch (\Exception $e) {
            // Em caso de erro, retorna uma mensagem de erro
            return "<h1>Erro na View</h1><p>Erro: " . $e->getMessage() . "</p><p>View: {$view}</p>";
        }
    }

    protected function renderWithLayout($content, $data = [])
    {
        // Extrai os dados para variáveis
        extract($data);
        
        // Inicia o buffer de saída
        ob_start();
        
        // Inclui o layout
        $layoutPath = __DIR__ . "/../Views/layouts/base.php";
        
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            throw new \Exception("Layout não encontrado: {$layoutPath}");
        }
        
        // Retorna o conteúdo do buffer
        return ob_get_clean();
    }

    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function getPost($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    protected function getQuery($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    protected function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    protected function getSession($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    protected function destroySession()
    {
        session_destroy();
    }

    protected function isLoggedIn()
    {
        return $this->getSession('user_id') !== null;
    }

    /**
     * Entrega um arquivo guardado no banco, a quem já teve a permissão conferida.
     *
     * @param array|false $arquivo linha com tipo e conteudo (e nome_arquivo, no download)
     * @param string|null $nome    nome sugerido para download, sem extensão; null
     *                             exibe o arquivo no navegador (logo)
     */
    protected function enviarConteudo($arquivo, $nome = null)
    {
        if (!$arquivo) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }

        header('Content-Type: ' . $arquivo['tipo']);
        header('Content-Length: ' . strlen($arquivo['conteudo']));
        header('X-Content-Type-Options: nosniff');
        if ($nome !== null) {
            $extensao = strtolower(pathinfo($arquivo['nome_arquivo'], PATHINFO_EXTENSION));
            $nome = trim(preg_replace('/[^A-Za-z0-9]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome)), '_');
            header('Content-Disposition: attachment; filename="' . ($nome ?: 'arquivo') . '.' . $extensao . '"');
        } else {
            // A URL do logo muda a cada envio (?v=), então o navegador pode guardar a imagem.
            header('Cache-Control: public, max-age=86400');
        }
        echo $arquivo['conteudo'];
        exit;
    }

    protected function requireAuth()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }
    }

    /**
     * RN21: cada perfil só acessa o que é dele. Exige usuário logado com o
     * perfil informado ('candidato', 'empresa' ou 'administrador').
     */
    protected function requirePerfil($perfil)
    {
        $login = ['candidato' => '/login', 'empresa' => '/login-empresa', 'administrador' => '/admin/login'];

        if (!$this->isLoggedIn()) {
            $this->redirect($login[$perfil] ?? '/login');
        }

        if ($this->getSession('user_type') !== $perfil) {
            $inicio = ['candidato' => '/', 'empresa' => '/painel-empresa', 'administrador' => '/admin'];
            $this->redirect($inicio[$this->getSession('user_type')] ?? '/');
        }
    }

    protected function formatMoney($value)
    {
        if (empty($value) || $value == 0) {
            return 'A combinar';
        }
        
        // Converte para float se for string
        $value = floatval($value);
        
        // Formata no padrão brasileiro
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
} 