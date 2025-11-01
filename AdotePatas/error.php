<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Adote Patas</title>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <link rel="stylesheet" href="assets/css/global/fonts/fonts.css">
    <link rel="stylesheet" href="assets/css/global/variables.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* --- Reset Básico e Estilo do Body --- */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-image: url("../../../../images/cadastro-login/background.png");
            color: var(--cor-cinza-texto, #5C5C57);
        }

        /* --- Container Principal (O Segredo do Centering) ---
          Usamos Flexbox para centrar perfeitamente o conteúdo 
          na tela, tanto vertical quanto horizontalmente.
        */
        .help-container {
            display: flex;
            align-items: center;         /* Alinha verticalmente */
            justify-content: center;    /* Alinha horizontalmente */
            min-height: 100vh;          /* Garante 100% da altura da tela */
            padding: 2rem;              /* Espaçamento para não colar nas bordas */
            box-sizing: border-box;
            text-align: center;
        }

          /* ==========================================================================
    BOTÃO VOLTAR
    ========================================================================== */
  .btn-voltar {
    position: absolute; 
    top: 1rem; 
    left: 1rem; 
    z-index: 100; 
    display: inline-flex; 
    align-items: center;
    gap: 0.5rem; 
    padding: 0.75rem 1.25rem;
    color: var(--cor-vermelho);
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
  }


        /* --- Wrapper do Conteúdo ---
          Empilha a animação e o texto verticalmente com um espaçamento.
        */
        .help-content-wrapper {
            display: flex;
            flex-direction: column;     /* Itens um em cima do outro */
            align-items: center;        /* Centra os itens no eixo cruzado (horizontal) */
            gap: 2.5rem;                /* Espaço entre a animação e o texto */
            max-width: 500px;           /* Evita que o texto fique muito largo em desktops */
        }

        /* --- Animação (Spinner) ---
          Criada com CSS puro para máxima performance.
          Usa a cor de destaque do projeto.
        */
        .loading-spinner {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            
            /* Cor base (cinza claro ou pastel) */
            border: 5px solid var(--cor-rosa-pastel, #ffc2c2); 
            
            /* Cor de destaque que irá girar */
            border-top-color: var(--cor-rosa-escuro, #bf6964); 
            
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* --- Texto de Ajuda --- */
        .help-text {
            font-size: 1.5rem;
            font-weight: 500;
            line-height: 1.6; /* Melhora a legibilidade */
        }

        /* --- Acessibilidade (WCAG) ---
          Respeita a preferência do usuário por "Movimento Reduzido".
          Se o usuário desativou animações no sistema, nós a removemos.
        */
        @media (prefers-reduced-motion: reduce) {
            .loading-spinner {
                /* Remove a animação e esconde o spinner */
                display: none; 
            }
            .help-content-wrapper {
                /* Remove o espaço extra quando o spinner some */
                gap: 0; 
            }
        }

    </style>
</head>
<body>

    <a href="./" class="btn-voltar" title="Voltar para a página inicial">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Voltar</span>
    </a>

    <main class="help-container">
        
        <div class="help-content-wrapper">
            
            
                       <lottie-player src="animações/Error.json" background="transparent" speed="1" style="width: 300px; height: 300px; margin-bottom: -50px;"
                            loop autoplay>
                        </lottie-player>
            
            <h5 class="help-text">
                Trabalhando nas configurações para dar o melhor suporte para você.
            </h5>

        </div>

    </main>

    <div vw class="enabled">
  <div vw-access-button class="active"></div>
  <div vw-plugin-wrapper>
    <div class="vw-plugin-top-wrapper"></div>
  </div>
</div>


    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
<script>
  new window.VLibras.Widget('https://vlibras.gov.br/app');
</script>

</body>
</html>