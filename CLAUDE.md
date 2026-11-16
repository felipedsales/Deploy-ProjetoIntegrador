# CLAUDE.md — Ferraz Conecta

Guia para qualquer pessoa (ou IA) que for escrever código neste repositório.
Leia por completo antes de tocar em qualquer arquivo.

---

## ⚠️ REGRA Nº 1 — O código DEVE coincidir exatamente com a documentação

Este é um projeto acadêmico da disciplina de **Laboratório de Engenharia de Software**
(Fatec). A aderência entre código e documentação é **critério de aprovação**. A documentação
oficial é composta por:

- Documento de Visão
- **Documento de Requisitos (RF / RNF / RN)** — lista canônica versionada em
  [`docs/REQUISITOS.md`](docs/REQUISITOS.md), com a matriz de rastreabilidade
- Especificação de Casos de Uso (UC)
- MER — Modelo Entidade-Relacionamento
- Diagrama de Classe
- Diagrama de Sequência

### Antes de escrever qualquer linha de código

1. **Identifique o requisito.** Diga explicitamente qual **RF/RNF/RN** e qual **caso de uso
   (UC-xxx)** está sendo implementado. Se não conseguir apontar, pare — não há o que
   codificar ainda.
2. **Confira o banco.** Os nomes de **tabelas e colunas** usados no código têm de bater
   **exatamente** com o [`database/schema.sql`](database/schema.sql) e com o MER.
3. **Confira as classes.** Os nomes de **classes, atributos e métodos** têm de bater
   **exatamente** com o Diagrama de Classe; a ordem das chamadas tem de bater com o
   Diagrama de Sequência.
4. **Escreva em português.** Classes, métodos, variáveis, comentários, mensagens, commits.

### Se a implementação precisar divergir da documentação

**PARE e avise a equipe.** A divergência é decidida em conjunto e **o documento é
atualizado na mesma tarefa** (Visão / Requisitos / UC / MER / Diagramas, conforme o caso).
Nunca "resolva" a divergência só no código: isso quebra o critério de aprovação. Código
novo e atualização da documentação entram no **mesmo commit / mesma tarefa do Trello**.

---

## O que é o projeto

**Ferraz Conecta** é um **balcão online de empregos** da cidade de **Ferraz de Vasconcelos**.
Conecta candidatos que moram na região a empresas locais que estão contratando.

- **Cliente:** Prefeitura de Ferraz de Vasconcelos.
- **Natureza:** projeto acadêmico (Fatec — Laboratório de Engenharia de Software). Não é
  produto comercial; o objetivo é entregar software que corresponda à documentação da
  disciplina.
- **Usuários finais:** munícipes candidatos, empresas da região e a equipe da Prefeitura
  (perfil administrador).

---

## Arquitetura

| Camada | Stack |
|---|---|
| **Back-end web** | PHP **8.2**, arquitetura **MVC em camadas**, `DocumentRoot` em `public/` |
| **App mobile** *(pós-v1)* | **React Native / Expo** em **TypeScript** |
| **API** *(pós-v1)* | REST em **`/api/v1`**, autenticação por **token Bearer** persistido na tabela `sessoes` |
| **Banco** | **MySQL 8**, acesso **exclusivamente via PDO com prepared statements** |
| **Hospedagem** | **Railway** |

- O app mobile **consome apenas a API REST**; não fala com o banco.
- Todo endpoint autenticado valida o token Bearer contra `sessoes` (token existente,
  não expirado — `expira_em`).
- Nada de SQL montado por concatenação. **PDO + prepared statements sempre.**
- **Fuso horário:** PHP e a sessão do MySQL usam `America/Sao_Paulo` (`app/bootstrap.php`
  e `App\Models\Database`), para que prazos e horários não sigam o UTC do container.

---

## Padrões de código

- **Orientação a objetos e separação de camadas.**
  - **View:** só apresentação. **Nenhuma linha de SQL** na View.
  - **Controller:** só orquestra (recebe request, chama o Model/serviço, devolve
    response/render). **Nenhuma regra de negócio** no Controller.
  - **Model / camada de negócio:** onde vivem as regras (RN) e o acesso a dados.
- **Regra de negócio só no back-end.** Nunca duplicada no app mobile. O mobile valida
  formulário por usabilidade, mas a decisão é sempre do servidor.
