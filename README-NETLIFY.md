# 🚀 Deploy no Netlify - Ferraz Conecta

Este projeto foi configurado para funcionar no Netlify usando uma arquitetura moderna com frontend estático e backend PHP via Netlify Functions.

## 📋 Pré-requisitos

- Conta no Netlify (https://netlify.com)
- Repositório Git (GitHub, GitLab, Bitbucket)
- Banco de dados externo (PlanetScale, Supabase, Railway, etc.)

## 🏗️ Arquitetura

### Frontend (Netlify)
- **Tecnologia**: HTML5, CSS3, JavaScript (Vanilla)
- **Framework CSS**: Bootstrap 5
- **Localização**: Pasta `public/`
- **Deploy**: Site estático no Netlify

### Backend (Netlify Functions)
- **Tecnologia**: PHP 8.1
- **Localização**: `netlify/functions/api.php`
- **API**: RESTful com endpoints JSON
- **Banco**: PostgreSQL/MySQL externo

## 🚀 Passo a Passo para Deploy

### 1. Preparar o Repositório
```bash
git add .
git commit -m "Configuração para Netlify"
git push origin main
```

### 2. Criar Conta no Netlify
1. Acesse https://netlify.com
2. Faça login com GitHub/GitLab/Bitbucket
3. Clique em "New site from Git"

### 3. Conectar Repositório
1. Selecione seu repositório
2. Configure o build:
   - **Build command**: `composer install --no-dev --optimize-autoloader`
   - **Publish directory**: `public`
   - **Functions directory**: `netlify/functions`

### 4. Configurar Variáveis de Ambiente
No painel do Netlify, vá em **Site settings > Environment variables**:

```
DB_HOST=sua-host-do-banco
DB_USERNAME=seu-usuario
DB_PASSWORD=sua-senha
DB_DATABASE=ferraz_conecta
DB_PORT=5432
DB_TYPE=postgresql
APP_URL=https://seu-site.netlify.app
APP_DEBUG=false
```

### 5. Configurar Banco de Dados

#### Opção A: PlanetScale (MySQL)
1. Crie conta em https://planetscale.com
2. Crie um novo banco de dados
3. Use as credenciais fornecidas

#### Opção B: Supabase (PostgreSQL)
1. Crie conta em https://supabase.com
2. Crie um novo projeto
3. Use as credenciais fornecidas

#### Opção C: Railway
1. Crie conta em https://railway.app
2. Crie um novo projeto PostgreSQL
3. Use as credenciais fornecidas

### 6. Deploy
1. Clique em "Deploy site"
2. Aguarde o build (pode levar alguns minutos)
3. Seu site estará disponível em `https://seu-site.netlify.app`

## 📁 Estrutura do Projeto

```
Deploy-ProjetoIntegrador/
├── public/                 # Frontend (deployado no Netlify)
│   ├── index.html         # Página principal
│   ├── css/               # Estilos
│   ├── js/                # JavaScript
│   └── img/               # Imagens
├── netlify/
│   └── functions/
│       └── api.php        # API PHP (Netlify Functions)
├── app/                   # Backend PHP
│   ├── Controllers/       # Controladores
│   ├── Models/           # Modelos
│   └── Views/            # Views (não usado no frontend)
├── config/               # Configurações
├── vendor/               # Dependências PHP
├── netlify.toml          # Configuração do Netlify
└── composer.json         # Dependências PHP
```

## 🔧 Configurações Importantes

### netlify.toml
- Configura o build e deploy
- Define redirecionamentos para API
- Configura funções Netlify

### API Endpoints
A API está disponível em `/.netlify/functions/api`:

- `GET /api/vagas` - Listar vagas
- `GET /api/vagas/{id}` - Ver vaga específica
- `POST /api/vagas` - Criar vaga
- `PUT /api/vagas/{id}` - Editar vaga
- `DELETE /api/vagas/{id}` - Excluir vaga
- `GET /api/empresas` - Listar empresas
- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Registro

### Frontend JavaScript
O arquivo `public/js/app.js` contém:
- Funções para interagir com a API
- Gerenciamento de estado do usuário
- Interface responsiva
- Modais de login/registro

## 🎨 Design e UX

### Características
- **Responsivo**: Funciona em todos os dispositivos
- **Moderno**: Design limpo e profissional
- **Acessível**: Segue boas práticas de acessibilidade
- **Performance**: Carregamento rápido e otimizado

### Tecnologias Frontend
- **Bootstrap 5**: Framework CSS
- **Font Awesome**: Ícones
- **Vanilla JavaScript**: Sem dependências pesadas
- **CSS Custom Properties**: Variáveis CSS modernas

## 🔍 Debug e Troubleshooting

### Verificar Deploy
1. Acesse o painel do Netlify
2. Vá em "Deploys"
3. Clique no deploy mais recente
4. Verifique os logs de build

### Testar API
```bash
curl https://seu-site.netlify.app/.netlify/functions/api/vagas
```

### Variáveis de Ambiente
No painel do Netlify:
1. Site settings > Environment variables
2. Verifique se todas as variáveis estão configuradas
3. Faça um novo deploy após alterações

### Logs de Erro
- **Build errors**: Verifique os logs no painel do Netlify
- **Runtime errors**: Verifique o console do navegador
- **API errors**: Verifique os logs das funções Netlify

## 🚀 Vantagens do Netlify

### Performance
- **CDN Global**: Distribuição mundial
- **Cache Inteligente**: Carregamento rápido
- **Compressão**: Arquivos otimizados

### Segurança
- **HTTPS Automático**: Certificado SSL gratuito
- **Headers de Segurança**: Configurados automaticamente
- **Proteção DDoS**: Incluída no plano

### Facilidade
- **Deploy Automático**: A cada push no Git
- **Rollback Fácil**: Voltar para versões anteriores
- **Preview Deploys**: Testar antes de publicar

## 📞 Suporte

- **Documentação Netlify**: https://docs.netlify.com
- **Netlify Functions**: https://docs.netlify.com/functions/overview/
- **Comunidade**: https://community.netlify.com

---

**Desenvolvido para Ferraz de Vasconcelos** 🏢 