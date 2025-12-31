ALTER TABLE `clientes` ADD COLUMN `status` ENUM('ativo', 'inativo') DEFAULT 'ativo' AFTER `telefone`;