- **Senhas:** `password_hash()` para gravar, `password_verify()` para conferir. Nunca
  comparar senha em texto puro, nunca gravar hash caseiro.
- **Zero credenciais no código.** Tudo por variável de ambiente (`.env` local, painel do
  Railway em produção). `.env` e chaves ficam fora do versionamento e fora do contexto
  do Claude (ver `.claudeignore`).
- **Tudo em português** — inclusive nomes de tabela, coluna, classe, método e rota.

---

## Modelo de dados (a partir de `database/schema.sql`)

### Tabelas

| Tabela | Colunas | Chaves e restrições |
|---|---|---|
| **candidatos** | `id`, `nome`, `email`, `senha`, `telefone`, `cpf`, `data_nascimento`, `endereco`, `endereco_campos`, `status`, `email_confirmado`, `created_at`, `updated_at` | PK `id`; **UNIQUE** `email`; **UNIQUE** `cpf`; `status` = conta ativa ou suspensa (RN18); `email_confirmado` (RN22) |
| **empresas** | `id`, `nome`, `responsavel`, `email`, `senha`, `cnpj`, `telefone`, `endereco`, `descricao`, `endereco_campos`, `setor`, `website`, `status`, `email_confirmado`, `created_at`, `updated_at` | PK `id`; **UNIQUE** `email`; **UNIQUE** `cnpj`; `status` = conta ativa ou suspensa (RN18); `email_confirmado` (RN22) |
| **vagas** | `id`, `empresa_id`, `titulo`, `descricao`, `requisitos`, `salario`, `tipo_contrato`, `modalidade`, `localizacao`, `categoria_id`, `nivel_experiencia`, `beneficios`, `status`, `data_expiracao`, `visualizacoes`, `created_at`, `updated_at` | PK `id`; **FK** `empresa_id` → `empresas(id)` `ON DELETE CASCADE`; **FK** `categoria_id` → `categorias(id)` (sem `ON DELETE`: categoria em uso não é excluída — UC13); índices em `empresa_id`, `status` e `data_expiracao`; **`data_expiracao` implementa a RN14** |
| **candidaturas** | `id`, `vaga_id`, `candidato_id`, `status`, `data_candidatura`, `observacoes` | PK `id`; **FK** `vaga_id` → `vagas(id)` `CASCADE`; **FK** `candidato_id` → `candidatos(id)` `CASCADE`; **`UNIQUE (vaga_id, candidato_id)` → implementa a RN04** (um candidato não se candidata duas vezes à mesma vaga) |
| **sessoes** | `id`, `usuario_id`, `tipo_usuario`, `token`, `expira_em`, `created_at` | PK `id`; **UNIQUE** `token`; guarda o **token Bearer** da API; índices em `token` e `expira_em` |
| **denuncias** | `id`, `vaga_id`, `candidato_id`, `motivo`, `descricao`, `status`, `created_at` | PK `id`; **FK** `vaga_id` → `vagas(id)` `ON DELETE SET NULL`; **FK** `candidato_id` → `candidatos(id)` `ON DELETE SET NULL` |
| **curriculos** | `id`, `candidato_id`, `nome_arquivo`, `tipo`, `tamanho`, `conteudo`, `texto`, `data_envio` | PK `id`; **FK** `candidato_id` → `candidatos(id)` `CASCADE`; **UNIQUE** `candidato_id` (um currículo por candidato); `conteudo` guarda o arquivo (RF08, RN02) |
| **logos_empresas** | `id`, `empresa_id`, `tipo`, `tamanho`, `conteudo`, `data_envio` | PK `id`; **FK** `empresa_id` → `empresas(id)` `CASCADE`; **UNIQUE** `empresa_id` (um logo por empresa); `conteudo` guarda a imagem (RF07) |
| **categorias** | `id`, `nome`, `created_at` | PK `id`; **UNIQUE** `nome` (RF12, UC13) |
| **vagas_historico** | `id`, `vaga_id`, `alteracoes`, `created_at` | PK `id`; **FK** `vaga_id` → `vagas(id)` `CASCADE`; `alteracoes` é JSON `{campo: {de, para}}` (RF11) |
| **cartas_apresentacao** | `id`, `candidato_id`, `vaga_id`, `nome_arquivo`, `tipo`, `tamanho`, `conteudo`, `texto`, `data_envio` | PK `id`; **FK** `candidato_id` → `candidatos(id)` `CASCADE`; **FK** `vaga_id` → `vagas(id)` `CASCADE`; `vaga_id` nulo = carta padrão do perfil (RF09) |
| **notificacoes** | `id`, `tipo_usuario`, `usuario_id`, `titulo`, `mensagem`, `link`, `lida`, `created_at` | PK `id`; índice em `(tipo_usuario, usuario_id, lida)` (RF14) |
| **mensagens** | `id`, `candidatura_id`, `remetente`, `texto`, `created_at` | PK `id`; **FK** `candidatura_id` → `candidaturas(id)` `CASCADE` (RF24) |
| **avaliacoes** | `id`, `candidatura_id`, `autor`, `nota`, `comentario`, `created_at` | PK `id`; **FK** `candidatura_id` → `candidaturas(id)` `CASCADE`; **UNIQUE** `(candidatura_id, autor)` (RF15) |
| **tickets_suporte** | `id`, `tipo_usuario`, `usuario_id`, `assunto`, `mensagem`, `status`, `resposta`, `administrador_id`, `respondido_em`, `created_at` | PK `id`; **FK** `administrador_id` → `administradores(id)` `SET NULL` (RF17, RN15) |
| **tokens_email** | `id`, `tipo_usuario`, `usuario_id`, `finalidade`, `token_hash`, `tentativas`, `expira_em`, `usado_em`, `created_at` | PK `id`; índices em `token_hash` e `(tipo_usuario, usuario_id, finalidade)`; guarda só o hash do código (RN22, RF05, RNF09) |
| **administradores** | `id`, `nome`, `email`, `senha`, `created_at`, `updated_at` | PK `id`; **UNIQUE** `email` (RF03, RF16) |
| **favoritos** | `id`, `candidato_id`, `vaga_id`, `created_at` | PK `id`; **FK** `candidato_id` → `candidatos(id)` `CASCADE`; **FK** `vaga_id` → `vagas(id)` `CASCADE`; **`UNIQUE (candidato_id, vaga_id)`** (RF19) |
| **entrevistas** | `id`, `vaga_id`, `candidato_id`, `data_hora`, `local`, `status`, `created_at`, `updated_at` | PK `id`; **FK** `vaga_id` → `vagas(id)` `CASCADE`; **FK** `candidato_id` → `candidatos(id)` `CASCADE`; índice em `(candidato_id, data_hora)` (RF26) |
| **logs_auditoria** | `id`, `administrador_id`, `acao`, `entidade`, `entidade_id`, `detalhes`, `created_at` | PK `id`; **FK** `administrador_id` → `administradores(id)` `ON DELETE SET NULL` (RN07) |

