// assets/js/pages/autenticacao/modules/ui/modal-recovery.js

/**
 * Gerencia todo o ciclo de vida do modal de recuperação de senha,
 * incluindo estados, timers e requisições AJAX para reenvio.
 */
export const initRecoveryModal = () => {
    // 1. Elementos do Modal de Recuperação
    const recoveryModal = document.getElementById("recovery-modal");
    const openRecoveryModalBtn = document.getElementById("open-recovery-modal");
    const closeRecoveryModalBtn = document.getElementById("close-recovery-modal");
    const recoveryForm = document.getElementById("recuperar-form");
    const emailRecuperarInput = document.getElementById("email_recuperar");
    const submitButton = recoveryForm ? recoveryForm.querySelector("button[type='submit']") : null;
    
    // Estados internos do modal
    const recoveryFormState = document.getElementById("recovery-form-state");
    const successState = document.getElementById("success-state");
    const sentEmailAddress = document.getElementById("sent-email-address");
    const resendBtn = document.getElementById("resend-btn");

    let timerInterval = null;

    // 2. Função para mostrar o modal
    const showModal = () => {
        if (recoveryModal) {
            recoveryModal.classList.remove("hidden");
        }
    };

    // 3. Função para esconder o modal
    const hideModal = () => {
        if (recoveryModal) {
            recoveryModal.classList.add("hidden");
            if(successState && recoveryFormState) {
                recoveryFormState.classList.remove("hidden");
                successState.classList.add("hidden");
            }
            if(emailRecuperarInput) emailRecuperarInput.value = '';
            
            // Limpa os parâmetros da URL
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

    // 5. Função para mostrar o estado de erro
    const showErrorState = (message) => {
        console.error("Erro na recuperação:", message);
        // Exibe o erro no formulário (assumindo que existe um <p> para isso)
        const errorP = document.getElementById("recovery-error-message");
        if(errorP) {
            errorP.textContent = message;
            errorP.classList.remove("hidden");
        }

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
        }
    };

    // 7. Lógica de Reenvio de E-mail (AJAX)
    if (resendBtn) {
        resendBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (!resendBtn.disabled) {
                startResendTimer();
                const emailToResend = sentEmailAddress ? sentEmailAddress.textContent : '';
                
                if (emailToResend) {
                    const formData = new FormData();
                    formData.append('email_recuperar', emailToResend);
                    
                    fetch('recuperar-senha.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // O PHP redireciona, então não esperamos um JSON.
                        // Apenas logamos que a ação foi disparada.
                        console.log("Requisição de reenvio enviada.");
                    })
                    .catch(error => {
                        console.error('Falha na comunicação com o servidor durante o reenvio:', error);
                    });
                }
            }
        });
    }

    // 8. Event Listener para o formulário de recuperação (envio inicial)
    if (recoveryForm && submitButton) {
        recoveryForm.addEventListener("submit", (e) => {
            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Enviando...';
            // O PHP cuida do redirecionamento
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

    // 10. Controle de estado de sucesso/erro (vindo da URL)
    const urlParams = new URLSearchParams(window.location.search);
    const recoverySuccess = urlParams.get('recovery_success');
    const recoveryError = urlParams.get('recovery_error');
    const emailFromUrl = urlParams.get('email');
    const activeTab = urlParams.get('active_tab');

    if (activeTab === 'recuperar' && recoveryModal) {
        showModal();
        
        if (recoverySuccess === 'true' && emailFromUrl) {
            showSuccessState(decodeURIComponent(emailFromUrl));
        } else if (recoveryError === 'invalid_email') { 
            showErrorState("O formato do e-mail é inválido ou o campo está vazio.");
        }
        // Adicione mais tratamentos de 'recovery_error' se o PHP enviar outros
    }
};