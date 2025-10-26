// Corrige problemas do offcanvas
document.addEventListener('DOMContentLoaded', function() {
  const offcanvasElement = document.getElementById('offcanvasNavbar');
  const body = document.body;
  
  if (offcanvasElement) {
    const offcanvas = new bootstrap.Offcanvas(offcanvasElement, {
      backdrop: true,
      scroll: false
    });
    
    // Remove qualquer padding que o Bootstrap possa adicionar
    offcanvasElement.addEventListener('show.bs.offcanvas', function() {
      body.classList.add('offcanvas-open');
      // Remove qualquer padding que possa causar recuos
      document.documentElement.style.overflow = 'hidden';
      body.style.overflow = 'hidden';
    });
    
    offcanvasElement.addEventListener('hidden.bs.offcanvas', function() {
      body.classList.remove('offcanvas-open');
      document.documentElement.style.overflow = '';
      body.style.overflow = '';
    });
    
    // Corrige redimensionamento da tela
    window.addEventListener('resize', function() {
      if (body.classList.contains('offcanvas-open')) {
        // Fecha o offcanvas durante o redimensionamento para evitar flickering
        offcanvas.hide();
      }
    });
  }
});