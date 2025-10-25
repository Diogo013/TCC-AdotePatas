// assets/js/pages/perfil/editar.js

// Diagnóstico: lista todos os inputs/selects/textareas do form
if (typeof profileForm !== 'undefined' && profileForm) {
    const elems = profileForm.querySelectorAll('input, select, textarea');
    console.log("=== Lista de elementos no profileForm ===");
    elems.forEach((el, idx) => {
        console.log(idx, {
            tag: el.tagName,
            type: el.type || null,
            id: el.id || null,
            name: el.name || null,
            placeholder: el.placeholder || null,
            valuePreview: (el.value || "").slice(0, 50)
        });
    });
    console.log("=======================================");
} else {
    console.warn("profileForm não definido no escopo quando foi chamado o diagnóstico.");
}


// 1. Importa a função de toast SIMPLES do NOVO ficheiro
import { showProfileToast } from './perfil-toast.js';

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

    // Referências para as divs de feedback
    const feedbackNome = document.getElementById('feedbackNome');
    const feedbackEmail = document.getElementById('feedbackEmail');
    const feedbackDocumento = document.getElementById('feedbackDocumento');

    const clearAllFeedback = () => {
    showFeedback(feedbackNome, '', inputNome);
    showFeedback(feedbackEmail, '', inputEmail);
    showFeedback(feedbackDocumento, '', inputDocumento);
    
    inputs.forEach(input => input.classList.remove('is-invalid'));
};

   const showFeedback = (element, message, inputElement = null) => {
    if (!element) {
        console.error("Elemento de feedback não encontrado:", element?.id);
        return;
    }

    // Coloca o texto da mensagem
    element.textContent = message;

    // Mostra a div apenas se houver mensagem
    if (message && message.trim() !== "") {
        element.classList.add('show');
        // Adiciona classe de erro no input correspondente, se fornecido
        if (inputElement) {
            inputElement.classList.add('is-invalid');
        }
    } else {
        element.classList.remove('show');
        // Remove classe de erro do input correspondente, se fornecido
        if (inputElement) {
            inputElement.classList.remove('is-invalid');
        }
    }
};


const validarNome = () => {
    const inputNome = document.getElementById('inputNome');
    const tipoUsuarioInput = document.querySelector('input[name="user_tipo"]');
    const tipoUsuario = tipoUsuarioInput ? tipoUsuarioInput.value.trim().toLowerCase() : '';
    const nomeVal = inputNome ? inputNome.value.trim() : '';

    console.log("Tipo de usuário:", tipoUsuario, "| Nome digitado:", `"${nomeVal}"`);

    if (!nomeVal) return "O nome é obrigatório.";

    // Se for protetor, não exige nome completo
    if (tipoUsuario === 'protetor') {
        console.log("Usuário é protetor — validação de nome completa ignorada.");
        return "";
    }

    // Se for adotante, exige nome completo
    if (tipoUsuario === 'adotante') {
        const partes = nomeVal.split(/\s+/).filter(part => part.length > 0);
        if (partes.length < 2) return "Por favor, digite seu nome completo (nome e sobrenome).";
    }

    return "";
};




    const validarEmail = () => {
        if (!inputEmail) return '';
        const emailVal = inputEmail.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailVal)) return "O formato do e-mail é inválido.";
        return "";
    };
    const validarCPF = () => {
        if (!inputDocumento) return "Erro: Campo CPF não encontrado.";
        let cpf = inputDocumento.value.replace(/[^\d]/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return "CPF inválido (11 dígitos não repetidos).";
        let soma = 0, resto;
        for (let i = 0; i < 9; i++) soma += parseInt(cpf.charAt(i)) * (10 - i);
        resto = (soma * 10) % 11; if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(9))) return "CPF inválido (dígito 1).";
        soma = 0;
        for (let i = 0; i < 10; i++) soma += parseInt(cpf.charAt(i)) * (11 - i);
        resto = (soma * 10) % 11; if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(10))) return "CPF inválido (dígito 2).";
        return "";
    };
    const validarCNPJ = () => {
        if (!inputDocumento) return "Erro: Campo CNPJ não encontrado.";
        let cnpj = inputDocumento.value.replace(/[^\d]/g, '');

        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
            return "CNPJ inválido (14 dígitos não repetidos).";
        }

        // Cálculo do primeiro dígito verificador
        let tamanho = 12;
        let numeros = cnpj.substring(0, tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
            if (pos < 2) pos = 9;
        }
        let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
        if (resultado !== parseInt(digitos.charAt(0))) {
            return "CNPJ inválido (dígito 1).";
        }

        // Cálculo do segundo dígito verificador (CORRIGIDO)
        tamanho = 13; // Usa os 12 primeiros + o primeiro dígito calculado
        numeros = cnpj.substring(0, tamanho); // Agora pega 13 dígitos
        soma = 0;
        pos = tamanho - 7; // Começa em 6 (13 - 7)
        for (let i = tamanho; i >= 1; i--) {
            soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
            if (pos < 2) pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
        if (resultado !== parseInt(digitos.charAt(1))) { // Compara com o segundo dígito original
            return "CNPJ inválido (dígito 2)."; // Mensagem mantida
        }

        return ""; // Válido
    };

    // --- LÓGICA DAS MÁSCARAS ---
    let inputmaskInstance = null;
    const aplicarMascara = () => {
        if (inputmaskInstance) inputmaskInstance.remove();
        if (!inputDocumento) return;
        try { // Adiciona try-catch para Inputmask
            if (userTipo === 'adotante') {
                inputmaskInstance = Inputmask("999.999.999-99");
            } else if (userTipo === 'protetor') {
                inputmaskInstance = Inputmask("99.999.999/9999-99");
            }
            if (inputmaskInstance) inputmaskInstance.mask(inputDocumento);
        } catch (e) {
            console.error("Erro ao aplicar máscara Inputmask:", e);
        }
    };
    const removerMascara = () => {
        if (inputmaskInstance) {
            try { // Adiciona try-catch para Inputmask
                inputmaskInstance.remove();
            } catch (e) {
                 console.error("Erro ao remover máscara Inputmask:", e);
            } finally {
                 inputmaskInstance = null;
            }
        }
    };

    // --- MODO DE EDIÇÃO ---
    btnEditar.addEventListener('click', () => {
        inputs.forEach(input => {
            input.disabled = false;
            input.classList.add('form-control-editable');
            input.classList.remove('is-invalid');
        });
        clearAllFeedback();
        aplicarMascara();
        btnEditar.classList.add('d-none');
        btnSalvar.classList.remove('d-none');
        if (inputNome) inputNome.focus();
    });

