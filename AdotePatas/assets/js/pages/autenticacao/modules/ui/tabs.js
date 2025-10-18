// assets/js/pages/autenticacao/modules/ui/tabs.js

/**
 * Inicializa o sistema de abas (Login, Cadastro, ONG)
 * e o roteamento client-side com history.pushState.
 */
export const initTabs = () => {
    const tabs = document.querySelectorAll(".tab-btn");
    const formContainers = document.querySelectorAll(".form-container");
    const pageTitle = document.getElementById("page-title");
    const titleMap = {
        login: "Entrar",
        cadastro_usuario: "Cadastro",
        cadastro_ong: "Cadastro ONG",
    };

    const urlMap = {
        login: "login",
        cadastro_usuario: "cadastro",
        cadastro_ong: "cadastro-ong",
    };

    const switchTab = (tabId, pushToHistory = true) => {
        if (pageTitle && titleMap[tabId]) {
            pageTitle.textContent = titleMap[tabId];
        }

        tabs.forEach((tab) =>
            tab.classList.toggle("active", tab.dataset.tab === tabId)
        );
        formContainers.forEach((container) =>
            container.classList.toggle("active", container.id === tabId)
        );

        if (pushToHistory) {
            const newUrl = urlMap[tabId] || 'login';
            history.pushState({ tabId: tabId }, '', newUrl);
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener("click", (e) => {
            e.preventDefault();
            const tabId = tab.dataset.tab;
            switchTab(tabId, true);
        });
    });

    window.addEventListener("popstate", (event) => {
        const tabId = event.state ? event.state.tabId : "login";
        switchTab(tabId, false);
    });

    // Carregamento inicial baseado na variável global definida pelo PHP
    const initialTab = window.activeTabOnLoad || "login";
    switchTab(initialTab, false);
};