### Domínios (ENUM no schema)

| Enum | Valores |
|---|---|
| `tipo_contrato` (vagas) | `CLT`, `PJ`, `Freelance`, `Estágio` |
| `modalidade` (vagas) | `Presencial`, `Remoto`, `Híbrido` |
| `nivel_experiencia` (vagas) | `Sem experiência`, `Júnior`, `Pleno`, `Sênior` |
| `status` (candidatos, empresas) | `Ativa`, `Suspensa` |
| `status` (vagas) | `Ativa`, `Inativa`, `Pausada` |
| `status` (candidaturas) | `Pendente`, `Em análise`, `Aprovada`, `Rejeitada`, `Contratada` |
| `tipo_usuario` (sessoes) | `candidato`, `empresa`, `administrador` |
| `status` (entrevistas) | `Agendada`, `Cancelada`, `Realizada` |
| `status` (denuncias) | `Pendente`, `Em análise`, `Resolvida`, `Descartada` |

> **RN04 em destaque:** a restrição `UNIQUE (vaga_id, candidato_id)` na tabela
> `candidaturas` é a implementação física da RN04. O back-end também deve verificar antes
> de inserir e responder **HTTP 409** quando a candidatura já existir — não confiar só no
> erro do banco.

---

## Escopo da v1 e lacunas conhecidas

