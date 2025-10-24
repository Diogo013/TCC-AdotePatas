// Importe a função do seu arquivo toast.js (se estiver usando módulos)
// import { showCustomToast } from './path/to/toast.js'; // Ajuste o caminho

// --- Se não estiver usando módulos, apenas defina a função showCustomToast aqui ---
// --- COPIE A LÓGICA RELEVANTE DE initToastNotification para esta função ---
const showCustomToast = (message, type = 'success') => {
    const duration = 5000;
    const toast = document.getElementById("toast-notification");
    const toastIcon = document.getElementById("toast-icon");
    const toastMessage = document.getElementById("toast-message");
    const progressBar = toast.querySelector(".toast-progress-bar"); // Seleciona a barra de progresso

    if (!toast || !toastIcon || !toastMessage || !progressBar) {
        console.error("Elementos do toast não encontrados!");
        // Fallback para um alert simples se o toast não funcionar
        alert(`${type === 'success' ? 'Sucesso' : 'Erro'}: ${message}`);
        return;
    }

    const lottieAnimations = {
        success: "animações/gatinho-amor.json", // Ajuste os caminhos
        warning: "animações/gatinho-aviso.json",  // Ajuste os caminhos
        danger: "animações/cachorro_agua_erro.json", // Ajuste os caminhos
    };

    toastMessage.textContent = message;
    const lottieSrc = lottieAnimations[type] || lottieAnimations.warning; // Default para warning
    toastIcon.innerHTML = `<lottie-player src="${lottieSrc}" background="transparent" speed="1" style="width: 100%; height: 100%;" loop autoplay></lottie-player>`;
    
    // Define a classe CSS correta para a cor do toast
    toast.className = `toast toast--${type}`; // Reset e aplica a classe correta
    
    // Garante que a barra de progresso reinicie a animação
    progressBar.style.animation = 'none'; // Remove animação antiga
    void progressBar.offsetWidth; // Força reflow para reiniciar a animação CSS
    progressBar.style.animation = `shrink ${duration / 1000}s linear forwards`; // Aplica a nova animação
    
    // Exibe o toast
    toast.style.display = "flex";
    toast.classList.remove("hide"); // Garante que não tenha a classe hide
    toast.classList.add("show");    // Adiciona a classe para animação de entrada

    // Esconde o toast após a duração
    setTimeout(() => {
        toast.classList.remove("show");
        toast.classList.add("hide");
        // Espera a animação de saída terminar antes de esconder com display: none
        setTimeout(() => { 
            if (!toast.classList.contains('show')) { // Verifica se outro toast não foi ativado nesse meio tempo
                 toast.style.display = "none"; 
            }
        }, 500); // Duração da animação slideOut
    }, duration);
};


