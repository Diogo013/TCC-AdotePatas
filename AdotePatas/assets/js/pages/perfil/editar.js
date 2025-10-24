// 1. Importa a função de toast do seu módulo
// Em assets/js/pages/perfil/editar.js
import { showCustomToast } from '../autenticacao/modules/ui/toast.js';

document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profileForm');
    if (!profileForm) return;

    const btnEditar = document.getElementById('btnEditar');
    const btnSalvar = document.getElementById('btnSalvar');
    const inputs = profileForm.querySelectorAll('[data-profile-field]');
    const inputNome = document.getElementById('inputNome');
    const inputEmail = document.getElementById('inputEmail');
    const inputDocumento = document.getElementById('inputDocumento');
    const userTipo = profileForm.querySelector('input[name="user_tipo"]').value;

    // Referências para as divs de feedback (verifique se os IDs no HTML correspondem)
    const feedbackNome = document.getElementById('feedbackNome');
    const feedbackEmail = document.getElementById('feedbackEmail');
    const feedbackDocumento = document.getElementById('feedbackDocumento');

    // --- FUNÇÃO AUXILIAR PARA EXIBIR/LIMPAR FEEDBACK ---
    const showFeedback = (element, message) => {
        if (!element) {
            console.error("Elemento de feedback não encontrado:", element); // Ajuda a depurar
            return;
        }
        element.textContent = message;
        if (message) {
            element.classList.add('show'); // Mostra a div
        } else {
            element.classList.remove('show'); // Esconde a div
        }
    };

    // Função para limpar todas as mensagens de feedback
    const clearAllFeedback = () => {
        showFeedback(feedbackNome, '');
        showFeedback(feedbackEmail, '');
        showFeedback(feedbackDocumento, '');
        // Remove a classe 'is-invalid' caso ainda exista por algum motivo (limpeza extra)
        inputs.forEach(input => input.classList.remove('is-invalid'));
    };

    // --- FUNÇÕES DE VALIDAÇÃO (Retornam apenas a mensagem de erro ou string vazia) ---
    const validarNome = () => {
        const nomeVal = inputNome.value.trim();
        if (nomeVal.length === 0) {
            return "O nome é obrigatório.";
        } else if (nomeVal.split(' ').length < 2) {
            return "Por favor, digite seu nome completo.";
        }
        return ""; // Válido
    };

    const validarEmail = () => {
        const emailVal = inputEmail.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailVal)) {
             return "O formato do e-mail é inválido.";
        }
        return ""; // Válido
    };

    const validarCPF = () => {
        if (!inputDocumento) return "Erro interno: Campo CPF não encontrado."; // Verificação extra
        let cpf = inputDocumento.value.replace(/[^\d]/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return "CPF inválido. Deve conter 11 dígitos não repetidos.";
        }
        let soma = 0;
        for (let i = 0; i < 9; i++) soma += parseInt(cpf.charAt(i)) * (10 - i);
        let resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(9))) return "CPF inválido (dígito verificador 1 incorreto).";

        soma = 0;
        for (let i = 0; i < 10; i++) soma += parseInt(cpf.charAt(i)) * (11 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(10))) return "CPF inválido (dígito verificador 2 incorreto).";

        return ""; // Válido
    };

    const validarCNPJ = () => {
         if (!inputDocumento) return "Erro interno: Campo CNPJ não encontrado."; // Verificação extra
        let cnpj = inputDocumento.value.replace(/[^\d]/g, '');
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
            return "CNPJ inválido. Deve conter 14 dígitos não repetidos.";
        }
        // Validação Dígito 1
        let tamanho = 12; let numeros = cnpj.substring(0, tamanho); let digitos = cnpj.substring(tamanho); let soma = 0; let pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) { soma += parseInt(numeros.charAt(tamanho - i)) * pos--; if (pos < 2) pos = 9; }
        let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
        if (resultado !== parseInt(digitos.charAt(0))) return "CNPJ inválido (dígito verificador 1 incorreto).";
        // Validação Dígito 2
        tamanho = 13; numeros = cnpj.substring(0, tamanho); digitos = cnpj.substring(tamanho); soma = 0; pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) { soma += parseInt(numeros.charAt(tamanho - i)) * pos--; if (pos < 2) pos = 9; }
        resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
        if (resultado !== parseInt(digitos.charAt(1))) return "CNPJ inválido (dígito verificador 2 incorreto).";

        return ""; // Válido
    };

    // --- LÓGICA DAS MÁSCARAS ---
    let inputmaskInstance = null;
    const aplicarMascara = () => {
        if (inputmaskInstance) inputmaskInstance.remove();
        if (!inputDocumento) return; // Garante que o input existe

        if (userTipo === 'adotante') {
            inputmaskInstance = Inputmask("999.999.999-99");
            inputmaskInstance.mask(inputDocumento);
        } else if (userTipo === 'protetor') {
            inputmaskInstance = Inputmask("99.999.999/9999-99");
            inputmaskInstance.mask(inputDocumento);
        }
    };
    
    const removerMascara = () => {
        if (inputmaskInstance) {
            inputmaskInstance.remove();
            inputmaskInstance = null;
        }
    };

    // --- MODO DE EDIÇÃO ---
    btnEditar.addEventListener('click', () => {
        inputs.forEach(input => {
            input.disabled = false;
            input.classList.add('form-control-editable');
            input.classList.remove('is-invalid'); // Garante limpeza inicial
        });
        clearAllFeedback(); // Limpa mensagens de erro antigas
        aplicarMascara();
        btnEditar.classList.add('d-none');
        btnSalvar.classList.remove('d-none');
        if (inputNome) inputNome.focus(); // Foca se existir
    });

    // --- MODO DE SALVAMENTO (SUBMIT) ---
    profileForm.addEventListener('submit', (e) => {
        e.preventDefault();
        clearAllFeedback(); // Limpa mensagens de erro antes de validar
        removerMascara(); // Remove máscara para validação correta dos dígitos

        // Valida e obtém mensagens de erro
        const nomeMsg = inputNome ? validarNome() : '';
        const emailMsg = inputEmail ? validarEmail() : '';
        const docMsg = inputDocumento ? (userTipo === 'adotante' ? validarCPF() : validarCNPJ()) : '';

        aplicarMascara(); // Reaplica máscara imediatamente após validação

        // Exibe mensagens de erro nas divs correspondentes
        showFeedback(feedbackNome, nomeMsg);
        showFeedback(feedbackEmail, emailMsg);
        showFeedback(feedbackDocumento, docMsg);

        // Verifica se houve algum erro
        const houveErro = nomeMsg || emailMsg || docMsg;

        if (houveErro) {
            // Se houve erro, mostra um toast geral e interrompe
            showCustomToast('Por favor, corrija os campos indicados.', 'danger');
            return; // Interrompe o envio
        }

        // Se NENHUM erro foi encontrado, prossegue com o AJAX
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Salvando...';

        const formData = new FormData(profileForm);
        // Pega o valor SEM máscara para enviar ao backend
        const documentoValue = inputDocumento ? inputDocumento.inputmask.unmaskedvalue() : '';
        formData.set('documento', documentoValue); // Atualiza o FormData com o valor sem máscara

        fetch('atualizar-perfil.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) { // Verifica erros HTTP (como 404, 500)
                 throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
         })
        .then(data => {
            if (data.success) {
                showCustomToast('Perfil atualizado com sucesso!', 'success');
                // Atualiza o nome no sidebar se existir
                const sidebarName = document.querySelector('.sidebar-header h5');
                if (sidebarName) sidebarName.textContent = formData.get('nome');

                inputs.forEach(input => {
                    input.disabled = true;
                    input.classList.remove('form-control-editable', 'is-invalid'); // Limpa tudo
                });
                removerMascara(); // Remove máscara ao desabilitar
                clearAllFeedback(); // Limpa mensagens
                btnSalvar.classList.add('d-none');
                btnEditar.classList.remove('d-none');
            } else {
                // Erro Lógico do Servidor (ex: duplicidade, validação backend)
                showCustomToast(data.message || 'Ocorreu um erro ao atualizar.', 'danger');
                // Tenta exibir o erro do servidor no campo correspondente, se possível
                 if (data.message) {
                    if (data.message.toLowerCase().includes('email')) {
                         showFeedback(feedbackEmail, data.message);
                    } else if (data.message.toLowerCase().includes('cpf') || data.message.toLowerCase().includes('cnpj')) {
                         showFeedback(feedbackDocumento, data.message);
                    } else if (data.message.toLowerCase().includes('nome')) {
                        showFeedback(feedbackNome, data.message);
                    }
                 }
            }
        })
        .catch(error => {
            console.error('Erro no fetch ou processamento:', error);
            showCustomToast(`Erro ao processar a solicitação: ${error.message}. Tente novamente.`, 'danger');
        })
        .finally(() => {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="fa-solid fa-check me-1"></i> Salvar Alterações';
            // Garante que a máscara seja reaplicada se a submissão falhar e ainda estiver em modo de edição
            if (!btnSalvar.classList.contains('d-none') && inputDocumento) {
                aplicarMascara();
            }
        });
    });

    // Aplica máscara inicialmente se o campo de documento existir e não estiver desabilitado (raro, mas seguro)
    if (inputDocumento && !inputDocumento.disabled) {
        aplicarMascara();
    }
});