# Ferraz Conecta - Sistema de Vagas

Sistema de vagas e candidatos para Ferraz de Vasconcelos, desenvolvido com arquitetura MVC e Composer.

## 🚀 Deploy no Render

### Pré-requisitos
- Conta no Render (https://render.com)
- Repositório Git (GitHub, GitLab, Bitbucket)
- Projeto subido para o repositório

### Passo a Passo para Deploy

#### 1. Preparar o Repositório
Certifique-se de que todos os arquivos estão commitados e pushados para o repositório:
```bash
git add .
git commit -m "Preparando para deploy no Render"
git push origin main
```

#### 2. Criar Conta no Render
- Acesse https://render.com
- Faça login com sua conta GitHub/GitLab/Bitbucket

#### 3. Criar Novo Web Service
1. Clique em "New +" > "Web Service"
2. Conecte seu repositório
3. Configure o serviço:
   - **Name**: ferraz-conecta
   - **Environment**: Docker
   - **Branch**: main
   - **Root Directory**: (deixe vazio)
   - **Build Command**: (deixe vazio - o Dockerfile cuida)
   - **Start Command**: (deixe vazio - o Dockerfile cuida)

#### 4. Configurar Variáveis de Ambiente
No painel do Render, vá em "Environment" e adicione:

**Variáveis da Aplicação:**
- `APP_URL`: https://seu-app.onrender.com
- `APP_DEBUG`: false

**Variáveis do Banco de Dados:**
- `DB_HOST`: (será preenchido automaticamente)
- `DB_USERNAME`: (será preenchido automaticamente)
- `DB_PASSWORD`: (será preenchido automaticamente)
- `DB_DATABASE`: (será preenchido automaticamente)
- `DB_PORT`: (será preenchido automaticamente)

#### 5. Criar Banco de Dados
1. No painel do Render, clique em "New +" > "PostgreSQL"
2. Configure:
   - **Name**: ferraz-conecta-db
   - **Database**: ferraz_conecta
   - **User**: ferraz_conecta_user
   - **Plan**: Starter (gratuito)

#### 6. Conectar Banco ao App
1. No seu Web Service, vá em "Environment"
2. Clique em "Link Database"
3. Selecione o banco criado
4. As variáveis de ambiente do banco serão preenchidas automaticamente

#### 7. Deploy
1. Clique em "Create Web Service"
2. O Render começará o build automaticamente
3. Aguarde o deploy (pode levar alguns minutos)

### 📁 Estrutura do Projeto

```
Deploy-ProjetoIntegrador/
├── app/
│   ├── Controllers/     # Controladores da aplicação
│   ├── Models/         # Modelos de dados
│   └── Views/          # Views/templates
├── config/             # Configurações
├── public/             # Arquivos públicos (CSS, JS, imagens)
├── uploads/            # Uploads de arquivos
├── vendor/             # Dependências do Composer
├── Dockerfile          # Configuração do Docker
├── composer.json       # Dependências PHP
└── render.yaml         # Configuração do Render
```

### 🔧 Configurações Importantes

#### Dockerfile
- Usa PHP 8.1 com Apache
- Instala todas as extensões necessárias
- Configura o Apache para usar a pasta `public/` como DocumentRoot
- Cria arquivo `.htaccess` para URL rewriting

#### Banco de Dados
- Configurado para usar variáveis de ambiente
- Suporte a MySQL/PostgreSQL
- Conexão automática via Render

### 🐛 Troubleshooting

#### Erro de Build
- Verifique se o `Dockerfile` está na raiz do projeto
- Confirme se o `composer.json` está correto
- Verifique os logs no painel do Render

#### Erro de Conexão com Banco
- Confirme se as variáveis de ambiente estão configuradas
- Verifique se o banco está criado e conectado
- Teste a conexão localmente primeiro

#### Erro 404
- Verifique se o `.htaccess` foi criado corretamente
- Confirme se o Apache está configurado para usar a pasta `public/`

### 📞 Suporte
Para problemas específicos do Render, consulte a documentação oficial: https://render.com/docs

## Estrutura do Projeto

```
ProjetoIntegradorFerraz/
├── app/                          # Código da aplicação
│   ├── Controllers/              # Controladores
│   │   ├── Controller.php        # Classe base dos controladores
│   │   ├── HomeController.php    # Controlador da página inicial
│   │   ├── AuthController.php    # Controlador de autenticação
│   │   ├── VagaController.php    # Controlador de vagas
│   │   ├── CandidatoController.php # Controlador de candidatos
│   │   └── EmpresaController.php # Controlador de empresas
│   ├── Models/                   # Modelos
│   │   ├── Database.php          # Classe de conexão com banco
│   │   ├── Model.php             # Classe base dos modelos
│   │   ├── Vaga.php              # Modelo de vagas
│   │   ├── Candidato.php         # Modelo de candidatos
│   │   └── Empresa.php           # Modelo de empresas
│   ├── Views/                    # Views
│   │   ├── layouts/              # Layouts
│   │   ├── partials/             # Componentes reutilizáveis
│   │   ├── home/                 # Views da página inicial
│   │   ├── auth/                 # Views de autenticação
│   │   ├── vagas/                # Views de vagas
│   │   ├── candidato/            # Views de candidatos
│   │   └── empresa/              # Views de empresas
│   └── Router.php                # Sistema de roteamento
├── config/                       # Configurações
│   └── database.php              # Configuração do banco de dados
├── public/                       # Arquivos públicos
│   ├── css/                      # Arquivos CSS
│   ├── js/                       # Arquivos JavaScript
│   ├── img/                      # Imagens
│   ├── .htaccess                 # Configuração do Apache
│   └── index.php                 # Ponto de entrada da aplicação
├── vendor/                       # Dependências do Composer
├── composer.json                 # Configuração do Composer
└── README.md                     # Este arquivo
```

## Instalação

1. **Clone o repositório:**
   ```bash
   git clone [url-do-repositorio]
   cd ProjetoIntegradorFerraz
   ```

2. **Instale as dependências do Composer:**
   ```bash
   composer install
   ```

3. **Configure o banco de dados:**
   - Edite o arquivo `config/database.php` com suas credenciais
   - Importe o banco de dados (estrutura disponível em `database.sql`)

4. **Configure o servidor web:**
   - Configure o DocumentRoot para a pasta `public/`
   - Certifique-se de que o mod_rewrite está habilitado

## Funcionalidades

### Para Candidatos:
- Cadastro e login
- Visualização de vagas
- Busca por vagas
- Candidatura em vagas
- Acompanhamento de candidaturas
- Edição de perfil

### Para Empresas:
- Cadastro e login
- Criação de vagas
- Edição de vagas
- Visualização de candidatos
- Gerenciamento de candidaturas
- Painel administrativo

## Rotas Principais

- `/` - Página inicial
- `/vagas` - Lista de vagas
- `/vagas/{id}` - Detalhes da vaga
- `/login` - Login de candidatos
- `/login-empresa` - Login de empresas
- `/cadastro` - Cadastro de candidatos
- `/cadastro-empresa` - Cadastro de empresas
- `/minhas-candidaturas` - Candidaturas do usuário
- `/painel-empresa` - Painel da empresa
- `/perfil` - Perfil do usuário

## Tecnologias Utilizadas

- **PHP 7.4+** - Linguagem de programação
- **Composer** - Gerenciador de dependências
- **PDO** - Acesso ao banco de dados
- **Bootstrap 5** - Framework CSS
- **Apache** - Servidor web
- **MySQL** - Banco de dados

## Arquitetura MVC

O projeto segue o padrão MVC (Model-View-Controller):

- **Models**: Responsáveis pela lógica de negócio e acesso aos dados
- **Views**: Responsáveis pela apresentação dos dados
- **Controllers**: Responsáveis por processar as requisições e coordenar Models e Views

## Desenvolvimento

Para contribuir com o projeto:

1. Faça um fork do repositório
2. Crie uma branch para sua feature
3. Implemente suas mudanças seguindo os padrões MVC
4. Teste suas alterações
5. Envie um pull request

## Licença

Este projeto está sob a licença MIT.

---

> Desenvolvido para a cidade de Ferraz de Vasconcelos – conectando pessoas e oportunidades locais.