A v1 entrega **somente a plataforma web** e os RFs marcados como **v1** em
[`docs/REQUISITOS.md`](docs/REQUISITOS.md). **App mobile e API REST `/api/v1` ficam para
depois da v1** — ver [`docs/IMPLEMENTACOES_FUTURAS.md`](docs/IMPLEMENTACOES_FUTURAS.md).
Desde 2026-11-13 os itens *Futuro* estão sendo implementados em lotes, na ordem do plano
daquele documento; o MER e o Diagrama de Classe serão refeitos ao final.

A estrutura de banco da v1 já existe no `schema.sql` (`administradores`, `favoritos`,
`entrevistas`, `logs_auditoria`, `curriculos`, `logos_empresas`, `vagas.data_expiracao`). Banco já existente
é atualizado no start do container por `database/migrar.php`, que aplica o que faltar
para chegar ao `schema.sql`. **Toda mudança de estrutura entra no `schema.sql` e, se
alterar tabela existente, também no `migrar.php`.**

Lacunas que continuam abertas:

| Lacuna | Atende |
|---|---|
| **MER e Diagrama de Classe não refletem o `schema.sql`** (usam `Usuário` como supertipo e `Notificação` como entidade, a entidade `Currículo` tem atributos diferentes da tabela `curriculos`, e não têm `favoritos` nem `logs_auditoria`) | Regra nº 1 |
| colunas `provider` / `provider_id` em `candidatos` (usadas em `SocialAuth.php`) | RF20 — *Futuro*; rotas e botões de login externo estão desligados na v1 |
| tabela `push_tokens` | RF14 — *Futuro* |
| multifator por app autenticador e para candidato / empresa | RNF09 — *Futuro* (hoje: código por e-mail, só administrador) |

> **Regra para fechar cada lacuna:** a **migration**, a atualização do **MER** e a do
> **Diagrama de Classe** são **a mesma tarefa** — não se faz uma sem as outras. Depois,
> atualizar também `docs/REQUISITOS.md` / UC se o texto tiver mudado.

---

## Regras de negócio, RF e RNF

A **fonte da verdade** é [`docs/REQUISITOS.md`](docs/REQUISITOS.md): RF01–RF26,
RNF01–RNF13, RN01–RN22 e a matriz de rastreabilidade. **Não copiar os enunciados para
cá** — toda tarefa, card do Trello e mensagem de commit cita os códigos de lá, e qualquer
dúvida se resolve lendo o documento.

Regras com **reflexo direto no banco**, que quem mexe em persistência precisa ter em mente:

- **RN01 — cadastro único de usuário:** cada e-mail vinculado a **uma** conta ativa;
  `UNIQUE` em `candidatos.email` e `empresas.email`.
- **RN04 — candidatura única por vaga:** `UNIQUE (vaga_id, candidato_id)` em
  `candidaturas` (ver o box **"RN04 em destaque"** na seção *Modelo de dados*).
- **RN12 — histórico de candidaturas:** `candidaturas.data_candidatura` e
  `candidaturas.status` registram data/hora e situação de cada ação.
- **RF08 / RF07 — arquivos no banco:** currículo em `curriculos.conteudo` e logo em
  `logos_empresas.conteudo`, não no disco, para não se perderem a cada deploy. O currículo
  só sai por rota que confere a permissão. Nunca selecionar `conteudo` em listagens — só
  na entrega do arquivo. `App\Models\Arquivo` valida tipo e tamanho.
- **RF13 — candidatura:** exige currículo anexado ao perfil (`Candidato::temCurriculo`).
- **RN16 — validação de formato** (`App\Models\Validador`)**:** CPF, CNPJ, e-mail e telefone validados na **camada de
  negócio do back-end**, não só no formulário.

- **RN14 — visibilidade de vagas ativas:** só se exibe vaga com `vagas.data_expiracao`
  válida; a validade mínima de 7 dias é conferida no back-end ao cadastrar e editar.
- **RN07 — ações administrativas registradas:** toda ação de administrador grava uma
  linha em `logs_auditoria`, na mesma transação da alteração (`Administrador`).
- **RF26 — entrevistas:** conflito de horário conferido em `Entrevista::agendamento`
  (empresa ou candidato com outra entrevista `Agendada` a menos de 60 minutos); a
  entrevista agendada é cancelada quando a candidatura deixa de existir.
