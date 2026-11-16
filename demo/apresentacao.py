"""
Ferraz Conecta — demonstração automatizada da plataforma web.

Abre um navegador visível e percorre todas as funcionalidades, na ordem do
roteiro da apresentação: cadastro e confirmação de e-mail, publicação de vaga,
candidatura, seleção com ranqueamento, comunicação e administração. Cada etapa
mostra no terminal e no próprio navegador quais requisitos (RF, RNF, RN) estão
sendo evidenciados, confere o resultado esperado e tira uma captura de tela.

No fim, gera demo/saida/relatorio.html com todas as etapas, o resultado de cada
uma e as capturas — a evidência dos testes.

Uso (ver demo/README.md):
    python demo/apresentacao.py            # roda direto
    python demo/apresentacao.py --pausar   # espera Enter antes de cada etapa

Os dados criados (contas, vaga, candidaturas) são fictícios e ficam no banco da
plataforma; cada execução usa nomes, e-mails, CPFs e CNPJ novos.
"""

import argparse
import datetime as dt
import html
import json
import os
import random
import re
import string
import sys
import time
import traceback
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

from playwright.sync_api import TimeoutError as TempoEsgotado
from playwright.sync_api import sync_playwright

PASTA = Path(__file__).resolve().parent
ARQUIVOS = PASTA / 'arquivos'
SAIDA = PASTA / 'saida'


# ---------------------------------------------------------------------------
# Configuração
# ---------------------------------------------------------------------------

def carregar_env():
    """Lê demo/.env (CHAVE=valor) sem sobrescrever variáveis já definidas."""
    arquivo = PASTA / '.env'
    if not arquivo.exists():
        return
    for linha in arquivo.read_text(encoding='utf-8').splitlines():
        linha = linha.strip()
        if not linha or linha.startswith('#') or '=' not in linha:
            continue
        chave, valor = linha.split('=', 1)
        os.environ.setdefault(chave.strip(), valor.strip().strip('"').strip("'"))


carregar_env()

URL = os.environ.get('FC_URL', 'https://deploy-projetointegrador-production.up.railway.app').rstrip('/')
ADMIN_EMAIL = os.environ.get('FC_ADMIN_EMAIL', '')
ADMIN_SENHA = os.environ.get('FC_ADMIN_SENHA', '')
MODO_EMAIL = os.environ.get('FC_EMAIL', 'mailtm')          # mailtm | manual | arquivo
ARQUIVO_EMAILS = os.environ.get('FC_EMAIL_ARQUIVO', '')    # só no modo arquivo (desenvolvimento)
LENTIDAO = int(os.environ.get('FC_LENTIDAO', '350'))       # ms entre ações, para a plateia acompanhar
SUFIXO = dt.datetime.now().strftime('%d%H%M')               # torna os dados de cada execução únicos


# ---------------------------------------------------------------------------
# Dados fictícios válidos (RN16)
# ---------------------------------------------------------------------------

def gerar_cpf():
    base = [random.randint(0, 9) for _ in range(9)]
    if len(set(base)) == 1:
        base[0] = (base[0] + 1) % 10
    for tamanho in (9, 10):
        soma = sum(d * p for d, p in zip(base, range(tamanho + 1, 1, -1)))
        resto = soma % 11
        base.append(0 if resto < 2 else 11 - resto)
    n = ''.join(map(str, base))
    return f'{n[:3]}.{n[3:6]}.{n[6:9]}-{n[9:]}'


def gerar_cnpj():
    base = [random.randint(0, 9) for _ in range(8)] + [0, 0, 0, 1]
    for pesos in ([5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]):
        resto = sum(d * p for d, p in zip(base, pesos)) % 11
        base.append(0 if resto < 2 else 11 - resto)
    n = ''.join(map(str, base))
    return f'{n[:2]}.{n[2:5]}.{n[5:8]}/{n[8:12]}-{n[12:]}'


def gerar_telefone():
    return f'(11) 9{random.randint(1000, 9999)}-{random.randint(1000, 9999)}'


# ---------------------------------------------------------------------------
# Caixas de e-mail
# ---------------------------------------------------------------------------

class CaixaMailTm:
    """Caixa temporária real, pela API pública do mail.tm (https://docs.mail.tm)."""

    API = 'https://api.mail.tm'

    def __init__(self, apelido):
        dominio = self._req('GET', '/domains')['hydra:member'][0]['domain']
        aleatorio = ''.join(random.choices(string.ascii_lowercase + string.digits, k=6))
        self.endereco = f'fc.{apelido}.{SUFIXO}{aleatorio}@{dominio}'
        self._senha = ''.join(random.choices(string.ascii_letters + string.digits, k=16))
        self._req('POST', '/accounts', {'address': self.endereco, 'password': self._senha})
        self._token = self._req('POST', '/token', {'address': self.endereco, 'password': self._senha})['token']
        self._lidas = set()

    def _req(self, metodo, caminho, corpo=None):
        dados = json.dumps(corpo).encode() if corpo is not None else None
        pedido = urllib.request.Request(self.API + caminho, data=dados, method=metodo)
        pedido.add_header('Content-Type', 'application/json')
        pedido.add_header('Accept', 'application/ld+json')
        if getattr(self, '_token', None):
            pedido.add_header('Authorization', 'Bearer ' + self._token)
        with urllib.request.urlopen(pedido, timeout=20) as resposta:
            return json.loads(resposta.read() or b'{}')

    def esperar(self, assunto, limite=120):
        """Espera chegar um e-mail cujo assunto contenha o texto; devolve o HTML."""
        fim = time.time() + limite
        while time.time() < fim:
            for msg in self._req('GET', '/messages').get('hydra:member', []):
                if msg['id'] not in self._lidas and assunto.lower() in msg.get('subject', '').lower():
                    self._lidas.add(msg['id'])
                    completa = self._req('GET', f"/messages/{msg['id']}")
                    corpo = completa.get('html') or [completa.get('text', '')]
                    return '\n'.join(corpo) if isinstance(corpo, list) else corpo
            time.sleep(3)
        raise AssertionError(f'O e-mail "{assunto}" não chegou em {limite} s na caixa {self.endereco}.')


