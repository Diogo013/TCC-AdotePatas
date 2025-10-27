  (function() {
    const track = document.getElementById('teamTrack');
    const carousel = document.getElementById('teamCarousel');
    const prevBtn = carousel.querySelector('.team-prev');
    const nextBtn = carousel.querySelector('.team-next');
    let isDown = false, startX, scrollLeft;
    const autoplayDelay = 4000;
    let autoId;

    function itemWidth() {
      const el = track.querySelector('.team-item');
      const gap = parseInt(getComputedStyle(track).gap || 0);
      return el ? (el.offsetWidth + gap) : 0;
    }

    function startAutoplay() {
      stopAutoplay();
      autoId = setInterval(() => {
        if (track.scrollLeft + track.offsetWidth >= track.scrollWidth - 2) {
          track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
          track.scrollBy({ left: itemWidth(), behavior: 'smooth' });
        }
      }, autoplayDelay);
    }

    function stopAutoplay() {
      if (autoId) {
        clearInterval(autoId);
        autoId = null;
      }
    }

    function scrollNext() {
      track.scrollBy({ left: itemWidth(), behavior: 'smooth' });
    }
    function scrollPrev() {
      track.scrollBy({ left: -itemWidth(), behavior: 'smooth' });
    }

    // botões
    nextBtn.addEventListener('click', (e) => {
      stopAutoplay();
      scrollNext();
      startAutoplay();
    });
    prevBtn.addEventListener('click', (e) => {
      stopAutoplay();
      scrollPrev();
      startAutoplay();
    });

    // Drag para mover o carrossel
    track.addEventListener('pointerdown', (e) => {
      isDown = true;
      track.setPointerCapture(e.pointerId);
      startX = e.clientX;
      scrollLeft = track.scrollLeft;
      stopAutoplay();
    });

    track.addEventListener('pointermove', (e) => {
      if (!isDown) return;
      const x = e.clientX;
      const walk = (startX - x);
      track.scrollLeft = scrollLeft + walk;
    });

    track.addEventListener('pointerup', (e) => {
      isDown = false;
      try { track.releasePointerCapture(e.pointerId); } catch (e) {}
      startAutoplay();
    });

    track.addEventListener('pointerleave', (e) => {
      if (isDown) {
        isDown = false;
        startAutoplay();
      }
    });

    // Pausa o autoplay ao passar o mouse
    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);
    carousel.addEventListener('focusin', stopAutoplay);
    carousel.addEventListener('focusout', startAutoplay);

    // Inicia o autoplay
    document.addEventListener('DOMContentLoaded', startAutoplay);
    if (document.readyState === 'complete' || document.readyState === 'interactive') startAutoplay();
  })();