// --- MODO DE SALVAMENTO (SUBMIT) ---
profileForm.addEventListener('submit', (e) => {
    e.preventDefault();

    clearAllFeedback();
    inputs.forEach(input => input.classList.remove('is-invalid'));

    removerMascara(); // Remove máscara antes de validar

    const nomeMsg = validarNome();
    const emailMsg = validarEmail();
    const docMsg = validarDocumento();

if (nomeMsg) showFeedback(feedbackNome, nomeMsg, inputNome);
if (emailMsg) showFeedback(feedbackEmail, emailMsg, inputEmail);
if (docMsg) showFeedback(feedbackDocumento, docMsg, inputDocumento);

const houveErro = nomeMsg || emailMsg || docMsg;

if (houveErro) {
    showProfileToast('Por favor, corrija os campos indicados.', 'danger');
    
    // Reaplica máscara imediatamente em caso de erro
    if (inputDocumento) {
        aplicarMascara();
    }
    return; // interrompe o submit
}
    // 5️⃣ Prepara botão salvar
    btnSalvar.disabled = true;
    btnSalvar.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Salvando...';

    // 6️⃣ Prepara FormData
    const formData = new FormData(profileForm);

    // Ajusta valor do documento sem máscara
    let documentoValue = '';
    if (inputDocumento) {
        try {
            if (inputDocumento.inputmask) {
                documentoValue = inputDocumento.inputmask.unmaskedvalue();
            } else {
                documentoValue = inputDocumento.value.replace(/[^\d]/g, '');
            }
        } catch (err) {
            console.error("Erro ao obter valor sem máscara:", err);
            documentoValue = inputDocumento.value.replace(/[^\d]/g, '');
        }
    }
    formData.set('documento', documentoValue);

    // 7️⃣ Envia via fetch
    fetch('atualizar-perfil.php', { method: 'POST', body: formData })
        .then(response => {
            if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showProfileToast('Perfil atualizado com sucesso!', 'success');

                // Atualiza nome na sidebar
                const sidebarName = document.querySelector('.sidebar-header h5');
                if (sidebarName) sidebarName.textContent = formData.get('nome');

                // Bloqueia inputs novamente
                inputs.forEach(input => {
                    input.disabled = true;
                    input.classList.remove('form-control-editable', 'is-invalid');
                });

                removerMascara();
                clearAllFeedback();

                btnSalvar.classList.add('d-none');
                btnEditar.classList.remove('d-none');
            } else {
                showProfileToast(data.message || 'Ocorreu um erro ao atualizar.', 'danger');

                // Aplica feedback específico por campo
                if (data.message) {
                    const msgLower = data.message.toLowerCase();
                    if (msgLower.includes('nome')) showFeedback(feedbackNome, data.message);
                    if (msgLower.includes('email')) showFeedback(feedbackEmail, data.message);
                    if (msgLower.includes('cpf') || msgLower.includes('cnpj')) showFeedback(feedbackDocumento, data.message);
                }
            }
        })
        .catch(error => {
            console.error('Erro no fetch ou processamento:', error);
            showProfileToast(`Erro: ${error.message}. Tente novamente.`, 'danger');
        })
        .finally(() => {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="fa-solid fa-check me-1"></i> Salvar Alterações';
            if (!btnSalvar.classList.contains('d-none') && inputDocumento) {
                aplicarMascara();
            }
        });
});

    // Função auxiliar para simplificar a validação do documento no submit
 const validarDocumento = () => {
    const tipoUsuarioInput = profileForm.querySelector('input[name="user_tipo"]');
    const tipoUsuarioAtual = tipoUsuarioInput ? tipoUsuarioInput.value.trim().toLowerCase() : '';

    if (!inputDocumento) return '';
    return tipoUsuarioAtual === 'adotante' ? validarCPF() : validarCNPJ();
};

    // Aplica máscara inicial se necessário
    if (inputDocumento && !inputDocumento.disabled) {
        aplicarMascara();
    }
});