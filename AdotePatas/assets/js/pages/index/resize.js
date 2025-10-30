// Seleciona o elemento que você quer modificar
const elemento = document.querySelector('.cachorro'); // troque pelo seletor desejado

// Função que verifica a largura da tela
function verificarLarguraTela() {
  if (window.innerWidth < 992) {
    elemento.classList.remove('d-none'); // remove o d-none em telas menores que 992px
  } else {
    elemento.classList.add('d-none'); // adiciona novamente quando for maior (opcional)
  }
}

// Executa ao carregar a página
verificarLarguraTela();

// Executa toda vez que a janela for redimensionada
window.addEventListener('resize', verificarLarguraTela);