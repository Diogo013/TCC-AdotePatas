// assets/js/pages/autenticacao/modules/forms/form-cadastro-ong.js

import {
    exibirMensagem,
    validarCNPJ,
    validarEmail,
    validarSenha,
    validarConfirmaSenha
} from './validation.js';

/**
 * Inicializa a validação em tempo real e no envio
 * para o formulário de cadastro de ONG.
 */
export const initCadastroOngForm = () => {
    const form = document.getElementById("form-cadastro-ong");
    if (!form) return;

    const nomeCampo = document.getElementById("nome_ong");
    const cnpjCampo = document.getElementById("cnpj");
    const emailCampo = document.getElementById("email_ong");
    const senhaCampo = document.getElementById("senha_ong");
    const confirmaSenhaCampo = document.getElementById("confirma_senha_ong");

    // Adiciona listeners para feedback em tempo real (input)
    if(nomeCampo) nomeCampo.addEventListener('input', () => {
        // Validação simples de "não vazio" para o nome da ONG
        const valido = nomeCampo.value.trim().length > 0;
        exibirMensagem(nomeCampo.id, valido ? "" : "O nome da ONG é obrigatório.", valido);
    });
    if(cnpjCampo) cnpjCampo.addEventListener('input', () => validarCNPJ(cnpjCampo));
    if(emailCampo) emailCampo.addEventListener('input', () => validarEmail(emailCampo));

    if (senhaCampo) {
        senhaCampo.addEventListener('input', () => {
            validarSenha(senhaCampo, 'mensagem-senha_ong');
            if (confirmaSenhaCampo && confirmaSenhaCampo.value.length > 0) {
                validarConfirmaSenha('senha_ong', 'confirma_senha_ong');
            }
        });
    }

    if (confirmaSenhaCampo) {
        confirmaSenhaCampo.addEventListener('input', () => {
            validarConfirmaSenha('senha_ong', 'confirma_senha_ong');
        });
    }

    // Validação final antes do envio (submit)
    form.addEventListener("submit", function (e) {
        const nomeValido = nomeCampo ? nomeCampo.value.trim().length > 0 : false;
        if (!nomeValido) exibirMensagem(nomeCampo.id, "O nome da ONG é obrigatório.", false);

        const cnpjValido = validarCNPJ(cnpjCampo);
        const emailValido = validarEmail(emailCampo);
        const senhaValida = validarSenha(senhaCampo, 'mensagem-senha_ong');
        const confirmaSenhaValida = validarConfirmaSenha('senha_ong', 'confirma_senha_ong');

        if (!nomeValido || !cnpjValido || !emailValido || !senhaValida || !confirmaSenhaValida) {
            e.preventDefault();
            console.log("Formulário ONG inválido. Prevenindo envio.");

            if (!senhaValida) validarSenha(senhaCampo, 'mensagem-senha_ong');
            if (!confirmaSenhaValida) validarConfirmaSenha('senha_ong', 'confirma_senha_ong');
        }
    });
};