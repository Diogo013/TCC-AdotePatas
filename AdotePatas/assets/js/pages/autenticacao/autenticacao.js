// JAVASCRIPT CORRIGIDO E COMPLETO
document.addEventListener("DOMContentLoaded", function () {

 // --- MÓDULO DE CONTROLE DAS ABAS (COM ROTEAMENTO) ---
const initTabs = () => {
    const tabs = document.querySelectorAll(".tab-btn");
    const formContainers = document.querySelectorAll(".form-container");
    const pageTitle = document.getElementById("page-title");
    const titleMap = {
        login: "Entrar",
        cadastro_usuario: "Cadastro",
        cadastro_ong: "Cadastro ONG",
    };

    // Objeto para mapear o ID da aba para a URL amigável
    const urlMap = {
        login: "login",
        cadastro_usuario: "cadastro",
        cadastro_ong: "cadastro-ong",
    };

    // Função central para trocar de aba e URL
    const switchTab = (tabId, pushToHistory = true) => {
        // Atualiza o título da página
        if (pageTitle && titleMap[tabId]) {
            pageTitle.textContent = titleMap[tabId];
        }

        // Alterna a classe 'active' nos botões e containers
        tabs.forEach((tab) =>
            tab.classList.toggle("active", tab.dataset.tab === tabId)
        );
        formContainers.forEach((container) =>
            container.classList.toggle("active", container.id === tabId)
        );

        // --- MÁGICA DO ROTEAMENTO ---
        if (pushToHistory) {
            const newUrl = urlMap[tabId] || 'login';
            // Atualiza a URL na barra de endereço sem recarregar a página
            history.pushState({ tabId: tabId }, '', newUrl);
        }
    };

    // Adiciona o evento de clique para cada aba
    tabs.forEach((tab) => {
        tab.addEventListener("click", (e) => {
            e.preventDefault(); // Previne qualquer comportamento padrão
            const tabId = tab.dataset.tab;
            switchTab(tabId, true); // Troca a aba e atualiza o histórico
        });
    });

    // Ouve os botões "Voltar" e "Avançar" do navegador
    window.addEventListener("popstate", (event) => {
        // Se o histórico tiver um estado salvo, usa-o. Senão, volta para o login.
        const tabId = event.state ? event.state.tabId : "login";
        switchTab(tabId, false); // Troca a aba sem criar uma nova entrada no histórico
    });

    // --- CARREGAMENTO INICIAL ---
    // Define a aba inicial com base na variável global que o PHP criou
    const initialTab = window.activeTabOnLoad || "login";
    switchTab(initialTab, false); // Mostra a aba correta sem mexer na URL inicial
};

const initPasswordToggle = () => {
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

const initInputMasks = () => {
    const cpfInput = document.getElementById('cpf');
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
}

// --- INÍCIO DO CÓDIGO FALTANTE ---

// --- MÓDULOS DE VALIDAÇÃO (CLIENT-SIDE) ---

const exibirMensagem = (campoId, mensagem, ehValido) => {
    const campo = document.getElementById(campoId);
    const mensagemDiv = document.getElementById(`mensagem-${campoId}`);
    if (mensagemDiv) {
        mensagemDiv.textContent = mensagem;
        mensagemDiv.classList.toggle('visivel', !ehValido && mensagem);
    }
    if (campo) {
        campo.classList.toggle('invalido', !ehValido && mensagem);
    }
};

const validarNome = (campo) => {
    if (!campo) return false;
    const nome = campo.value.trim();
    const ehValido = nome.includes(" ");
    exibirMensagem(campo.id, ehValido ? "" : "Digite seu nome completo.", ehValido);
    return ehValido;
};

const validarCPF = (campo) => {
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
 
const validarEmail = (campo) => {
    if (!campo) return false;
    const email = campo.value.trim();
    const regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    const ehValido = regex.test(email);
    exibirMensagem(campo.id, ehValido ? "" : "Formato de e-mail inválido.", ehValido);
    return ehValido;
};

const validarSenha = (campo) => {
    if (!campo) return false;
    const senha = campo.value;
    const mensagemDiv = document.getElementById('mensagem-senha-cadastro');
    
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

    let mensagem = "";
    if (!todosValidos && senha.length > 0) {
        mensagem = "A senha precisa ter: " + erros.join(', ') + ".";
    }

    if (mensagemDiv) {
        mensagemDiv.textContent = mensagem;
        mensagemDiv.classList.toggle('visivel', !todosValidos && senha.length > 0);
    }
    
    campo.classList.toggle('invalido', !todosValidos && senha.length > 0);
    
    return todosValidos;
};
 
const validarConfirmaSenha = (campoSenha, campoConfirma) => {
    if (!campoSenha || !campoConfirma) return false;
    const ehValido = campoSenha.value === campoConfirma.value && campoConfirma.value !== "";
    exibirMensagem(campoConfirma.id, ehValido ? "" : "As senhas não coincidem.", ehValido);
    return ehValido;
};

// --- MÓDULO PARA INICIALIZAR A VALIDAÇÃO DO FORMULÁRIO DE CADASTRO ---
const initCadastroUsuarioForm = () => {
    const form = document.getElementById("form-cadastro");
    if (!form) return;
    const nomeCampo = document.getElementById("nome-completo");
    const cpfCampo = document.getElementById("cpf-cadastro");
    const emailCampo = document.getElementById("email-cadastro");
    const senhaCampo = document.getElementById("senha-cadastro");
    const confirmaSenhaCampo = document.getElementById("confirma-senha-cadastro");

    // Adiciona os listeners para validação em tempo real
    if(senhaCampo) senhaCampo.addEventListener("input", () => validarSenha(senhaCampo));
    if(confirmaSenhaCampo) confirmaSenhaCampo.addEventListener("input", () => validarConfirmaSenha(senhaCampo, confirmaSenhaCampo));
    
    // Validação final antes do envio
    form.addEventListener("submit", function (e) {
        const nomeValido = validarNome(nomeCampo);
        const cpfValido = validarCPF(cpfCampo);
        const emailValido = validarEmail(emailCampo);
        const senhaValida = validarSenha(senhaCampo);
        const confirmaSenhaValida = validarConfirmaSenha(senhaCampo, confirmaSenhaCampo);

        if (!nomeValido || !cpfValido || !emailValido || !senhaValida || !confirmaSenhaValida) {
            e.preventDefault(); // Impede o envio do formulário se houver erros
        }
    });
};

// --- FIM DO CÓDIGO FALTANTE ---

// --- MÓDULO DE NOTIFICAÇÃO (TOAST) ---
    const initToastNotification = () => {
        const phpData = document.getElementById("php-data");
        if (phpData) {
            const message = phpData.dataset.message;
            const type = phpData.dataset.type;
            const duration = 5000;

            const toast = document.getElementById("toast-notification");
            const toastIcon = document.getElementById("toast-icon");
            const toastMessage = document.getElementById("toast-message");
            
            const lottieAnimations = {
                success: "animações/gatinho-amor.json",
                warning: "animações/gatinho-aviso.json",
                danger: "animações/cachorro_agua_erro.json",
            };

            toastMessage.textContent = message;
            const lottieSrc = lottieAnimations[type] || lottieAnimations.warning;
            toastIcon.innerHTML = `<lottie-player src="${lottieSrc}" background="transparent" speed="1" style="width: 100%; height: 100%;" loop autoplay></lottie-player>`;
            
            toast.className = `toast toast--${type}`;
            toast.style.display = "flex";
            toast.classList.add("show");

            setTimeout(() => {
                toast.classList.remove("show");
                toast.classList.add("hide");
                setTimeout(() => { toast.style.display = "none"; }, 500);
            }, duration);
        }
    };


// --- MÓDULO DE RECUPERAÇÃO DE SENHA ---
const initRecoveryModal = () => {
    // 1. Elementos do Modal de Recuperação
    const recoveryModal = document.getElementById("recovery-modal"); // Modal principal (o wrapper)
    const openRecoveryModalBtn = document.getElementById("open-recovery-modal"); // Botão para abrir (no form de login)
    const closeRecoveryModalBtn = document.getElementById("close-recovery-modal"); // Botão para fechar (no modal)
    const recoveryForm = document.getElementById("recovery-form"); // O formulário de envio
    const emailRecuperarInput = document.getElementById("email_recuperar"); // Input de e-mail no modal
    const submitButton = recoveryForm ? recoveryForm.querySelector("button[type='submit']") : null;
    
    // Estados internos do modal
    const recoveryFormState = document.getElementById("recovery-form-state"); // Conteúdo do formulário (estado inicial)
    const successState = document.getElementById("success-state"); // Conteúdo da mensagem de sucesso
    const sentEmailAddress = document.getElementById("sent-email-address"); // Span para o e-mail enviado
    const resendBtn = document.getElementById("resend-btn"); // Botão de reenvio

    let timerInterval = null; // Para o timer de reenvio

    // --- Funções de Estado ---
    
    // 2. Função para mostrar o modal (remove a classe 'hidden')
    const showModal = () => {
        if (recoveryModal) {
            recoveryModal.classList.remove("hidden");
        }
    };

    // 3. Função para esconder o modal
    const hideModal = () => {
        if (recoveryModal) {
            recoveryModal.classList.add("hidden");
            // Volta para o estado inicial (formulário) ao fechar
            if(successState && recoveryFormState) {
                recoveryFormState.classList.remove("hidden");
                successState.classList.add("hidden");
            }
            // Limpa o input
            if(emailRecuperarInput) emailRecuperarInput.value = '';
            
            // Limpa os parâmetros da URL para evitar reabrir ao recarregar a página desnecessariamente
            const url = new URL(window.location.href);
            url.searchParams.delete('active_tab');
            url.searchParams.delete('recovery_success');
            url.searchParams.delete('recovery_error');
            url.searchParams.delete('email');
            window.history.replaceState({}, document.title, url.toString());
        }
    };
    
    // 4. Função para mostrar o estado de sucesso
    const showSuccessState = (email) => {
        if (recoveryFormState && successState) {
            recoveryFormState.classList.add("hidden");
            successState.classList.remove("hidden");
            if (sentEmailAddress) {
                sentEmailAddress.textContent = email;
            }
            startResendTimer();
        }
    };

    // 5. Função para mostrar o estado de erro (e.g., e-mail inválido ou falha no envio)
    const showErrorState = (message) => {
        // Por simplicidade, exibe um erro no console, mas em um app real deveria ser um alert bonito ou um span
        console.error("Erro na recuperação:", message);
        // Volta para o estado do formulário, se necessário
        if (recoveryFormState && successState) {
            recoveryFormState.classList.remove("hidden");
            successState.classList.add("hidden");
        }
    };

    // 6. Lógica do Timer de Reenvio
    const startResendTimer = () => {
        let timeLeft = 30;
        
        if (resendBtn) {
            resendBtn.disabled = true;
            clearInterval(timerInterval); // Limpa qualquer timer anterior
            
            // Adiciona o span do timer ao botão de reenvio
            resendBtn.innerHTML = `Reenviar em (<span id="resend-timer">${timeLeft}</span>s)`;
            
            timerInterval = setInterval(() => {
                timeLeft--;
                const innerTimerSpan = document.getElementById("resend-timer");
                if(innerTimerSpan) innerTimerSpan.textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = "Reenviar";
                }
            }, 1000);
        }
    };

    // 7. Lógica de Reenvio de E-mail (Implementação do AJAX)
    if (resendBtn) {
        resendBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (!resendBtn.disabled) {
                // Desabilita o botão e inicia o timer imediatamente
                startResendTimer();
                
                // Pega o email que foi enviado (que está visível no modal)
                const emailToResend = sentEmailAddress ? sentEmailAddress.textContent : '';
                
                if (emailToResend) {
                    // Prepara os dados para o envio (simulando um POST do formulário)
                    const formData = new FormData();
                    // O nome do campo de e-mail é 'email_recuperar', conforme recuperar-senha.php
                    formData.append('email_recuperar', emailToResend);
                    
                    // Faz a requisição AJAX para o script que processa a recuperação
                    fetch('recuperar-senha.php', {
                        method: 'POST',
                        body: formData
                        // CRÍTICO: Não precisamos definir Content-Type: application/x-www-form-urlencoded
                        // pois o FormData faz isso automaticamente com o boundary correto.
                    })
                    .then(response => {
                        // O PHP está configurado para sempre redirecionar, então
                        // esta requisição AJAX vai falhar ou retornar o HTML da página de redirecionamento,
                        // o que não é ideal, mas é o comportamento atual do recuperar-senha.php.
                        // Para funcionar corretamente, o script PHP deveria retornar um JSON.
                        
                        // **SOLUÇÃO TEMPORÁRIA:**
                        // Como o script PHP atual redireciona em caso de sucesso, o
                        // reenvio *só vai funcionar visualmente* no front-end (iniciando o timer), 
                        // pois o PHP não retornará uma resposta JSON tratável em caso de sucesso.
                        // Em um projeto de nível mais alto, você criaria um 'recuperar-senha-ajax.php'
                        // que retornaria `{"status": "success"}`.
                        
                        console.log("Requisição de reenvio enviada. O PHP redirecionou, então o status da promise pode ser inesperado, mas a operação de reenvio foi solicitada.");
                        // Não fazemos mais nada aqui no JS, apenas deixamos o timer correr.

                        // Se o PHP fosse ajustado para retornar JSON (como deveria):
                        /*
                        if (!response.ok) {
                             throw new Error('Erro de servidor ao reenviar.');
                        }
                        return response.json();
                        */
                    })
                    .catch(error => {
                        console.error('Falha na comunicação com o servidor durante o reenvio:', error);
                        // Mensagem de erro para o usuário (opcional)
                        // showErrorState("Falha ao solicitar o reenvio. Tente novamente mais tarde."); 
                    });
                }
            }
        });
    }

    // 8. Event Listener para o formulário de recuperação
    if (recoveryForm && submitButton) {
        recoveryForm.addEventListener("submit", (e) => {
            // O formulário está configurado para enviar para recuperar-senha.php. 
            // O PHP é quem fará o redirecionamento com os parâmetros de sucesso ou erro.
            // Aqui, apenas adicionamos o feedback visual de loading
            
            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Enviando...';
            // O PHP cuida do resto (envio do e-mail e redirecionamento)
            
            // Adiciona um listener temporário para resetar o botão em caso de erro no lado do cliente
            // (Embora o PHP deva redirecionar antes que isso seja útil)
            setTimeout(() => {
                if (submitButton.disabled) {
                    submitButton.disabled = false;
                    submitButton.querySelector('span').textContent = 'Enviar';
                }
            }, 5000);
        });
    }

    // 9. Event Listeners para abrir/fechar o modal
    if (openRecoveryModalBtn) {
        openRecoveryModalBtn.addEventListener("click", (e) => {
            e.preventDefault();
            showModal();
        });
    }

    if (closeRecoveryModalBtn) {
        closeRecoveryModalBtn.addEventListener("click", hideModal);
    }

    // 10. Controle de estado de sucesso/erro (se vier da URL, após redirecionamento do PHP)
    const urlParams = new URLSearchParams(window.location.search);
    const recoverySuccess = urlParams.get('recovery_success');
    const recoveryError = urlParams.get('recovery_error');
    const emailFromUrl = urlParams.get('email');
    const activeTab = urlParams.get('active_tab'); // 'recuperar'

    // ESTA É A LÓGICA DE CORREÇÃO: Se o PHP nos mandou de volta para a aba 'recuperar' (via URL), abrimos o modal.
    if (activeTab === 'recuperar' && recoveryModal) {
        showModal();
        
        if (recoverySuccess === 'true' && emailFromUrl) {
            // Se for sucesso, exibimos o estado de sucesso
            showSuccessState(decodeURIComponent(emailFromUrl));
        } else if (recoveryError === 'invalid_email') { 
            // Se for erro, exibimos o erro (o modal já está aberto por showModal())
            showErrorState("O formato do e-mail é inválido ou o campo está vazio. Por favor, tente novamente.");
        }
    }
};

  // --- INICIALIZAÇÃO GERAL ---
  initTabs();
  initPasswordToggle();
  initInputMasks();
  initToastNotification();
  initRecoveryModal(); // Garante que a lógica de recuperação seja inicializada
  initCadastroUsuarioForm();    
});
