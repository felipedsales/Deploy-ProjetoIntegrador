# Ferraz Conecta — Requisitos (lista canônica)

> **Este é o documento de referência único do projeto.** Toda implementação, caso de uso,
> card do Trello e commit deve referenciar os códigos definidos aqui.
>
> Consolidado a partir de `Projeto_Integrador_-_FATEC_2024.docx` (base) e `RFRNFRN.xlsx`
> (complementos). Versão 1.0 — pendente de validação com o orientador.

---

## Como esta lista foi construída

| Fonte | O que trouxe |
|---|---|
| Projeto Integrador (.docx) | Base da numeração: RF01–RF26, RNF01–RNF12, RN01–RN15 e a matriz de rastreabilidade |
| RFRNFRN.xlsx | RF01–RF24 e RNF01–RNF12 **idênticos** ao .docx; folha de RNs divergente, aproveitada como RN16–RN22 |

**Por que a numeração do .docx venceu:** a matriz de rastreabilidade (idêntica nos dois
arquivos) referencia RF25, RF26, RN13, RN14 e RN15 — códigos que só existem no .docx.
Renumerar quebraria a matriz inteira.

---

## Requisitos Funcionais

| Código | Nome | Descrição |
|---|---|---|
| RF01 | Cadastro de Candidatos | Validação de CPF, e-mail único e senha segura. |
| RF02 | Cadastro de Empresas | CNPJ, razão social e nome do responsável pela conta. |
| RF03 | Cadastro de Administradores | Autenticação dupla e permissões especiais. |
| RF04 | Login *(Include)* | E-mail/senha, redes sociais e autenticação multifator. |
| RF05 | Recuperação de Senha *(Include)* | Envio de link temporário por e-mail (válido por 15 minutos). |
| RF06 | Edição de Perfil do Candidato | Atualização de dados pessoais, currículo e preferências. |
| RF07 | Edição de Perfil da Empresa | Alteração de dados cadastrais, logo e descrição institucional. |
| RF08 | Upload de Currículo | Suporte a PDF/DOC, com validação de tipo e tamanho de arquivo. |
| RF09 | Upload de Carta de Apresentação | Upload opcional, associada à vaga ou padrão no perfil. |
| RF10 | Cadastro de Vagas | Descrição completa, requisitos, localização e benefícios. |
| RF11 | Edição/Exclusão de Vagas | Permitido com histórico de alterações. |
| RF12 | Busca de Vagas com Filtros | Filtros por área, localidade, tipo, experiência e modelo de trabalho. |
| RF13 | Candidatura em Vagas | Envio de currículo e/ou carta de apresentação. |
| RF14 | Notificações de Vagas | Vagas relevantes com base em perfil e histórico de candidaturas. |
| RF15 | Feedback e Avaliação | Feedback pós-processo e avaliações por candidatos e empresas. |
| RF16 | Painel Administrativo | Gestão de usuários, vagas, denúncias e logs do sistema. |
| RF17 | Suporte ao Usuário | Chatbot e formulário com acompanhamento de ticket. |
| RF18 | Histórico de Candidaturas | Status atualizado e mensagens da empresa. |
| RF19 | Favoritar Vagas | Salvar para ver depois, com alerta de expiração. |
| RF20 | Login Externo *(Include)* | Google, Facebook, LinkedIn e código único por e-mail. |
| RF21 | Denúncia de Conteúdo/Usuários | Com motivo e evidência. |
| RF22 | Integração com APIs Externas | Ex.: Receita Federal, validação LGPD. |
| RF23 | Exportação de Dados (LGPD) | Feita pelo próprio usuário. |
| RF24 | Mensagens Internas | Comunicação entre empresa e candidato após candidatura aceita. |
| RF25 | IA para Currículos *(opcional)* | Análise automática de currículos para sugestão de vagas e ranqueamento. |
| RF26 | Agendamento de Entrevistas | Ferramenta para agendamento entre empresa e candidato. |

> ⚠️ **RF25 e RF26 estavam ausentes da planilha.** O RF26 é essencial: sustenta o caso de
> uso "Agendar Entrevista" do diagrama mobile e o UC-E06. O RF25 permanece como opcional e
> está declarado **fora do escopo da v1** no Documento de Visão.

---

## Requisitos Não Funcionais

