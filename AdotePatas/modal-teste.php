<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal de Cookies - Adote Patas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        :root {
            --cor-vermelho: #ff6b6b;
            --cor-rosa-escuro: #ff8787;
            --cor-rosa-pastel: #ffc2c2;
            --cor-branca: #ffffff;
            --cor-cinza-texto: #555555;
            --cor-cinza-claro: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cor-cinza-claro);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Overlay escuro de fundo */
        .cookie-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .cookie-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* Container do Modal */
        .cookie-modal {
            position: fixed;
            bottom: -500px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--cor-branca) 0%, #fefefe 100%);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 550px;
            width: 90%;
            padding: 2rem;
            z-index: 9999;
            transition: bottom 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            border: 3px solid var(--cor-rosa-pastel);
        }

        .cookie-modal.show {
            bottom: 30px;
        }

        /* Cabeçalho com ícone */
        .cookie-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .cookie-icon {
            font-size: 2.5rem;
            animation: rotate 3s infinite ease-in-out;
        }

        @keyframes rotate {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }

        .cookie-header h2 {
            font-size: clamp(1.3rem, 5vw, 1.6rem);
            color: var(--cor-vermelho);
            font-weight: 700;
            margin: 0;
        }

        /* Conteúdo do texto */
        .cookie-content {
            margin-bottom: 1.5rem;
        }

        .cookie-content p {
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            color: var(--cor-cinza-texto);
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }

        .cookie-content a {
            color: var(--cor-vermelho);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .cookie-content a:hover {
            color: var(--cor-rosa-escuro);
            text-decoration: underline;
        }

.cookie-buttons {
    width: 100%;
}


        /* Botão base */
        .cookie-btn {
            flex: 1;
            min-width: 140px;
            padding: 0.85rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Botão Aceitar (destaque) */
        .btn-aceitar {
            background: linear-gradient(135deg, var(--cor-vermelho) 0%, var(--cor-rosa-escuro) 100%);
            color: var(--cor-branca);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            transform: scale(1.05);
            animation: pulse 2s infinite;
            padding: 0.85rem 25%;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            }
            50% {
                box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
            }
        }

        .btn-aceitar:hover {
            background: linear-gradient(135deg, var(--cor-rosa-escuro) 0%, var(--cor-vermelho) 100%);
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.5);
        }

        .btn-aceitar:active {
            transform: scale(1.02);
        }

        .btn-aceitar i {
            font-size: 1.2rem;
        }

        /* Botão Rejeitar (secundário) */
        .btn-rejeitar {
            background-color: transparent;
            color: var(--cor-cinza-texto);
            padding: 1rem;
        }

        .btn-rejeitar:hover {
            background-color: #f0f0f0;
            border-color: var(--cor-cinza-texto);
            transform: translateY(-2px);
        }

        .btn-rejeitar:active {
            transform: scale(0.98);
        }   

        /* Container do botão aceitar com badge */
        .btn-aceitar-wrapper {
            position: relative;
            flex: 1;
            min-width: 140px;
        }

        /* Responsividade */
        @media (max-width: 576px) {
            .cookie-modal {
                padding: 1.5rem;
                width: 95%;
                bottom: -500px;
            }

            .cookie-modal.show {
                bottom: 15px;
            }

            .cookie-buttons {
                flex-direction: column;
            }

            .cookie-btn,
            .btn-aceitar-wrapper {
                min-width: 100%;
            }

            .cookie-icon {
                font-size: 2rem;
            }

            .badge-recomendado {
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }
        }

        /* Animação de saída */
        .cookie-modal.hide {
            animation: slideOut 0.4s ease forwards;
        }

        @keyframes slideOut {
            to {
                bottom: -500px;
                opacity: 0;
            }
        }

        /* Demonstração - remover em produção */
        .demo-controls {
            text-align: center;
            margin-top: 2rem;
        }

        .demo-btn {
            padding: 1rem 2rem;
            background-color: var(--cor-vermelho);
            color: white;
            border: none;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }

        .demo-btn:hover {
            background-color: var(--cor-rosa-escuro);
        }
    </style>
