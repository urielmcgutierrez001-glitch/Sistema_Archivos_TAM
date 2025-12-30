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
-- Table structure for table `registro_preventivos`
--

DROP TABLE IF EXISTS `registro_preventivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_preventivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estado_perdido` tinyint(1) DEFAULT 0,
  `gestion` date DEFAULT NULL,
  `nro_preventivo` varchar(50) NOT NULL,
  `codigo_abc` varchar(100) DEFAULT NULL,
  `contenedor_fisico_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_preventivos` (`gestion`,`nro_preventivo`),
  KEY `contenedor_fisico_id` (`contenedor_fisico_id`),
  CONSTRAINT `registro_preventivos_ibfk_1` FOREIGN KEY (`contenedor_fisico_id`) REFERENCES `contenedores_fisicos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registro_preventivos`
--

LOCK TABLES `registro_preventivos` WRITE;
/*!40000 ALTER TABLE `registro_preventivos` DISABLE KEYS */;
INSERT INTO `registro_preventivos` VALUES (1,0,'0000-00-00','26',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(2,0,'0000-00-00','191',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(3,0,'0000-00-00','251',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(4,0,'0000-00-00','271',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(5,0,'0000-00-00','279',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(6,0,'0000-00-00','301',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(7,0,'0000-00-00','303',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(8,0,'0000-00-00','63',NULL,NULL,NULL,1,'2025-12-19 09:26:15','2025-12-19 09:26:15'),(9,0,'2000-02-02','486',NULL,NULL,NULL,1,'2025-12-19 09:26:16','2025-12-19 09:26:16');
/*!40000 ALTER TABLE `registro_preventivos` ENABLE KEYS */;
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
