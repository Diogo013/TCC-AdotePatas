// assets/js/pages/autenticacao/modules/forms/validation.js

/**
 * Exibe uma mensagem de validação para um campo específico.
 * @param {string} campoId - O ID do campo de input.
 * @param {string} mensagem - A mensagem de erro a ser exibida.
 * @param {boolean} ehValido - Define se o campo é válido ou não.
 */
export const exibirMensagem = (campoId, mensagem, ehValido) => {
    const campo = document.getElementById(campoId);
    const mensagemDiv = document.getElementById(`mensagem-${campoId}`);

    if (mensagemDiv) {
        mensagemDiv.textContent = mensagem;
        if (ehValido || !mensagem) {
            mensagemDiv.classList.remove('visivel');
        } else {
            mensagemDiv.classList.add('visivel');
        }
    }

    if (campo) {
         if (ehValido || !mensagem) {
             campo.classList.remove('invalido');
         } else {
             campo.classList.add('invalido');
         }
    }
};

/**
 * Valida um campo de nome completo.
 * @param {HTMLInputElement} campo - O elemento input do nome.
 * @returns {boolean} - True se válido, false se inválido.
 */
export const validarNome = (campo) => {
    if (!campo) return false;
    const nome = campo.value.trim();
    const ehValido = nome.includes(" ");
    exibirMensagem(campo.id, ehValido ? "" : "Digite seu nome completo.", ehValido);
    return ehValido;
};

/**
 * Valida um campo de CPF.
 * @param {HTMLInputElement} campo - O elemento input do CPF.
 * @returns {boolean} - True se válido, false se inválido.
 */
export const validarCPF = (campo) => {
    if (!campo) return false;
    let cpf = campo.value.replace(/\D/g, "");
    let ehValido = true;
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) { ehValido = false; }
    if (ehValido) {
        let soma = 0, resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) ehValido = false;
    }
    if (ehValido) {
        let soma = 0, resto;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) ehValido = false;
    }
    exibirMensagem(campo.id, ehValido ? "" : "CPF inválido.", ehValido);
    return ehValido;
};
 
/**
 * Valida um campo de e-mail.
 * @param {HTMLInputElement} campo - O elemento input do e-mail.
 * @returns {boolean} - True se válido, false se inválido.
 */
export const validarEmail = (campo) => {
    if (!campo) return false;
    const email = campo.value.trim();
    const regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    const ehValido = regex.test(email);
    exibirMensagem(campo.id, ehValido ? "" : "Formato de e-mail inválido.", ehValido);
    return ehValido;
};
     
/**
 * Valida um campo de CNPJ.
 * @param {HTMLInputElement} campo - O elemento input do CNPJ.
 * @returns {boolean} - True se válido, false se inválido.
 */
export const validarCNPJ = (campo) => {
    if (!campo) return false;

    let cnpj = campo.value.replace(/\D/g, '');

    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
        const mostrarErroTamanho = cnpj.length >= 14 || document.activeElement !== campo;
        exibirMensagem(campo.id, mostrarErroTamanho ? "CNPJ inválido." : "", false);
        return false;
    }

    let ehValido = true;
    let tamanho = 12;
    let numeros = cnpj.substring(0, tamanho);
    let digitosVerificadores = cnpj.substring(tamanho);
    let soma = 0;
    let pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
        if (pos < 2) pos = 9;
    }
    let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado !== parseInt(digitosVerificadores.charAt(0))) {
        ehValido = false;
    }

    if (ehValido) {
        tamanho = 13;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
            if (pos < 2) pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitosVerificadores.charAt(1))) {
            ehValido = false;
        }
    }

    exibirMensagem(campo.id, ehValido ? "" : "CNPJ inválido.", ehValido);
    return ehValido;
};

/**
 * Valida a força de um campo de senha e exibe requisitos faltantes.
 * @param {HTMLInputElement} campoSenha - O elemento input da senha.
 * @param {string} idMensagemDiv - O ID da div onde as mensagens de erro devem ser exibidas.
 * @returns {boolean} - True se a senha for forte, false se não.
 */
export const validarSenha = (campoSenha, idMensagemDiv) => {
    if (!campoSenha) return false;

    const senha = campoSenha.value;
    const mensagemDiv = document.getElementById(idMensagemDiv);

    const requisitos = [
        { regex: /.{8,}/, texto: "mínimo 8 caracteres" },
        { regex: /[A-Z]/, texto: "uma letra maiúscula" },
        { regex: /[0-9]/, texto: "um número" },
        { regex: /[\W_]/, texto: "um caractere especial" }
    ];

    const erros = requisitos
        .filter(req => !req.regex.test(senha))
        .map(req => req.texto);

    const todosValidos = erros.length === 0;
    const mostrarMensagem = senha.length > 0 && !todosValidos;

    let mensagem = "";
    if (mostrarMensagem) {
        mensagem = "A senha precisa ter: " + erros.join(', ') + ".";
    }

    if (mensagemDiv) {
        mensagemDiv.textContent = mensagem;
        mensagemDiv.classList.toggle('visivel', mostrarMensagem);
    }

    campoSenha.classList.toggle('invalido', mostrarMensagem);

    return todosValidos;
};
 
/**
 * Valida se o campo de confirmação de senha coincide com o campo de senha.
 * @param {string} idCampoSenha - O ID do campo de senha original.
 * @param {string} idCampoConfirma - O ID do campo de confirmação de senha.
 * @returns {boolean} - True se as senhas coincidirem, false se não.
 */
export const validarConfirmaSenha = (idCampoSenha, idCampoConfirma) => {
    const campoSenha = document.getElementById(idCampoSenha);
    const campoConfirma = document.getElementById(idCampoConfirma);

    if (!campoSenha || !campoConfirma) return false;
    
    const ehValido = campoSenha.value === campoConfirma.value && campoConfirma.value !== "";
    exibirMensagem(campoConfirma.id, ehValido ? "" : "As senhas não coincidem.", ehValido);
    return ehValido;
};

/**
 * Inicializa os botões de "mostrar/ocultar" senha.
 */
export const initPasswordToggle = () => {
    const togglePasswordIcons = document.querySelectorAll(".toggle-senha");
    togglePasswordIcons.forEach((icon) => {
        icon.addEventListener("click", () => {
            const targetId = icon.dataset.target;
            const targetInput = document.getElementById(targetId);
            if (targetInput.type === "password") {
                targetInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                targetInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        });
    });
};

/**
 * Inicializa as máscaras de input para CPF e CNPJ.
 */
export const initInputMasks = () => {
    const cpfInput = document.getElementById('cpf-cadastro');
    const cnpjInput = document.getElementById('cnpj');
    
    // Máscara de CPF: 000.000.000-00
    if (cpfInput) {
        cpfInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value.substring(0, 14);
        });
    }

    // Máscara de CNPJ: 00.000.000/0000-00
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value.substring(0, 18);
        });
    }
};