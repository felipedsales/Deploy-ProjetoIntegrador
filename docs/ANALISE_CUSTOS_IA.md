# Ferraz Conecta — Análise de custos: IA para análise de currículos

> **Situação:** implementação futura. O RF25 entra primeiro como **ranqueamento por regras**
> (lote 4 do plano em [`IMPLEMENTACOES_FUTURAS.md`](IMPLEMENTACOES_FUTURAS.md)), sem custo
> de uso. Este documento compara o que custaria trocar ou complementar as regras por um
> modelo de IA, para a decisão ser tomada depois da entrega.
>
> **Preços consultados em 15/11/2026**, em sites que acompanham as tabelas dos provedores
> (lista no fim). Os preços mudam com frequência e as fontes nem sempre concordam entre
> si: **conferir na página oficial de cada provedor antes de contratar.**

---

## 1. O que a IA faria

Para cada candidatura, o modelo recebe o texto do currículo, o texto da carta de
apresentação (quando houver) e os dados da vaga, e devolve:

- uma **nota de aderência** (0 a 100);
- os **requisitos atendidos e os não atendidos**, com o trecho do currículo que sustenta
  cada um;
- um **resumo de duas ou três linhas** para a empresa.

A empresa continua decidindo. A IA só ordena e explica (ver seção 6, RN13 e LGPD).

## 2. Premissas do cálculo

| Item | Valor usado | Observação |
|---|---|---|
| Currículo | 1.500 tokens | Cerca de duas páginas em português |
| Carta de apresentação | 500 tokens | Opcional; conta como se sempre existisse |
| Vaga (título, descrição, requisitos) | 500 tokens | |
| Instruções ao modelo | 1.000 tokens | Critérios, formato da resposta |
| **Entrada por análise** | **3.500 tokens** | |
| **Saída por análise** | **400 tokens** | Nota, requisitos e resumo, em JSON |

São estimativas. O consumo real só se conhece medindo com currículos de verdade; a
proporção entre as opções, porém, não muda.

Três volumes mensais, para dar ordem de grandeza:

| Cenário | Análises por mês | Exemplo |
|---|---|---|
| Piloto | 500 | 25 vagas com 20 candidatos cada |
| Operação municipal | 5.000 | 200 vagas com 25 candidatos cada |
| Uso intenso | 50.000 | Reanálise a cada edição de vaga ou de currículo |

## 3. Opções e custo

Preço de tabela por milhão de tokens (entrada / saída), em dólares.

| Opção | Preço (entrada / saída) | Custo por análise | 500/mês | 5.000/mês | 50.000/mês |
|---|---|---|---|---|---|
| **Regras, sem IA** (lote 4) | — | US$ 0 | US$ 0 | US$ 0 | US$ 0 |
| GPT-5 nano (OpenAI) | 0,05 / 0,40 | US$ 0,00034 | US$ 0,17 | US$ 1,68 | US$ 16,75 |
| **Claude Haiku 5.5** (Anthropic) | 0,10 / 0,50 | US$ 0,00055 | US$ 0,28 | US$ 2,75 | US$ 27,50 |
| Gemini 3.1 Flash-Lite (Google) | 0,25 / 1,50 | US$ 0,00148 | US$ 0,74 | US$ 7,38 | US$ 73,75 |
| Gemini 3.5 Flash-Lite (Google) | 0,30 / 2,50 | US$ 0,00205 | US$ 1,03 | US$ 10,25 | US$ 102,50 |
| GPT-5.4 mini (OpenAI) | 0,75 / 4,50 | US$ 0,00443 | US$ 2,21 | US$ 22,13 | US$ 221,25 |
| Claude Sonnet 5.5 (Anthropic) | 2,00 / 10,00 | US$ 0,01100 | US$ 5,50 | US$ 55,00 | US$ 550,00 |

Como ler:

- **Os modelos pequenos custam centavos de dólar por mês no volume esperado.** Mesmo no
  cenário de 5.000 análises, as três opções mais baratas ficam abaixo de US$ 8 por mês.
- **Processamento em lote corta o preço pela metade** nos três provedores, em troca de a
  resposta chegar em até 24 horas. Serve para a análise rodar de madrugada; não serve se a
  empresa precisar da nota na hora.
- **Modelos maiores custam de 8 a 20 vezes mais** e, para comparar currículo com
  requisitos, o ganho esperado é pequeno. Só fazem sentido se os testes mostrarem que o
  modelo pequeno erra demais.
- Em reais, somar o câmbio do dia e o IOF do cartão internacional.

### Camada gratuita do Google

O Gemini tem uma camada gratuita para os modelos Flash e Flash-Lite, com limite de
requisições. **Não serve para este projeto:** na camada gratuita o conteúdo enviado é
usado para melhorar os produtos do Google, e currículo é dado pessoal. Usá-la exigiria
consentimento específico do candidato e contraria o cuidado que a RN20 (LGPD) pede. A
camada gratuita pode ser usada só em testes com currículos fictícios.

### Hospedar um modelo próprio

