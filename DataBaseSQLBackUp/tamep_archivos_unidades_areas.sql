-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: tamep_archivos
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `unidades_areas`
--

DROP TABLE IF EXISTS `unidades_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidades_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidades_areas`
--

LOCK TABLES `unidades_areas` WRITE;
/*!40000 ALTER TABLE `unidades_areas` DISABLE KEYS */;
INSERT INTO `unidades_areas` VALUES (1,'Encomiendas',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(2,'El Alto',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(3,'Revision',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(6,'Contrataciones',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(9,'Almacenes',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(11,'SECC. JEFE DE CONTABILIDAD',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(12,'SECC. CONTABILIDAD',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(14,'SAL CONTA',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(19,'Informatica 2',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(20,'Informatica',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21'),(21,'SALA CONTA',NULL,1,'2025-12-18 16:04:21','2025-12-18 16:04:21');
/*!40000 ALTER TABLE `unidades_areas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-29  9:14:03
