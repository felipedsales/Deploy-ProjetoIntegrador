# Demonstração automatizada — Ferraz Conecta

Script que abre o navegador e percorre **todas as funcionalidades da plataforma web**, na
ordem do roteiro da apresentação (`ROTEIRO_APRESENTACAO.md`). Em cada etapa ele:

1. mostra no terminal e numa faixa no rodapé do navegador **quais requisitos** (RF, RNF,
   RN, UC) estão sendo evidenciados e uma frase de explicação;
2. executa a ação como um usuário real (preenche formulários, envia arquivos, clica);
3. **confere o resultado esperado** — inclusive os casos que devem ser recusados;
4. tira uma captura de tela.

No fim gera `demo/saida/relatorio.html`: a lista das 40 etapas com o resultado e a captura
de cada uma. É a evidência dos testes para mostrar à banca.

## Instalação (uma vez)

Precisa do Python 3.9 ou mais novo.

```bash
pip install -r demo/requirements.txt
python -m playwright install chromium
cp demo/.env.exemplo demo/.env      # no Windows: copy demo\.env.exemplo demo\.env
```

Preencha `demo/.env` com o e-mail e a senha do administrador. Esse arquivo fica fora do Git.

## Uso

```bash
# Na apresentação: espera Enter antes de cada etapa, para você explicar
python demo/apresentacao.py --pausar

# Ensaio: roda tudo direto, sem pausas
python demo/apresentacao.py

# Conferência rápida, sem abrir janela
python demo/apresentacao.py --sem-janela
```

Abrem-se quatro janelas, uma por pessoa: **Ana** (candidata com currículo aderente),
**Mercado Vila Ferraz** (empresa), **Bruno** (candidato com currículo pouco aderente) e o
**administrador**.

**Única ação manual:** no login do administrador, digite na janela do navegador o código de
6 dígitos que chega no e-mail do administrador. O script espera até 5 minutos.

## De onde vêm os dados

| Dado | Origem |
|---|---|
| CPF e CNPJ | Gerados pelo próprio script, com dígitos verificadores válidos (mesmo algoritmo dos geradores online) |
| E-mails | Caixas temporárias criadas na hora pela API do [mail.tm](https://docs.mail.tm); o script lê os e-mails de confirmação e de recuperação de senha sozinho |
| Currículos, carta e logo | Arquivos fictícios em `demo/arquivos/` |
| Nomes, vaga e categoria | Fixos, com um sufixo de data e hora para cada execução ser única |

Se o mail.tm estiver fora do ar, use `FC_EMAIL=manual`: o script pede um e-mail (por
exemplo, do temp-mail.org) para cada conta e você cola no terminal o link que chegar.

## Cuidados

- **Os dados ficam em produção.** Cada execução cria 3 contas, 1 vaga, 1 categoria, uma
  denúncia e um pedido de suporte. A plataforma não tem exclusão de conta; para limpar,
  o administrador pode suspender as contas de demonstração.
- **Ensaie antes** no mesmo computador e rede da apresentação, e guarde o
  `relatorio.html` de um ensaio aprovado como plano B.
- **Código do administrador:** a plataforma só envia um código novo por minuto. Se rodar o
  script duas vezes seguidas, espere um minuto entre as execuções.
- **Mudanças no frontend:** o script encontra os campos pelo atributo `name` e os
  formulários pelo `action`, não pela aparência. Mudar classes, cores e textos não o quebra;
  renomear um campo, uma rota ou um código de status (`?status=...`) exige ajustar o script.

## Etapas

| # | Etapa | Requisitos |
|---|---|---|
| 01 | Páginas públicas | Visão, RNF01, RNF05 |
| 02 | CPF inválido recusado | RN16, RF01 |
| 03–06 | Cadastro, login bloqueado, confirmação de e-mail, login | RF01, RN22, RF04, RNF03 |
| 07 | E-mail repetido recusado | RN01 |
| 08 | Segundo candidato | RF01, RN22 |
| 09 | Cadastro da empresa | RF02, RN16, RN22 |
| 10 | Perfil e logo (PDF recusado, PNG aceito) | RF07 |
| 11 | Validade de 3 dias recusada pelo servidor | RN14, RN03 |
| 12 | Publicação da vaga | RF10, RF12, RN03 |
| 13 | Edição com histórico | RF11 |
| 14 | Candidatura sem currículo bloqueada | RF13, RN17 |
| 15 | Envio do currículo (PNG recusado, PDF aceito) | RF08, RN02, RF25 |
| 16 | Busca com filtros combinados | RF12, RN05, RN14 |
| 17 | Vagas recomendadas | RF25, RN13 |
| 18 | Candidatura com carta | RF13, RF09, RN12 |
| 19 | Segunda candidatura recusada | RN04 |
| 20 | Favoritar | RF19 |
| 21 | Denúncia de vaga | RF21 |
| 22 | Segunda candidatura (DOCX) | RF08, RF13 |
| 23 | Ranqueamento dos candidatos | RF25, RN13 |
| 24 | Currículo só para quem tem permissão | RF08, RN21, RN20 |
| 25 | Entrevista e conflito de horário | RF26 |
| 26 | Aprovação | RF18, RN12, RF14 |
| 27 | Notificações e histórico | RF14, RF18, RN06 |
| 28 | Mensagens internas | RF24, RN21 |
| 29 | Avaliação e reputação | RF15, RN08 |
| 30 | Pedido de suporte | RF17, RN15 |
| 31 | Exportação LGPD | RF23, RN20 |
| 32 | Recuperação de senha | RF05 |
| 33 | Permissões por perfil | RN21, RN09 |
| 34 | Login do administrador com código | RF03, RNF09, RN09 |
| 35 | Moderação de denúncia | RF16, RF21, RN07 |
| 36 | Resposta ao suporte | RF17, RN15 |
| 37 | Suspensão e reativação de conta | RN18, RN07 |
| 38 | Categorias (em uso não pode ser excluída) | UC13, RF12 |
| 39 | Logs de auditoria e CSV | RN07, RF16, RNF12 |
| 40 | Relatório da empresa | RN19 |