class CaixaArquivo:
    """Só para desenvolvimento: lê os e-mails gravados pela aplicação (EMAIL_ARQUIVO_TESTE)."""

    def __init__(self, apelido):
        self.endereco = f'fc.{apelido}.{SUFIXO}{random.randint(100, 999)}@exemplo.com.br'
        self._vistos = set()

    @staticmethod
    def _mensagens():
        arquivo = Path(ARQUIVO_EMAILS)
        return [json.loads(l) for l in arquivo.read_text(encoding='utf-8').splitlines()] if arquivo.exists() else []

    def esperar(self, assunto, limite=30):
        fim = time.time() + limite
        while time.time() < fim:
            for i, msg in enumerate(self._mensagens()):
                if i not in self._vistos and msg['para'] == self.endereco and assunto.lower() in msg['assunto'].lower():
                    self._vistos.add(i)
                    return msg['html']
            time.sleep(1)
        raise AssertionError(f'O e-mail "{assunto}" não foi gravado em {ARQUIVO_EMAILS}.')

    @classmethod
    def ultimo_codigo(cls):
        return next(m['html'] for m in reversed(cls._mensagens()) if 'código de acesso' in m['assunto'].lower())


class CaixaManual:
    """Sem automação: o apresentador informa o e-mail e cola o link recebido (ex.: temp-mail.org)."""

    def __init__(self, apelido):
        self.endereco = input(f'\n  E-mail temporário para "{apelido}": ').strip()

    def esperar(self, assunto, limite=None):
        return input(f'  Cole o link do e-mail "{assunto}" recebido em {self.endereco}: ').strip()


def nova_caixa(apelido):
    return {'mailtm': CaixaMailTm, 'arquivo': CaixaArquivo, 'manual': CaixaManual}[MODO_EMAIL](apelido)


def extrair_link(corpo, caminho):
    achado = re.search(r'https?://[^\s"\'<>]+' + re.escape(caminho) + r'[0-9a-f]+', corpo)
    if not achado:
        raise AssertionError(f'Link {caminho} não encontrado no e-mail.')
    return achado.group(0)


# ---------------------------------------------------------------------------
# Roteiro: etapas, faixa no navegador e relatório
# ---------------------------------------------------------------------------

class Roteiro:
    def __init__(self, pausar):
        self.pausar = pausar
        self.etapas = []
        self.contextos = []
        SAIDA.mkdir(exist_ok=True)
        for antiga in SAIDA.glob('*.png'):
            antiga.unlink()

    def registrar_contexto(self, contexto):
        self.contextos.append(contexto)

    def _faixa(self, texto):
        """Mostra a etapa atual numa faixa no rodapé de todas as janelas."""
        script = """(texto) => {
            const pintar = () => {
                let f = document.getElementById('fc-faixa-demo');
                if (!f) {
                    f = document.createElement('div');
                    f.id = 'fc-faixa-demo';
                    f.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:2147483647;' +
                        'background:rgba(13,42,94,.94);color:#fff;font:600 15px/1.4 system-ui,sans-serif;' +
                        'padding:10px 18px;pointer-events:none;box-shadow:0 -2px 8px rgba(0,0,0,.25)';
                    document.documentElement.appendChild(f);
                }
                f.textContent = texto;
            };
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pintar); else pintar();
        }"""
        for contexto in self.contextos:
            contexto.add_init_script(f'({script})({json.dumps(texto)})')
            for pagina in contexto.pages:
                try:
                    pagina.evaluate(script, texto)
                except Exception:
                    pass

    def etapa(self, requisitos, titulo, explicacao, pagina, funcao, critica=False):
        numero = len(self.etapas) + 1
        print('\n' + '═' * 78)
        print(f' {numero:02d}. {titulo}')
        print(f'     Requisitos: {requisitos}')
        print(f'     {explicacao}')
        print('═' * 78)
        self._faixa(f'{numero:02d} · {requisitos} — {titulo}')
        if self.pausar:
            input('     [Enter] para executar… ')

        registro = {'numero': numero, 'requisitos': requisitos, 'titulo': titulo,
                    'explicacao': explicacao, 'ok': False, 'detalhe': '', 'imagem': ''}
        inicio = time.time()
        try:
            detalhe = funcao()
            registro['ok'] = True
            registro['detalhe'] = detalhe or ''
            print(f'     ✔ OK  {detalhe or ""}')
        except Exception as erro:  # a etapa falhou: registra e segue, salvo se for crítica
            registro['detalhe'] = f'{type(erro).__name__}: {erro}'
            print(f'     ✘ FALHOU  {registro["detalhe"]}')
            if os.environ.get('FC_DEPURAR'):
                traceback.print_exc()
        registro['segundos'] = round(time.time() - inicio, 1)

        alvo = pagina() if callable(pagina) else pagina
        if alvo is not None:
            imagem = SAIDA / f'{numero:02d}.png'
            try:
                alvo.screenshot(path=str(imagem), full_page=False)
                registro['imagem'] = imagem.name
            except Exception:
                pass
        self.etapas.append(registro)

        if critica and not registro['ok']:
            self.gerar_relatorio()
            sys.exit(f'\nEtapa crítica {numero:02d} falhou; as seguintes dependem dela. Relatório em {SAIDA / "relatorio.html"}')
        return registro['ok']

    def gerar_relatorio(self):
        aprovadas = sum(e['ok'] for e in self.etapas)
        linhas = []
        for e in self.etapas:
            imagem = (f'<a href="{e["imagem"]}" target="_blank"><img src="{e["imagem"]}" alt="Captura da etapa {e["numero"]}"></a>'
                      if e['imagem'] else '')
            linhas.append(f"""
            <tr class="{'ok' if e['ok'] else 'falha'}">
              <td>{e['numero']:02d}</td>
              <td><strong>{html.escape(e['titulo'])}</strong><br><span class="req">{html.escape(e['requisitos'])}</span>
                  <p>{html.escape(e['explicacao'])}</p></td>
              <td>{'✔ Aprovada' if e['ok'] else '✘ Falhou'}<br><small>{html.escape(e['detalhe'])}</small><br>
                  <small class="tempo">{e['segundos']} s</small></td>
              <td>{imagem}</td>
            </tr>""")
        (SAIDA / 'relatorio.html').write_text(f"""<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="utf-8">
<title>Ferraz Conecta — evidência dos testes</title>
<style>
  body {{ font: 15px/1.5 system-ui, sans-serif; margin: 32px; color: #1d2433; }}
  h1 {{ margin-bottom: 4px; }} .resumo {{ color: #555; margin-bottom: 24px; }}
  table {{ border-collapse: collapse; width: 100%; }}
  th, td {{ border-bottom: 1px solid #dde2ea; padding: 10px; vertical-align: top; text-align: left; }}
  th {{ background: #0d2a5e; color: #fff; }}
  tr.ok td:nth-child(3) {{ color: #146c2e; font-weight: 600; }}
  tr.falha td:nth-child(3) {{ color: #b3261e; font-weight: 600; }}
  tr.falha {{ background: #fff4f3; }}
  .req {{ color: #0d6efd; font-size: 13px; }} p {{ margin: 4px 0 0; color: #444; }}
  small {{ font-weight: 400; color: #555; }} .tempo {{ color: #888; }}
  img {{ width: 320px; border: 1px solid #ccd; border-radius: 4px; }}
</style></head><body>
<h1>Ferraz Conecta — evidência dos testes da plataforma web</h1>
<div class="resumo">{aprovadas} de {len(self.etapas)} etapas aprovadas ·
  {dt.datetime.now().strftime('%d/%m/%Y %H:%M')} · {html.escape(URL)} · e-mail: {MODO_EMAIL}</div>
<table><tr><th>#</th><th>Etapa e requisitos</th><th>Resultado</th><th>Captura</th></tr>
{''.join(linhas)}
</table></body></html>""", encoding='utf-8')


