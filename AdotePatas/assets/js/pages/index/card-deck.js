document.addEventListener("DOMContentLoaded", () => {
  const cardDeck = document.querySelector(".card-deck");
  if (!cardDeck) return; // Se o baralho não existir na página, não faz nada

  const cards = Array.from(cardDeck.querySelectorAll(".card-item-deck"));
  let activeIndex = 0;
  let isDragging = false;
  let startX = 0;
  let dragX = 0;

  // --- CONSTANTES DE CONTROLE ---
  const DRAG_THRESHOLD = 50; // Distância em pixels para ativar a troca de card
  const MAX_DRAG_X = 350; // Limite máximo de arraste em pixels

  /**
   * Função principal que atualiza a aparência de todos os cards
   * com base no card ativo e na interação do usuário.
   */
  function updateCards(isSnappingBack = false) {
    cards.forEach((card, i) => {
      const pos = i - activeIndex;
      const isActive = i === activeIndex;

      // Desativa a transição durante o arraste para uma resposta imediata
      if (!isSnappingBack && isDragging && isActive) {
        card.style.transition = "none";
      } else {
        // Habilita a transição suave para a animação de "snap"
        card.style.transition =
          "transform 0.6s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.6s ease";
      }

      const currentDragX = isDragging && isActive ? dragX : 0;

      card.style.setProperty("--z-index", cards.length - Math.abs(pos));
      card.style.setProperty("--opacity", isActive ? 1 : 0.6);
      card.style.setProperty("--scale", isActive ? 1 : 0.9);

      if (pos > 0) {
        card.style.setProperty(
          "--x-offset",
          `calc(${pos * 10}% + ${currentDragX}px)`
        );
        card.style.setProperty("--y-offset", `${pos * 20}px`);
        card.style.setProperty("--rotation", `${pos * 10}deg`);
      } else {
        card.style.setProperty(
          "--x-offset",
          `calc(${pos * 10}% + ${currentDragX}px)`
        );
        card.style.setProperty("--y-offset", "0px");

        if (isActive) {
          card.style.setProperty("--rotation", "-20deg");
        } else {
          card.style.setProperty("--rotation", `${pos * 5}deg`);
        }
      }
    });
  }

  // --- FUNÇÕES DA LÓGICA DE ARRASTAR ---

  function onDragStart(e) {
    isDragging = true;
    startX = e.pageX || e.touches[0].pageX;
    const activeCard = cards[activeIndex];
    if (activeCard) activeCard.style.transition = "none";
  }

  function onDragMove(e) {
    if (!isDragging) return;
    const currentX = e.pageX || e.touches[0].pageX;
    const currentDragX = currentX - startX;

    dragX = Math.max(-MAX_DRAG_X, Math.min(MAX_DRAG_X, currentDragX));

    updateCards();
  }

  function onDragEnd() {
    if (!isDragging) return;
    isDragging = false;

    if (dragX < -DRAG_THRESHOLD) {
      activeIndex = (activeIndex + 1) % cards.length;
    } else if (dragX > DRAG_THRESHOLD) {
      activeIndex = (activeIndex - 1 + cards.length) % cards.length;
    }

    dragX = 0;
    updateCards(true);
  }

  // Adiciona os Event Listeners ao baralho para mouse e toque
  cardDeck.addEventListener("mousedown", onDragStart);
  cardDeck.addEventListener("touchstart", onDragStart, { passive: true });

  document.addEventListener("mousemove", onDragMove);
  document.addEventListener("touchmove", onDragMove, { passive: true });

  document.addEventListener("mouseup", onDragEnd);
  document.addEventListener("touchend", onDragEnd);

  // --- INICIALIZAÇÃO ---
  // A CHAMADA DA FUNÇÃO FOI REMOVIDA DAQUI
  updateCards();
});
