CREATE DATABASE IF NOT EXISTS academia;
USE academia;

CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    idade INT,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);
CREATE TABLE Funcao (
    id_funcao INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(50) NOT NULL UNIQUE
);
CREATE TABLE Usuario_Funcao (
    id_usuario_funcao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_funcao INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_funcao) REFERENCES Funcao(id_funcao)
);
CREATE TABLE Plano (
    id_plano INT AUTO_INCREMENT PRIMARY KEY,
    nome_plano VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL
);
CREATE TABLE Pagamento (
    id_pagamento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_plano INT NOT NULL,
    data_pagamento DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_plano) REFERENCES Plano(id_plano)
);
CREATE TABLE Aula (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    id_aluno INT NOT NULL,
    id_personal INT NOT NULL,
    data DATE NOT NULL,
    horario TIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_aluno) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_personal) REFERENCES Usuario(id_usuario)
);
CREATE TABLE Mensagem (
    id_mensagem INT AUTO_INCREMENT PRIMARY KEY,
    id_remetente INT NOT NULL,
    id_destinatario INT NOT NULL,
    conteudo TEXT NOT NULL,
    data_envio DATETIME NOT NULL,
    FOREIGN KEY (id_remetente) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_destinatario) REFERENCES Usuario(id_usuario)
);
