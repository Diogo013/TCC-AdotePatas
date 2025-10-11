// JAVASCRIPT CORRIGIDO E COMPLETO
document.addEventListener("DOMContentLoaded", function () {

  // --- MÓDULO DE CONTROLE DAS ABAS ---
  const initTabs = () => {
    const tabs = document.querySelectorAll(".tab-btn");
    const formContainers = document.querySelectorAll(".form-container");
    const pageTitle = document.getElementById("page-title");
    const titleMap = {
      login: "Entrar",
      cadastro_usuario: "Cadastro",
      cadastro_ong: "Cadastro ONG",
    };
    const switchTab = (tabId) => {
      if (pageTitle && titleMap[tabId]) {
        pageTitle.textContent = titleMap[tabId];
      }
      tabs.forEach((tab) =>
        tab.classList.toggle("active", tab.dataset.tab === tabId)
      );
      formContainers.forEach((container) =>
        container.classList.toggle("active", container.id === tabId)
      );
    };
    tabs.forEach((tab) =>
      tab.addEventListener("click", () => switchTab(tab.dataset.tab))
    );
    // Corrige a variável que vem do PHP
    const activeTabOnLoad = window.activeTabOnLoad || "login";
    switchTab(activeTabOnLoad);
  };

const initPasswordToggle = () => {
    const togglePasswordIcons = document.querySelectorAll(".toggle-senha");
    togglePasswordIcons.forEach((icon) => {
        icon.addEventListener("click", function () {
            const targetInput = document.getElementById(this.dataset.target);
            if (targetInput) {
                const type = targetInput.getAttribute("type") === "password" ? "text" : "password";
                targetInput.setAttribute("type", type);

                // --- MELHORIA APLICADA ---
                // Alterna as duas classes de ícone. Isso garante que, a cada clique,
                // uma classe seja removida e a outra adicionada, evitando conflitos.
                this.classList.toggle("fa-eye");
                this.classList.toggle("fa-eye-slash");
            }
        });
    });
};


  // --- MÓDULOS DE VALIDAÇÃO (CLIENT-SIDE) ---

  // (Funções de validação: exibirMensagem, validarNome, validarCPF, etc. - Sem alterações)
  const exibirMensagem = (campoId, mensagem, ehValido) => {
    const campo = document.getElementById(campoId);
    const mensagemDiv = document.getElementById(`mensagem-${campoId}`);
    if (mensagemDiv) {
        mensagemDiv.textContent = mensagem;
        mensagemDiv.classList.toggle('visivel', !ehValido && mensagem);
    } else {
        // Fallback para a lista de requisitos da senha
        const listaRequisitos = document.getElementById('mensagem-senha-cadastro');
        if (campoId === 'senha-cadastro' && listaRequisitos) {
            campo.classList.toggle('invalido', !ehValido && mensagem.length > 0);
        }
    }
    if (campo && campoId !== 'senha-cadastro') {
        campo.classList.toggle('invalido', !ehValido && mensagem);
    }
  };

  const validarNome = (campo) => {
    const nome = campo.value.trim();
    const ehValido = nome.includes(" ");
    exibirMensagem(campo.id, ehValido ? "" : "Digite seu nome completo.", ehValido);
    return ehValido;
  };

  const validarCPF = (campo) => {
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
    const email = campo.value.trim();
    const regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    const ehValido = regex.test(email);
    exibirMensagem(campo.id, ehValido ? "" : "Formato de e-mail inválido.", ehValido);
    return ehValido;
  };

  // Em autenticacao.js

const validarSenha = (campo) => {
    const senha = campo.value;
    const requisitos = [
        { regex: /.{8,}/, texto: "mínimo 8 caracteres" },
        { regex: /[A-Z]/, texto: "uma letra maiúscula" },
        { regex: /[0-9]/, texto: "um número" },
        { regex: /[\W_]/, texto: "um caractere especial" }
    ];

    // Cria um array apenas com os erros (requisitos não cumpridos)
    const erros = requisitos
        .filter(req => !req.regex.test(senha))
        .map(req => req.texto);

    const todosValidos = erros.length === 0;

    // Se houver erros, junta-os em uma única string
    let mensagem = "";
    if (!todosValidos && senha.length > 0) {
        mensagem =  erros.join(', ') + ".";
    }

    // Usa a sua função de exibir mensagem original
    exibirMensagem(campo.id, mensagem, todosValidos);

    campo.classList.toggle('invalido', !todosValidos && senha.length > 0);
    
    return todosValidos;
};
  
  const validarConfirmaSenha = (campoSenha, campoConfirma) => {
    const ehValido = campoSenha.value === campoConfirma.value && campoConfirma.value !== "";
    exibirMensagem(campoConfirma.id, ehValido ? "" : "As senhas não coincidem.", ehValido);
    return ehValido;
  };

  // --- MÓDULO PRINCIPAL DO FORMULÁRIO DE CADASTRO ---
  const initCadastroUsuarioForm = () => {
    const form = document.getElementById("form-cadastro");
    if (!form) return;
    const nomeCampo = document.getElementById("nome-completo");
    const cpfCampo = document.getElementById("cpf-cadastro");
    const emailCampo = document.getElementById("email-cadastro");
    const senhaCampo = document.getElementById("senha-cadastro");
    const confirmaSenhaCampo = document.getElementById("confirma-senha-cadastro");

    senhaCampo.addEventListener("input", () => validarSenha(senhaCampo));
    confirmaSenhaCampo.addEventListener("input", () => validarConfirmaSenha(senhaCampo, confirmaSenhaCampo));
    
    form.addEventListener("submit", function (e) {
      const nomeValido = validarNome(nomeCampo);
      const cpfValido = validarCPF(cpfCampo);
      const emailValido = validarEmail(emailCampo);
      const senhaValida = validarSenha(senhaCampo);
      const confirmaSenhaValida = validarConfirmaSenha(senhaCampo, confirmaSenhaCampo);

      if (!nomeValido || !cpfValido || !emailValido || !senhaValida || !confirmaSenhaValida) {
        e.preventDefault();
      }
    });
  };

  // --- MÓDULO DE NOTIFICAÇÃO (TOAST) ---
  const initToastNotification = () => {
    const phpData = document.getElementById("php-data");
    if (phpData) {
      const message = phpData.dataset.message;
      const type = phpData.dataset.type;
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
        setTimeout(() => {
          toast.style.display = "none";
        }, 500);
      }, 5000);
    }
  };