// editar.js - Correção na aplicação da máscara
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

    // --- FUNÇÕES DE EXIBIÇÃO ---
    const showCustomToast = (message, type = 'success') => {
        // ... (mantenha a função showCustomToast como está)
    };

    const setValidationState = (input, isValid) => {
        if (!input) return;
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
    };

    // --- FUNÇÕES DE VALIDAÇÃO ---
    const validarNome = () => {
        const nomeVal = inputNome.value.trim();
        let isValid = true;
        let message = "";
        
        if (nomeVal.length === 0) {
            isValid = false;
            message = "O nome é obrigatório.";
        } else if (nomeVal.split(' ').length < 2) {
            isValid = false;
            message = "Por favor, digite seu nome completo.";
        }
        
        setValidationState(inputNome, isValid);
        return { isValid, message };
    };

    const validarEmail = () => {
        const emailVal = inputEmail.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(emailVal);
        const message = isValid ? "" : "O formato do e-mail é inválido.";
        
        setValidationState(inputEmail, isValid);
        return { isValid, message };
    };

    const validarCPF = () => {
        let cpf = inputDocumento.value.replace(/[^\d]/g, '');
        let isValid = true;
        let message = "";

        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            isValid = false;
            message = "CPF deve conter 11 dígitos.";
        } else {
            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(9))) {
                isValid = false;
            }
            
            if (isValid) {
                soma = 0;
                for (let i = 0; i < 10; i++) {
                    soma += parseInt(cpf.charAt(i)) * (11 - i);
                }
                resto = (soma * 10) % 11;
                if (resto === 10 || resto === 11) resto = 0;
                if (resto !== parseInt(cpf.charAt(10))) {
                    isValid = false;
                }
            }
        }
        
        message = isValid ? "" : "O CPF informado é inválido.";
        setValidationState(inputDocumento, isValid);
        return { isValid, message };
    };

    const validarCNPJ = () => {
        let cnpj = inputDocumento.value.replace(/[^\d]/g, '');
        let isValid = true;
        let message = "";

        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
            isValid = false;
            message = "CNPJ deve conter 14 dígitos.";
        } else {
            // Validação Dígito 1
            let tamanho = cnpj.length - 2;
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
                isValid = false;
            }

            // Validação Dígito 2
            if (isValid) {
                tamanho = tamanho + 1;
                numeros = cnpj.substring(0, tamanho);
                soma = 0;
                pos = tamanho - 7;
                
                for (let i = tamanho; i >= 1; i--) {
                    soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
                    if (pos < 2) pos = 9;
                }
                
                resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
                if (resultado !== parseInt(digitos.charAt(1))) {
                    isValid = false;
                }
            }
        }
        
        message = isValid ? "" : "O CNPJ informado é inválido.";
        setValidationState(inputDocumento, isValid);
        return { isValid, message };
    };

    // --- CORREÇÃO: Aplicar máscara apenas quando habilitado ---
    let inputmaskInstance = null;

    const aplicarMascara = () => {
        if (inputmaskInstance) {
            inputmaskInstance.remove();
        }
        
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
            input.classList.remove('is-valid', 'is-invalid');
        });

        // Aplica máscara apenas quando habilitar edição
        aplicarMascara();
        
        btnEditar.classList.add('d-none');
        btnSalvar.classList.remove('d-none');
        inputNome.focus();
    });

    // --- MODO DE SALVAMENTO (SUBMIT) ---
    profileForm.addEventListener('submit', (e) => {
        e.preventDefault();

        // Remove máscara temporariamente para validação
        removerMascara();

        // 1. Validações
        const nomeVal = validarNome();
        const emailVal = validarEmail();
        const docVal = (userTipo === 'adotante') ? validarCPF() : validarCNPJ();

        // Reaplica máscara após validação
        aplicarMascara();

        // 2. Verifica se são válidas
        if (!nomeVal.isValid || !emailVal.isValid || !docVal.isValid) {
            showCustomToast(nomeVal.message || emailVal.message || docVal.message, 'danger');
            return;
        }

        // 3. Envio AJAX
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Salvando...';

        const formData = new FormData(profileForm);
        
        // Remove máscara do documento antes de enviar
        const documentoValue = inputDocumento.value.replace(/[^\d]/g, '');
        formData.set('documento', documentoValue);

        fetch('atualizar-perfil.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showCustomToast('Perfil atualizado com sucesso!', 'success');
                document.querySelector('.sidebar-header h5').textContent = formData.get('nome');
                
                // Desabilita campos após sucesso
                inputs.forEach(input => {
                    input.disabled = true;
                    input.classList.remove('form-control-editable', 'is-valid', 'is-invalid');
                });
                removerMascara();
                
                btnSalvar.classList.add('d-none');
                btnEditar.classList.remove('d-none');
            } else {
                showCustomToast(data.message || 'Ocorreu um erro ao atualizar.', 'danger');
                inputs.forEach(input => {
                    input.disabled = false;
                    input.classList.add('form-control-editable');
                });
                aplicarMascara();
            }
        })
        .catch(error => {
            console.error('Erro no fetch:', error);
            showCustomToast('Erro de conexão. Tente novamente.', 'danger');
            inputs.forEach(input => {
                input.disabled = false;
                input.classList.add('form-control-editable');
            });
            aplicarMascara();
        })
        .finally(() => {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="fa-solid fa-check me-1"></i> Salvar Alterações';
        });
    });
});