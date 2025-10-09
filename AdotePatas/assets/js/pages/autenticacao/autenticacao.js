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

    // Inicializa a aba correta com base no PHP
    const activeTabOnLoad = window.activeTabOnLoad || "login";
    switchTab(activeTabOnLoad);
  };

  // --- MÓDULO DE VISUALIZAÇÃO DE SENHA ---
  const initPasswordToggle = () => {
    const togglePasswordIcons = document.querySelectorAll(".toggle-senha");
    togglePasswordIcons.forEach((icon) => {
      icon.addEventListener("click", function () {
        const targetInput = document.getElementById(this.dataset.target);
        if (targetInput) {
          const type =
            targetInput.getAttribute("type") === "password"
              ? "text"
              : "password";
          targetInput.setAttribute("type", type);
          this.classList.toggle("fa-eye-slash");
        }
      });
    });
  };

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
        setTimeout(() => {
          toast.style.display = "none";
        }, 500);
      }, duration);
    }
  };

  // --- MÓDULO DO MODAL 'ESQUECI A SENHA' (AJUSTADO) ---
  const initForgotPasswordModal = () => {
    const modal = document.getElementById("modal-esqueci-senha");
    const openBtn = document.getElementById("forgot-password-link");
    const closeBtn = document.getElementById("close-modal-btn");
    const form = document.getElementById("form-recuperar-senha");

    const recoveryFormState = document.getElementById("recovery-form-state");
    const successState = document.getElementById("recovery-success-state");
    const sentEmailAddress = document.getElementById("sent-email-address");
    const errorMsg = document.getElementById("modal-error-msg");

    const resendBtn = document.getElementById("resend-button");
    const timerSpan = document.getElementById("resend-timer");
    let timerInterval;

    const openModal = () => modal.classList.add("active");
    const closeModal = () => {
      modal.classList.remove("active");
      setTimeout(() => {
        successState.classList.add("hidden");
        recoveryFormState.classList.remove("hidden");
        form.reset();
        errorMsg.classList.add("hidden");
      }, 300);
    };

    openBtn.addEventListener("click", (e) => {
      e.preventDefault();
      openModal();
    });

    closeBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });

    // ** LÓGICA DE SUBMISSÃO SIMPLIFICADA **
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const email = document.getElementById("email_recuperar").value;

      if (!email || !email.includes("@")) {
        errorMsg.textContent = "Por favor, insira um e-mail válido.";
        errorMsg.classList.remove("hidden");
        return;
      }

      // Esconde a mensagem de erro se estiver tudo certo
      errorMsg.classList.add("hidden");

      // Simula o sucesso e troca para a tela de confirmação
      showSuccessState(email);
    });

    const showSuccessState = (email) => {
      recoveryFormState.classList.add("hidden");
      successState.classList.remove("hidden");
      sentEmailAddress.textContent = email;
      startResendTimer();
    };

    const startResendTimer = () => {
      let timeLeft = 30;
      resendBtn.disabled = true;
      timerSpan.textContent = timeLeft;

      clearInterval(timerInterval);
      timerInterval = setInterval(() => {
        timeLeft--;
        timerSpan.textContent = timeLeft;
        if (timeLeft <= 0) {
          clearInterval(timerInterval);
          resendBtn.disabled = false;
          resendBtn.innerHTML = "Reenviar";
        }
      }, 1000);
      resendBtn.innerHTML = `Reenviar em (<span id="resend-timer">${timeLeft}</span>s)`;
    };

    resendBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (!resendBtn.disabled) {
        // Simplesmente reinicia o timer ao clicar em reenviar
        startResendTimer();
      }
    });
  };

  // --- INICIALIZAÇÃO GERAL ---
  initTabs();
  initPasswordToggle();
  initToastNotification();
  initForgotPasswordModal();
});