Rodar um modelo aberto em servidor próprio elimina o envio de dados a terceiros, mas exige
máquina com GPU, que o Railway não oferece no plano atual. O aluguel de uma GPU fica na
casa de dezenas a centenas de dólares por mês, ligada o tempo todo, contra centavos das
APIs. **Não compensa neste volume.**

## 4. Custos além do preço por token

| Item | Impacto |
|---|---|
| **Extração de texto** | O currículo está no banco como PDF, DOC ou DOCX. O ranqueamento por regras (lote 4) já vai extrair e guardar o texto, e a IA reaproveita. Arquivos `.doc` antigos e PDFs digitalizados (imagem) não têm texto extraível. |
| **Enviar o PDF inteiro ao modelo** | Claude e Gemini aceitam PDF direto e leem até currículo digitalizado, mas cada página custa bem mais tokens que o texto puro. Usar só como alternativa para os arquivos sem texto. |
| **Desenvolvimento** | Integração com a API, tela de resultado, fila de processamento e testes. É o maior custo real, muito acima do custo de uso. |
| **Reanálise** | Guardar o resultado por par (versão do currículo, versão da vaga) e só reanalisar quando um dos dois mudar. Sem isso, o cenário de 50.000 análises vira o normal. |
| **Limite de gasto** | Definir teto mensal no painel do provedor. Todos permitem. |

## 5. Recomendação

**Mais barato:** manter o **ranqueamento por regras** do lote 4. Custo zero, resultado
explicável e nenhuma dependência externa. É o que entra agora.

**Mais eficiente para o nosso contexto**, quando a IA for implementada: um **modelo
pequeno, usado só sob demanda, em cima do ranqueamento por regras**.

1. As regras ordenam todos os candidatos da vaga, sem custo.
2. A empresa pede a análise por IA de um candidato, ou dos primeiros da lista. Só essas
   análises são pagas.
3. O resultado fica guardado e só é refeito se o currículo ou a vaga mudarem.

Nesse desenho o gasto acompanha o uso real e fica, na prática, abaixo do cenário de 500
análises por mês.

Sobre o modelo, a diferença de preço entre os três mais baratos é de centavos por mês,
então o critério de escolha é a qualidade em português e a facilidade de obter resposta
estruturada, não o preço:

- **Claude Haiku 5.5** é a sugestão de partida: está entre os mais baratos, é o modelo
  que a Anthropic indica para classificação e extração em volume, e o projeto já usa
  ferramentas da Anthropic no desenvolvimento.
- **GPT-5 nano** é a alternativa de menor preço de tabela.

Esta é uma indicação para começar os testes, não uma medição: **nenhum dos modelos foi
avaliado com currículos reais do projeto.** Antes de decidir, rodar os dois em um conjunto
de 30 a 50 pares currículo–vaga com a classificação de uma pessoa como referência, e
comparar acerto e custo.

## 6. Condições para implementar (RN13 e LGPD)

A RN13 exige que a análise por IA respeite critérios objetivos e não discriminatórios, e a
LGPD dá ao titular o direito de pedir revisão de decisão automatizada. Para atender:

- **A IA recomenda; a empresa decide.** Nenhuma candidatura é recusada automaticamente.
- **Enviar ao modelo só o que importa para a vaga.** Retirar nome, data de nascimento,
  CPF, endereço, foto e contato antes do envio.
- **Mostrar a justificativa** de cada nota à empresa e deixar o candidato saber que a
  análise existe e como pedir revisão.
- **Consentimento** do candidato para o tratamento por IA, registrado no cadastro.
- **Contratar o provedor em plano pago**, cujos termos não usam os dados enviados pela
  API para treinar modelos, e registrar o provedor como operador de dados na política de
  privacidade.

## 7. Fontes dos preços

Consultadas em 15/11/2026. São páginas de terceiros que reproduzem as tabelas oficiais;
duas delas divergem sobre os preços dos modelos mais novos da OpenAI, por isso a tabela
usa os modelos em que as fontes concordam.

- Anthropic (Claude Haiku 5.5 e Sonnet 5.5, lote e cache):
  [Maxim AI](https://www.getmaxim.ai/articles/claude-api-pricing/),
  [Proxidize](https://proxidize.com/blog/anthropic-api-pricing/),
  [DEV Community](https://dev.to/scorpion4ik26lang/claude-api-pricing-per-million-tokens-all-models-oct-2026-2am2)
- OpenAI (GPT-5 nano, GPT-5.4 mini, lote):
  [API Evangelist](https://apievangelist.com/2026/11/06/openai-api-pricing-what-every-model-costs-per-million-tokens/),
  [BenchLM](https://benchlm.ai/openai/api-pricing),
  [ComputePrices](https://computeprices.com/providers/openai)
- Google (Gemini Flash-Lite, camada gratuita):
  [Fastero](https://fastero.com/blog/gemini-api-pricing),
  [Morph](https://www.morphllm.com/gemini-api-pricing),
  [Licenseware](https://licenseware.io/gemini-api-cost/)
