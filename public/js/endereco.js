(() => {
    const cep = document.getElementById('cep');
    const status = document.getElementById('status-cep');
    const campos = ['logradouro', 'bairro', 'cidade', 'uf'].map(id => document.getElementById(id));
    let consulta;
    cep.addEventListener('input', async () => {
        consulta?.abort();
        const codigo = cep.value.replace(/\D/g, '');
        campos.forEach(campo => { campo.value = ''; });
        status.textContent = '';
        if (codigo.length !== 8) return;

        const requisicao = new AbortController();
        consulta = requisicao;
        status.textContent = 'Consultando CEP…';
        const limite = setTimeout(() => requisicao.abort(), 8000);
        try {
            const resposta = await fetch('https://viacep.com.br/ws/' + codigo + '/json/', {
                signal: requisicao.signal, referrerPolicy: 'no-referrer'
            });
            if (!resposta.ok) throw new Error();
            const dados = await resposta.json();
            if (consulta !== requisicao || cep.value.replace(/\D/g, '') !== codigo) return;
            if (dados.erro) {
                status.textContent = 'CEP não encontrado. Confira o CEP ou preencha o endereço manualmente.';
                return;
            }
            const valores = [dados.logradouro, dados.bairro, dados.localidade, dados.uf];
            campos.forEach((campo, i) => {
                if (!campo.value) campo.value = valores[i] || '';
            });
            status.textContent = campos.every(campo => campo.value)
                ? 'Endereço encontrado. Informe o número da casa ou empresa.'
                : 'Esse CEP tem dados parciais. Complete o endereço e informe o número.';
        } catch {
            if (consulta === requisicao && cep.value.replace(/\D/g, '') === codigo) {
                status.textContent = 'Consulta indisponível. Você pode preencher o endereço manualmente.';
            }
        } finally {
            clearTimeout(limite);
        }
    });
})();
