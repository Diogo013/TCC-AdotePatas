document.addEventListener("DOMContentLoaded", function () {
  const tabs = document.querySelectorAll(".tab-btn");
  const formContainers = document.querySelectorAll(".form-container");
  const pageTitle = document.getElementById("page-title");

  const titleMap = {
    login: "Entrar",
    cadastro_usuario: "Cadastro",
    cadastro_ong: "Cadastro ONG",
  };

  function switchTab(tabId) {
    if (pageTitle && titleMap[tabId]) {
      pageTitle.textContent = titleMap[tabId];
    }
    tabs.forEach((tab) => {
      tab.classList.toggle("active", tab.dataset.tab === tabId);
    });
    formContainers.forEach((container) => {
      container.classList.toggle("active", container.id === tabId);
    });
  }

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      switchTab(tab.dataset.tab);
    });
  });

  const togglePasswordIcons = document.querySelectorAll(".toggle-senha");
  togglePasswordIcons.forEach((icon) => {
    icon.addEventListener("click", function () {
      const targetInputId = this.dataset.target;
      const passwordInput = document.getElementById(targetInputId);
      if (passwordInput) {
        const type =
          passwordInput.getAttribute("type") === "password"
            ? "text"
            : "password";
        passwordInput.setAttribute("type", type);
        this.classList.toggle("fa-eye-slash");
        this.classList.toggle("fa-eye");
      }
    });
  });

  // --- LÓGICA DO MODAL 'ESQUECI A SENHA' ---
  const forgotPasswordLink = document.getElementById("forgot-password-link");
  const modal = document.getElementById("modal-esqueci-senha");
  const closeModalBtn = document.getElementById("close-modal-btn");

  const recoveryFormState = document.getElementById("recovery-form-state");
  const recoverySuccessState = document.getElementById(
    "recovery-success-state"
  );
  const recoveryForm = document.getElementById("form-recuperar-senha");
  const modalErrorMsg = document.getElementById("modal-error-msg");
  const sentEmailAddress = document.getElementById("sent-email-address");

  const resendButton = document.getElementById("resend-button");
  const resendTimerSpan = document.getElementById("resend-timer");

  let resendInterval;

  const startResendTimer = () => {
    clearInterval(resendInterval); // Limpa qualquer timer anterior
    resendButton.disabled = true;
    let countdown = 30;
    resendTimerSpan.textContent = countdown;
    resendButton.innerHTML = `Reenviar em (<span id="resend-timer">${countdown}</span>s)`;

    resendInterval = setInterval(() => {
      countdown--;
      // É preciso buscar o elemento de novo pois o innerHTML foi reescrito
      document.getElementById("resend-timer").textContent = countdown;
      if (countdown <= 0) {
        clearInterval(resendInterval);
        resendButton.disabled = false;
        resendButton.textContent = "Reenviar e-mail";
      }
    }, 1000);
  };

  const resetModalState = () => {
    recoveryForm.reset();
    modalErrorMsg.classList.add("hidden");
    recoveryFormState.style.display = "block";
    recoverySuccessState.classList.add("hidden");
    clearInterval(resendInterval);
    resendButton.disabled = true;
    resendButton.innerHTML = `Reenviar em (<span id="resend-timer">30</span>s)`;
  };

  if (forgotPasswordLink && modal && closeModalBtn && recoveryForm) {
    forgotPasswordLink.addEventListener("click", (e) => {
      e.preventDefault();
      resetModalState(); // Garante que o modal sempre abra no estado inicial
      modal.classList.add("active");
    });

    const closeModal = () => {
      modal.classList.remove("active");
    };

    closeModalBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        closeModal();
      }
    });

    recoveryForm.addEventListener("submit", (e) => {
      e.preventDefault();
      modalErrorMsg.classList.add("hidden");
      const email = document.getElementById("email_recuperar").value.trim();

      // Simula o envio e troca para a tela de sucesso
      // AQUI IRÁ A LÓGICA DE ENVIO PARA O BACK-END (fetch())
      console.log(`Simulando envio de recuperação para: ${email}`);

      sentEmailAddress.textContent = email;
      recoveryFormState.style.display = "none";
      recoverySuccessState.classList.remove("hidden");
      startResendTimer();
    });

    resendButton.addEventListener("click", () => {
      if (!resendButton.disabled) {
        const email = document.getElementById("email_recuperar").value;
        console.log(`Simulando REENVIO de recuperação para: ${email}`);
        // AQUI IRÁ A LÓGICA DE REENVIO PARA O BACK-END (fetch())
        startResendTimer();
      }
    });
  }

  // --- INICIALIZAÇÃO ---
  if (typeof activeTabOnLoad !== "undefined" && activeTabOnLoad) {
    switchTab(activeTabOnLoad);
  } else {
    switchTab("login");
  }
});
