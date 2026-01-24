# 🐶 AdotePatas

<img width="1919" height="909" alt="image" src="https://github.com/user-attachments/assets/26777266-3f23-481c-bdb7-68148a948a06" />
> *Conectando corações a novos lares: Uma plataforma completa para adoção responsável.*

## 💻 Sobre o Projeto

O **AdotePatas** é uma solução Full-Stack robusta desenvolvida para facilitar o encontro entre ONGs/protetores e pessoas interessadas em adotar. 

Diferente de catálogos simples, este sistema gerencia **todo o fluxo de adoção**: desde o cadastro e geolocalização do animal, passando pela comunicação via chat, até a moderação de segurança realizada por administradores.

🔗 **Acesse o projeto online:** [https://www.adotepatas.com](https://www.adotepatas.com)

---

## ✨ Funcionalidades Principais

### 👤 Área do Usuário (Doador e Adotante)
- [x] **Cadastro Multi-perfil:** Registro diferenciado para **Pessoas Físicas** e **ONGs**.
- [x] **Gestão de Perfil:** Edição de dados pessoais e personalização de banner de perfil.
- [x] **Interatividade:** Sistema de "Curtir" (Favoritos) e envio de formulário de interesse.
- [x] **Chat Integrado:** Comunicação direta em tempo real entre interessado e doador.
- [x] **Segurança:** Recuperação de senha ("Esqueci minha senha").

### 🐾 Gestão de Pets
- [x] **CRUD Completo:** Cadastro, Edição e Visualização de animais.
- [x] **Geolocalização:** Integração com **Google Maps API** para visualizar pets próximos.
- [x] **Busca Avançada:** Filtros por nome, espécie, porte e localização.

### 🛡️ Painel Administrativo (Back-office)
- [x] **Dashboard Geral:** Visão macro de usuários, ONGs, pets e adoções em andamento.
- [x] **Sistema de Moderação:** Todo pet cadastrado entra em "Análise". O admin aprova ou reprova para impedir conteúdo ilícito.
- [x] **Controle de Usuários:** Visualização e gestão da base de cadastros.

---

## 🛠️ Tecnologias Utilizadas

O projeto foi construído com foco em **Performance** e **Regras de Negócio**:

- **Front-End:** HTML5, CSS3, JavaScript (ES6+).
- **Back-End:** PHP (Estruturado/MVC).
- **Banco de Dados:** MySQL (Relacional).
- **APIs Externas:** Google Maps Platform (Maps JavaScript API).

---

## 📸 Galeria do Projeto

| Tela Perfil | Tela dos Pets |
| :---: | :---: |
| ![Home](link-print-home.png) | ![Maps](link-print-mapa.png) |

| Chat | Painel Admin |
| :---: | :---: |
| ![Chat](link-print-chat.png) | ![Admin](link-print-admin.png) |

---

## 💡 Desafios e Aprendizados

O maior desafio técnico deste projeto foi a implementação do **Sistema de Moderação e Chat**.
- Para o chat, precisei estruturar o banco de dados para armazenar as mensagens de forma relacional entre dois usuários.
- A integração com o **Google Maps** exigiu manipulação de coordenadas e renderização de pinos dinâmicos baseados no banco de dados MySQL.
