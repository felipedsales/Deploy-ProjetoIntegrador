# Ferraz Conecta — Implementações futuras (pós-v1)

> Tudo o que foi **deixado fora da v1** na decisão de escopo de 2026-11-12, para ser
> retomado **depois da entrega completa da plataforma web**.
>
> A lista canônica de requisitos continua sendo o [`REQUISITOS.md`](REQUISITOS.md). Este
> documento só organiza o que ficou para depois: o que é, o que falta e do que depende.
> A v1 foi entregue em 2026-11-12; a implementação destes itens segue o plano abaixo.

---

## Plano de execução

**Decisão de 2026-11-13:** os itens deste documento passam a ser implementados agora, em
lotes, e o MER e o Diagrama de Classe são refeitos ao final. Cada lote é um pull request.

| Lote | Conteúdo | Depende de | Situação |
|---|---|---|---|
| **1** | RF02 (responsável), RF09 (carta de apresentação), RF11 (histórico de alterações), RF12 (área e experiência) + UC13 (categorias), RF19 (alerta de expiração), RN19 + UC02 (relatório de vagas) | — | ✅ Implementado |
| **2** | RF14 (notificações na plataforma), RF24 (mensagens internas), RF15 (feedback e avaliação), RF17 (suporte por formulário), RF16 (relatório de logs), RF23 (exportação LGPD) | — | A fazer |
| **3** | RN17 / RN22 (confirmação de e-mail), RF05 (recuperação de senha), RNF09 e RF03 (multifator) | Serviço de envio de e-mail | Aguardando decisão |
| **4** | RF20 (login externo), RF22 (consulta de CNPJ), RF25 (IA para currículos) | Credenciais e serviços externos | Aguardando decisão |
| **5** | API REST `/api/v1` | Lotes 1 a 3 | A fazer |
| **6** | App mobile (React Native / Expo) | Lote 5 | A fazer |

---

## Como retomar um item

1. Mover o requisito de *Futuro* para a versão em que ele entra, no `REQUISITOS.md`.
2. Criar a migration e atualizar MER e Diagrama de Classe **na mesma tarefa**.
3. Atualizar o caso de uso correspondente, se o texto mudar.
4. Só então implementar, citando o código do requisito no commit e no card do Trello.

---

## Fase 2 — App mobile e API REST

A primeira entrega depois da v1. Na v1 o candidato usa a plataforma web.

| Item | O que falta |
|---|---|
| API REST `/api/v1` | Autenticação por token Bearer (tabela `sessoes`), envelope JSON padrão, códigos HTTP e CORS, conforme as convenções do `CLAUDE.md`. Hoje só existe `GET /api/vagas`, fora do padrão. |
| Endpoints | Vagas (lista, filtros, detalhes), candidatura com upload, favoritos, denúncias e entrevistas — os mesmos fluxos que a web já terá na v1. |
| App React Native / Expo | Onboarding, cadastro, login, lista e detalhes de vagas, candidatura, favoritos, denúncia, minhas candidaturas e entrevistas. |
| Documentação da API | Coleção Postman e Diagramas de Sequência com as chamadas da API. |
| Distribuição | Build do APK com EAS. |

Depende de: v1 concluída, porque a API expõe as regras de negócio que já estarão no back-end.

---

## Fase 3 — Requisitos funcionais adiados

Em ordem sugerida de prioridade.

| # | Requisito | O que é | O que falta | Depende de |
|---|---|---|---|---|
| 1 | **RN17 / RN22** — Confirmação de e-mail | Conta só ativa após confirmar o e-mail; candidatura exige e-mail verificado. | Colunas `email_confirmado` em `candidatos` e `empresas`; envio de e-mail com link de confirmação. | Serviço de envio de e-mail |
| 2 | **RF05** — Recuperação de senha | Link temporário por e-mail, válido por 15 minutos. | Tabela de tokens de recuperação; telas de solicitar e redefinir senha. | Serviço de envio de e-mail |
| 3 | **RF14** — Notificações | Aviso de mudança de status da candidatura, convite de entrevista e vagas compatíveis com o perfil (RN06). | Central de notificações na web; tabela `push_tokens` e push no app. | Fase 2 (para o push) |
| 4 | **RF20** — Login externo | Google, Facebook, LinkedIn e código único por e-mail. | Colunas `provider` / `provider_id` em `candidatos`, no MER e no Diagrama de Classe. | — |
| 5 | **RN19 / R18** — Métricas da empresa  ✅ *entregue no lote 1* | Visualizações, candidaturas e taxa de retorno por vaga (UC-E02). | Contagem de visualizações por vaga; tela de relatório no painel da empresa. | — |
| 6 | **RF09** — Carta de apresentação  ✅ *entregue no lote 1* | Upload opcional, por vaga ou padrão no perfil. | Coluna para o arquivo em `candidaturas` e em `candidatos`. | — |
| 7 | **RF17** — Suporte ao usuário | Formulário com acompanhamento de ticket; chatbot; resposta em até 48 h úteis (RN15). | Tabela de tickets; tela de abertura para o usuário e de atendimento para o administrador (UC-E09, UC-C06). | — |
| 8 | **RF24** — Mensagens internas | Conversa entre empresa e candidato após candidatura aceita. | Tabela de mensagens; tela de conversa nos dois perfis. | — |
| 9 | **RF15** — Feedback e avaliação | Feedback pós-processo e avaliações de candidatos e empresas (RN08). | Tabela de avaliações; telas de avaliar e de exibir. | — |
| 10 | **RF23** — Exportação de dados (LGPD) | O próprio usuário exporta seus dados (RN20). | Geração do arquivo com os dados do usuário. | — |
| 11 | **RF22** — Integração com APIs externas | Validação de CNPJ e CPF em bases oficiais, como a Receita Federal (RN10). | Escolha do serviço de consulta e tratamento de indisponibilidade. | Acesso a serviço externo |
| 12 | **RF25** — IA para currículos *(opcional)* | Análise automática para sugerir vagas e ranquear candidatos (RN13). | Definição de critérios objetivos e não discriminatórios antes de qualquer código. | RF08 e RF12 maduros |

