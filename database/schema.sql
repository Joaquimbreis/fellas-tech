-- =====================================================================
-- Fellas Tech - Script de criação do banco de dados e carga inicial
-- Compatível com MySQL 8 / MariaDB 10.4+ (XAMPP)
--
-- ATENÇÃO: este script APAGA e recria as tabelas (uso em desenvolvimento).
-- Credenciais de teste (somente para avaliação, nunca usar em produção):
--   admin   -> admin@fellastech.test   / Admin@123
--   cliente -> cliente@fellastech.test / Cliente@123
-- As senhas abaixo estão armazenadas como hash (bcrypt), nunca em texto puro.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS fellas_tech
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fellas_tech;

-- ---------------------------------------------------------------------
-- Remoção das tabelas (ordem inversa das dependências)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS itens_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

-- ---------------------------------------------------------------------
-- Usuários (clientes e administradores)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  senha_hash  VARCHAR(255) NOT NULL,
  perfil      ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
  criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Categorias de produtos
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome  VARCHAR(60) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Produtos (valor monetário em DECIMAL; "ativo" permite desativar sem apagar)
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id  INT UNSIGNED NOT NULL,
  nome          VARCHAR(150) NOT NULL,
  descricao     TEXT NOT NULL,
  preco         DECIMAL(10,2) NOT NULL,
  estoque       INT UNSIGNED NOT NULL DEFAULT 0,
  imagem        VARCHAR(255) NULL,
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_produtos_categoria (categoria_id),
  CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id)
    REFERENCES categorias (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_produtos_preco CHECK (preco >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pedidos
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id       INT UNSIGNED NOT NULL,
  data_pedido      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  forma_pagamento  ENUM('pix','boleto','cartao') NOT NULL,
  total            DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pedidos_usuario (usuario_id),
  CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Itens do pedido (preco_unitario guarda o preço NO MOMENTO da compra)
-- ---------------------------------------------------------------------
CREATE TABLE itens_pedido (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pedido_id       INT UNSIGNED NOT NULL,
  produto_id      INT UNSIGNED NOT NULL,
  quantidade      INT UNSIGNED NOT NULL,
  preco_unitario  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_itens_pedido_produto (pedido_id, produto_id),
  KEY idx_itens_produto (produto_id),
  CONSTRAINT fk_itens_pedido FOREIGN KEY (pedido_id)
    REFERENCES pedidos (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_itens_produto FOREIGN KEY (produto_id)
    REFERENCES produtos (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_itens_quantidade CHECK (quantidade > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- CARGA INICIAL DE TESTE
-- =====================================================================

INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
('Administrador Fellas', 'admin@fellastech.test',
 '$2y$10$MGxGtbLY8DGalU9iuVq1VejXFxJWs99k8EWYm3XTlblX6gUj0F3/2', 'admin'),
('Cliente de Teste', 'cliente@fellastech.test',
 '$2y$10$vDrw1cd/MPcFIAJvB6q2wueWJ5Y..9OqBSEiRaAFquANH8edcQPOa', 'cliente');

INSERT INTO categorias (nome) VALUES
('Teclados'), ('Mouses'), ('Áudio'), ('Monitores'), ('Acessórios');

-- categoria_id: 1 Teclados, 2 Mouses, 3 Áudio, 4 Monitores, 5 Acessórios
INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, ativo) VALUES
(1, 'Teclado Mecânico 60% RGB',
 'Layout compacto de 61 teclas, switches mecânicos e iluminação RGB. Cabo USB-C destacável.',
 289.90, 15, 'teclado-mecanico-60.jpg', 1),
(1, 'Teclado Mecânico TKL Sem Fio',
 'Formato TKL (sem teclado numérico), conexão Bluetooth e 2,4 GHz e bateria de longa duração.',
 399.90, 8, 'teclado-tkl-sem-fio.jpg', 1),
(1, 'Teclado Slim Silencioso',
 'Teclas de baixo perfil e digitação silenciosa, ideal para escritório e estudos.',
 129.90, 25, 'teclado-slim.jpg', 1),
(2, 'Mouse Gamer 6400 DPI',
 'Sensor óptico de 6400 DPI ajustáveis, 6 botões programáveis e iluminação RGB.',
 119.90, 30, 'mouse-gamer.jpg', 1),
(2, 'Mouse Sem Fio Ergonômico',
 'Formato vertical que reduz o cansaço no pulso. Conexão sem fio de 2,4 GHz.',
 159.90, 12, 'mouse-ergonomico.jpg', 1),
(3, 'Headset Gamer 7.1 USB',
 'Som surround 7.1 virtual, microfone com cancelamento de ruído e almofadas macias.',
 249.90, 10, 'headset-gamer.jpg', 1),
(3, 'Fone de Ouvido Bluetooth',
 'Fone sem fio com cancelamento passivo de ruído e até 30 horas de bateria.',
 199.90, 20, 'fone-bluetooth.jpg', 1),
(3, 'Microfone USB de Mesa',
 'Microfone condensador com padrão cardioide, ideal para aulas, lives e podcasts.',
 179.90, 0, 'microfone-usb.jpg', 1),
(4, 'Monitor 24 polegadas Full HD 75 Hz',
 'Painel IPS Full HD com 75 Hz, bordas finas e entradas HDMI e VGA.',
 799.90, 6, 'monitor-24.jpg', 1),
(4, 'Monitor Gamer 27 polegadas 144 Hz',
 'Painel de 27 polegadas com 144 Hz, tempo de resposta de 1 ms e suporte a ajuste de altura.',
 1499.90, 3, 'monitor-27-144hz.jpg', 1),
(5, 'Suporte Articulado para Monitor',
 'Braço articulado em alumínio com ajuste de altura, inclinação e rotação.',
 149.90, 18, 'suporte-monitor.jpg', 1),
(5, 'Mousepad Extra Grande 90x40 cm',
 'Superfície de tecido com base antiderrapante e bordas costuradas.',
 79.90, 40, 'mousepad-extra-grande.jpg', 1),
(5, 'Hub USB-C 7 em 1',
 'HDMI 4K, 3 portas USB 3.0, leitor de cartão SD e microSD e entrada de energia.',
 189.90, 14, 'hub-usb-c.jpg', 1),
(5, 'Carregador Rápido 65 W GaN',
 'Carregador compacto com tecnologia GaN e duas saídas (USB-C e USB-A).',
 169.90, 22, 'carregador-65w.jpg', 1),
(5, 'Webcam Full HD 1080p',
 'Webcam com microfone embutido e foco automático. Produto desativado na vitrine (teste do painel).',
 139.90, 9, 'webcam-1080p.jpg', 0);