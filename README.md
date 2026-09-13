# Ferraz Conecta - Sistema de Vagas
### Integrantes
- Felipe Santos de Sales
- Felipe Schueller Araujo
- Nayara Porto Bastos
- Yuri Soares Sales

Sistema de vagas e candidatos para Ferraz de Vasconcelos, desenvolvido com arquitetura MVC e Composer.

## 🚀 Deploy

O deploy é feito no **Railway**, com **MySQL 8** como banco de dados. Passo a
passo completo, variáveis de ambiente e troubleshooting específico do Railway
estão em [README-RAILWAY.md](README-RAILWAY.md).

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
│   ├── database.php              # Configuração do banco de dados (lê variáveis de ambiente)
│   └── social_auth.php           # Configuração de login social (Google/LinkedIn)
├── database/                     # Scripts SQL (MySQL 8)
│   ├── schema.sql                # Estrutura das tabelas
│   └── seed.sql                  # Dados de exemplo (opcional)
├── public/                       # Arquivos públicos
│   ├── css/                      # Arquivos CSS
│   ├── js/                       # Arquivos JavaScript
│   ├── img/                      # Imagens
│   ├── .htaccess                 # Configuração do Apache
│   └── index.php                 # Ponto de entrada da aplicação
├── vendor/                       # Dependências do Composer
├── .env.example                  # Modelo de variáveis de ambiente
├── composer.json                 # Configuração do Composer
└── README.md                     # Este arquivo
```

## Como rodar localmente

Pré-requisitos: **PHP 8.2**, **Composer** e um servidor **MySQL 8** acessível.

1. **Clone o repositório e instale as dependências:**
   ```bash
   git clone [url-do-repositorio]
   cd Deploy-ProjetoIntegrador
   composer install
   ```

2. **Crie o `.env` a partir do modelo e preencha os valores locais:**
   ```bash
   cp .env.example .env
   ```
   No mínimo, ajuste `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` para o seu
   MySQL local (as demais chaves já têm um valor padrão sensato).

3. **Crie o banco e importe a estrutura (e, opcionalmente, os dados de exemplo):**
   ```bash
   mysql -u root -p --default-character-set=utf8mb4 \
     -e "CREATE DATABASE IF NOT EXISTS ferraz_conecta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p --default-character-set=utf8mb4 ferraz_conecta -e "source database/schema.sql"
   mysql -u root -p --default-character-set=utf8mb4 ferraz_conecta -e "source database/seed.sql"
   ```
   Troque `ferraz_conecta` pelo valor que você usou em `DB_NAME` no `.env`.

4. **Suba o servidor embutido do PHP a partir da raiz do projeto:**
   ```bash
   php -S localhost:8000 -t public
   ```
   Acesse http://localhost:8000.

Para o deploy em Railway, veja [README-RAILWAY.md](README-RAILWAY.md).

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

- **PHP 8.2** - Linguagem de programação
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