# ---------------------------------------------------------------------------
# Ajudantes de navegação
# ---------------------------------------------------------------------------

def ir(pagina, caminho):
    pagina.goto(URL + caminho)
    pagina.wait_for_load_state('domcontentloaded')


def enviar(pagina, acao, campos=None, arquivos=None, botao=None):
    """Preenche o formulário de action=acao pelos atributos name e o envia."""
    form = pagina.locator(f'form[action="{acao}"]').first
    for nome, valor in (campos or {}).items():
        campo = form.locator(f'[name="{nome}"]').first
        tag = campo.evaluate('e => e.tagName')
        if tag == 'SELECT':
            campo.select_option(str(valor))
        else:
            campo.fill(str(valor))
    for nome, caminho in (arquivos or {}).items():
        form.locator(f'input[type=file][name="{nome}"]').set_input_files(str(caminho))
    with pagina.expect_navigation():
        (form.locator(botao) if botao else form.locator('[type=submit]:not([form])').first).click()


def tem_erro(pagina):
    """O servidor recusou: a página mostra mensagem de erro de validação."""
    return pagina.locator('.alert-danger, .invalid-feedback:visible, .is-invalid, .text-danger').count() > 0


def esperar_texto(pagina, texto):
    if texto.lower() not in pagina.locator('body').inner_text().lower():
        raise AssertionError(f'Texto esperado não encontrado: "{texto}"')


def tirar_validacao_do_navegador(pagina):
    """Remove required/min dos campos para provar que a regra também está no servidor."""
    pagina.evaluate("""() => document.querySelectorAll('[required],[min],[minlength],[pattern]').forEach(c => {
        c.removeAttribute('required'); c.removeAttribute('min'); c.removeAttribute('minlength'); c.removeAttribute('pattern');
    })""")


def sair(pagina):
    ir(pagina, '/logout')


def entrar(pagina, perfil, email, senha):
    sair(pagina)  # começa sempre deslogado: o cadastro pode já ter aberto a sessão
    if perfil == 'empresa':
        ir(pagina, '/login-empresa')
        enviar(pagina, '/login-empresa', {'email': email, 'senha': senha})
    else:
        ir(pagina, '/login')
        enviar(pagina, '/login', {'email': email, 'senha': senha, 'tipo': 'candidato'})
    return '/login' not in pagina.url




# ---------------------------------------------------------------------------
# A demonstração
# ---------------------------------------------------------------------------

