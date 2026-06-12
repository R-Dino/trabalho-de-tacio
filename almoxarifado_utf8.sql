-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: almoxarifado
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (5,'Consumíveis'),(6,'EPIs'),(4,'Equipamentos'),(2,'Escritório'),(3,'Ferramentas'),(1,'Limpeza');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fornecedores`
--

DROP TABLE IF EXISTS `fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fornecedores`
--

LOCK TABLES `fornecedores` WRITE;
/*!40000 ALTER TABLE `fornecedores` DISABLE KEYS */;
INSERT INTO `fornecedores` VALUES (3,'JOAQUIM DE MOURA DOMINGOS','87992037608','joaquim.moura@aluno.ifsertao-pe.edu.br','Floresta','Ativo','2026-05-21 20:08:07'),(4,'Domingos.Agro','769907675','Domingos.Agro.gg@gmail.com','São Paulo','Ativo','2026-05-29 15:46:01');
/*!40000 ALTER TABLE `fornecedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_atividades`
--

DROP TABLE IF EXISTS `logs_atividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs_atividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  CONSTRAINT `logs_atividades_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_atividades`
--

LOCK TABLES `logs_atividades` WRITE;
/*!40000 ALTER TABLE `logs_atividades` DISABLE KEYS */;
INSERT INTO `logs_atividades` VALUES (1,1,'Banir Usuário','Baniu o usuário ID 4 (joaquim)','2026-05-29 15:05:14'),(2,1,'Redefinir Senha','Redefiniu a senha do ID 5 (Leite) para \'123456\'','2026-05-29 15:05:49'),(3,1,'Desbanir Usuário','Desbaniu o usuário ID 4 (joaquim)','2026-05-29 15:18:01'),(4,1,'Mudar Nível','Alterou nível do ID 4 (joaquim) para admin','2026-05-29 15:18:06');
/*!40000 ALTER TABLE `logs_atividades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimentacoes`
--

DROP TABLE IF EXISTS `movimentacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimentacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `tipo` enum('Entrada','Saída') NOT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `fornecedor_destino` varchar(100) DEFAULT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_produto_id` (`produto_id`),
  KEY `idx_data_movimentacao` (`data_movimentacao`),
  CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimentacoes`
--

LOCK TABLES `movimentacoes` WRITE;
/*!40000 ALTER TABLE `movimentacoes` DISABLE KEYS */;
INSERT INTO `movimentacoes` VALUES (1,4,1,'Saída',NULL,NULL,'2026-05-29 14:38:44'),(2,1,3,'Saída',NULL,NULL,'2026-05-29 14:38:56'),(3,1,2,'Saída',NULL,NULL,'2026-05-29 15:46:42');
/*!40000 ALTER TABLE `movimentacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 0,
  `preco` decimal(10,2) DEFAULT 0.00,
  `localizacao` varchar(100) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT 'Unidade (un)',
  `status` enum('Disponível','Baixo','Zerado') DEFAULT 'Disponível',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_nome` (`nome`),
  KEY `idx_categoria_id` (`categoria_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (1,'joaquim',NULL,1,7,1.12,NULL,'Unidade (un)','Baixo','2026-05-21 19:18:00','2026-05-29 15:46:42'),(4,'Izel',NULL,4,1,3.50,NULL,'Unidade (un)','Baixo','2026-05-29 14:35:14','2026-05-29 14:38:44');
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','comum') NOT NULL DEFAULT 'comum',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('ativo','banido') DEFAULT 'ativo',
  `idade` int(11) DEFAULT NULL,
  `genero` varchar(50) DEFAULT NULL,
  `sexo` varchar(50) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','adm@almox.com','$2y$10$tgLeiorlNM8qGWAlk6rQ4Oyy9VYCQsfzZ65EX8N7E2faOHoDODxRC','admin','2026-05-21 19:14:16','ativo',NULL,NULL,NULL,NULL),(4,'joaquim','teste@gmail.com','$2y$10$8BzlBEO0RdahAFI7G24FPuImEwG45j68Dglip2Rx6UcBVI4C9HwFm','admin','2026-05-29 14:51:10','ativo',NULL,NULL,NULL,NULL),(5,'Leite','gg@gmail.com','$2y$10$P41PIk4bmNS5OCDVQuhUhOdSxYRV9S1p4J8FtH1P/SMo2ep5ZW3Ym','comum','2026-05-29 14:52:08','ativo',NULL,NULL,NULL,NULL),(6,'DOMINGOS','hh@gmail.com','$2y$10$yqbWQ4AxK3DOSFzHUKreLuDdbGsI1C4SccMBDVPC3sINqaJcxOjSu','comum','2026-05-29 14:52:35','ativo',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-29 13:06:34
