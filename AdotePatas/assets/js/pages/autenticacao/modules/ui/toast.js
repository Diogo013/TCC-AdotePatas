// assets/js/pages/autenticacao/modules/ui/toast.js

/**
 * Exibe uma notificação toast customizada.
 * @param {string} message - A mensagem a ser exibida.
 * @param {('success'|'warning'|'danger')} [type='success'] - O tipo de toast (success, warning, danger).
 * @param {number} [duration=5000] - Duração em milissegundos.
 */
export const showCustomToast = (message, type = 'success', duration = 5000) => {
    const toast = document.getElementById("toast-notification");
    const toastIcon = document.getElementById("toast-icon");
    const toastMessage = document.getElementById("toast-message");
    const progressBar = toast.querySelector(".toast-progress-bar"); // Seleciona a barra de progresso dentro do toast

    if (!toast || !toastIcon || !toastMessage || !progressBar) {
        console.error("Elementos essenciais do toast não foram encontrados no DOM!");
        // Fallback para um alert simples se o toast não funcionar
        alert(`${type === 'success' ? 'Sucesso' : 'Erro'}: ${message}`);
        return;
    }

const lottieAnimations = {
    success: "/TCC-AdotePatas/AdotePatas/animacoes/gatinho-amor.json",
    warning: "/TCC-AdotePatas/AdotePatas/animacoes/gatinho-aviso.json",
    danger: "/TCC-AdotePatas/AdotePatas/animacoes/cachorro_agua_erro.json",
};

    // Define a mensagem
    toastMessage.textContent = message;

    // Define o ícone (Lottie)
    const lottieSrc = lottieAnimations[type] || lottieAnimations.warning; // Default para warning
    toastIcon.innerHTML = `<lottie-player src="${lottieSrc}" background="transparent" speed="1" style="width: 100%; height: 100%;" loop autoplay></lottie-player>`;

    // Define a classe CSS correta para a cor do toast e garante visibilidade
    toast.className = `toast toast--${type}`; // Reset e aplica a classe correta

    // Garante que a barra de progresso reinicie a animação
    progressBar.style.animation = 'none'; // Remove animação antiga
    void progressBar.offsetWidth; // Força reflow (importante para reiniciar animação CSS)
    progressBar.style.animation = `shrink ${duration / 1000}s linear forwards`; // Aplica a nova animação

    // Garante que o toast esteja visível e inicia a animação de entrada
    toast.style.display = "flex";
    toast.classList.remove("hide"); // Garante que não tenha a classe hide remanescente
    toast.classList.add("show");    // Adiciona a classe para animação de entrada

    // Configura o timer para esconder o toast
    // Limpa timers anteriores para evitar comportamentos estranhos se chamado rapidamente
    if (toast.hideTimeout) clearTimeout(toast.hideTimeout);
    if (toast.displayTimeout) clearTimeout(toast.displayTimeout);

    toast.hideTimeout = setTimeout(() => {
        toast.classList.remove("show");
        toast.classList.add("hide");

        // Espera a animação de saída terminar antes de esconder com display: none
        toast.displayTimeout = setTimeout(() => {
            // Verifica novamente se o toast ainda deve ser escondido
            // (pode ter sido reativado nesse meio tempo)
            if (!toast.classList.contains('show')) {
                 toast.style.display = "none";
            }
        }, 500); // Duração da animação slideOut (deve corresponder ao CSS)
    }, duration);
};

/**
 * Função separada para inicializar o toast a partir dos dados do PHP, se existirem.
 * Esta função agora USA a função showCustomToast exportada.
 */
const initToastFromPHP = () => {
    const phpData = document.getElementById("php-data");
    if (phpData && phpData.dataset.message && phpData.dataset.type) {
        const message = phpData.dataset.message;
        const type = phpData.dataset.type;
        // Chama a função genérica para exibir o toast inicial
        showCustomToast(message, type);
    }
};

// Opcional: Se você ainda precisa que o toast apareça automaticamente
// ao carregar a página com base nos dados do PHP, adicione este listener.
// Se não precisar mais disso, pode remover as próximas 3 linhas.
document.addEventListener('DOMContentLoaded', () => {
    initToastFromPHP();
});