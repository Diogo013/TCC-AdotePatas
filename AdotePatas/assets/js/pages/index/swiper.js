document.addEventListener("DOMContentLoaded", () => {
  const swiper = new Swiper(".mySwiper", {
    // 1. Usamos o efeito "creative", que é o nosso canivete suíço
    effect: "creative",
    grabCursor: true,
    centeredSlides: true,
    slidesPerView: 1, // Mostramos 1 slide principal, os outros são "efeito"
    loop: true,

    // 2. Configuração do efeito para simular os cards
    creativeEffect: {
      // Configuração para os slides ANTERIORES
      prev: {
        shadow: true,
        translate: ["-120%", 0, -500], // Joga para a esquerda e para trás
        rotate: [0, 0, -20], // Rotaciona
      },
      // Configuração para os slides SEGUINTES
      next: {
        shadow: true,
        translate: ["120%", 0, -500], // Joga para a direita e para trás
        rotate: [0, 0, 20], // Rotaciona
      },
    },

    // 3. Nossa função para aplicar os ângulos únicos continua aqui
    on: {
      init: function () {
        const slides = this.slides;
        const rotationAngles = [6, -8, 5, -4, 7];

        slides.forEach((slide, index) => {
          const angle = rotationAngles[index % rotationAngles.length];
          // A variável é definida no slide, mas usada pelo .card-content no CSS
          slide.style.setProperty("--rotation-angle", `${angle}deg`);
        });
      },
      // Bônus: Refaz o cálculo ao mudar de slide no modo loop
      slideChange: function () {
        this.slides.forEach((slide, index) => {
          const rotationAngles = [6, -8, 5, -4, 7];
          const angle = rotationAngles[index % rotationAngles.length];
          slide.style.setProperty("--rotation-angle", `${angle}deg`);
        });
      },
    },
  });
});
