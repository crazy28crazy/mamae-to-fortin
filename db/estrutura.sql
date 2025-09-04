CREATE DATABASE academia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academia;

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    idade INT,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE funcao (
    id_funcao INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_funcao (
    id_usuario_funcao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_funcao INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_funcao) REFERENCES funcao(id_funcao) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personal_trainer (
    id_personal INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    cref VARCHAR(20) NULL,
    especializacao VARCHAR(100),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plano (
    id_plano INT AUTO_INCREMENT PRIMARY KEY,
    nome_plano VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagamento (
    id_pagamento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_plano INT NOT NULL,
    data_pagamento DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    id_transacao_api VARCHAR(255) NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_plano) REFERENCES plano(id_plano) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agendamento (
    id_agendamento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_personal INT NOT NULL,
    data_hora DATETIME NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_personal) REFERENCES personal_trainer(id_personal) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensagem (
    id_mensagem INT AUTO_INCREMENT PRIMARY KEY,
    id_remetente INT NOT NULL,
    id_destinatario INT NOT NULL,
    conteudo TEXT NOT NULL,
    data_envio DATETIME NOT NULL,
    data_visualizacao DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (id_remetente) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_destinatario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE anamnese (
  id_anamnese int(11) NOT NULL AUTO_INCREMENT,
  id_aluno int(11) NOT NULL,
  id_personal int(11) NOT NULL,
  objetivos text DEFAULT NULL,
  historico_lesoes text DEFAULT NULL,
  medicamentos text DEFAULT NULL,
  observacoes text DEFAULT NULL,
  data_criacao timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_anamnese),
  UNIQUE KEY idx_aluno_personal (id_aluno,id_personal),
  CONSTRAINT fk_anamnese_aluno FOREIGN KEY (id_aluno) REFERENCES usuario (id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_anamnese_personal FOREIGN KEY (id_personal) REFERENCES personal_trainer (id_personal) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Dados Iniciais
INSERT INTO funcao (descricao) VALUES ('Aluno'), ('PersonalTrainer'), ('Administrador');
INSERT INTO plano (nome_plano, descricao, preco) VALUES
('Plano Bronze', 'Até 10 alunos;Acesso a Anamnese;Agendamento de Aulas', 25.00),
('Plano Prata', 'Até 25 alunos;Todos os benefícios do plano Bronze', 50.00),
('Plano Gold', 'Alunos ilimitados;Todos os benefícios do plano Prata;Brinde exclusivo do site', 75.00);