</head>
<body>

    <!-- Overlay escuro -->
    <div class="cookie-overlay" id="cookieOverlay"></div>

    <!-- Modal de Cookies -->
    <div class="cookie-modal" id="cookieModal">
        <div class="cookie-header">
            <span class="cookie-icon">🍪</span>
            <h2>Cookies & Privacidade</h2>
        </div>

        <div class="cookie-content">
            <p>
                Utilizamos cookies para melhorar sua experiência de navegação, personalizar conteúdo e analisar o desempenho do site. 
            </p>
            <p>
                Ao clicar em <strong>"Aceitar Cookies"</strong>, você concorda com o uso de cookies conforme nossa 
                <a href="politicas-privacidade.php" target="_blank">Política de Privacidade</a>.
            </p>
        </div>

        <div class="cookie-buttons">

  <div class="row">
      <div class="col-4">

                    <button class="cookie-btn btn-rejeitar" id="btnRejeitar">
                <i class="bi bi-x-circle"></i>
                Rejeitar
            </button>

      </div>

    <div class="col-8">

        <div class="btn-aceitar-wrapper">
            <button class="cookie-btn btn-aceitar" id="btnAceitar">
                <i class="bi bi-check-circle-fill"></i>
                Aceitar Cookies
            </button>
        </div>
      </div>
      </div>

    </div>
  </div>


    <div class="demo-controls">
        <button class="demo-btn" onclick="showCookieModal()">
            Mostrar Modal de Cookies (Demo)
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

    <script>
        // Função para mostrar o modal
        function showCookieModal() {
            const modal = document.getElementById('cookieModal');
            const overlay = document.getElementById('cookieOverlay');
            
            overlay.classList.add('show');
            setTimeout(() => {
                modal.classList.add('show');
            }, 100);
        }

        // Função para esconder o modal
        function hideCookieModal() {
            const modal = document.getElementById('cookieModal');
            const overlay = document.getElementById('cookieOverlay');
            
            modal.classList.remove('show');
            modal.classList.add('hide');
            
            setTimeout(() => {
                overlay.classList.remove('show');
                modal.classList.remove('hide');
            }, 400);
        }

        // Função para aceitar cookies
        function acceptCookies() {
            // Salva a preferência no localStorage
            localStorage.setItem('cookiesAccepted', 'true');
            localStorage.setItem('cookiesDecision', new Date().toISOString());
            
            console.log('✅ Cookies aceitos!');
            hideCookieModal();
            
            // Aqui você pode adicionar código para ativar cookies/analytics
            // Por exemplo: initializeAnalytics();
        }

        // Função para rejeitar cookies
        function rejectCookies() {
            // Salva a preferência no localStorage
            localStorage.setItem('cookiesAccepted', 'false');
            localStorage.setItem('cookiesDecision', new Date().toISOString());
            
            console.log('❌ Cookies rejeitados!');
            hideCookieModal();
            
            // Aqui você pode adicionar código para desativar cookies não essenciais
        }

        // Event Listeners
        document.getElementById('btnAceitar').addEventListener('click', acceptCookies);
        document.getElementById('btnRejeitar').addEventListener('click', rejectCookies);
        document.getElementById('cookieOverlay').addEventListener('click', hideCookieModal);

        // Verifica se o usuário já fez uma escolha
        function checkCookieConsent() {
            const cookiesAccepted = localStorage.getItem('cookiesAccepted');
            
            // Se não houver decisão, mostra o modal após 1 segundo
            if (cookiesAccepted === null) {
                setTimeout(() => {
                    showCookieModal();
                }, 1000);
            } else {
                console.log('Decisão anterior de cookies:', cookiesAccepted === 'true' ? 'Aceito' : 'Rejeitado');
            }
        }

        // Executa ao carregar a página
        window.addEventListener('load', checkCookieConsent);

        // Função para resetar a escolha (útil para testes)
        function resetCookieConsent() {
            localStorage.removeItem('cookiesAccepted');
            localStorage.removeItem('cookiesDecision');
            console.log('🔄 Preferência de cookies resetada!');
            location.reload();
        }

        // Adiciona função ao console para facilitar testes
        console.log('💡 Para resetar a escolha de cookies, digite: resetCookieConsent()');
    </script>

</body>
</html>