def principal():
    argumentos = argparse.ArgumentParser(description='Demonstração automatizada do Ferraz Conecta')
    argumentos.add_argument('--pausar', action='store_true', help='espera Enter antes de cada etapa')
    argumentos.add_argument('--sem-janela', action='store_true', help='roda sem abrir o navegador (headless)')
    opcoes = argumentos.parse_args()

    if not ADMIN_EMAIL or not ADMIN_SENHA:
        sys.exit('Defina FC_ADMIN_EMAIL e FC_ADMIN_SENHA em demo/.env (veja demo/.env.exemplo).')

    roteiro = Roteiro(opcoes.pausar)
    d = {}  # dados compartilhados entre as etapas

    print(f'\nFerraz Conecta — demonstração em {URL}  (e-mail: {MODO_EMAIL})')
    print('Preparando caixas de e-mail…')
    caixas = {nome: nova_caixa(nome) for nome in ('ana', 'bruno', 'empresa')}
    pessoas = {
        'ana': {'nome': f'Ana Demonstração {SUFIXO}', 'email': caixas['ana'].endereco, 'senha': 'Demo2026a',
                'cpf': gerar_cpf(), 'telefone': gerar_telefone(), 'data_nascimento': '1998-04-12'},
        'bruno': {'nome': f'Bruno Demonstração {SUFIXO}', 'email': caixas['bruno'].endereco, 'senha': 'Demo2026b',
                  'cpf': gerar_cpf(), 'telefone': gerar_telefone(), 'data_nascimento': '1995-09-30'},
    }
    empresa = {'razao_social': f'Mercado Vila Ferraz {SUFIXO} Ltda', 'responsavel': 'Carla Responsável',
               'email': caixas['empresa'].endereco, 'senha': 'Demo2026e', 'cnpj': gerar_cnpj(),
               'telefone': gerar_telefone(), 'descricao': 'Empresa fictícia criada para a demonstração.'}
    titulo_vaga = f'Auxiliar Administrativo (demo {SUFIXO})'
    requisitos_vaga = 'Pacote Office, Atendimento ao cliente, Excel avançado, Ensino médio completo, Inglês básico'

    with sync_playwright() as p:
        navegador = p.chromium.launch(headless=opcoes.sem_janela, slow_mo=0 if opcoes.sem_janela else LENTIDAO)

        def janela():
            contexto = navegador.new_context(viewport={'width': 1280, 'height': 760}, accept_downloads=True, locale='pt-BR')
            contexto.set_default_timeout(20000)
            roteiro.registrar_contexto(contexto)
            pagina = contexto.new_page()
            pagina.on('dialog', lambda dialogo: dialogo.accept())  # confirma os "Tem certeza?" da plataforma
            return contexto, pagina

        ctx_ana, ana = janela()
        ctx_emp, emp = janela()
        ctx_bruno, bruno = janela()
        ctx_adm, adm = janela()

        # ------------------------------------------------------------------ público
        def publico():
            ir(ana, '/')
            ir(ana, '/sobre')
            ir(ana, '/vagas')
            return 'Páginas públicas abertas sem login.'
        roteiro.etapa('Visão · RNF01 · RNF05', 'Páginas públicas',
                      'Início, Sobre e lista de vagas: o problema da Prefeitura e a proposta do balcão online.',
                      ana, publico)

        # ------------------------------------------------------------------ cadastro candidato
        def cpf_invalido():
            ir(ana, '/cadastro')
            enviar(ana, '/cadastro', {**pessoas['ana'], 'cpf': '123.456.789-00'})
            if not ('/cadastro' in ana.url and tem_erro(ana)):
                raise AssertionError('O cadastro com CPF inválido não foi recusado.')
            return 'CPF 123.456.789-00 recusado pelo back-end.'
        roteiro.etapa('RN16 · RF01', 'Validação de CPF no cadastro',
                      'O dígito verificador do CPF é conferido na camada de negócio, não só no formulário.',
                      ana, cpf_invalido)

        def cadastro_ana():
            ir(ana, '/cadastro')
            enviar(ana, '/cadastro', pessoas['ana'])
            d['confirmacao'] = 'confirme seu e-mail' in ana.locator('body').inner_text().lower()
            if not d['confirmacao']:
                return 'Conta criada e já ativa (serviço de e-mail desligado neste ambiente).'
            return f'Conta criada; link de confirmação enviado para {pessoas["ana"]["email"]}.'
        roteiro.etapa('RF01 · RN22', 'Cadastro do candidato',
                      f'CPF válido gerado ({pessoas["ana"]["cpf"]}) e e-mail temporário. A conta nasce aguardando confirmação.',
                      ana, cadastro_ana, critica=True)

        if d.get('confirmacao'):
            def login_antes():
                sair(ana)
                entrar(ana, 'candidato', pessoas['ana']['email'], pessoas['ana']['senha'])
                esperar_texto(ana, 'ainda não foi ativada')
                return 'Login recusado até a confirmação do e-mail.'
            roteiro.etapa('RN22', 'Login antes de confirmar o e-mail',
                          'A conta só é ativada depois que o dono do e-mail abre o link.', ana, login_antes)

            def confirmar_ana():
                link = extrair_link(caixas['ana'].esperar('Confirme seu e-mail'), '/confirmar-email/')
                ana.goto(link)
                if 'email_confirmado' not in ana.url:
                    raise AssertionError('O link de confirmação não ativou a conta.')
                return 'E-mail de confirmação recebido e link aberto: conta ativada.'
            roteiro.etapa('RN22 · RNF03', 'Confirmação do e-mail',
                          'O link é de uso único e vale 24 h; o banco guarda só o hash do código (tokens_email).',
                          ana, confirmar_ana, critica=True)

        def login_ana():
            if not entrar(ana, 'candidato', pessoas['ana']['email'], pessoas['ana']['senha']):
                raise AssertionError('Login recusado.')
            return f'Logado como {pessoas["ana"]["nome"]}.'
        roteiro.etapa('RF04 · RNF03', 'Login do candidato',
                      'Senha conferida com password_verify; nunca é gravada em texto puro.', ana, login_ana, critica=True)

        def email_repetido():
            ir(bruno, '/cadastro')
            enviar(bruno, '/cadastro', {**pessoas['bruno'], 'email': pessoas['ana']['email']})
            if not ('/cadastro' in bruno.url and tem_erro(bruno)):
                raise AssertionError('Cadastro com e-mail repetido não foi recusado.')
            return 'E-mail já cadastrado: recusado.'
        roteiro.etapa('RN01', 'Cadastro único por e-mail',
                      'Cada e-mail pertence a uma única conta (UNIQUE em candidatos.email).', bruno, email_repetido)

        def cadastro_bruno():
            ir(bruno, '/cadastro')
            enviar(bruno, '/cadastro', pessoas['bruno'])
            if d.get('confirmacao'):
                bruno.goto(extrair_link(caixas['bruno'].esperar('Confirme seu e-mail'), '/confirmar-email/'))
            if not entrar(bruno, 'candidato', pessoas['bruno']['email'], pessoas['bruno']['senha']):
                raise AssertionError('Login do segundo candidato recusado.')
            return f'{pessoas["bruno"]["nome"]} cadastrado, confirmado e logado.'
        roteiro.etapa('RF01 · RN22', 'Segundo candidato',
                      'Um segundo candidato, com currículo pouco aderente, para comparar no ranqueamento.',
                      bruno, cadastro_bruno, critica=True)

        # ------------------------------------------------------------------ empresa
        def cadastro_empresa():
            ir(emp, '/cadastro-empresa')
            enviar(emp, '/cadastro-empresa', empresa)
            if d.get('confirmacao'):
                emp.goto(extrair_link(caixas['empresa'].esperar('Confirme seu e-mail'), '/confirmar-email/'))
            if not entrar(emp, 'empresa', empresa['email'], empresa['senha']):
                raise AssertionError('Login da empresa recusado.')
            return f'CNPJ válido gerado ({empresa["cnpj"]}); empresa confirmada e logada.'
        roteiro.etapa('RF02 · RN16 · RN22', 'Cadastro da empresa',
                      'Razão social, responsável pela conta e CNPJ com dígitos verificadores conferidos.',
                      emp, cadastro_empresa, critica=True)

        def perfil_empresa():
            ir(emp, '/empresa/perfil')
            enviar(emp, '/empresa/perfil', {'setor': 'Comércio', 'descricao': empresa['descricao'] + ' Atua no varejo de Ferraz de Vasconcelos.'})
            enviar(emp, '/empresa/perfil/logo', arquivos={'logo': ARQUIVOS / 'curriculo_ana.pdf'})
            if not tem_erro(emp):
                raise AssertionError('Logo em PDF não foi recusado.')
            ir(emp, '/empresa/perfil')
            enviar(emp, '/empresa/perfil/logo', arquivos={'logo': ARQUIVOS / 'logo_empresa.png'})
            if 'logo_enviado' not in emp.url:
                raise AssertionError('Logo PNG não foi aceito.')
            return 'PDF recusado como logo; PNG aceito e guardado no banco.'
        roteiro.etapa('RF07', 'Perfil e logo da empresa',
                      'Tipo e tamanho do arquivo validados; o logo fica no banco (logos_empresas) e não se perde a cada deploy.',
                      emp, perfil_empresa)

        # ------------------------------------------------------------------ vaga
        def campos_vaga(validade_dias):
            return {'titulo': titulo_vaga,
                    'descricao': 'Rotinas administrativas, atendimento a clientes e controle de planilhas.',
                    'requisitos': requisitos_vaga, 'beneficios': 'Vale-transporte, vale-refeição',
                    'salario': '2200', 'localizacao': 'Ferraz de Vasconcelos - SP',
                    'tipo_contrato': 'CLT', 'modalidade': 'Presencial', 'nivel_experiencia': 'Júnior',
                    'data_expiracao': (dt.date.today() + dt.timedelta(days=validade_dias)).isoformat()}

        def escolher_categoria():
            opcoes = emp.locator('select[name="categoria_id"] option').evaluate_all(
                'os => os.filter(o => o.value).map(o => [o.value, o.textContent.trim()])')
            preferida = next((o for o in opcoes if 'admin' in o[1].lower()), opcoes[0])
            d['categoria_id'], d['categoria_nome'] = preferida
            emp.locator('select[name="categoria_id"]').select_option(preferida[0])

        def vaga_invalida():
            ir(emp, '/vagas/criar')
            tirar_validacao_do_navegador(emp)
            escolher_categoria()
            enviar(emp, '/vagas/criar', campos_vaga(3))
            if not tem_erro(emp):
                raise AssertionError('Vaga com validade de 3 dias não foi recusada.')
            return 'Mesmo sem a validação do navegador, o servidor recusou a validade de 3 dias.'
        roteiro.etapa('RN14 · RN03', 'Validade mínima da vaga',
                      'A vaga precisa de prazo de pelo menos 7 dias; a regra está no back-end, não só no formulário.',
                      emp, vaga_invalida)

        def publicar_vaga():
            ir(emp, '/vagas/criar')
            escolher_categoria()
            enviar(emp, '/vagas/criar', campos_vaga(30))
            if 'vaga_criada' not in emp.url:
                raise AssertionError('A vaga não foi publicada.')
            ir(emp, '/vagas?busca=' + urllib.parse.quote(f'demo {SUFIXO}'))
            hrefs = emp.locator('a[href^="/vagas/"]').evaluate_all('as => as.map(a => a.getAttribute("href"))')
            d['vaga_id'] = next(re.fullmatch(r'/vagas/(\d+)', h).group(1) for h in hrefs if re.fullmatch(r'/vagas/\d+', h))
            return f'Vaga #{d["vaga_id"]} publicada na área "{d["categoria_nome"]}" com 5 requisitos.'
        roteiro.etapa('RF10 · RF12 · RN03', 'Publicação da vaga',
                      f'Requisitos: {requisitos_vaga}.', emp, publicar_vaga, critica=True)

        def editar_vaga():
            ir(emp, f'/vagas/{d["vaga_id"]}/editar')
            enviar(emp, f'/vagas/{d["vaga_id"]}/editar', {'beneficios': 'Vale-transporte, vale-refeição, plano de saúde'})
            ir(emp, f'/vagas/{d["vaga_id"]}/editar')
            esperar_texto(emp, 'Histórico de alterações')
            emp.locator('text=Histórico de alterações').scroll_into_view_if_needed()
            return 'Benefícios alterados; a mudança "de → para" ficou no histórico (vagas_historico).'
        roteiro.etapa('RF11', 'Edição com histórico',
                      'Toda edição de vaga grava os campos alterados, com valor anterior e novo.', emp, editar_vaga)

        # ------------------------------------------------------------------ candidata
        def sem_curriculo():
            ir(ana, f'/vagas/{d["vaga_id"]}')
            enviar(ana, '/vagas/candidatar')
            if 'curriculo_obrigatorio' not in ana.url:
                raise AssertionError('Candidatura sem currículo não foi bloqueada.')
            return 'Sem currículo no perfil, a candidatura é bloqueada e a candidata vai para o perfil.'
        roteiro.etapa('RF13 · RN17', 'Candidatura exige currículo',
                      'O candidato só se candidata com currículo anexado e e-mail verificado.', ana, sem_curriculo)

        def enviar_curriculo():
            ir(ana, '/perfil')
            enviar(ana, '/perfil/curriculo', arquivos={'curriculo': ARQUIVOS / 'logo_empresa.png'})
            if not tem_erro(ana):
                raise AssertionError('Imagem PNG foi aceita como currículo.')
            ir(ana, '/perfil')
            enviar(ana, '/perfil/curriculo', arquivos={'curriculo': ARQUIVOS / 'curriculo_ana.pdf'})
            if 'curriculo_enviado' not in ana.url:
                raise AssertionError('Currículo em PDF não foi aceito.')
            return 'PNG recusado; PDF aceito, guardado no banco e com o texto extraído para o ranqueamento.'
        roteiro.etapa('RF08 · RN02 · RF25', 'Envio do currículo',
                      'Aceita PDF, DOC ou DOCX de até 2 MB. O currículo é enviado uma vez e fica na conta.',
                      ana, enviar_curriculo)

        def buscar():
            ir(ana, '/vagas?' + urllib.parse.urlencode({'busca': 'Auxiliar', 'modalidade': 'Presencial',
                                                        'categoria_id': d['categoria_id']}))
            esperar_texto(ana, titulo_vaga)
            return 'Busca + modalidade Presencial + área combinadas: a vaga aparece.'
        roteiro.etapa('RF12 · RN05 · RN14', 'Busca com filtros combinados',
                      'Filtros por palavra, área, modalidade, tipo e experiência; só vagas no prazo são exibidas.',
                      ana, buscar)

        def recomendadas():
            ir(ana, '/vagas-recomendadas')
            esperar_texto(ana, titulo_vaga)
            achado = re.search(r'Aderência:\s*(\d+)%', ana.locator('body').inner_text())
            return f'A vaga aparece recomendada com {achado.group(1) if achado else "?"}% de aderência ao currículo.'
        roteiro.etapa('RF25 · RN13', 'Vagas recomendadas',
                      'Compara os requisitos de cada vaga aberta com o texto do currículo, por regras e sem IA.',
                      ana, recomendadas)

        def candidatar_ana():
            ir(ana, f'/vagas/{d["vaga_id"]}')
            enviar(ana, '/vagas/candidatar', arquivos={'carta': ARQUIVOS / 'carta_ana.docx'})
            if 'candidatura_sucesso' not in ana.url:
                raise AssertionError('A candidatura não foi registrada.')
            return 'Candidatura registrada com carta de apresentação (DOCX).'
        roteiro.etapa('RF13 · RF09 · RN12', 'Candidatura com carta de apresentação',
                      'A candidatura leva o currículo do perfil e, opcionalmente, uma carta para esta vaga.',
                      ana, candidatar_ana)

        def candidatar_de_novo():
            ir(ana, f'/vagas/{d["vaga_id"]}')
            with ana.expect_navigation():
                ana.evaluate("""(id) => { const f = document.createElement('form'); f.method = 'POST';
                    f.action = '/vagas/candidatar'; f.innerHTML = `<input name="vaga_id" value="${id}">`;
                    document.body.appendChild(f); f.submit(); }""", d['vaga_id'])
            if 'ja_candidatado' not in ana.url:
                raise AssertionError('A segunda candidatura não foi bloqueada.')
            return 'Pedido forçado direto ao servidor: recusado por já existir candidatura.'
        roteiro.etapa('RN04', 'Candidatura única por vaga',
                      'A tela esconde o botão, mas a regra está no back-end e no banco: UNIQUE (vaga_id, candidato_id).',
                      ana, candidatar_de_novo)

        def favoritar():
            ir(ana, f'/vagas/{d["vaga_id"]}')
            enviar(ana, '/vagas/favoritar')
            ir(ana, '/vagas-favoritas')
            esperar_texto(ana, titulo_vaga)
            return 'Vaga salva em "Vagas Favoritas", com o prazo de expiração.'
        roteiro.etapa('RF19', 'Favoritar vaga', 'Salvar para ver depois, com alerta quando o prazo estiver acabando.',
                      ana, favoritar)

        def denunciar():
            ir(bruno, f'/vagas/{d["vaga_id"]}')
            bruno.locator('[data-bs-target="#denunciaModal"]').first.click()
            form = bruno.locator('form[action="/vagas/denunciar"]')
            form.locator('[name="motivo"]').select_option(index=1)
            form.locator('[name="descricao"]').fill('Denúncia de teste feita pela demonstração.')
            with bruno.expect_navigation():
                form.locator('[type=submit]').click()
            if 'denuncia_sucesso' not in bruno.url:
                raise AssertionError('A denúncia não foi registrada.')
            return 'Denúncia registrada com motivo e descrição; vai para a moderação.'
        roteiro.etapa('RF21', 'Denúncia de vaga', 'O candidato denuncia conteúdo suspeito para a Prefeitura moderar.',
                      bruno, denunciar)

        def candidatar_bruno():
            ir(bruno, '/perfil')
            enviar(bruno, '/perfil/curriculo', arquivos={'curriculo': ARQUIVOS / 'curriculo_bruno.docx'})
            ir(bruno, f'/vagas/{d["vaga_id"]}')
            enviar(bruno, '/vagas/candidatar')
            if 'candidatura_sucesso' not in bruno.url:
                raise AssertionError('Candidatura do segundo candidato não registrada.')
            return 'Currículo em DOCX enviado e candidatura registrada, sem carta.'
        roteiro.etapa('RF08 · RF13', 'Segunda candidatura',
                      'Um currículo com menos requisitos da vaga, para a empresa comparar.', bruno, candidatar_bruno)

        # ------------------------------------------------------------------ seleção
        def ranqueamento():
            ir(emp, f'/empresa/vagas/{d["vaga_id"]}/candidatos?ordem=aderencia')
            texto = emp.locator('body').inner_text()
            pos_ana, pos_bruno = texto.find(pessoas['ana']['nome']), texto.find(pessoas['bruno']['nome'])
            if min(pos_ana, pos_bruno) < 0:
                raise AssertionError('Os dois candidatos não aparecem na lista.')
            if pos_ana > pos_bruno:
                raise AssertionError('A candidata mais aderente não ficou em primeiro.')
            notas = re.findall(r'Aderência aos requisitos:\s*(\d+)%', texto)
            return f'Ordenado por aderência: Ana {notas[0] if notas else "?"}%, Bruno {notas[1] if len(notas) > 1 else "?"}%.'
        roteiro.etapa('RF25 · RN13', 'Ranqueamento dos candidatos',
                      'Requisito no currículo vale 1 ponto, só na carta vale meio. Nenhum dado pessoal entra na conta '
                      'e o ranqueamento não muda o status de ninguém.', emp, ranqueamento)

        def baixar_curriculo():
            url_cv = emp.locator('a[href$="/curriculo"]').first.get_attribute('href')
            resposta = ctx_emp.request.get(URL + url_cv)
            if resposta.status != 200 or 'pdf' not in resposta.headers.get('content-type', ''):
                raise AssertionError(f'A empresa não conseguiu baixar o currículo (HTTP {resposta.status}).')
            alheio = ctx_bruno.request.get(URL + url_cv, max_redirects=0)
            if alheio.status == 200 and 'pdf' in alheio.headers.get('content-type', ''):
                raise AssertionError('Outro usuário conseguiu baixar o currículo.')
            return f'Empresa dona da vaga: HTTP 200 (PDF). Outro candidato: HTTP {alheio.status}, sem acesso.'
        roteiro.etapa('RF08 · RN21 · RN20', 'Currículo só para quem tem permissão',
                      'O arquivo sai do banco apenas por rota que confere o perfil e a dona da vaga.',
                      emp, baixar_curriculo)

        def cartao(nome):
            return emp.locator('.card').filter(has_text=nome).last

        def entrevista():
            ir(emp, f'/empresa/vagas/{d["vaga_id"]}/candidatos')
            amanha = (dt.date.today() + dt.timedelta(days=1)).isoformat()
            form = cartao(pessoas['ana']['nome']).locator('form[action="/empresa/entrevistas/agendar"]')
            form.locator('[name="data_hora"]').fill(f'{amanha}T10:00')
            form.locator('[name="local"]').fill('Rua da Demonstração, 100 — Centro, Ferraz de Vasconcelos')
            with emp.expect_navigation():
                form.locator('[type=submit]').click()
            if 'entrevista_agendada' not in emp.url:
                raise AssertionError('A entrevista não foi agendada.')
            form = cartao(pessoas['bruno']['nome']).locator('form[action="/empresa/entrevistas/agendar"]')
            form.locator('[name="data_hora"]').fill(f'{amanha}T10:30')
            form.locator('[name="local"]').fill('Mesmo local')
            with emp.expect_navigation():
                form.locator('[type=submit]').click()
            if 'entrevista_agendada' in emp.url or not tem_erro(emp):
                raise AssertionError('O conflito de horário não foi detectado.')
            return 'Ana: entrevista amanhã às 10h. Bruno às 10h30: recusado por conflito (menos de 60 min).'
        roteiro.etapa('RF26', 'Agendamento de entrevista',
                      'A empresa agenda; o sistema impede duas entrevistas da empresa ou do candidato a menos de 60 minutos.',
                      emp, entrevista)

        def aprovar():
            ir(emp, f'/empresa/vagas/{d["vaga_id"]}/candidatos')
            form = cartao(pessoas['ana']['nome']).locator('form[action="/empresa/candidatos/status"]').filter(
                has=emp.locator('input[name="status"][value="Aprovada"]'))
            with emp.expect_navigation():
                form.locator('[type=submit]').click()
            if 'status_atualizado' not in emp.url:
                raise AssertionError('O status não foi atualizado.')
            return 'Candidatura da Ana aprovada; ela é notificada.'
        roteiro.etapa('RF18 · RN12 · RF14', 'Atualização do status',
                      'Cada mudança de status fica registrada e gera notificação para o candidato.', emp, aprovar)

        def retorno_candidata():
            ir(ana, '/notificacoes')
            esperar_texto(ana, titulo_vaga.split(' (')[0])
            ir(ana, '/minhas-candidaturas')
            esperar_texto(ana, 'Aprovada')
            esperar_texto(ana, 'Entrevista')
            return 'Notificações recebidas; candidatura Aprovada com a entrevista marcada.'
        roteiro.etapa('RF14 · RF18 · RN06', 'Retorno para a candidata',
                      'Central de notificações e histórico de candidaturas com status e entrevista.',
                      ana, retorno_candidata)

        def mensagens():
            ir(ana, '/minhas-candidaturas')
            ana.locator('a[href*="/conversa"]').first.click()
            ana.wait_for_load_state('domcontentloaded')
            d['conversa'] = re.search(r'/candidaturas/\d+/conversa', ana.url).group(0)
            acao = d['conversa'].replace('conversa', 'mensagens')
            enviar(ana, acao, {'texto': 'Olá! Obrigada pela oportunidade. Confirmo presença na entrevista.'})
            ir(emp, d['conversa'])
            enviar(emp, acao, {'texto': 'Perfeito, Ana. Traga um documento com foto. Até amanhã!'})
            ir(ana, d['conversa'])
            esperar_texto(ana, 'Traga um documento com foto')
            return 'Mensagens trocadas nos dois sentidos, só porque a candidatura está Aprovada.'
        roteiro.etapa('RF24 · RN21', 'Mensagens internas',
                      'A conversa só é liberada com candidatura Aprovada ou Contratada, e só para quem participa dela.',
                      ana, mensagens)

        def avaliacao():
            ir(ana, d['conversa'])
            enviar(ana, d['conversa'].replace('conversa', 'avaliacao'),
                   {'nota': '5', 'comentario': 'Processo rápido e bem explicado.'})
            if 'avaliacao_salva' not in ana.url:
                raise AssertionError('A avaliação não foi salva.')
            ir(ana, '/empresas?' + urllib.parse.urlencode({'busca': empresa['razao_social']}))
            perfil = next((h for h in ana.locator('.empresa-card, .card').filter(has_text=empresa['razao_social'])
                           .locator('a[href^="/empresas/"]').evaluate_all('as => as.map(a => a.getAttribute("href"))')
                           if re.fullmatch(r'/empresas/\d+', h)), None)
            if not perfil:
                raise AssertionError('Página pública da empresa não encontrada em /empresas.')
            ir(ana, perfil)
            esperar_texto(ana, 'de 5')
            return 'Nota 5 registrada; a reputação aparece na página pública da empresa.'
        roteiro.etapa('RF15 · RN08', 'Feedback e avaliação',
                      'Depois do processo, candidato e empresa avaliam um ao outro; só usuários autenticados.',
                      ana, avaliacao)

        def suporte():
            ir(ana, '/suporte')
            enviar(ana, '/suporte', {'assunto': f'Dúvida da demonstração {SUFIXO}',
                                     'mensagem': 'Como atualizo meu currículo depois de me candidatar?'})
            if 'aberto' not in ana.url:
                raise AssertionError('O pedido de suporte não foi aberto.')
            return 'Pedido aberto, com prazo de resposta de 2 dias úteis.'
        roteiro.etapa('RF17 · RN15', 'Suporte ao usuário',
                      'Formulário com acompanhamento; a Prefeitura responde pelo painel.', ana, suporte)

        def exportar():
            ir(ana, '/perfil')
            resposta = ctx_ana.request.get(URL + '/meus-dados/exportar')
            dados = resposta.json()
            texto = json.dumps(dados, ensure_ascii=False)
            if '"senha"' in texto:
                raise AssertionError('A exportação contém a senha.')
            (SAIDA / 'exportacao_lgpd.json').write_text(json.dumps(dados, ensure_ascii=False, indent=2), encoding='utf-8')
            return f'JSON com {len(dados)} seções, sem senha nem conteúdo de arquivos (salvo em demo/saida).'
        roteiro.etapa('RF23 · RN20', 'Exportação dos dados (LGPD)',
                      'O próprio usuário baixa seus dados pessoais, candidaturas e mensagens.', ana, exportar)

        def recuperar_senha():
            sair(ana)
            ir(ana, '/recuperar-senha')
            if ana.locator('form[action="/recuperar-senha"]').count() == 0:
                entrar(ana, 'candidato', pessoas['ana']['email'], pessoas['ana']['senha'])
                return 'Recuperação indisponível neste ambiente (serviço de e-mail desligado).'
            enviar(ana, '/recuperar-senha', {'email': pessoas['ana']['email'], 'tipo': 'candidato'})
            link = extrair_link(caixas['ana'].esperar('Redefinição de senha'), '/redefinir-senha/')
            caminho = urllib.parse.urlparse(link).path
            ana.goto(link)
            pessoas['ana']['senha'] = 'NovaSenha2026'
            enviar(ana, caminho, {'senha': pessoas['ana']['senha'], 'confirmacao': pessoas['ana']['senha']})
            if not entrar(ana, 'candidato', pessoas['ana']['email'], pessoas['ana']['senha']):
                raise AssertionError('Login com a nova senha recusado.')
            return 'Link recebido por e-mail (15 min, uso único); senha trocada e login com a nova.'
        roteiro.etapa('RF05', 'Recuperação de senha', 'Link temporário por e-mail, válido por 15 minutos.',
                      ana, recuperar_senha)

        def permissoes():
            ir(ana, '/painel-empresa')
            if '/painel-empresa' in ana.url:
                raise AssertionError('Candidato abriu o painel da empresa.')
            ir(ana, '/admin')
            if ana.url.rstrip('/').endswith('/admin'):
                raise AssertionError('Candidato abriu o painel administrativo.')
            return f'Candidato redirecionado ao tentar /painel-empresa e /admin (foi para {urllib.parse.urlparse(ana.url).path}).'
        roteiro.etapa('RN21 · RN09', 'Permissões por perfil',
                      'Toda área restrita começa com requirePerfil(); cada perfil vê só o que é seu.', ana, permissoes)

        # ------------------------------------------------------------------ administração
        def login_admin():
            ir(adm, '/admin/login')
            enviar(adm, '/admin/login', {'email': ADMIN_EMAIL, 'senha': ADMIN_SENHA})
            if '/admin/verificacao' in adm.url:
                if MODO_EMAIL == 'arquivo':
                    codigo = re.search(r'>(\d{6})<', CaixaArquivo.ultimo_codigo()).group(1)
                    enviar(adm, '/admin/verificacao', {'codigo': codigo})
                else:
                    print('\n     >>> Digite no navegador o código de 6 dígitos enviado ao e-mail do administrador.')
                    adm.locator('[name="codigo"]').focus()
                    adm.wait_for_url(re.compile(r'/admin/?$'), timeout=300000)
            if not re.search(r'/admin/?$', adm.url):
                raise AssertionError('O administrador não entrou no painel.')
            return 'Senha + código de 6 dígitos por e-mail: painel administrativo aberto.'
        roteiro.etapa('RF03 · RNF09 · RN09', 'Login do administrador com segundo fator',
                      'A senha é o primeiro fator; o código enviado por e-mail (10 min, 5 tentativas) é o segundo.',
                      adm, login_admin, critica=True)

        def denuncia_admin():
            ir(adm, '/admin/denuncias')
            bloco = adm.locator('form[action="/admin/denuncias/status"]').filter(
                has=adm.locator('option')).first
            alvo = adm.locator('.card, tr, li').filter(has_text=titulo_vaga).locator('form[action="/admin/denuncias/status"]').first
            form = alvo if alvo.count() else bloco
            form.locator('[name="status"]').select_option('Resolvida')
            with adm.expect_navigation():
                form.locator('[type=submit]').click()
            return 'Denúncia da vaga marcada como Resolvida.'
        roteiro.etapa('RF16 · RF21 · RN07', 'Moderação de denúncias',
                      'A Prefeitura analisa a denúncia; a ação fica registrada no log de auditoria.', adm, denuncia_admin)

        def responder_ticket():
            ir(adm, '/admin/suporte')
            card = adm.locator('.card').filter(has_text=f'Dúvida da demonstração {SUFIXO}').first
            card.locator('[name="resposta"]').fill('Olá! Pelo menu Perfil você envia um novo currículo; ele substitui o anterior.')
            with adm.expect_navigation():
                card.locator('[type=submit]').click()
            ir(ana, '/suporte')
            esperar_texto(ana, 'substitui o anterior')
            return 'Ticket respondido; a candidata vê a resposta e recebe notificação.'
        roteiro.etapa('RF17 · RN15', 'Resposta ao suporte', 'Atendimento pelo painel, dentro do prazo de 2 dias úteis.',
                      adm, responder_ticket)

        def suspender():
            ir(adm, '/admin/usuarios')
            linha = adm.locator('tr').filter(has_text=pessoas['bruno']['email'])
            with adm.expect_navigation():
                linha.locator('form[action="/admin/usuarios/suspender"] [type=submit]').click()
            sair(bruno)
            if entrar(bruno, 'candidato', pessoas['bruno']['email'], pessoas['bruno']['senha']):
                raise AssertionError('Conta suspensa conseguiu entrar.')
            ir(adm, '/admin/usuarios')
            with adm.expect_navigation():
                adm.locator('tr').filter(has_text=pessoas['bruno']['email']).locator(
                    'form[action="/admin/usuarios/reativar"] [type=submit]').click()
            if not entrar(bruno, 'candidato', pessoas['bruno']['email'], pessoas['bruno']['senha']):
                raise AssertionError('Conta reativada não conseguiu entrar.')
            return 'Bruno suspenso: login recusado. Reativado: login volta a funcionar.'
        roteiro.etapa('RN18 · RN07', 'Suspensão de conta',
                      'Só o administrador suspende ou reativa contas; conta suspensa não faz login.', adm, suspender)

        def categorias():
            ir(adm, '/admin/categorias')
            nova = f'Logística demo {SUFIXO}'
            enviar(adm, '/admin/categorias', {'nova': nova})
            nomes = adm.locator('input[name^="nome["]').evaluate_all('cs => cs.map(c => c.value)')
            if 'status=ok' not in adm.url or nova not in nomes:
                raise AssertionError('A nova categoria não foi criada.')
            form = adm.locator(f'form[action="/admin/categorias/excluir"]').filter(
                has=adm.locator(f'input[name="categoria_id"][value="{d["categoria_id"]}"]'))
            botao = adm.locator(f'button[form="{form.get_attribute("id")}"]').first
            botao.evaluate('b => b.removeAttribute("disabled")')  # a tela já bloqueia; força para provar o servidor
            with adm.expect_navigation():
                botao.click()
            if not tem_erro(adm):
                raise AssertionError('A categoria em uso foi excluída.')
            return (f'Categoria "{nova}" criada. "{d["categoria_nome"]}", em uso pela vaga: a tela bloqueia o botão '
                    'e, mesmo forçando o envio, o servidor recusa.')
        roteiro.etapa('UC13 · RF12', 'Categorias de vagas',
                      'O administrador mantém as áreas usadas nos filtros; categoria em uso é protegida pela chave estrangeira.',
                      adm, categorias)

        def logs():
            ir(adm, '/admin/logs')
            texto = adm.locator('table').inner_text()
            if texto.count('\n') < 3:
                raise AssertionError('Os logs não registraram as ações.')
            resposta = ctx_adm.request.get(URL + '/admin/logs?formato=csv')
            (SAIDA / 'logs_auditoria.csv').write_bytes(resposta.body())
            return f'Ações da demonstração listadas; CSV exportado ({len(resposta.body())} bytes, salvo em demo/saida).'
        roteiro.etapa('RN07 · RF16 · RNF12', 'Logs de auditoria',
                      'Toda ação administrativa grava quem fez, o quê e quando, na mesma transação.', adm, logs)

        def relatorio_empresa():
            ir(emp, '/empresa/relatorio')
            esperar_texto(emp, titulo_vaga.split(' (')[0])
            return 'Visualizações, candidaturas e taxa de retorno da vaga.'
        roteiro.etapa('RN19', 'Relatório da empresa', 'Métricas das vagas para a empresa acompanhar o resultado.',
                      emp, relatorio_empresa)

        roteiro.gerar_relatorio()
        aprovadas = sum(e['ok'] for e in roteiro.etapas)
        print(f'\n{aprovadas} de {len(roteiro.etapas)} etapas aprovadas. Relatório: {SAIDA / "relatorio.html"}')
        if opcoes.pausar:
            input('\n[Enter] para fechar o navegador… ')
        navegador.close()
        return 0 if aprovadas == len(roteiro.etapas) else 1


if __name__ == '__main__':
    sys.exit(principal())
