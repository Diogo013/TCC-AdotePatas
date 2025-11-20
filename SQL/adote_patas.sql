-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19/11/2025 às 03:08
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `adote_patas`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administrador`
--

CREATE TABLE `administrador` (
  `id_admin` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacao`
--

CREATE TABLE `avaliacao` (
  `id_avaliacao` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_pet` int(11) NOT NULL,
  `nota` tinyint(4) DEFAULT NULL CHECK (`nota` between 1 and 5),
  `data` datetime DEFAULT current_timestamp(),
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `conversa`
--

CREATE TABLE `conversa` (
  `id_conversa` int(11) NOT NULL,
  `id_solicitacao_fk` int(11) NOT NULL,
  `id_adotante_fk` int(11) NOT NULL COMMENT 'FK para usuario.id_usuario',
  `id_protetor_fk` int(11) NOT NULL COMMENT 'Pode ser um ID de usuario ou ong',
  `tipo_protetor` enum('usuario','ong') NOT NULL,
  `data_criacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conversa`
--

INSERT INTO `conversa` (`id_conversa`, `id_solicitacao_fk`, `id_adotante_fk`, `id_protetor_fk`, `tipo_protetor`, `data_criacao`) VALUES
(1, 3, 18, 19, 'usuario', '2025-11-18 01:33:09'),
(2, 4, 19, 18, 'usuario', '2025-11-18 22:17:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `favorito`
--

CREATE TABLE `favorito` (
  `id_favorito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_pet` int(11) NOT NULL,
  `data` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `formulario_adocao`
--

CREATE TABLE `formulario_adocao` (
  `id_formulario` int(11) NOT NULL,
  `id_solicitacao_fk` int(11) NOT NULL,
  `id_usuario_fk` int(11) NOT NULL COMMENT 'ID do adotante',
  `id_pet_fk` int(11) NOT NULL,
  `tem_criancas` varchar(3) NOT NULL,
  `todos_apoiam` varchar(3) NOT NULL,
  `tipo_moradia` varchar(50) NOT NULL,
  `pet_sera_presente` varchar(3) NOT NULL,
  `presente_responsavel` varchar(3) DEFAULT NULL,
  `teve_pets` varchar(50) NOT NULL,
  `autoriza_visita` varchar(3) NOT NULL,
  `ciente_devolucao` varchar(3) NOT NULL,
  `ciente_termo_responsabilidade` varchar(3) NOT NULL,
  `data_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `formulario_adocao`
--

INSERT INTO `formulario_adocao` (`id_formulario`, `id_solicitacao_fk`, `id_usuario_fk`, `id_pet_fk`, `tem_criancas`, `todos_apoiam`, `tipo_moradia`, `pet_sera_presente`, `presente_responsavel`, `teve_pets`, `autoriza_visita`, `ciente_devolucao`, `ciente_termo_responsabilidade`, `data_envio`) VALUES
(3, 3, 18, 19, 'sim', 'sim', 'Casa grande', 'sim', 'sim', 'Sim, eu tenho', 'sim', 'sim', 'sim', '2025-11-18 01:33:09'),
(4, 4, 19, 18, 'nao', 'sim', 'Casa grande', 'nao', '', 'Sim, eu tenho', 'sim', 'sim', 'sim', '2025-11-18 22:17:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagem`
--

CREATE TABLE `mensagem` (
  `id_mensagem` int(11) NOT NULL,
  `id_conversa_fk` int(11) NOT NULL,
  `id_remetente_fk` int(11) NOT NULL COMMENT 'ID do usuario ou ong que enviou',
  `tipo_remetente` enum('usuario','ong') NOT NULL,
  `conteudo` text NOT NULL,
  `data_envio` datetime DEFAULT current_timestamp(),
  `denuncia` tinyint(1) DEFAULT 0,
  `status` enum('ativo','removido') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mensagem`
--

INSERT INTO `mensagem` (`id_mensagem`, `id_conversa_fk`, `id_remetente_fk`, `tipo_remetente`, `conteudo`, `data_envio`, `denuncia`, `status`) VALUES
(1, 1, 18, 'usuario', 'Olá! Tenho interesse em adotar o(a) ateus Ferrante Ribeiro.', '2025-11-18 01:33:09', 0, 'ativo'),
(2, 1, 18, 'usuario', 'oi', '2025-11-18 02:44:53', 0, 'ativo'),
(3, 1, 18, 'usuario', 'tudo bom?', '2025-11-18 02:45:06', 0, 'ativo'),
(4, 1, 19, 'usuario', 'tudo, você que quer adotar o ateus?', '2025-11-18 02:46:34', 0, 'ativo'),
(5, 1, 18, 'usuario', 'sim', '2025-11-18 21:21:06', 0, 'ativo'),
(6, 1, 19, 'usuario', 'ok', '2025-11-18 21:33:39', 0, 'ativo'),
(7, 1, 18, 'usuario', 'sim', '2025-11-18 21:36:17', 0, 'ativo'),
(8, 2, 19, 'usuario', 'Olá! Tenho interesse em adotar o(a) ateus Ferrante Ribeiro.', '2025-11-18 22:17:32', 0, 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ong`
--

CREATE TABLE `ong` (
  `id_ong` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL COMMENT 'Nome completo da ONG',
  `cnpj` varchar(20) NOT NULL COMMENT 'CNPJ da ONG',
  `email` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `senha` varchar(255) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `banner_fixo` varchar(255) DEFAULT 'banner1.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ong`
--

INSERT INTO `ong` (`id_ong`, `nome`, `cnpj`, `email`, `telefone`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `data_cadastro`, `senha`, `foto_perfil`, `banner`, `banner_fixo`) VALUES
(1, 'RobertoCarlos cachorros', '22093212000101', 'Robertos@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-30 05:13:34', '$2y$10$FwlhINPn9SmNpHq6P1Bz5uJMnOsS3X0me1lzMGZRCLV6fCDa23oQW', NULL, NULL, 'banner1.jpg'),
(2, 'PatasAmigas', '01193725000106', 'patasamigas@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-23 01:47:11', '$2y$10$OC9TnR0QKrr66iDQhsuFuOSNXiDipykyrw/ZYzYcMOQ0CXdZ2wNVy', NULL, NULL, 'banner1.jpg'),
(3, 'lardocelar', '62554467000130', 'lardocelar@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 21:37:22', '$2y$10$D5Ahu/1Fj9r0Lz.N/dBP9OprIvxgKqHN1iXk0b6s8mctRZVQNNm26', NULL, NULL, 'banner5.jpg');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pet`
--

CREATE TABLE `pet` (
  `id_pet` int(11) NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `idade` int(11) DEFAULT NULL,
  `cor` varchar(50) DEFAULT NULL,
  `raca` varchar(50) DEFAULT NULL,
  `porte` enum('pequeno','medio','grande') DEFAULT NULL,
  `comportamento` text DEFAULT NULL,
  `status_vacinacao` enum('sim','nao') DEFAULT NULL,
  `especie` enum('cachorro','gato') DEFAULT NULL,
  `status_castracao` enum('sim','nao') DEFAULT NULL,
  `status_disponibilidade` enum('disponivel','adotado','indisponivel') DEFAULT 'disponivel',
  `id_usuario_fk` int(11) DEFAULT NULL COMMENT 'Chave estrangeira para o Usuário (Doador PF)',
  `id_ong_fk` int(11) DEFAULT NULL COMMENT 'Chave estrangeira para a ONG Doadora',
  `sexo` enum('macho','femea') DEFAULT NULL,
  `caracteristicas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`caracteristicas`))
) ;

--
-- Despejando dados para a tabela `pet`
--

INSERT INTO `pet` (`id_pet`, `nome`, `idade`, `cor`, `raca`, `porte`, `comportamento`, `status_vacinacao`, `especie`, `status_castracao`, `status_disponibilidade`, `id_usuario_fk`, `id_ong_fk`, `sexo`, `caracteristicas`) VALUES
(18, 'ateus Ferrante Ribeiro', 15, 'preto', 'SRD', 'pequeno', 'Snoopy Dog', 'sim', 'cachorro', 'sim', 'disponivel', 18, NULL, 'macho', '[\"Brincalhão\",\"Sociável\",\"Medroso\",\"Hiperativo\",\"Com Gatos\"]'),
(19, 'ateus Ferrante Ribeiro', 15, 'preto', 'SRD', 'pequeno', 'Snoopy Dog', 'sim', 'cachorro', 'sim', 'disponivel', 19, NULL, 'macho', '[\"Medroso\",\"Sociável\",\"Calmo\",\"Alta Energia\",\"Hiperativo\"]');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pet_fotos`
--

CREATE TABLE `pet_fotos` (
  `id_foto` int(11) NOT NULL,
  `id_pet_fk` int(11) NOT NULL,
  `caminho_foto` varchar(255) NOT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pet_fotos`
--

INSERT INTO `pet_fotos` (`id_foto`, `id_pet_fk`, `caminho_foto`, `data_upload`) VALUES
(23, 18, 'uploads/pets/6913b80da234b5.61414442.webp', '2025-11-11 22:26:21'),
(24, 18, 'uploads/pets/6913b96ee2ceb8.44356628.webp', '2025-11-11 22:32:14'),
(25, 19, 'uploads/pets/69167e20cd3e01.33900629.webp', '2025-11-14 00:56:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperar_senha_tolken`
--

CREATE TABLE `recuperar_senha_tolken` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorio`
--

CREATE TABLE `relatorio` (
  `id_relatorio` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `data` datetime DEFAULT current_timestamp(),
  `conteudo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacao`
--

CREATE TABLE `solicitacao` (
  `id_solicitacao` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_pet` int(11) NOT NULL,
  `id_protetor_usuario_fk` int(11) DEFAULT NULL,
  `id_protetor_ong_fk` int(11) DEFAULT NULL,
  `status_solicitacao` enum('pendente','aprovada','rejeitada') DEFAULT 'pendente',
  `data` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacao`
--

INSERT INTO `solicitacao` (`id_solicitacao`, `id_usuario`, `id_pet`, `id_protetor_usuario_fk`, `id_protetor_ong_fk`, `status_solicitacao`, `data`) VALUES
(3, 18, 19, 19, NULL, 'pendente', '2025-11-18 01:33:09'),
(4, 19, 18, 18, NULL, 'pendente', '2025-11-18 22:17:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `cpf` varchar(11) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `banner_fixo` varchar(255) DEFAULT 'banner1.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `email`, `senha`, `nome`, `estado`, `cidade`, `cpf`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `foto_perfil`, `banner`, `banner_fixo`) VALUES
(1, 'fer@gmail.com', '$2y$10$2htIzwn56l8KTiqOYMrwpOgtk0uCTXlowgULtfxKGBvEoTDoYpr1m', 'Diogo Rodrigues', NULL, NULL, '336511', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'banner1.jpg'),
(2, 'moisesfdelima760@gmail.com', '$2y$10$hIVaSp6memMzTHDNEaiu4.g57W9OEBuOSXcJtKOzVp2MbP1EQzxRC', 'Diogo Rodrigues', NULL, NULL, '2147483647', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'banner1.jpg'),
(16, 'dioguin@gmail.com', '$2y$10$ahmnI/uEyjsrZBxfTRGhgepxuKHeRKPDAOE1PwAOk3a/W2ji1cJCu', 'diogo rogerio', NULL, NULL, '2147483647', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'banner1.jpg'),
(17, 'teste@gmail.com', '$2y$10$U8oLA3M2YiK3Ymexlo3/HOh/WzKO/uQYUXebJGMlT2NBP424L1Fpq', 'diogo rogerio', NULL, NULL, '2147483647', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'banner1.jpg'),
(18, 'teste2@gmail.com', '$2y$10$oFFqGB2XPYH2JfPMtVf4MeSNyzP5/9uvEFj8Au2yyIhjcqUInlFdq', 'diogo rogerio', NULL, NULL, '79539418054', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'banner1.jpg'),
(19, 'teste3@gmail.com', '$2y$10$bX0UvH5CLT54vJlKWxG3aO70r6Gi7KW.9IvH6MsTNq/lJ5orvQW2K', 'Diogo Rodrigues de Lima', 'MS', 'Campo Grande', '49624461007', '79103580', 'Rua Pindaré-Mirim', '54', '', 'Jardim Aeroporto', NULL, NULL, 'banner1.jpg');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD PRIMARY KEY (`id_avaliacao`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_pet` (`id_pet`);

--
-- Índices de tabela `conversa`
--
ALTER TABLE `conversa`
  ADD PRIMARY KEY (`id_conversa`),
  ADD KEY `fk_conversa_solicitacao` (`id_solicitacao_fk`),
  ADD KEY `fk_conversa_adotante` (`id_adotante_fk`);

--
-- Índices de tabela `favorito`
--
ALTER TABLE `favorito`
  ADD PRIMARY KEY (`id_favorito`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_pet` (`id_pet`);

--
-- Índices de tabela `formulario_adocao`
--
ALTER TABLE `formulario_adocao`
  ADD PRIMARY KEY (`id_formulario`),
  ADD KEY `fk_formulario_solicitacao` (`id_solicitacao_fk`),
  ADD KEY `fk_formulario_usuario` (`id_usuario_fk`),
  ADD KEY `fk_formulario_pet` (`id_pet_fk`);

--
-- Índices de tabela `mensagem`
--
ALTER TABLE `mensagem`
  ADD PRIMARY KEY (`id_mensagem`),
  ADD KEY `fk_mensagem_conversa` (`id_conversa_fk`);

--
-- Índices de tabela `ong`
--
ALTER TABLE `ong`
  ADD PRIMARY KEY (`id_ong`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `pet`
--
ALTER TABLE `pet`
  ADD PRIMARY KEY (`id_pet`),
  ADD KEY `id_usuario_fk` (`id_usuario_fk`),
  ADD KEY `id_ong_fk` (`id_ong_fk`);

--
-- Índices de tabela `pet_fotos`
--
ALTER TABLE `pet_fotos`
  ADD PRIMARY KEY (`id_foto`),
  ADD KEY `id_pet_fk` (`id_pet_fk`);

--
-- Índices de tabela `recuperar_senha_tolken`
--
ALTER TABLE `recuperar_senha_tolken`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `email` (`email`),
  ADD KEY `token_2` (`token`);

--
-- Índices de tabela `relatorio`
--
ALTER TABLE `relatorio`
  ADD PRIMARY KEY (`id_relatorio`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Índices de tabela `solicitacao`
--
ALTER TABLE `solicitacao`
  ADD PRIMARY KEY (`id_solicitacao`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_pet` (`id_pet`),
  ADD KEY `fk_solicitacao_protetor_usuario` (`id_protetor_usuario_fk`),
  ADD KEY `fk_solicitacao_protetor_ong` (`id_protetor_ong_fk`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administrador`
--
ALTER TABLE `administrador`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  MODIFY `id_avaliacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `conversa`
--
ALTER TABLE `conversa`
  MODIFY `id_conversa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `favorito`
--
ALTER TABLE `favorito`
  MODIFY `id_favorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `formulario_adocao`
--
ALTER TABLE `formulario_adocao`
  MODIFY `id_formulario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `mensagem`
--
ALTER TABLE `mensagem`
  MODIFY `id_mensagem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `ong`
--
ALTER TABLE `ong`
  MODIFY `id_ong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pet`
--
ALTER TABLE `pet`
  MODIFY `id_pet` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pet_fotos`
--
ALTER TABLE `pet_fotos`
  MODIFY `id_foto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `recuperar_senha_tolken`
--
ALTER TABLE `recuperar_senha_tolken`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `relatorio`
--
ALTER TABLE `relatorio`
  MODIFY `id_relatorio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `solicitacao`
--
ALTER TABLE `solicitacao`
  MODIFY `id_solicitacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD CONSTRAINT `avaliacao_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacao_ibfk_2` FOREIGN KEY (`id_pet`) REFERENCES `pet` (`id_pet`) ON DELETE CASCADE;

--
-- Restrições para tabelas `conversa`
--
ALTER TABLE `conversa`
  ADD CONSTRAINT `fk_conversa_adotante` FOREIGN KEY (`id_adotante_fk`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conversa_solicitacao` FOREIGN KEY (`id_solicitacao_fk`) REFERENCES `solicitacao` (`id_solicitacao`) ON DELETE CASCADE;

--
-- Restrições para tabelas `favorito`
--
ALTER TABLE `favorito`
  ADD CONSTRAINT `favorito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorito_ibfk_2` FOREIGN KEY (`id_pet`) REFERENCES `pet` (`id_pet`) ON DELETE CASCADE;

--
-- Restrições para tabelas `formulario_adocao`
--
ALTER TABLE `formulario_adocao`
  ADD CONSTRAINT `fk_formulario_pet` FOREIGN KEY (`id_pet_fk`) REFERENCES `pet` (`id_pet`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_formulario_solicitacao` FOREIGN KEY (`id_solicitacao_fk`) REFERENCES `solicitacao` (`id_solicitacao`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_formulario_usuario` FOREIGN KEY (`id_usuario_fk`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `mensagem`
--
ALTER TABLE `mensagem`
  ADD CONSTRAINT `fk_mensagem_conversa` FOREIGN KEY (`id_conversa_fk`) REFERENCES `conversa` (`id_conversa`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pet`
--
ALTER TABLE `pet`
  ADD CONSTRAINT `pet_ibfk_1` FOREIGN KEY (`id_usuario_fk`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pet_ibfk_2` FOREIGN KEY (`id_ong_fk`) REFERENCES `ong` (`id_ong`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `pet_fotos`
--
ALTER TABLE `pet_fotos`
  ADD CONSTRAINT `fk_pet_fotos_pet` FOREIGN KEY (`id_pet_fk`) REFERENCES `pet` (`id_pet`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `relatorio`
--
ALTER TABLE `relatorio`
  ADD CONSTRAINT `relatorio_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `administrador` (`id_admin`) ON DELETE CASCADE;

--
-- Restrições para tabelas `solicitacao`
--
ALTER TABLE `solicitacao`
  ADD CONSTRAINT `fk_solicitacao_protetor_ong` FOREIGN KEY (`id_protetor_ong_fk`) REFERENCES `ong` (`id_ong`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_solicitacao_protetor_usuario` FOREIGN KEY (`id_protetor_usuario_fk`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `solicitacao_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitacao_ibfk_2` FOREIGN KEY (`id_pet`) REFERENCES `pet` (`id_pet`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
