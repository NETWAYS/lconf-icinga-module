--
-- Table structure for table lconf_connection
--
CREATE SEQUENCE lconf_connection_connection_id_seq;
CREATE TABLE lconf_connection (
  connection_id int NOT NULL DEFAULT nextval('lconf_connection_connection_id_seq'),
  connection_name varchar NOT NULL,
  connection_description text,
  owner int DEFAULT NULL,
  connection_binddn text NOT NULL,
  connection_bindpass varchar DEFAULT NULL,
  connection_host varchar NOT NULL,
  connection_port int NOT NULL DEFAULT '389',
  connection_basedn text,
  connection_tls int DEFAULT '0',
  connection_ldaps int DEFAULT '0',
  PRIMARY KEY (connection_id)
); 


CREATE SEQUENCE lconf_defaultconnection_defaultconnection_id_seq;
CREATE TABLE lconf_defaultconnection (
  defaultconnection_id int NOT NULL DEFAULT nextval('lconf_defaultconnection_defaultconnection_id_seq'),
  user_id int NOT NULL,
  connection_id int NOT NULL,
  PRIMARY KEY (defaultconnection_id)
); 


CREATE SEQUENCE lconf_filter_filter_id_seq;
CREATE TABLE lconf_filter (
  filter_id int NOT NULL DEFAULT nextval('lconf_filter_filter_id_seq'),
  user_id int NOT NULL DEFAULT '-1',
  filter_name varchar NOT NULL,
  filter_json text NOT NULL,
  filter_isglobal int NOT NULL DEFAULT '0',
  PRIMARY KEY (filter_id)
); 

CREATE SEQUENCE lconf_principal_principal_id_seq;
CREATE TABLE lconf_principal (
  principal_id int NOT NULL DEFAULT nextval('lconf_principal_principal_id_seq'),
  principal_user_id int DEFAULT NULL,
  principal_role_id int DEFAULT NULL,
  connection_id int DEFAULT NULL,
  PRIMARY KEY (principal_id)
); 

CREATE INDEX user_id_idx ON lconf_filter(user_id);
CREATE INDEX principal_user_id_idx ON lconf_principal(principal_user_id);
CREATE INDEX principal_role_id_idx ON lconf_principal(principal_role_id);
CREATE INDEX connection_id_idx ON lconf_principal(connection_id);
CREATE INDEX owner_id ON lconf_connection(owner);
