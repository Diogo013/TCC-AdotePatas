
/**
 * Exibe uma notificação toast SIMPLES (sem ícone Lottie) na página de perfil.
 * @param {string} message - A mensagem a ser exibida.
 * @param {('success'|'warning'|'danger')} [type='success'] - O tipo de toast.
 * @param {number} [duration=5000] - Duração em milissegundos.
 */
export const showProfileToast = (message, type = 'success', duration = 5000) => {
    const toast = document.getElementById("toast-notification");
    const toastIcon = document.getElementById("toast-icon"); // Referência para esconder
    const toastMessage = document.getElementById("toast-message");
    const progressBar = toast ? toast.querySelector(".toast-progress-bar") : null;

    if (!toast || !toastMessage || !progressBar) { // Ícone não é mais essencial aqui
        console.error("Elementos essenciais do toast (perfil) não foram encontrados!");
        alert(`${type === 'success' ? 'Sucesso' : 'Erro'}: ${message}`);
        return;
    }

    // Define a mensagem
    toastMessage.textContent = message;

    // Esconde a área do ícone Lottie
    if (toastIcon) {
        toastIcon.innerHTML = '';
        toastIcon.style.display = 'none';
    }

    toast.className = `toast toast--${type}`;

    progressBar.style.animation = 'none';
    void progressBar.offsetWidth;
    progressBar.style.animation = `shrink ${duration / 1000}s linear forwards`;

    toast.style.display = "flex";
    toast.classList.remove("hide");
    toast.classList.add("show");

    if (toast.hideTimeout) clearTimeout(toast.hideTimeout);
    if (toast.displayTimeout) clearTimeout(toast.displayTimeout);

    toast.hideTimeout = setTimeout(() => {
        toast.classList.remove("show");
        toast.classList.add("hide");
        toast.displayTimeout = setTimeout(() => {
            if (!toast.classList.contains('show')) {
                toast.style.display = "none";
                // Restaura a visibilidade do ícone para outros toasts (importante)
                if (toastIcon) toastIcon.style.display = '';
            }
        }, 500);
    }, duration);
};