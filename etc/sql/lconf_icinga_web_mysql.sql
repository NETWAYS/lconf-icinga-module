--
-- Table structure for table `lconf_connection`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `lconf_connection` (
  `connection_id` int(11) NOT NULL AUTO_INCREMENT,
  `connection_name` varchar(32) NOT NULL,
  `connection_description` text,
  `owner` int(11) DEFAULT NULL,
  `connection_binddn` text NOT NULL,
  `connection_bindpass` varchar(64) DEFAULT NULL,
  `connection_host` varchar(64) NOT NULL,
  `connection_port` int(11) NOT NULL DEFAULT '389',
  `connection_basedn` text,
  `connection_tls` int(11) DEFAULT '0',
  `connection_ldaps` int(11) DEFAULT '0',
  PRIMARY KEY (`connection_id`),
  KEY `owner_idx` (`owner`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lconf_connection`
--

LOCK TABLES `lconf_connection` WRITE;
/*!40000 ALTER TABLE `lconf_connection` DISABLE KEYS */;
/*!40000 ALTER TABLE `lconf_connection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lconf_defaultconnection`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `lconf_defaultconnection` (
  `defaultconnection_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `connection_id` int(11) NOT NULL,
  PRIMARY KEY (`defaultconnection_id`),
  UNIQUE KEY `defaultconn_unique_idx` (`user_id`),
  KEY `connection_id_idx` (`connection_id`),
  CONSTRAINT `lclc` FOREIGN KEY (`connection_id`) REFERENCES `lconf_connection` (`connection_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lconf_defaultconnection`
--

LOCK TABLES `lconf_defaultconnection` WRITE;
/*!40000 ALTER TABLE `lconf_defaultconnection` DISABLE KEYS */;
/*!40000 ALTER TABLE `lconf_defaultconnection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lconf_filter`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `lconf_filter` (
  `filter_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '-1',
  `filter_name` varchar(127) NOT NULL,
  `filter_json` text NOT NULL,
  `filter_isglobal` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`filter_id`),
  KEY `user_id_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lconf_filter`
--

LOCK TABLES `lconf_filter` WRITE;
/*!40000 ALTER TABLE `lconf_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `lconf_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lconf_principal`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `lconf_principal` (
  `principal_id` int(11) NOT NULL AUTO_INCREMENT,
  `principal_user_id` int(11) DEFAULT NULL,
  `principal_role_id` int(11) DEFAULT NULL,
  `connection_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`principal_id`),
  KEY `principal_user_id_idx` (`principal_user_id`),
  KEY `principal_role_id_idx` (`principal_role_id`),
  KEY `connection_id_idx` (`connection_id`),
  CONSTRAINT `lconf_principal_connection_id_lconf_connection_connection_id` FOREIGN KEY (`connection_id`) REFERENCES `lconf_connection` (`connection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lconf_principal_principal_role_id_nsm_role_role_id` FOREIGN KEY (`principal_role_id`) REFERENCES `nsm_role` (`role_id`),
  CONSTRAINT `lconf_principal_principal_user_id_nsm_user_user_id` FOREIGN KEY (`principal_user_id`) REFERENCES `nsm_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lconf_principal`
--

LOCK TABLES `lconf_principal` WRITE;
/*!40000 ALTER TABLE `lconf_principal` DISABLE KEYS */;
/*!40000 ALTER TABLE `lconf_principal` ENABLE KEYS */;
UNLOCK TABLES;

