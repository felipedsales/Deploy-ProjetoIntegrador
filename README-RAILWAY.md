# 🚂 Deploy no Railway - Ferraz Conecta (MySQL)

Este projeto está configurado para deploy no Railway com MySQL, oferecendo suporte nativo a PHP e banco MySQL no mesmo projeto.

## 🎯 Por que Railway + MySQL?

- ✅ **Suporte nativo a PHP** com Apache
- ✅ **Banco MySQL incluído** no mesmo projeto
- ✅ **Deploy simples** sem reestruturação
- ✅ **Variáveis de ambiente** automáticas
- ✅ **Logs detalhados** para debug
- ✅ **Escalabilidade** automática
- ✅ **Compatibilidade total** com seu código atual

## 📋 Pré-requisitos

- Conta no Railway (https://railway.app)
- Repositório Git (GitHub, GitLab, Bitbucket)
- Cartão de crédito (para verificação)

## 🚀 Passo a Passo para Deploy

### 1. Preparar o Repositório
```bash
git add .
git commit -m "Configuração para Railway com MySQL"
git push origin main
```

### 2. Criar Conta no Railway
1. Acesse https://railway.app
2. Faça login com GitHub/GitLab/Bitbucket
3. Verifique sua conta (cartão de crédito necessário)

### 3. Criar Novo Projeto
1. Clique em "New Project"
2. Selecione "Deploy from GitHub repo"
3. Escolha seu repositório
4. Clique em "Deploy Now"

### 4. Configurar Banco MySQL
1. No projeto criado, clique em "New"
2. Selecione "Database" > "MySQL"
3. Aguarde a criação do banco
4. Anote as credenciais fornecidas

### 5. Conectar Banco ao App
1. No seu app PHP, vá em "Variables"
2. Clique em "Reference Variable"
3. Selecione o banco MySQL criado
4. As variáveis serão preenchidas automaticamente

### 6. Configurar Variáveis de Ambiente
No painel do Railway, vá em "Variables" do serviço PHP e adicione:

```
APP_URL=https://seu-app.railway.app
APP_DEBUG=false
```

**Banco de dados** — o código lê exatamente estes nomes (ver `config/database.php`):
`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`. O plugin MySQL do Railway
**não** cria variáveis com esses nomes — ele expõe as próprias (`MYSQLHOST`,
`MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, visíveis na aba
"Variables" do serviço MySQL). Referencie-as explicitamente no serviço PHP:

```
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
```

(troque `MySQL` pelo nome que você deu ao serviço de banco, se for diferente).
Sem esse mapeamento, `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS` ficam ausentes e a
aplicação responde "Erro de configuração do servidor. Contate o administrador
do sistema." — a validação está em `config/database.php`.

### 7. Deploy
1. O Railway detectará o Dockerfile automaticamente
2. O build começará automaticamente
3. Aguarde o deploy (pode levar alguns minutos)
4. Seu app estará disponível em `https://seu-app.railway.app`

## 📁 Estrutura do Projeto

```
Deploy-ProjetoIntegrador/
├── app/                   # Backend PHP (MVC)
│   ├── Controllers/       # Controladores
│   ├── Models/           # Modelos
│   └── Views/            # Views
├── config/               # Configurações
├── public/               # Arquivos públicos
├── uploads/              # Uploads
├── vendor/               # Dependências PHP
├── Dockerfile            # Configuração Docker
├── railway.json          # Configuração Railway
└── composer.json         # Dependências PHP
```

## 🔧 Configurações Importantes

### Dockerfile
- Usa PHP 8.2 com Apache
- Instala a extensão `pdo_mysql`
- Configura Apache para usar pasta `public/` (o `.htaccess` de URL rewriting já
  vem versionado em `public/.htaccess`, o Dockerfile não recria esse arquivo)
- O Apache escuta na porta definida pela variável `PORT` (injetada
  automaticamente pelo Railway em tempo de execução), via
  `docker/entrypoint.sh` — não há porta fixa no Dockerfile

### railway.json
- Configura o build usando Dockerfile
- Define healthcheck e restart policy
- Configura timeout e retries

### Banco MySQL
- MySQL 8.0 configurado automaticamente
- Variáveis de ambiente preenchidas automaticamente
- Conexão segura via SSL
- Charset utf8mb4 para suporte completo a Unicode

## 🎨 Frontend

O projeto mantém sua estrutura MVC original:
- **Views PHP** com templates
- **Sistema de rotas** completo
- **Sessões** funcionando
- **Uploads** de arquivos
- **Autenticação** completa

## 🔍 Debug e Troubleshooting

### Verificar Deploy
1. No painel do Railway, vá em "Deployments"
2. Clique no deploy mais recente
3. Verifique os logs de build

### Logs da Aplicação
1. No seu app, vá em "Logs"
2. Verifique logs em tempo real
3. Filtre por erro, info, etc.

### Testar Conexão com Banco
Acesse `GET /health`: responde 200 com `{"success":true,"data":{"status":"ok","banco":"conectado"|"indisponivel"}}`.
É esse endpoint que o `railway.json` usa como `healthcheckPath`. Se
`banco` vier `"indisponivel"` (ou a home responder "Erro de configuração do
servidor. Contate o administrador do sistema."), vá em "Logs" no Railway:
`config/database.php` grava no log exatamente quais variáveis (`DB_HOST`,
`DB_NAME`, `DB_USER`, `DB_PASS`) estão ausentes antes de lançar essa mensagem
genérica.

### Variáveis de Ambiente
1. No app, vá em "Variables"
2. Verifique se todas estão configuradas
3. Faça um novo deploy após alterações

## 💰 Custos

### Plano Gratuito
- **$5 de crédito** mensal
- **Aproximadamente 500 horas** de uso
- **1GB de RAM** por serviço
- **1GB de armazenamento**

### Plano Pago
- **$20/mês** para uso ilimitado
- **Mais recursos** e melhor performance
- **Suporte prioritário**

## 🚀 Vantagens do Railway + MySQL

### Facilidade
- **Deploy automático** a cada push
- **Configuração simples** via interface
- **Rollback fácil** para versões anteriores
- **Preview deploys** para branches

### Performance
- **Infraestrutura otimizada** para PHP + MySQL
- **Escalabilidade automática**
- **Load balancing** automático
- **CDN global** incluído

### Segurança
- **HTTPS automático**
- **Variáveis de ambiente** seguras
- **Isolamento** entre serviços
- **Backup automático** do banco

## 🔧 Comandos Úteis

### Railway CLI (Opcional)
```bash
# Instalar CLI
npm install -g @railway/cli

# Login
railway login

# Deploy manual
railway up

# Ver logs
railway logs

# Abrir no navegador
railway open
```

## 📞 Suporte

- **Documentação Railway**: https://docs.railway.app
- **Comunidade**: https://community.railway.app
- **Discord**: https://discord.gg/railway

## 🎯 Próximos Passos

1. **Deploy no Railway** seguindo o passo a passo
2. **Configurar domínio personalizado** (opcional)
3. **Configurar CI/CD** para deploy automático
4. **Monitorar performance** e logs
5. **Configurar backup** do banco de dados

---

**Desenvolvido para Ferraz de Vasconcelos** 🏢 