- **RF14 — notificações:** quem gera o evento chama `Notificacao::criar()` no Model
  (status da candidatura, entrevista, mensagem, suporte, vaga da mesma área — RN06).
- **RF24 / RF15 — mensagens e avaliação:** sempre a partir de
  `Candidatura::buscarParaUsuario()`, que só devolve a candidatura a quem participa dela.
  Mensagens só com candidatura `Aprovada` ou `Contratada`; avaliação só pós-processo.
- **RN18 — suspensão de contas:** só o administrador muda `candidatos.status` /
  `empresas.status`; conta `Suspensa` não faz login.
- **RN22 / RN17 / RF05 / RNF09 — acesso por e-mail** (`App\Models\Acesso`, `TokenEmail`,
  `Email`)**:** conta de candidato e empresa só entra com `email_confirmado = 1`; link de
  recuperação vale 15 minutos; o administrador entra com senha + código de 6 dígitos. O
  envio é pela API HTTP do Brevo (`BREVO_API_KEY`, `BREVO_REMETENTE_EMAIL`). **Sem essas
  variáveis nada disso é exigido**: a conta nasce confirmada e o administrador entra só com
  a senha. `tokens_email` guarda só o hash do código.
- **RF25 / RN13 — ranqueamento por regras** (`App\Models\Ranqueamento`, `ExtratorTexto`)**:**
  o texto do currículo e da carta é extraído no envio e guardado em `curriculos.texto` /
  `cartas_apresentacao.texto` (nulo = ainda não extraído; vazio = sem texto legível). A
  aderência compara só os requisitos da vaga com esse texto — **nenhum dado pessoal entra
  na conta** — e nunca muda o status de uma candidatura. PDF depende do `pdftotext`
  (pacote `poppler-utils` no `Dockerfile`).
- **RN21 — permissões por perfil:** todo controller de área restrita começa com
  `requirePerfil('candidato' | 'empresa' | 'administrador')`.

Regras ainda não implementadas: ver **Escopo da v1 e lacunas conhecidas**.

---

## Convenções da API REST (`/api/v1`)

### Envelope de resposta (sempre)

```json
{
  "success": true,
  "data": { },
  "message": "texto para exibição, em português"
}
```

- `success`: booleano.
- `data`: objeto ou lista com o payload; `null` quando não há corpo.
- `message`: string; em erro, descreve o problema para o usuário.
- Em erro de validação, incluir `data.errors` com o mapa `campo → mensagem`.

### Códigos HTTP

| Código | Uso |
|---|---|
| **200 OK** | Consulta ou atualização bem-sucedida. |
| **201 Created** | Recurso criado (cadastro, nova vaga, nova candidatura). |
| **204 No Content** | Sucesso sem corpo (ex.: exclusão). |
| **400 Bad Request** | Requisição malformada (JSON inválido, parâmetro ausente). |
| **401 Unauthorized** | Token Bearer ausente, inválido ou expirado (`sessoes`). |
| **403 Forbidden** | Autenticado, mas sem permissão / não é dono do recurso (**RN21** — permissões por perfil). |
| **404 Not Found** | Recurso inexistente. |
| **409 Conflict** | **Candidatura duplicada (RN04)**; e-mail/CPF/CNPJ já cadastrado. |
| **422 Unprocessable Entity** | **Falha de validação** de dados (campos inválidos, regra de formato). |
| **500 Internal Server Error** | Erro não tratado. Nunca vazar stack trace no `message`. |

---

## Fluxo de trabalho

- **Trello** como quadro do time, **sprints de 2 semanas**.
- Cada tarefa referencia o requisito que atende.
- **Commits em português referenciando o requisito**, no formato
  `tipo(REQUISITO): descrição`. Exemplos:
  - `feat(RF13): candidatura com upload de currículo`
  - `fix(RN04): retornar 409 em candidatura duplicada`
  - `docs(MER): adicionar tabela favoritos (RF19)`
- Alteração de código que mexe em regra/estrutura **entra junto** com a atualização da
  documentação correspondente (ver REGRA Nº 1).
- Antes de abrir PR: nomes de tabela/coluna conferidos com `schema.sql`; nomes de
  classe/método conferidos com o Diagrama de Classe; RF/RN/UC citados na descrição.