> **Atenção ao RF20:** o repositório tem código de login com Google e LinkedIn
> (`SocialAuth.php` e métodos em `AuthController`), mas as colunas que ele usa não existem
> no `schema.sql`. Como o RF20 está fora da v1, as rotas `/auth/...` e os botões das telas
> de login e cadastro **foram desligados em 2026-11-12**. Para retomar: criar as colunas,
> religar as rotas em `public/index.php` e devolver os botões às views.

---

## Partes adiadas de requisitos que entram na v1

Estes RFs fazem parte da v1, mas com recorte. O que foi cortado fica aqui.

| Requisito | Parte adiada | O que falta |
|---|---|---|
| **RF02** — Cadastro de empresas | Nome do responsável pela conta  ✅ *entregue no lote 1* | Coluna em `empresas`, no MER e no Diagrama de Classe; campo no cadastro e no perfil. |
| **RF03** — Cadastro de administradores | Autenticação dupla | Segundo fator no login do administrador (ver RNF09). |
| **RF04** — Login | Redes sociais e multifator | Coberto pelo RF20 e pelo RNF09. |
| **RF11** — Edição e exclusão de vagas | Histórico de alterações  ✅ *entregue no lote 1* | Tabela de histórico por vaga e tela de consulta. |
| **RF12** — Busca de vagas | Filtros por área e por experiência  ✅ *entregue no lote 1* | Colunas em `vagas` (ou a tabela de categorias do UC-E13), no MER e no Diagrama de Classe; campos no cadastro da vaga. |
| **RF13** — Candidatura | Envio de carta de apresentação | Coberto pelo RF09. |
| **RF16** — Painel administrativo | Relatório de logs do sistema | Tela de consulta e exportação dos logs (UC-E15, UC-C11). A gravação do log já entra na v1 pela RN07. |
| **RF18** — Histórico de candidaturas | Mensagens da empresa | Coberto pelo RF24. |
| **RF19** — Favoritar vagas | Alerta de expiração  ✅ *entregue no lote 1* | Aviso quando a vaga favoritada estiver perto de vencer; depende do RF14. |

---

## Requisitos não funcionais adiados

| Requisito | Situação |
|---|---|
| **RNF04** — Escalabilidade via microsserviços | A v1 é uma aplicação única em MVC. Avaliar se o requisito continua fazendo sentido ou deve ser reescrito. |
| **RNF09** — Autenticação multifator | App autenticador e código por e-mail. Depende de envio de e-mail. |
| **RNF12** — Logs criptografados | A v1 grava o log (RN07) com acesso restrito ao administrador; a criptografia fica para depois. |
| **RNF13** — A definir | Continua pendente com o orientador (ver "Decisões pendentes" no `REQUISITOS.md`). |

---

## Pontos em aberto para decidir antes de retomar

1. **Categorias de vagas (UC-E13).** O caso de uso existe na especificação, mas nenhum RF
   o cobre e não há tabela de categorias no `schema.sql`. Decidir se vira requisito ou se o
   caso de uso sai da documentação.
2. **Serviço de envio de e-mail.** Quatro itens dependem dele (RN17/RN22, RF05, RF14 e
   RNF09). Escolher o serviço destrava todos de uma vez.
3. **Casos de uso com cenários fora da v1.** Os cenários alternativos de login social
   (UC-E11, UC-C02, UC-E12, UC-C12), de notificação (UC-C07) e de relatórios (UC-E02,
   UC-E15, UC-C11) descrevem comportamento que a v1 não terá. A especificação precisa
   marcar esses trechos como pós-v1.
