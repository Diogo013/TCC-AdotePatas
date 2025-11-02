/*
 * ==========================================================================
 * INICIALIZAÇÃO DO SCROLLREVEAL.JS
 * ==========================================================================
 * Este arquivo controla as animações de "fade-in" e "slide-in"
 * dos elementos da página conforme o usuário rola a tela.
 * ==========================================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // 1. Configuração padrão do ScrollReveal
    // Vamos usar opções que melhoram a performance e a experiência.
    const sr = ScrollReveal({
        origin: 'top',      // Animação padrão virá de cima
        distance: '60px',   // Distância que o elemento se move
        duration: 1500,     // Duração da animação (1.5 segundos)
        delay: 200,         // Delay padrão para a animação começar
        easing: 'ease-out', // Curva de animação suave
        reset: false        // A animação ocorrerá apenas UMA VEZ (melhor para performance)
    });

    // 2. Animações da Seção HERO
    // Efeitos diferentes para cada elemento criam uma entrada mais dinâmica.
    sr.reveal('.hero-text-content .adote-patas', { origin: 'left' });
    sr.reveal('.hero-text-content .adote-vidas', { origin: 'right', delay: 400 });
    sr.reveal('.hero-text-content p', { origin: 'bottom', delay: 600 });
    sr.reveal('.hero .btn-container', { origin: 'bottom', delay: 800 });
    
    // Animação para a imagem do cachorro em telas móveis
    sr.reveal('#cachorro', { origin: 'bottom', delay: 600 }); 

    // 3. Animações da Seção de CARDS
    // Usamos 'interval' para criar um efeito escalonado (um após o outro).
    sr.reveal('.cards-section .card-item', { 
        origin: 'bottom', 
        interval: 200 // Anima cada card com 200ms de diferença
    });

    // 4. Animações da Seção PETS
    sr.reveal('.pets-section .titulo-adocao', { origin: 'top' });
    sr.reveal('.pets-section .pet-card', {
        origin: 'bottom',
        interval: 200 // Efeito escalonado para os cards de pets
    });
    // Botão "Quero Adotar" centralizado
    sr.reveal('.pets-section .btn-container', { origin: 'bottom' });

    // Animação do Lottiefile do cachorro andando
    sr.reveal('.dog-walking', { 
        origin: 'left', 
        distance: '150px', // Um pouco mais de distância para o efeito de "caminhada"
        delay: 500
    });

    // 5. Animações da Seção ABOUT (Como surgimos?)
    sr.reveal('.about .titulo-about', { origin: 'top' });
    sr.reveal('.about .about-content', { origin: 'left' });
    sr.reveal('.about .card-carousel-container', { origin: 'right', delay: 400 });

});