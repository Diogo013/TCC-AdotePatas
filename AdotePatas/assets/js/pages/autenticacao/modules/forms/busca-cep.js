function attachCepListener(cepFieldId, fieldIds) {
    const cepInput = document.getElementById(cepFieldId);
    if (!cepInput) return;

    const msgElement = document.getElementById(fieldIds.message);
    
    // Função para mostrar/limpar erros
    const showMessage = (message) => {
        if (msgElement) {
            msgElement.textContent = message;
            msgElement.classList.toggle('visivel', !!message);
        }
    };

    // Função para preencher os campos
    const fillAddressFields = (data) => {
        document.getElementById(fieldIds.logradouro).value = data.logradouro || '';
        document.getElementById(fieldIds.bairro).value = data.bairro || '';
        document.getElementById(fieldIds.cidade).value = data.localidade || '';
        document.getElementById(fieldIds.estado).value = data.uf || '';
    };
    
    // Evento de "blur" (quando o usuário sai do campo)
    cepInput.addEventListener('blur', async function () {
        const cep = this.value.replace(/\D/g, ''); // Limpa o CEP
        
        if (cep.length !== 8) {
            if (cep.length > 0) showMessage('CEP inválido.');
            return;
        }

        showMessage('Buscando CEP...');
        cepInput.disabled = true; // Desabilita o campo durante a busca

        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            if (!response.ok) throw new Error('Falha na rede');
            
            const data = await response.json();

            if (data.erro) {
                // CEP não encontrado
                showMessage('CEP não encontrado.');
                fillAddressFields({}); // Limpa os campos
                document.getElementById(fieldIds.logradouro).readOnly = false; // Libera para digitar
            } else {
                // CEP encontrado!
                showMessage(''); // Limpa a mensagem
                fillAddressFields(data);
                
                // Foca no campo "Número", que é o próximo
                const numeroInput = document.getElementById(fieldIds.numero);
                if (numeroInput) numeroInput.focus();
            }

        } catch (error) {
            console.error('Erro ao buscar CEP:', error);
            showMessage('Erro ao buscar. Tente novamente.');
            document.getElementById(fieldIds.logradouro).readOnly = false; // Libera
        } finally {
            cepInput.disabled = false; // Reabilita o campo
        }
    });
}

/**
 * Inicializa os listeners de CEP para todos os formulários da página.
 */
export function initBuscaCep() {
    // Configuração para o formulário de Cadastro de Usuário
    attachCepListener('cep-cadastro', {
        logradouro: 'logradouro-cadastro',
        numero: 'numero-cadastro',
        bairro: 'bairro-cadastro',
        cidade: 'cidade-cadastro',
        estado: 'estado-cadastro',
        message: 'mensagem-cep-cadastro'
    });

    // Configuração para o formulário de Cadastro de ONG
    attachCepListener('cep-ong', {
        logradouro: 'logradouro-ong',
        numero: 'numero-ong',
        bairro: 'bairro-ong',
        cidade: 'cidade-ong',
        estado: 'estado-ong',
        message: 'mensagem-cep-ong'
    });
}