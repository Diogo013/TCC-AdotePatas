// assets/js/pages/autenticacao/modules/ui/toast.js

/**
 * Inicializa o sistema de notificações (toast).
 * Lê as mensagens e tipos do PHP (data-attributes) e exibe o toast.
 */
export const initToastNotification = () => {
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