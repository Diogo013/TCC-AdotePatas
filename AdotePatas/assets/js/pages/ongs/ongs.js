document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('ongs-container');
    const loadingSpinner = document.getElementById('loading-spinner');

    // Função para buscar os dados
    async function fetchParceiros() {
        try {
            // Ajuste o caminho conforme sua estrutura de pastas real
            const response = await fetch('assets/data/parceiros.json');
            
            if (!response.ok) throw new Error('Erro ao carregar dados');

            const parceiros = await response.json();
            renderParceiros(parceiros);
        } catch (error) {
            console.error("Erro:", error);
            container.innerHTML = `
                <div class="col-12 text-center">
                    <p class="text-muted">Não foi possível carregar os parceiros no momento.</p>
                </div>`;
        } finally {
            // Remove o spinner se ele existir
            if(loadingSpinner) loadingSpinner.remove();
        }
    }

    // Função para renderizar os cards HORIZONTAIS
    function renderParceiros(data) {
        data.forEach(ong => {
            const cardHTML = `
                <div class="col-12 mb-4">
                    <article class="card ong-card-horizontal w-100">
                        <div class="row g-0 h-100">
                            <!-- Imagem -->
                            <div class="col-md-3">
                                <div class="card-img-container h-100">
                                    <img src="${ong.fotoPerfil}" 
                                         alt="Logo da ${ong.nome}" 
                                         class="ong-img-horizontal"
                                         loading="lazy">
                                </div>
                            </div>
                            
                            <!-- Conteúdo -->
                            <div class="col-md-9">
                                <div class="card-body-horizontal d-flex flex-column h-100 p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h2 class="ong-title-horizontal">${ong.nome}</h2>
                                        <span class="badge bg-partner" title="Data de Parceria">
                                            Desde ${ong.dataParceria.split('/')[2]}
                                        </span>
                                    </div>
                                    
                                    <p class="ong-desc-horizontal flex-grow-1">
                                        ${ong.descricao}
                                    </p>
                                    
                                    <div class="ong-footer-horizontal mt-auto pt-3 border-top">
                                         <div class="d-flex justify-content-between align-items-center">
                                            <!-- Lado ESQUERDO - Localização -->
                                            <div class="location-section">
                                                ${ong.local ? `
                                                    <a href="${ong.local}" target="_blank" rel="noopener noreferrer" class="location-btn" aria-label="Ver localização no mapa">
                                                        <i class="bi bi-geo-alt-fill"></i> Localização
                                                    </a>
                                                ` : ''}
                                            </div>
                                            
                                            <!-- Lado DIREITO - Email e Instagram -->
                                            <div class="contact-section d-flex gap-2">
                                                <a href="mailto:${ong.email}" class="email-btn btn-contact">
                                                    <i class="bi bi-envelope-fill"></i> Email
                                                </a>
                                                
                                                ${ong.redeSocial.instagram ? `
                                                    <a href="${ong.redeSocial.instagram}" target="_blank" rel="noopener noreferrer" class="social-btn social-icon insta" aria-label="Instagram">
                                                        <i class="bi bi-instagram"></i> Instagram
                                                    </a>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', cardHTML);
        });
    }

    fetchParceiros();
});