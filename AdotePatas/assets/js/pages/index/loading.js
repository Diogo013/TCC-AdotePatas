window.onload = function () {
  // Esta linha é a chave:
  const loadingScreen = document.getElementById("loading");

  // Este "if" garante que o script não quebre caso o PHP não renderize o HTML
  if (loadingScreen) { 
    setTimeout(() => {
      loadingScreen.classList.add("hidden");
    }, 3000); 
  }
};