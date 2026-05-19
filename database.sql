CREATE DATABASE IF NOT EXISTS almoxarifado;
USE almoxarifado;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sku VARCHAR(50) UNIQUE,
    categoria_id INT,
    quantidade INT DEFAULT 0,
    preco DECIMAL(10,2) DEFAULT 0.00,
    localizacao VARCHAR(100),
    unidade_medida VARCHAR(20) DEFAULT 'Unidade (un)',
    status ENUM('Disponível', 'Baixo', 'Zerado') DEFAULT 'Disponível',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    cidade VARCHAR(100),
    status ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    tipo ENUM('Entrada', 'Saída') NOT NULL,
    nota_fiscal VARCHAR(50),
    fornecedor_destino VARCHAR(100),
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- Inserir usuário administrador padrão (senha: admin123)
INSERT IGNORE INTO usuarios (nome, email, senha) VALUES ('Administrador', 'adm@almox.com', '$2y$10$eE/.9R7c.k8A9D9mF9xL6e8sB7D.Z5K.xO1hG8/V4eW.B8.H4U1K');

-- Categorias padrão
INSERT IGNORE INTO categorias (nome) VALUES ('Limpeza'), ('Escritório'), ('Ferramentas'), ('Equipamentos'), ('Consumíveis'), ('EPIs');