| Código | Nome | Descrição |
|---|---|---|
| RNF01 | Interface Responsiva e Acessível | Navegação com suporte a atalhos, leitor de tela e contraste adequado. |
| RNF02 | Desempenho | Tempo de resposta inferior a 2 segundos nas ações do usuário. |
| RNF03 | Segurança de Dados | Criptografia de senhas, documentos e comunicação (TLS/SSL). |
| RNF04 | Escalabilidade | Suporte à escalabilidade horizontal via microsserviços. |
| RNF05 | Compatibilidade | Funciona nos principais navegadores e dispositivos móveis. |
| RNF06 | Qualidade de Código | Código modular com testes unitários e de integração. |
| RNF07 | Backup e Restauração | Backup diário automático com restauração em até 24 horas. |
| RNF08 | Acessibilidade AA | Conformidade com o padrão WCAG 2.1 nível AA. |
| RNF09 | Autenticação Multifator | Suporte via app autenticador e código por e-mail. |
| RNF10 | Monitoramento de Segurança | Monitoramento constante contra ataques e atividades suspeitas. |
| RNF11 | Alta Disponibilidade | Uptime mínimo garantido de 99,5% ao mês. |
| RNF12 | Logs Seguros | Logs de auditoria criptografados e com controle de acesso. |
| RNF13 | ❓ **A DEFINIR** | A matriz (R03 – Login e Autenticação) referencia RNF13, mas ele não existe em nenhuma fonte. Ver "Decisões pendentes". |

---

## Regras de Negócio

### Base — herdadas do Projeto Integrador (numeração preservada pela matriz)

| Código | Nome | Descrição |
|---|---|---|
| RN01 | Cadastro Único de Usuário | Cada e-mail pode estar vinculado a apenas uma conta ativa no sistema. |
| RN02 | Atualização e Exclusão de Currículo | O usuário pode atualizar ou excluir o currículo a qualquer momento. |
| RN03 | Vagas com Dados Obrigatórios | Toda vaga deve conter título, descrição, requisitos e localização. |
| RN04 | Candidatura Única por Vaga | O usuário só pode se candidatar uma vez à mesma vaga. |
| RN05 | Filtros Combináveis | O sistema deve permitir o uso simultâneo de múltiplos filtros de busca. |
| RN06 | Notificações Baseadas em Perfil | O envio de vagas deve considerar o perfil e o histórico do candidato. |
| RN07 | Ações Administrativas Registradas | Toda ação feita por administradores deve ser registrada em logs. |
| RN08 | Avaliação de Vagas e Empresas | Apenas usuários autenticados podem avaliar ou comentar. |
| RN09 | Controle de Acesso Administrativo | Apenas administradores autenticados têm acesso ao painel e aos logs. |
| RN10 | Integração com Órgãos Oficiais | Validação de CNPJ e CPF com bases oficiais (ex.: Receita Federal). |
| RN11 | Retenção e Backup de Dados | Dados excluídos logicamente permanecem nos backups por 6 meses. |
| RN12 | Histórico de Candidaturas | Cada ação de candidatura registra data, hora e status. |
| RN13 | IA de Currículo *(opcional)* | A análise com IA deve respeitar critérios objetivos e não discriminatórios. |
| RN14 | Visibilidade de Vagas Ativas | Somente vagas com prazo de candidatura válido podem ser exibidas. **A vaga deve ter validade mínima de 7 dias, definida pela empresa.** *(complemento vindo da planilha)* |
| RN15 | Tempo de Resposta de Suporte | O suporte deve responder aos usuários em até 48 horas úteis. |

### Incorporadas — regras que só existiam na planilha

| Código | Nome | Descrição | Origem |
|---|---|---|---|
| RN16 | Validação de Formulários | Todos os formulários devem ter validação de campos obrigatórios e de formato (CPF, CNPJ, e-mail, telefone). | planilha RN01 |
| RN17 | Requisitos para Candidatura | O candidato só pode se candidatar com currículo anexado e e-mail verificado. | planilha RN02 |
| RN18 | Suspensão de Contas Denunciadas | Apenas administradores podem suspender ou excluir contas denunciadas. | planilha RN05 |
| RN19 | Métricas de Vaga para Empresas | A empresa visualiza visualizações, candidaturas e taxa de retorno das suas vagas. | planilha RN09 |
| RN20 | Conformidade com a LGPD | Dados pessoais e sensíveis devem ser tratados conforme a Lei Geral de Proteção de Dados. | planilha RN10 |
| RN21 | Permissões por Perfil | Cada tipo de usuário (candidato, empresa, administrador) possui permissões e visibilidade específicas. | planilha RN11 |
| RN22 | Confirmação de E-mail | A conta só é ativada após a confirmação do e-mail. | planilha RN12 |

### Regra descartada

**Planilha RN08 — "A carta de apresentação aumenta em 30% a chance de visualização".**
Não é regra de negócio: é uma estatística sem fonte e sem comportamento verificável no
sistema. Não pode ser testada nem implementada. O conteúdo útil já está coberto pelo RF09
(carta de apresentação opcional). *Se a equipe quiser preservá-la, reescrever como regra
verificável — ex.: "candidaturas com carta de apresentação são exibidas primeiro na
listagem da empresa" — e aí ela vira comportamento implementável.*

---

## Matriz de Rastreabilidade

