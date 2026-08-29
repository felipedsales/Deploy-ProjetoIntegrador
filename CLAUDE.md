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
  (perfil administrador — ver "Lacunas conhecidas").

---

## Arquitetura

| Camada | Stack |
|---|---|
| **Back-end web** | PHP **8.2**, arquitetura **MVC em camadas**, `DocumentRoot` em `public/` |
| **App mobile** | **React Native / Expo** em **TypeScript** |
| **API** | REST em **`/api/v1`**, autenticação por **token Bearer** persistido na tabela `sessoes` |
| **Banco** | **MySQL 8**, acesso **exclusivamente via PDO com prepared statements** |
| **Hospedagem** | **Railway** |

- O app mobile **consome apenas a API REST**; não fala com o banco.
- Todo endpoint autenticado valida o token Bearer contra `sessoes` (token existente,
  não expirado — `expira_em`).
- Nada de SQL montado por concatenação. **PDO + prepared statements sempre.**

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
| **candidatos** | `id`, `nome`, `email`, `senha`, `telefone`, `cpf`, `data_nascimento`, `endereco`, `curriculo_path`, `created_at`, `updated_at` | PK `id`; **UNIQUE** `email`; **UNIQUE** `cpf` |
| **empresas** | `id`, `nome`, `email`, `senha`, `cnpj`, `telefone`, `endereco`, `descricao`, `setor`, `logo_path`, `website`, `created_at`, `updated_at` | PK `id`; **UNIQUE** `email`; **UNIQUE** `cnpj` |
| **vagas** | `id`, `empresa_id`, `titulo`, `descricao`, `requisitos`, `salario`, `tipo_contrato`, `modalidade`, `localizacao`, `beneficios`, `status`, `created_at`, `updated_at` | PK `id`; **FK** `empresa_id` → `empresas(id)` `ON DELETE CASCADE`; índices em `empresa_id` e `status` |
| **candidaturas** | `id`, `vaga_id`, `candidato_id`, `status`, `data_candidatura`, `observacoes` | PK `id`; **FK** `vaga_id` → `vagas(id)` `CASCADE`; **FK** `candidato_id` → `candidatos(id)` `CASCADE`; **`UNIQUE (vaga_id, candidato_id)` → implementa a RN04** (um candidato não se candidata duas vezes à mesma vaga) |
| **sessoes** | `id`, `usuario_id`, `tipo_usuario`, `token`, `expira_em`, `created_at` | PK `id`; **UNIQUE** `token`; guarda o **token Bearer** da API; índices em `token` e `expira_em` |
| **denuncias** | `id`, `vaga_id`, `candidato_id`, `motivo`, `status`, `created_at` | PK `id`; **FK** `vaga_id` → `vagas(id)` `ON DELETE SET NULL`; **FK** `candidato_id` → `candidatos(id)` `ON DELETE SET NULL` |

### Domínios (ENUM no schema)

| Enum | Valores |
|---|---|
| `tipo_contrato` (vagas) | `CLT`, `PJ`, `Freelance`, `Estágio` |
| `modalidade` (vagas) | `Presencial`, `Remoto`, `Híbrido` |
| `status` (vagas) | `Ativa`, `Inativa`, `Pausada` |
| `status` (candidaturas) | `Pendente`, `Em análise`, `Aprovada`, `Rejeitada`, `Contratada` |
| `tipo_usuario` (sessoes) | `candidato`, `empresa` |
| `status` (denuncias) | `Pendente`, `Em análise`, `Resolvida`, `Descartada` |

> **RN04 em destaque:** a restrição `UNIQUE (vaga_id, candidato_id)` na tabela
> `candidaturas` é a implementação física da RN04. O back-end também deve verificar antes
> de inserir e responder **HTTP 409** quando a candidatura já existir — não confiar só no
> erro do banco.

---

## Lacunas conhecidas (documentação × `schema.sql`)

Itens que aparecem na documentação mas **ainda não existem** no `schema.sql`. Enquanto não
forem criados, **não há base para implementar o requisito correspondente**.

| Falta no `schema.sql` | Atende |
|---|---|
| tabela `favoritos` | **RF19** |
| tabela `entrevistas` | **RF26**, **UC-E06** |
| `vagas.data_expiracao` (validade mínima de 7 dias, definida pela empresa) | **RN14** |
| `candidatos.email_confirmado` / `empresas.email_confirmado` | **RN17**, **RN22** |
| perfil/tabela de administrador (`sessoes.tipo_usuario` só aceita `candidato` e `empresa`) | **RF03**, **RF16**, **RN09**, **RN18**, **RN21** |
| colunas `provider` / `provider_id` em `candidatos` (já usadas no Model, login externo) | **RF20** |
| tabela `push_tokens` | **RF14** |
| tabela de logs de auditoria | **RN07**, **RNF12** |
| tabela de avaliações / feedback | **RF15**, **RN08** |
| tabela de mensagens internas | **RF24** |
| tabela de tickets de suporte | **RF17**, **RN15** |

> Esta tabela **espelha** a seção *"Impacto no banco de dados"* de
> [`docs/REQUISITOS.md`](docs/REQUISITOS.md); em caso de divergência, o `REQUISITOS.md` vence.

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
- **RN16 — validação de formato:** CPF, CNPJ, e-mail e telefone validados na **camada de
  negócio do back-end**, não só no formulário.

Regras **sem base ainda no `schema.sql`** — RN14 (expiração de vaga), RN17/RN22 (e-mail
verificado para candidatar-se / ativar conta), RN07/RN09/RN18/RN21 (perfil administrador,
logs de auditoria, permissões por perfil), entre outras: ver **Lacunas conhecidas**.

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
