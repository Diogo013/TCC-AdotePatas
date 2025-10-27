// assets/js/pages/autenticacao/modules/ui/toast.js

/**
 * Exibe uma notificação toast
 * @param {string} message - Mensagem a ser exibida
 * @param {string} type - Tipo do toast (success, warning, danger)
 * @param {number} duration - Duração em milissegundos (padrão: 5000ms)
 */
export const showToast = (message, type = 'warning', duration = 5000) => {
    const toast = document.getElementById("toast-notification");
    const toastIcon = document.getElementById("toast-icon");
    const toastMessage = document.getElementById("toast-message");
    
    const lottieAnimations = {
        success: "animações/gatinho-amor.json",
        warning: "animações/gatinho-aviso.json",
        danger: "animações/cachorro_agua_erro.json",
    };

    // Define a mensagem
    toastMessage.textContent = message;
    
    // Define a animação baseada no tipo
    const lottieSrc = lottieAnimations[type] || lottieAnimations.warning;
    toastIcon.innerHTML = `<lottie-player src="${lottieSrc}" background="transparent" speed="1" style="width: 100%; height: 100%; margin-bottom: -30rem; !important" loop autoplay></lottie-player>`;
    
    // Aplica as classes CSS conforme o estilo definido
    toast.className = `toast toast--${type}`;
    toast.style.display = "flex";
    
    // Animação de entrada
    setTimeout(() => {
        toast.classList.add("show");
    }, 100);

    // Configura o fechamento automático
    const closeToast = () => {
        toast.classList.remove("show");
        toast.classList.add("hide");
        setTimeout(() => { 
            toast.style.display = "none";
            toast.classList.remove("hide");
        }, 500);
    };

    // Fecha automaticamente após o tempo definido
    const timeoutId = setTimeout(closeToast, duration);

    // Permite pausar o timeout quando o mouse está sobre o toast
    toast.addEventListener('mouseenter', () => {
        clearTimeout(timeoutId);
    });

    toast.addEventListener('mouseleave', () => {
        setTimeout(closeToast, 1000);
    });
};

/**
 * Inicializa o sistema de notificações (toast) a partir de dados do PHP
 */
export const initToastNotification = () => {
    const phpData = document.getElementById("php-data");
    if (phpData && phpData.dataset.message) {
        const message = phpData.dataset.message;
        const type = phpData.dataset.type || 'danger';
        showToast(message, type);
    }
};