| ID | Descrição | Tipo | RFs | RNFs | RNs |
|---|---|---|---|---|---|
| R01 | Cadastro de Candidato | Funcional | RF01, RF06, RF08, RF09, RF18 | RNF01, RNF03, RNF05 | RN01, RN02, RN12, RN16, RN22 |
| R02 | Cadastro de Empresa | Funcional | RF02, RF07, RF10, RF11 | RNF01, RNF03, RNF05 | RN03, RN14, RN16, RN22 |
| R03 | Login e Autenticação | Funcional *(Include)* | RF04, RF05, RF20 | RNF03, RNF08, RNF13 | RN01, RN21, RN22 |
| R04 | Cadastro de Administrador | Funcional | RF03, RF16 | RNF08, RNF11 | RN09, RN21 |
| R05 | Busca de Vagas | Funcional | RF12, RF19 | RNF02, RNF05 | RN05, RN06, RN14 |
| R06 | Candidatura | Funcional | RF13, RF24 | RNF02, RNF05 | RN04, RN12, RN17 |
| R07 | Sistema de Notificações | Funcional | RF14, RF15 | RNF02, RNF06 | RN06, RN15 |
| R08 | Feedback de Candidatura | Funcional | RF15 | RNF01, RNF03 | RN08 |
| R09 | Painel Administrativo | Funcional | RF16 | RNF06, RNF11 | RN07, RN09, RN18, RN21 |
| R10 | Suporte ao Usuário | Funcional | RF17 | RNF01, RNF09 | RN15 |
| R11 | Gestão de Histórico | Funcional | RF18 | RNF01, RNF02 | RN12, RN21 |
| R12 | Denúncias | Funcional | RF21 | RNF09, RNF11 | RN07, RN18 |
| R13 | Integrações Externas | Funcional | RF22 | RNF03, RNF04 | RN10, RN20 |
| R14 | LGPD e Portabilidade de Dados | Funcional | RF23 | RNF03, RNF12 | RN20 |
| R15 | Comunicação Interna | Funcional | RF24 | RNF02, RNF03 | RN21 |
| R16 | IA para Currículos *(opcional)* | Funcional | RF25 | RNF06, RNF10 | RN13 |
| R17 | Agendamento de Entrevistas | Funcional | RF26 | RNF02, RNF05 | RN15, RN21 |
| **R18** | **Métricas e Relatórios da Empresa** | **Funcional** | **RF16** | **RNF02** | **RN19** | 

> Ajustes feitos na matriz original: as RNs incorporadas (RN16–RN22) foram distribuídas nos
> processos correspondentes, e a linha R18 foi criada porque a RN19 (métricas de vaga) não
> tinha nenhum processo associado.

---

## Impacto no banco de dados

Requisitos e regras que **ainda não têm suporte no `schema.sql`**. Cada item exige migration
+ atualização do MER e do Diagrama de Classe na mesma tarefa:

| Falta no schema | Atende |
|---|---|
| Tabela `favoritos` | RF19 |
| Tabela `entrevistas` | RF26, UC-E06 |
| `vagas.data_expiracao` | **RN14** (validade mínima de 7 dias) |
| `candidatos.email_confirmado` / `empresas.email_confirmado` | **RN22** |
| Perfil/tabela de administrador (hoje `sessoes.tipo_usuario` só aceita candidato e empresa) | RF03, RF16, RN09, RN18, RN21 |
| Colunas `provider` / `provider_id` (já usadas no Model) | RF20 |
| Tabela `push_tokens` | RF14 (notificações no app) |
| Tabela de logs de auditoria | RN07, RNF12 |
| Tabela de avaliações/feedback | RF15, RN08 |
| Tabela de mensagens internas | RF24 |
| Tabela de tickets de suporte | RF17, RN15 |

---

## Decisões pendentes (levar ao orientador)

1. **RNF13** — a matriz o referencia, mas ele não existe. Pelo contexto (R03, Login e
   Autenticação), a hipótese mais provável é algo como "proteção contra ataques de força
   bruta / bloqueio após tentativas sucessivas". **Definir o texto ou remover a referência
   da matriz.**
2. **Escopo da v1** — são 26 RFs e 22 RNs para entregar até 27/11. Recomenda-se classificar
   cada RF como *v1 (obrigatório)*, *v1 (desejável)* ou *futuro*, e o Documento de Visão
   passa a declarar explicitamente o que ficou fora.
3. **Planilha RN08 (carta = +30%)** — confirmar o descarte ou reescrever como regra
   verificável.
4. **RF25 / RN13 (IA de currículos)** — permanecem como opcionais e fora da v1?
5. **Banco de dados** — ~~MySQL (documentação) × PostgreSQL (código atual)~~.
   **Resolvido em 2026-08-29:** padronizado em **MySQL 8** (Projeto Integrador e
   Documento de Visão). `database/schema.sql` convertido para o dialeto MySQL,
   dados de exemplo movidos para `database/seed.sql`, e removidos o driver
   `pdo_pgsql` (Dockerfile) e o ramo `pgsql` de `app/Models/Database.php`.
