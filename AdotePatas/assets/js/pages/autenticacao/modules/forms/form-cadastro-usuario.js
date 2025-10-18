// assets/js/pages/autenticacao/modules/forms/form-cadastro-usuario.js

import {
    validarNome,
    validarCPF,
    validarEmail,
    validarSenha,
    validarConfirmaSenha
} from './validation.js';

/**
 * Inicializa a validação em tempo real e no envio
 * para o formulário de cadastro de usuário.
 */
export const initCadastroUsuarioForm = () => {
    const form = document.getElementById("form-cadastro");
    if (!form) return;

    const nomeCampo = document.getElementById("nome-completo");
    const cpfCampo = document.getElementById("cpf-cadastro");
    const emailCampo = document.getElementById("email-cadastro");
    const senhaCampo = document.getElementById("senha-cadastro");
    const confirmaSenhaCampo = document.getElementById("confirma-senha-cadastro");

    // Adiciona os listeners para validação em tempo real (input)
    if(nomeCampo) nomeCampo.addEventListener('input', () => validarNome(nomeCampo));
    if(cpfCampo) cpfCampo.addEventListener('input', () => validarCPF(cpfCampo));
    if(emailCampo) emailCampo.addEventListener('input', () => validarEmail(emailCampo));

    if (senhaCampo) {
        senhaCampo.addEventListener('input', () => {
            validarSenha(senhaCampo, 'mensagem-senha-cadastro');
            // Valida a confirmação se ela já foi preenchida
            if (confirmaSenhaCampo && confirmaSenhaCampo.value.length > 0) {
                validarConfirmaSenha('senha-cadastro', 'confirma-senha-cadastro');
            }
        });
    }

    if (confirmaSenhaCampo) {
        confirmaSenhaCampo.addEventListener('input', () => {
            validarConfirmaSenha('senha-cadastro', 'confirma-senha-cadastro');
        });
    }

    // Validação final antes do envio (submit)
    form.addEventListener("submit", function (e) {
        // Roda todas as validações
        const nomeValido = validarNome(nomeCampo);
        const cpfValido = validarCPF(cpfCampo);
        const emailValido = validarEmail(emailCampo);
        const senhaValida = validarSenha(senhaCampo, 'mensagem-senha-cadastro');
        const confirmaSenhaValida = validarConfirmaSenha('senha-cadastro', 'confirma-senha-cadastro');

        if (!nomeValido || !cpfValido || !emailValido || !senhaValida || !confirmaSenhaValida) {
            e.preventDefault(); // Impede o envio do formulário se houver erros
            console.log("Formulário Usuário inválido. Prevenindo envio.");
            
            // Força a exibição de mensagens de erro que podem não ter aparecido
            if (!senhaValida) validarSenha(senhaCampo, 'mensagem-senha-cadastro');
            if (!confirmaSenhaValida) validarConfirmaSenha('senha-cadastro', 'confirma-senha-cadastro');
        }
    });
};