// --- MÓDULO DO MODAL 'ESQUECI A SENHA' (VERSÃO FINAL E SEGURA PARA PRODUÇÃO) ---
const initForgotPasswordModal = () => {
    const modal = document.getElementById("modal-esqueci-senha");
    if (!modal) return;

    const openBtn = document.getElementById("forgot-password-link");
    const closeBtn = document.getElementById("close-modal-btn");
    const form = document.getElementById("form-recuperar-senha");
    
    const emailInput = document.getElementById("email_recuperar");
    const submitButton = form.querySelector('button[type="submit"]');

    const recoveryFormState = document.getElementById("recovery-form-state");
    const successState = document.getElementById("recovery-success-state");
    const sentEmailAddress = document.getElementById("sent-email-address");
    const errorMsg = document.getElementById("modal-error-msg");
    const resendBtn = document.getElementById("resend-button");
    let timerInterval;

    const openModal = () => modal.classList.add("active");
    const closeModal = () => {
        modal.classList.remove("active");
        clearInterval(timerInterval);
        setTimeout(() => {
            successState.classList.add("hidden");
            recoveryFormState.classList.remove("hidden");
            form.reset();
            errorMsg.classList.add("hidden");
        }, 300);
    };

    if (openBtn) openBtn.addEventListener("click", (e) => { e.preventDefault(); openModal(); });
    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });

    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            const email = emailInput.value.trim();

            if (!validarEmail(emailInput)) {
                errorMsg.textContent = "Por favor, insira um e-mail válido.";
                errorMsg.classList.remove("hidden");
                return;
            }
            errorMsg.classList.add("hidden");

            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Verificando...';
            
            try {
                const response = await fetch('verificar-email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: email })
                });

                if (!response.ok) {
                    // Mesmo com erro, não vazamos a informação para o usuário
                    throw new Error('Erro na resposta do servidor.');
                }

                const data = await response.json();

                if (data.exists) {
                    // A lógica de envio de e-mail REAL aconteceria aqui, no lado do servidor.
                    // O front-end não precisa saber de mais nada.
                }
                
                // Ação final: MOSTRAR A TELA DE SUCESSO INDEPENDENTEMENTE DO RESULTADO.
                showSuccessState(email);

            } catch (error) {
                // Em caso de erro, o console.error é mais aceitável, pois indica uma falha
                // e não uma informação de lógica de negócio. Mas mesmo assim, o usuário não vê.
                console.error("Falha na API de verificação de e-mail:", error);
                
                // Mesmo em caso de erro, mostramos a tela de sucesso para o usuário.
                // Isso impede que um atacante use uma falha do sistema para obter informações.
                showSuccessState(email);
            } finally {
                submitButton.disabled = false;
                submitButton.querySelector('span').textContent = 'Enviar';
            }
        });
    }

    const showSuccessState = (email) => {
        recoveryFormState.classList.add("hidden");
        successState.classList.remove("hidden");
        sentEmailAddress.textContent = email;
        startResendTimer();
    };

    const startResendTimer = () => {
        let timeLeft = 30;
        const timerSpan = document.getElementById("resend-timer");
        resendBtn.disabled = true;
        
        clearInterval(timerInterval);
        
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
    };

    if (resendBtn) {
        resendBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (!resendBtn.disabled) {
                startResendTimer();
            }
        });
    }
};

  // --- INICIALIZAÇÃO GERAL ---
  initTabs();
  initPasswordToggle();
  initCadastroUsuarioForm();
  initToastNotification();
  initForgotPasswordModal();

});