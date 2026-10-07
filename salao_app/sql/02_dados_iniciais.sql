-- Dados iniciais para testes (execute após 01_criar_banco.sql)
-- Usuários de teste:  admin/admin123 · maria/maria123 · ana/ana123
SET NAMES utf8mb4;
USE salao;

INSERT INTO pessoa (nome, telefone, email, login, senha, perfil) VALUES
 ('Administrador do Salão', '(11) 90000-0000', 'admin@salao.com', 'admin', '$2y$10$42OuQThwXGn8NenO8.7jWuvnCSRH3Gh.JqQTRJpKKksb5UIVatVl2', 'ADMINISTRADOR'),
 ('Maria Souza',            '(11) 91111-1111', 'maria@salao.com', 'maria', '$2y$10$5XPaFEyICQCz6fIpYiiujOL/5IngoHXUx2NXpl1V5PTViitm///OG', 'PROFISSIONAL'),
 ('Ana Lima',               '(11) 92222-2222', 'ana@email.com',   'ana',   '$2y$10$MITDuuS/FZ.UZsZIr5kS2OpwOTkx.8j9jx6gvwfuKqMo9QLMRm7aW', 'CLIENTE');

INSERT INTO profissional (codigo, especialidade, comissao, ativo)
 SELECT codigo, 'Cabeleireira', 40.00, 'S' FROM pessoa WHERE login = 'maria';

INSERT INTO cliente (codigo, preferencias)
 SELECT codigo, 'Prefere atendimento pela manhã' FROM pessoa WHERE login = 'ana';

INSERT INTO servico (nome, preco, duracao_min, descricao) VALUES
 ('Corte feminino',  80.00,  60, 'Corte com lavagem e finalização'),
 ('Corte masculino', 45.00,  30, 'Corte tradicional ou degradê'),
 ('Escova',          50.00,  40, 'Escova modeladora'),
 ('Coloração',      150.00, 120, 'Coloração completa');
