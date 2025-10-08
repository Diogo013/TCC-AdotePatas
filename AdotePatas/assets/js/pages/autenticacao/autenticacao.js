document.addEventListener("DOMContentLoaded", function () {
  // --- CONTROLE DAS ABAS ---
  const tabs = document.querySelectorAll(".tab-btn");
  const formContainers = document.querySelectorAll(".form-container");
  const pageTitle = document.getElementById("page-title");

  const titleMap = {
    login: "Entrar",
    cadastro_usuario: "Cadastro",
    cadastro_ong: "Cadastro ONG",
  };

  function switchTab(tabId) {
    // A lógica antiga de esconder a mensagem foi REMOVIDA daqui.
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

  // --- CONTROLE DE VISUALIZAÇÃO DE SENHA ---
  const togglePasswordIcons = document.querySelectorAll(".toggle-senha");
  togglePasswordIcons.forEach((icon) => {
    icon.addEventListener("click", function () {
      const targetInput = document.getElementById(this.dataset.target);
      if (targetInput) {
        const type =
          targetInput.getAttribute("type") === "password" ? "text" : "password";
        targetInput.setAttribute("type", type);
        this.classList.toggle("fa-eye-slash");
      }
    });
  });

  // ==========================================================================
  // NOVA LÓGICA DO TOAST DE NOTIFICAÇÃO
  // ==========================================================================
  const phpData = document.getElementById("php-data");

  if (phpData) {
    const message = phpData.dataset.message;
    const type = phpData.dataset.type; // 'success', 'warning', 'danger'
    const duration = 5000; // 5 segundos

    const toast = document.getElementById("toast-notification");
    const toastIcon = document.getElementById("toast-icon");
    const toastMessage = document.getElementById("toast-message");

    // Mapeamento de tipos para animações Lottie
    // IMPORTANTE: Substitua as URLs pelas animações que você escolher!
    const lottieAnimations = {
      success: "animações/gatinho-amor.json", // Exemplo: Checkmark
      warning: "animações/gatinho-aviso.json", // Exemplo: Warning
      danger: "animações/cachorro_agua_erro.json", // Exemplo: Warning
    };

    // Define a mensagem
    toastMessage.textContent = message;

    // Define o ícone (Lottie Player)
    const lottieSrc = lottieAnimations[type] || lottieAnimations["warning"];
    toastIcon.innerHTML = `
      <lottie-player 
        src="${lottieSrc}" 
        background="transparent" 
        speed="1" 
        style="width: 100%; height: 100%;" 
        loop 
        autoplay>
      </lottie-player>`;

    // Aplica a classe de cor correta
    toast.className = "toast"; // Limpa classes antigas
    toast.classList.add(`toast--${type}`);

    // Exibe o toast
    toast.style.display = "flex";
    toast.classList.add("show");

    // Agenda o desaparecimento do toast
    setTimeout(() => {
      toast.classList.remove("show");
      toast.classList.add("hide");
      // Espera a animação de saída terminar para esconder o elemento
      setTimeout(() => {
        toast.style.display = "none";
      }, 500); // Deve ser igual à duração da animação de saída no CSS
    }, duration);
  }

  // --- LÓGICA DO MODAL 'ESQUECI A SENHA' (código existente) ---
  // ... (todo o seu código do modal 'esqueci a senha' continua aqui sem alterações) ...

  // --- INICIALIZAÇÃO DAS ABAS ---
  if (typeof activeTabOnLoad !== "undefined" && activeTabOnLoad) {
    switchTab(activeTabOnLoad);
  } else {
    switchTab("login");
  }
});

// A lógica do modal "esqueci a senha" deve ser colocada aqui se estiver fora do DOMContentLoaded
// Mas no seu código original, ela está dentro, então mantenha como está.
